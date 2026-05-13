<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentGroupStanding;
use App\Helpers\ApiResponse;
use App\Services\Tournament\TournamentStandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentMatchController extends Controller
{
    // Lấy danh sách trận đấu
    public function index(Request $request)
    {
        try {
            $query = TournamentMatch::with(['tournament', 'homeTeam', 'awayTeam', 'field.fieldCluster', 'round']);

            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('round_id')) {
                $query->where('tournament_round_id', $request->round_id);
            }

            $matches = $query->orderBy('match_date')->orderBy('match_time')->paginate(20);

            return ApiResponse::success('Danh sách trận đấu', $matches);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tạo trận đấu mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tournament_id' => 'required|exists:tournaments,id',
                'tournament_round_id' => 'required|exists:tournament_rounds,id',
                'tournament_group_id' => 'nullable|exists:tournament_groups,id',
                'home_team_id' => 'nullable|exists:teams,id',
                'away_team_id' => 'nullable|exists:teams,id',
                'field_id' => 'required|exists:fields,id',
                'match_date' => 'required|date',
                'match_time' => 'required',
                'match_number' => 'nullable|integer',
            ]);

            $match = TournamentMatch::create($validated);

            return ApiResponse::success('Tạo trận đấu thành công',
                $match->load(['tournament', 'homeTeam', 'awayTeam', 'field.fieldCluster']), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Chi tiết trận đấu
    public function show(string $id)
    {
        try {
            $match = TournamentMatch::with([
                'tournament',
                'homeTeam',
                'awayTeam',
                'field.fieldCluster',
                'round',
                'group'
            ])->findOrFail($id);

            return ApiResponse::success('Chi tiết trận đấu', $match);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy trận đấu', null, 404);
        }
    }

    /**
     * Alias of updateScore — PUT /tournament-matches/{id}/result
     * Provided so FE can use a more semantic endpoint name.
     */
    public function result(Request $request, string $id)
    {
        return $this->updateScore($request, $id);
    }

    // Cập nhật tỷ số trận đấu
    public function updateScore(Request $request, string $id)
    {
        try {
            $match = TournamentMatch::with(['tournament', 'group', 'round'])->findOrFail($id);

            // Chỉ organizer của giải mới được nhập tỷ số
            $user = Auth::user();
            if ($match->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền cập nhật tỷ số', null, 403);
            }

            $validated = $request->validate([
                'home_score' => 'required|integer|min:0',
                'away_score' => 'required|integer|min:0',
                'home_penalty_score' => 'nullable|integer|min:0',
                'away_penalty_score' => 'nullable|integer|min:0',
            ]);

            DB::beginTransaction();

            try {
                // Determine winner
                $winner_id = null;
                if ($validated['home_score'] > $validated['away_score']) {
                    $winner_id = $match->home_team_id;
                } elseif ($validated['away_score'] > $validated['home_score']) {
                    $winner_id = $match->away_team_id;
                } elseif (isset($validated['home_penalty_score']) && isset($validated['away_penalty_score'])) {
                    // Penalty shootout
                    if ($validated['home_penalty_score'] > $validated['away_penalty_score']) {
                        $winner_id = $match->home_team_id;
                    } else {
                        $winner_id = $match->away_team_id;
                    }
                }

                $match->update([
                    'home_score' => $validated['home_score'],
                    'away_score' => $validated['away_score'],
                    'home_penalty_score' => $validated['home_penalty_score'] ?? null,
                    'away_penalty_score' => $validated['away_penalty_score'] ?? null,
                    'winner_team_id' => $winner_id,
                    'status' => 'completed',
                ]);

                // Nếu là trận vòng bảng → tự động cập nhật standings
                if ($match->tournament_group_id) {
                    $this->updateGroupStandings($match);
                }

                // Nếu là knockout → update trận tiếp theo
                if ($match->round->is_knockout && $winner_id) {
                    $this->updateNextMatch($match, $winner_id);
                }

                DB::commit();

                return ApiResponse::success('Cập nhật tỷ số thành công',
                    $match->fresh()->load(['homeTeam', 'awayTeam']));
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tự động cập nhật bảng xếp hạng — tính lại từ đầu, tránh double-count
    private function updateGroupStandings($match)
    {
        (new TournamentStandingService())->recalculate($match->tournament_group_id);
    }

    // Cập nhật thứ hạng trong bảng (kept for backward-compat, now a no-op since service handles it)
    private function updateGroupRankings($groupId)
    {
        // Rankings are now handled inside TournamentStandingService::recalculate()
    }

    // Cập nhật trận tiếp theo trong knockout
    private function updateNextMatch($currentMatch, $winnerId)
    {
        // Tìm đội thua (cho trận tranh hạng 3)
        $loserId = null;
        if ($currentMatch->home_team_id && $currentMatch->away_team_id) {
            $loserId = ($winnerId == $currentMatch->home_team_id) 
                ? $currentMatch->away_team_id 
                : $currentMatch->home_team_id;
        }

        // Tìm trận đấu tiếp theo cần đội thắng/thua này
        $nextMatches = TournamentMatch::with('round')
            ->where(function($query) use ($currentMatch) {
                $query->where('home_from_match_id', $currentMatch->id)
                      ->orWhere('away_from_match_id', $currentMatch->id);
            })->get();

        \Log::info('Updating next matches after match completion', [
            'current_match_id' => $currentMatch->id,
            'winner_id' => $winnerId,
            'loser_id' => $loserId,
            'next_matches_count' => $nextMatches->count(),
        ]);

        foreach ($nextMatches as $nextMatch) {
            $updated = false;
            
            // Check nếu là trận tranh hạng 3 → fill đội thua
            $isThirdPlaceMatch = ($nextMatch->bracket_position === 'third_place') 
                || ($nextMatch->round && $nextMatch->round->round_type === 'third_place');
            
            $teamToAdvance = $isThirdPlaceMatch ? $loserId : $winnerId;
            
            if ($nextMatch->home_from_match_id == $currentMatch->id) {
                $nextMatch->home_team_id = $teamToAdvance;
                $updated = true;
            }
            if ($nextMatch->away_from_match_id == $currentMatch->id) {
                $nextMatch->away_team_id = $teamToAdvance;
                $updated = true;
            }
            
            if ($updated) {
                $nextMatch->save();
                \Log::info('Updated next match team', [
                    'next_match_id' => $nextMatch->id,
                    'is_third_place' => $isThirdPlaceMatch,
                    'team_advanced' => $teamToAdvance,
                    'home_team_id' => $nextMatch->home_team_id,
                    'away_team_id' => $nextMatch->away_team_id,
                ]);
            }
        }
    }

    // Cập nhật thông tin trận
    public function update(Request $request, string $id)
    {
        try {
            $match = TournamentMatch::findOrFail($id);

            $validated = $request->validate([
                'match_date' => 'sometimes|date',
                'match_time' => 'sometimes',
                'field_id' => 'sometimes|exists:fields,id',
                'referee_name' => 'nullable|string',
                'status' => 'sometimes|in:scheduled,ongoing,completed,postponed,cancelled',
            ]);

            $match->update($validated);

            return ApiResponse::success('Cập nhật trận đấu thành công', $match);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xóa trận đấu
    public function destroy(string $id)
    {
        try {
            $match = TournamentMatch::findOrFail($id);

            // Chỉ xóa được trận chưa completed
            if ($match->status === 'completed') {
                return ApiResponse::error('Không thể xóa trận đã hoàn thành', null, 400);
            }

            $match->delete();

            return ApiResponse::success('Xóa trận đấu thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gán đội vào slot home/away của trận đấu (dùng khi kéo thả thủ công trên FE)
     * PATCH /tournament-matches/{id}/assign-team
     * Body: { "slot": "home|away", "team_id": 5 }
     */
    public function assignTeam(Request $request, string $id)
    {
        try {
            $match = TournamentMatch::with('tournament')->findOrFail($id);

            // Không cho gán nếu trận đã đá
            if (in_array($match->status, ['completed', 'ongoing'])) {
                return ApiResponse::error('Không thể thay đổi đội trong trận đang diễn ra hoặc đã kết thúc', null, 400);
            }

            $user = Auth::user();
            if ($match->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền gán đội', null, 403);
            }

            $validated = $request->validate([
                'slot'    => 'required|in:home,away',
                'team_id' => 'nullable|exists:teams,id',
            ]);

            // Nếu có team_id thì kiểm tra đội đã được duyệt tham gia giải chưa
            if (!is_null($validated['team_id'])) {
                $isApproved = TournamentRegistration::where('tournament_id', $match->tournament_id)
                    ->where('team_id', $validated['team_id'])
                    ->where('registration_status', 'approved')
                    ->exists();

                if (!$isApproved) {
                    return ApiResponse::error('Đội bóng này chưa được duyệt tham gia giải đấu', null, 400);
                }
            }

            if ($validated['slot'] === 'home') {
                $match->home_team_id = $validated['team_id'];
            } else {
                $match->away_team_id = $validated['team_id'];
            }
            $match->save();

            return ApiResponse::success(
                'Gán đội vào trận thành công',
                $match->fresh()->load(['homeTeam', 'awayTeam', 'field', 'round'])
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật sân thi đấu của trận
     * PATCH /tournament-matches/{id}/field
     * Body: { "field_id": 3 }
     */
    public function updateField(Request $request, string $id)
    {
        try {
            $match = TournamentMatch::with('tournament')->findOrFail($id);

            // Không cho đổi sân nếu trận đã đá
            if (in_array($match->status, ['completed', 'ongoing'])) {
                return ApiResponse::error('Không thể thay đổi sân trong trận đang diễn ra hoặc đã kết thúc', null, 400);
            }

            $user = Auth::user();
            if ($match->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền đổi sân', null, 403);
            }

            $validated = $request->validate([
                'field_id' => 'required|exists:fields,id',
            ]);

            $match->field_id = $validated['field_id'];
            $match->save();

            return ApiResponse::success(
                'Cập nhật sân thi đấu thành công',
                $match->fresh()->load(['homeTeam', 'awayTeam', 'field', 'round'])
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật ngày giờ thi đấu
     * PATCH /tournament-matches/{id}/schedule
     * Body: { "match_date": "2026-06-15", "match_time": "14:00" }
     */
    public function updateSchedule(Request $request, string $id)
    {
        try {
            $match = TournamentMatch::with('tournament')->findOrFail($id);

            // Không cho đổi lịch nếu trận đã đá
            if (in_array($match->status, ['completed', 'ongoing'])) {
                return ApiResponse::error('Không thể thay đổi lịch trong trận đang diễn ra hoặc đã kết thúc', null, 400);
            }

            $user = Auth::user();
            if ($match->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền đổi lịch', null, 403);
            }

            $validated = $request->validate([
                'match_date' => 'required|date',
                'match_time' => 'required|date_format:H:i',
            ]);

            $match->match_date = $validated['match_date'];
            $match->match_time = $validated['match_time'];
            $match->save();

            return ApiResponse::success(
                'Cập nhật lịch thi đấu thành công',
                $match->fresh()->load(['homeTeam', 'awayTeam', 'field', 'round'])
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}