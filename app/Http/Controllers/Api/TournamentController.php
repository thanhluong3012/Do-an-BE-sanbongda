<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentController extends Controller
{
    // Danh sách giải đấu
    public function index(Request $request)
    {
        try {
            $query = Tournament::with(['organizer', 'fieldCluster']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('format')) {
                $query->where('format', $request->format);
            }

            // Nếu có tham số my_tournaments=true, chỉ lấy giải của user đang login
            if ($request->has('my_tournaments') && $request->my_tournaments == 'true') {
                $query->where('organizer_id', Auth::id());
            }
            // Nếu không có tham số, hiển thị tất cả giải công khai (status != draft)
            elseif (!$request->has('my_tournaments')) {
                $user = Auth::user();
                // Admin xem tất cả
                if ($user->role->name !== 'admin') {
                    // User thường xem: giải công khai + giải của mình
                    $query->where(function($q) use ($user) {
                        $q->where('status', '!=', 'draft')
                          ->orWhere('organizer_id', $user->id);
                    });
                }
            }

            $tournaments = $query->orderBy('created_at', 'desc')->paginate(10);

            return ApiResponse::success('Danh sách giải đấu', $tournaments);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tạo giải mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'format' => 'required|in:round_robin,knockout,group_knockout',
                'max_teams' => 'required|integer|min:2',
                'min_teams' => 'required|integer|min:2',
                'registration_start' => 'required|date',
                'registration_end' => 'required|date|after:registration_start',
                'start_date' => 'required|date|after:registration_end',
                'end_date' => 'required|date|after:start_date',
                'field_cluster_id' => 'required|exists:field_clusters,id',
            ]);

            // Validate max_teams > min_teams
            if ($validated['max_teams'] < $validated['min_teams']) {
                return ApiResponse::error('max_teams phải lớn hơn hoặc bằng min_teams', 400);
            }

            $userId = Auth::id();

            // Kiểm tra user đã có giải đang hoạt động chưa (draft, open, in_progress)
            $activeTournament = Tournament::where('organizer_id', $userId)
                ->whereIn('status', ['draft', 'open', 'in_progress'])
                ->first();

            if ($activeTournament) {
                return ApiResponse::error(
                    'Bạn đã có giải đấu "' . $activeTournament->name . '" đang hoạt động (trạng thái: ' . $activeTournament->status . '). Vui lòng hoàn thành hoặc hủy giải đấu này trước khi tạo giải mới.',
                    ['active_tournament_id' => $activeTournament->id, 'active_tournament_status' => $activeTournament->status],
                    400
                );
            }

            $validated['organizer_id'] = $userId;
            $validated['status'] = 'draft';

            $tournament = Tournament::create($validated);

            return ApiResponse::success('Tạo giải đấu thành công',
                $tournament->load(['organizer', 'fieldCluster']), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xem chi tiết giải
    public function show(string $id)
    {
        try {
            $tournament = Tournament::with([
                'organizer',
                'fieldCluster',
                'schedules',
                'registrations.team.captain',
                'groups.standings.team',
                'rounds.matches'
            ])->findOrFail($id);

            return ApiResponse::success('Chi tiết giải đấu', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy giải đấu', 404);
        }
    }

    // Cập nhật giải
    public function update(Request $request, string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($user->role->name !== 'admin' && $tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Không có quyền', 403);
            }

            // Chỉ cho phép update khi status là draft hoặc open
            if (!in_array($tournament->status, ['draft', 'open'])) {
                return ApiResponse::error('Không thể cập nhật giải đấu đang diễn ra hoặc đã kết thúc', 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'format' => 'sometimes|in:round_robin,knockout,group_knockout',
                'max_teams' => 'sometimes|integer|min:2',
                'min_teams' => 'sometimes|integer|min:2',
                'registration_start' => 'sometimes|date',
                'registration_end' => 'sometimes|date|after:registration_start',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
            ]);

            $tournament->update($validated);

            return ApiResponse::success('Cập nhật thành công', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xóa giải
    public function destroy(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($user->role->name !== 'admin' && $tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Không có quyền', 403);
            }

            if (!in_array($tournament->status, ['draft', 'cancelled'])) {
                return ApiResponse::error('Chỉ xóa được giải ở trạng thái draft hoặc cancelled', 400);
            }

            $tournament->delete();

            return ApiResponse::success('Xóa giải thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Publish giải (mở đăng ký)
    public function publish(string $id)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            $user = Auth::user();
            if ($user->role->name !== 'admin' && $tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Không có quyền', 403);
            }

            if ($tournament->status !== 'draft') {
                return ApiResponse::error('Chỉ publish được giải ở trạng thái draft', 400);
            }

            $tournament->update(['status' => 'open']);

            return ApiResponse::success('Mở đăng ký thành công', $tournament);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
