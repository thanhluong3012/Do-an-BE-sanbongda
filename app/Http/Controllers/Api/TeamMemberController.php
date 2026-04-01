<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\Team;
use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamMemberController extends Controller
{
    /**
     * Danh sách thành viên của đội
     * GET /api/teams/{team_id}/members
     */
    public function index(string $teamId)
    {
        try {
            $team = Team::findOrFail($teamId);

            $members = TeamMember::where('team_id', $teamId)
                ->orderBy('jersey_number')
                ->get();

            return ApiResponse::success('Danh sách thành viên đội', [
                'team' => $team,
                'members' => $members,
                'total_members' => $members->count(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Thêm thành viên vào đội
     * POST /api/teams/{team_id}/members
     */
    public function store(Request $request, string $teamId)
    {
        try {
            $team = Team::findOrFail($teamId);

            // Kiểm tra quyền: Chỉ captain hoặc admin
            $user = Auth::user();
            if ($user->role->name !== 'admin' && $team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng hoặc admin mới có quyền thêm thành viên', 403);
            }

            $validated = $request->validate([
                'player_name' => 'required|string|max:255',
                'code_id' => 'nullable|string|max:20',
                'jersey_number' => 'nullable|integer|min:1|max:99',
                'position' => 'nullable|in:Thủ môn,Hậu vệ,Tiền vệ,Tiền đạo',
            ]);

            // ✅ KIỂM TRA: Nếu có CCCD, kiểm tra CCCD có bị trùng không
            if (!empty($validated['code_id'])) {
                // Kiểm tra CCCD có trùng với đội trưởng nào không
                $captainWithCodeId = User::where('code_id', $validated['code_id'])->first();
                if ($captainWithCodeId) {
                    $captainTeam = Team::where('captain_user_id', $captainWithCodeId->id)->first();
                    if ($captainTeam) {
                        return ApiResponse::error(
                            'CCCD này đã được sử dụng bởi đội trưởng của đội "' . $captainTeam->name . '"',
                            ['existing_team_id' => $captainTeam->id],
                            400
                        );
                    }
                }

                // Kiểm tra CCCD đã có trong team_members của đội khác chưa
                $existingMember = TeamMember::where('code_id', $validated['code_id'])
                    ->where('team_id', '!=', $teamId)
                    ->first();

                if ($existingMember) {
                    $existingTeam = Team::find($existingMember->team_id);
                    return ApiResponse::error(
                        'CCCD này đã tham gia đội "' . ($existingTeam ? $existingTeam->name : 'khác') . '". Một người chỉ được tham gia một đội bóng.',
                        [
                            'existing_team_id' => $existingMember->team_id,
                            'existing_member_name' => $existingMember->player_name
                        ],
                        400
                    );
                }
            }

            // Kiểm tra số áo có bị trùng không (nếu có nhập)
            if (isset($validated['jersey_number'])) {
                $jerseyExists = TeamMember::where('team_id', $teamId)
                    ->where('jersey_number', $validated['jersey_number'])
                    ->exists();

                if ($jerseyExists) {
                    return ApiResponse::error('Số áo ' . $validated['jersey_number'] . ' đã có người sử dụng', 400);
                }
            }

            $validated['team_id'] = $teamId;
            $member = TeamMember::create($validated);

            return ApiResponse::success('Thêm thành viên thành công', $member, 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xem chi tiết thành viên
     * GET /api/teams/{team_id}/members/{id}
     */
    public function show(string $teamId, string $id)
    {
        try {
            $member = TeamMember::with('team')
                ->where('team_id', $teamId)
                ->findOrFail($id);

            return ApiResponse::success('Chi tiết thành viên', $member);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy thành viên', 404);
        }
    }

    /**
     * Cập nhật thông tin thành viên
     * PUT /api/teams/{team_id}/members/{id}
     */
    public function update(Request $request, string $teamId, string $id)
    {
        try {
            $team = Team::findOrFail($teamId);
            $member = TeamMember::where('team_id', $teamId)->findOrFail($id);

            // Kiểm tra quyền: Chỉ captain hoặc admin
            $user = Auth::user();
            if ($user->role->name !== 'admin' && $team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng hoặc admin mới có quyền cập nhật', 403);
            }

            $validated = $request->validate([
                'player_name' => 'sometimes|string|max:255',
                'code_id' => 'nullable|string|max:20',
                'jersey_number' => 'nullable|integer|min:1|max:99',
                'position' => 'nullable|in:Thủ môn,Hậu vệ,Tiền vệ,Tiền đạo',
            ]);

            // Kiểm tra số áo trùng (nếu có thay đổi)
            if (isset($validated['jersey_number']) && $validated['jersey_number'] != $member->jersey_number) {
                $jerseyExists = TeamMember::where('team_id', $teamId)
                    ->where('jersey_number', $validated['jersey_number'])
                    ->where('id', '!=', $id)
                    ->exists();

                if ($jerseyExists) {
                    return ApiResponse::error('Số áo ' . $validated['jersey_number'] . ' đã có người sử dụng', 400);
                }
            }

            $member->update($validated);

            return ApiResponse::success('Cập nhật thành công', $member);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xóa thành viên khỏi đội
     * DELETE /api/teams/{team_id}/members/{id}
     */
    public function destroy(string $teamId, string $id)
    {
        try {
            $team = Team::findOrFail($teamId);
            $member = TeamMember::where('team_id', $teamId)->findOrFail($id);

            // Kiểm tra quyền: Chỉ captain hoặc admin
            $user = Auth::user();
            if ($user->role->name !== 'admin' && $team->captain_user_id !== $user->id) {
                return ApiResponse::error('Chỉ đội trưởng hoặc admin mới có quyền xóa thành viên', 403);
            }

            $member->delete();

            return ApiResponse::success('Xóa thành viên khỏi đội thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
