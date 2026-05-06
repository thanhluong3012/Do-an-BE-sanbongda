<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentRegistration;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

            // Phân quyền xem danh sách đăng ký
            if ($user->role->name_role === 'admin') {
                // Admin xem tất cả
            } elseif ($request->has('tournament_id')) {
                // Organizer xem đăng ký của giải mình tổ chức
                $tournament = Tournament::find($request->tournament_id);
                if ($tournament && $tournament->organizer_id === $user->id) {
                    // Organizer: xem tất cả đăng ký của giải đó
                } else {
                    // Đội trưởng chỉ xem đăng ký của đội mình
                    $teamIds = Team::where('captain_user_id', $user->id)->pluck('id');
                    $query->whereIn('team_id', $teamIds);
                }
            } else {
                // Không filter theo tournament: chỉ cho xem đăng ký của đội mình
                $teamIds = Team::where('captain_user_id', $user->id)->pluck('id');
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

            // Kiểm tra hạn đăng ký (cho phép đăng ký đến hết ngày registration_end - 23:59:59)
            if (now() > $tournament->registration_end->endOfDay()) {
                return ApiResponse::error('Đã hết hạn đăng ký', null, 400);
            }

            // Kiểm tra đã đủ số đội chưa
            if ($tournament->isFull()) {
                return ApiResponse::error(
                    "Giải đã đủ số đội ({$tournament->max_teams}/{$tournament->max_teams})",
                    null,
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
            $team = Team::findOrFail($validated['team_id']);
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
                    null,
                    400
                );
            }

            // Gán payment_method từ giải đấu vào đăng ký
            $validated['payment_method'] = $tournament->payment_method;

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

            // Chỉ organizer hoặc admin mới được duyệt đăng ký
            $user = Auth::user();
            if ($user->role->name_role !== 'admin' && $registration->tournament->organizer_id !== $user->id) {
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
                Log::warning('Failed to send approval email: ' . $mailError->getMessage());
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
                Log::warning('Failed to create notification: ' . $notifError->getMessage());
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

            // Chỉ organizer hoặc admin mới được từ chối đăng ký
            $user = Auth::user();
            if ($user->role->name_role !== 'admin' && $registration->tournament->organizer_id !== $user->id) {
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
                Log::warning('Failed to send rejection email: ' . $mailError->getMessage());
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
                Log::warning('Failed to create notification: ' . $notifError->getMessage());
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

    /**
     * Upload payment proof - Team upload bill chuyển khoản
     */
    public function uploadPaymentProof(Request $request, string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament', 'team'])->find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy đăng ký', null, 404);
            }

            $user = Auth::user();

            // Chỉ đội trưởng mới được upload bill
            if ($registration->team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng mới có thể upload bill', null, 403);
            }

            // Kiểm tra registration đã được approve chưa
            if ($registration->registration_status !== 'approved') {
                return ApiResponse::error('Chỉ upload bill cho đăng ký đã được duyệt', null, 400);
            }

            // Kiểm tra payment_method phải là transfer
            if ($registration->payment_method !== 'transfer') {
                return ApiResponse::error('Chỉ thanh toán chuyển khoản mới cần upload bill', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Upload file
            $file = $request->file('payment_proof');
            $path = $file->store('payment_proofs/tournaments', 'public');

            // Xóa ảnh cũ nếu có
            if ($registration->payment_proof) {
                Storage::disk('public')->delete($registration->payment_proof);
            }

            $registration->payment_proof = $path;
            $registration->save();

            // ✅ Tạo thông báo cho organizer (người tổ chức giải)
            try {
                Notification::create([
                    'user_id' => $registration->tournament->organizer_id,
                    'type' => 'tournament_payment_bill_uploaded',
                    'title' => '📄 Đội đã upload bill thanh toán giải đấu',
                    'message' => "Đội {$registration->team->name} đã upload bill thanh toán cho giải {$registration->tournament->name}. Vui lòng kiểm tra và xác nhận.",
                    'data' => [
                        'registration_id' => $registration->id,
                        'tournament_id' => $registration->tournament_id,
                        'tournament_name' => $registration->tournament->name,
                        'team_id' => $registration->team_id,
                        'team_name' => $registration->team->name,
                        'payment_proof_url' => asset('storage/' . $path),
                    ],
                ]);
            } catch (\Exception $notifError) {
                Log::warning('Failed to create organizer notification: ' . $notifError->getMessage());
            }

            return ApiResponse::success(
                'Upload bill thành công. Chờ BTC xác nhận thanh toán.',
                [
                    'registration_id' => $registration->id,
                    'payment_proof_url' => asset('storage/' . $path),
                    'payment_status' => $registration->payment_status,
                ],
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Upload thất bại', $e->getMessage(), 500);
        }
    }

    /**
     * Confirm payment - BTC xác nhận thanh toán
     */
    public function confirmPayment(Request $request, string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament', 'team.captain'])->find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy đăng ký', null, 404);
            }

            $user = Auth::user();

            // Chỉ organizer hoặc admin mới được xác nhận thanh toán
            if ($user->role->name_role !== 'admin' && $registration->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Bạn không có quyền xác nhận thanh toán cho đăng ký này', null, 403);
            }

            // Kiểm tra registration đã được approve chưa
            if ($registration->registration_status !== 'approved') {
                return ApiResponse::error('Chỉ xác nhận thanh toán cho đăng ký đã được duyệt', null, 400);
            }

            // Kiểm tra payment_status đang pending
            if ($registration->payment_status !== 'pending') {
                return ApiResponse::error('Đăng ký này không ở trạng thái chờ thanh toán', null, 400);
            }

            $validator = Validator::make($request->all(), [
                'action' => ['required', 'in:approve,reject'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', $validator->errors(), 422);
            }

            // Nếu là transfer, kiểm tra đã upload bill chưa
            if ($registration->payment_method === 'transfer' && !$registration->payment_proof && $request->action === 'approve') {
                return ApiResponse::error('Đội chưa upload bill chuyển khoản. Không thể xác nhận thanh toán.', null, 400);
            }

            // Update payment status
            if ($request->action === 'approve') {
                $registration->payment_status = 'paid';
                $registration->payment_confirmed_at = now();
                $registration->payment_date = now();
                $message = 'Xác nhận thanh toán thành công';

                // ✅ Tạo thông báo cho đội trưởng
                try {
                    $captain = $registration->team->captain;
                    if ($captain) {
                        Notification::create([
                            'user_id' => $captain->id,
                            'type' => 'tournament_payment_approved',
                            'title' => '✅ Thanh toán giải đấu đã được xác nhận',
                            'message' => "Thanh toán của đội {$registration->team->name} cho giải {$registration->tournament->name} đã được BTC xác nhận.",
                            'data' => [
                                'registration_id' => $registration->id,
                                'tournament_id' => $registration->tournament_id,
                                'tournament_name' => $registration->tournament->name,
                                'team_id' => $registration->team_id,
                                'team_name' => $registration->team->name,
                                'payment_status' => 'paid',
                            ],
                        ]);
                    }
                } catch (\Exception $notifError) {
                    Log::warning('Failed to create payment notification: ' . $notifError->getMessage());
                }
            } else {
                $registration->payment_status = 'failed';
                // Xóa file bill bị từ chối khỏi storage
                if ($registration->payment_proof) {
                    Storage::disk('public')->delete($registration->payment_proof);
                }
                $registration->payment_proof = null;
                $message = 'Đã từ chối thanh toán';

                // ✅ Tạo thông báo từ chối
                try {
                    $captain = $registration->team->captain;
                    if ($captain) {
                        Notification::create([
                            'user_id' => $captain->id,
                            'type' => 'tournament_payment_rejected',
                            'title' => '❌ Thanh toán giải đấu bị từ chối',
                            'message' => "Thanh toán của đội {$registration->team->name} cho giải {$registration->tournament->name} đã bị BTC từ chối. Lý do: " . ($request->note ?? 'Không có lý do cụ thể'),
                            'data' => [
                                'registration_id' => $registration->id,
                                'tournament_id' => $registration->tournament_id,
                                'tournament_name' => $registration->tournament->name,
                                'team_id' => $registration->team_id,
                                'team_name' => $registration->team->name,
                                'rejected_reason' => $request->note ?? null,
                            ],
                        ]);
                    }
                } catch (\Exception $notifError) {
                    Log::warning('Failed to create rejection notification: ' . $notifError->getMessage());
                }
            }

            $registration->payment_note = $request->note;
            $registration->save();

            return ApiResponse::success(
                $message,
                [
                    'registration_id' => $registration->id,
                    'payment_status' => $registration->payment_status,
                    'payment_confirmed_at' => $registration->payment_confirmed_at,
                    'payment_proof' => $registration->payment_proof,
                    'note' => $request->note ?? null,
                ],
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi xác nhận thanh toán', $e->getMessage(), 500);
        }
    }

    /**
     * Get payment info - Lấy thông tin thanh toán (QR code)
     */
    public function getPaymentInfo(string $id)
    {
        try {
            $registration = TournamentRegistration::with(['tournament.organizer.ownerProfile', 'team'])->find($id);

            if (!$registration) {
                return ApiResponse::error('Không tìm thấy đăng ký', null, 404);
            }

            $user = Auth::user();

            // Chỉ đội trưởng của đội hoặc organizer mới xem được
            if ($registration->team->captain_user_id !== $user->id &&
                $registration->tournament->organizer_id !== $user->id &&
                $user->role->name_role !== 'admin') {
                return ApiResponse::error('Bạn không có quyền xem thông tin thanh toán này', null, 403);
            }

            // Kiểm tra registration đã được approve chưa
            if ($registration->registration_status !== 'approved') {
                return ApiResponse::error('Đăng ký chưa được duyệt', null, 400);
            }

            $data = [
                'registration_id' => $registration->id,
                'tournament_name' => $registration->tournament->name,
                'team_name' => $registration->team->name,
                'payment_method' => $registration->payment_method,
                'payment_status' => $registration->payment_status,
                'payment_proof_url' => $registration->payment_proof_url,
            ];

            // Nếu là transfer, trả về QR code
            if ($registration->payment_method === 'transfer') {
                $organizer = $registration->tournament->organizer;
                if ($organizer && $organizer->ownerProfile && $organizer->ownerProfile->qr_code_image) {
                    $data['qr_code_url'] = asset('storage/' . $organizer->ownerProfile->qr_code_image);
                }
            }

            return ApiResponse::success('Thông tin thanh toán', $data, 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi lấy thông tin thanh toán', $e->getMessage(), 500);
        }
    }
}

