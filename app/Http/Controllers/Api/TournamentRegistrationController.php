<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentRegistration;
use App\Models\Tournament;
use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\TournamentRegistrationApprovedMail;
use App\Mail\TournamentRegistrationRejectedMail;

class TournamentRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = TournamentRegistration::with(['tournament', 'team.captain']);

            // Filter by tournament
            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            // Filter by status
            if ($request->has('registration_status')) {
                $query->where('registration_status', $request->registration_status);
            }

            $user = Auth::user();

            // Admin/BTC xem tất cả đăng ký của giải mình tổ chức
            if ($user->role->name_role !== 'admin') {
                // Đội trưởng chỉ xem đăng ký của đội mình
                $teamIds = \App\Models\Team::where('captain_user_id', $user->id)->pluck('id');
                $query->whereIn('team_id', $teamIds);
            }

            $registrations = $query->orderBy('created_at', 'desc')->paginate(10);

            return ApiResponse::success('Danh sách đăng ký', $registrations);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tournament_id' => 'required|exists:tournaments,id',
                'team_id' => 'required|exists:teams,id',
                'notes' => 'nullable|string',
            ]);

            // Kiểm tra giải có đang mở đăng ký không
            $tournament = Tournament::findOrFail($validated['tournament_id']);
            if ($tournament->status !== 'open') {
                return ApiResponse::error('Giải đấu không mở đăng ký', null, 400);
            }

            // Kiểm tra hạn đăng ký
            if (now() > $tournament->registration_end) {
                return ApiResponse::error('Đã hết hạn đăng ký', null, 400);
            }

            // Kiểm tra đã đủ số đội chưa
            if ($tournament->isFull()) {
                return ApiResponse::error(
                    "Giải đã đủ số đội ({$tournament->max_teams}/{$tournament->max_teams})",
                    400
                );
            }

            // Kiểm tra đội đã đăng ký chưa
            $exists = TournamentRegistration::where('tournament_id', $validated['tournament_id'])
                ->where('team_id', $validated['team_id'])
                ->exists();

            if ($exists) {
                return ApiResponse::error('Đội đã đăng ký giải này rồi', null, 400);
            }

            // Kiểm tra quyền đội trưởng
            $user = Auth::user();
            $team = \App\Models\Team::findOrFail($validated['team_id']);
            if ($team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng mới có thể đăng ký', null, 403);
            }

            // Kiểm tra đội có đủ số người không (captain + members)
            $totalMembers = $team->totalMembersCount();
            $minPlayers = match($tournament->field_type) {
                '5v5' => 5,
                '7v7' => 7,
                '11v11' => 11,
                default => 5,
            };

            if ($totalMembers < $minPlayers) {
                return ApiResponse::error(
                    "Đội phải có ít nhất {$minPlayers} cầu thủ để đăng ký giải {$tournament->field_type}. Hiện tại đội có: {$totalMembers} người (bao gồm đội trưởng)",
                    400
                );
            }

            $registration = TournamentRegistration::create($validated);

            return ApiResponse::success('Đăng ký thành công',
                $registration->load(['tournament', 'team']), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament', 'team.captain'])->findOrFail($id);

            return ApiResponse::success('Chi tiết đăng ký', $registration);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy đăng ký', null, 404);
        }
    }

    // Duyệt đăng ký (BTC)
    public function approve(string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament', 'team.captain'])->findOrFail($id);

            // Chỉ organizer (người tổ chức giải) mới được duyệt đăng ký
            $user = Auth::user();
            if ($registration->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền duyệt đăng ký', null, 403);
            }

            if ($registration->registration_status !== 'pending') {
                return ApiResponse::error('Chỉ duyệt được đăng ký ở trạng thái pending', null, 400);
            }

            // Kiểm tra đã đủ đội chưa
            $approvedCount = $registration->tournament->registrations()
                ->where('registration_status', 'approved')
                ->count();

            if ($approvedCount >= $registration->tournament->max_teams) {
                return ApiResponse::error('Giải đã đủ số đội', null, 400);
            }

            $registration->update(['registration_status' => 'approved']);

            $captain = $registration->team->captain;

            // ✅ GỬI EMAIL THÔNG BÁO
            try {
                if ($captain && $captain->email) {
                    Mail::to($captain->email)->send(new TournamentRegistrationApprovedMail(
                        captainName: $captain->name,
                        teamName: $registration->team->name,
                        tournamentName: $registration->tournament->name,
                        startDate: $registration->tournament->start_date->format('d/m/Y'),
                    ));
                }
            } catch (\Exception $mailError) {
                // Log lỗi nhưng không fail request
                \Log::warning('Failed to send approval email: ' . $mailError->getMessage());
            }

            // ✅ TẠO THÔNG BÁO TRONG APP
            try {
                if ($captain) {
                    Notification::create([
                        'user_id' => $captain->id,
                        'type' => 'tournament_registration_approved',
                        'title' => '✅ Đăng ký giải đấu đã được duyệt',
                        'message' => "Chúc mừng! Đội {$registration->team->name} đã được duyệt tham gia giải {$registration->tournament->name}.",
                        'data' => [
                            'tournament_id' => $registration->tournament_id,
                            'tournament_name' => $registration->tournament->name,
                            'registration_id' => $registration->id,
                            'team_id' => $registration->team_id,
                            'team_name' => $registration->team->name,
                            'start_date' => $registration->tournament->start_date->format('Y-m-d'),
                        ],
                    ]);
                }
            } catch (\Exception $notifError) {
                \Log::warning('Failed to create notification: ' . $notifError->getMessage());
            }

            return ApiResponse::success('Duyệt đăng ký thành công', $registration);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Từ chối đăng ký (BTC)
    public function reject(Request $request, string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament', 'team.captain'])->findOrFail($id);

            // Chỉ organizer mới được từ chối đăng ký
            $user = Auth::user();
            if ($registration->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền từ chối đăng ký', null, 403);
            }

            $validated = $request->validate([
                'rejected_reason' => 'required|string',
            ]);

            $registration->update([
                'registration_status' => 'rejected',
                'rejected_reason' => $validated['rejected_reason'],
            ]);

            $captain = $registration->team->captain;

            // ✅ GỬI EMAIL THÔNG BÁO KÈM LÝ DO TỪ CHỐI
            try {
                if ($captain && $captain->email) {
                    Mail::to($captain->email)->send(new TournamentRegistrationRejectedMail(
                        captainName: $captain->name,
                        teamName: $registration->team->name,
                        tournamentName: $registration->tournament->name,
                        rejectedReason: $validated['rejected_reason'],
                    ));
                }
            } catch (\Exception $mailError) {
                // Log lỗi nhưng không fail request
                \Log::warning('Failed to send rejection email: ' . $mailError->getMessage());
            }

            // ✅ TẠO THÔNG BÁO TRONG APP KÈM LÝ DO
            try {
                if ($captain) {
                    Notification::create([
                        'user_id' => $captain->id,
                        'type' => 'tournament_registration_rejected',
                        'title' => '❌ Đăng ký giải đấu bị từ chối',
                        'message' => "Rất tiếc, đăng ký của đội {$registration->team->name} tham gia giải {$registration->tournament->name} đã bị từ chối. Lý do: {$validated['rejected_reason']}",
                        'data' => [
                            'tournament_id' => $registration->tournament_id,
                            'tournament_name' => $registration->tournament->name,
                            'registration_id' => $registration->id,
                            'team_id' => $registration->team_id,
                            'team_name' => $registration->team->name,
                            'rejected_reason' => $validated['rejected_reason'],
                        ],
                    ]);
                }
            } catch (\Exception $notifError) {
                \Log::warning('Failed to create notification: ' . $notifError->getMessage());
            }

            return ApiResponse::success('Từ chối đăng ký thành công', $registration);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Rút đơn đăng ký
    public function destroy(string $id)
    {
        try {
            $registration = TournamentRegistration::with('tournament', 'team')->findOrFail($id);
            $user = Auth::user();

            // Chỉ đội trưởng của đội đó mới được rút đăng ký
            if ($registration->team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng mới có quyền rút đăng ký', null, 403);
            }

            // Chỉ rút được khi pending hoặc tự rút khi approved
            if (!in_array($registration->registration_status, ['pending', 'approved'])) {
                return ApiResponse::error('Không thể rút đăng ký ở trạng thái này', null, 400);
            }

            $registration->delete();
            return ApiResponse::success('Rút đăng ký thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
