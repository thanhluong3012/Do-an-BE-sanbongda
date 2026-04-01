<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            // Admin xem tất cả, user chỉ xem đội của mình
            if ($user->role->name === 'admin') {
                $teams = Team::with('captain')->paginate(10);
            } else {
                $teams = Team::with('captain')
                    ->where('captain_user_id', $user->id)
                    ->paginate(10);
            }

            return ApiResponse::success('Danh sách đội bóng', $teams);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = $user->id;

            // ✅ KIỂM TRA 1: User đã là đội trưởng của đội nào chưa
            $existingTeam = Team::where('captain_user_id', $userId)->first();
            if ($existingTeam) {
                return ApiResponse::error(
                    'Bạn đã là đội trưởng của đội "' . $existingTeam->name . '". Mỗi người chỉ được tạo 1 đội bóng.',
                    ['existing_team_id' => $existingTeam->id],
                    400
                );
            }

            // ✅ KIỂM TRA 2: Nếu user có CCCD, kiểm tra CCCD có trong team_members không
            if ($user->code_id) {
                $membershipExists = TeamMember::where('code_id', $user->code_id)->first();
                if ($membershipExists) {
                    $team = Team::find($membershipExists->team_id);
                    return ApiResponse::error(
                        'CCCD của bạn đã tham gia đội "' . ($team ? $team->name : 'khác') . '" với vai trò thành viên. Vui lòng rời khỏi đội hiện tại trước khi tạo đội mới.',
                        [
                            'existing_team_id' => $membershipExists->team_id,
                            'member_name' => $membershipExists->player_name
                        ],
                        400
                    );
                }
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:teams,name',
                'short_name' => 'nullable|string|max:50',
                'logo' => 'nullable|string',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
            ]);

            $validated['captain_user_id'] = $userId;

            $team = Team::create($validated);

            return ApiResponse::success('Tạo đội thành công', $team->load('captain'), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id)
    {
        try {
            $team = Team::with(['captain', 'registrations.tournament'])->findOrFail($id);

            return ApiResponse::success('Chi tiết đội bóng', $team);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy đội', 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $team = Team::findOrFail($id);

            $user = Auth::user();
            if ($user->role->name !== 'admin' && $team->captain_user_id !== $user->id) {
                return ApiResponse::error('Không có quyền', 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:teams,name,' . $id,
                'short_name' => 'nullable|string|max:50',
                'logo' => 'nullable|string',
                'contact_phone' => 'nullable|string|max:20',
                'contact_email' => 'nullable|email',
            ]);

            $team->update($validated);

            return ApiResponse::success('Cập nhật thành công', $team);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $team = Team::findOrFail($id);

            $user = Auth::user();
            if ($user->role->name !== 'admin' && $team->captain_user_id !== $user->id) {
                return ApiResponse::error('Không có quyền', 403);
            }

            // Kiểm tra đội có đang tham gia giải nào không
            if ($team->registrations()->whereIn('registration_status', ['pending', 'approved'])->exists()) {
                return ApiResponse::error('Không thể xóa đội đang tham gia giải đấu', 400);
            }

            $team->delete();

            return ApiResponse::success('Xóa đội thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
