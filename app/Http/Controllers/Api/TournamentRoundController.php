<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentRound;
use App\Models\Tournament;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentRoundController extends Controller
{
    // Lấy danh sách vòng đấu (theo tournament_id)
    public function index(Request $request)
    {
        try {
            $query = TournamentRound::with(['matches.homeTeam', 'matches.awayTeam', 'matches.field']);

            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            $rounds = $query->orderBy('tournament_id')->orderBy('round_order')->get();

            return ApiResponse::success('Danh sách vòng đấu', $rounds);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tạo vòng đấu mới (thủ công)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tournament_id'  => 'required|exists:tournaments,id',
                'round_name'     => 'required|string|max:50',
                'round_type'     => 'required|in:group_stage,round_of_32,round_of_16,quarter_final,semi_final,third_place,final',
                'round_order'    => 'required|integer|min:1',
                'start_date'     => 'nullable|date',
                'end_date'       => 'nullable|date|after_or_equal:start_date',
                'is_knockout'    => 'nullable|boolean',
            ]);

            $tournament = Tournament::findOrFail($validated['tournament_id']);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền tạo vòng đấu', null, 403);
            }

            $round = TournamentRound::create($validated);

            return ApiResponse::success('Tạo vòng đấu thành công', $round->load('tournament'), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Chi tiết vòng đấu
    public function show(string $id)
    {
        try {
            $round = TournamentRound::with([
                'tournament',
                'matches' => function ($q) {
                    $q->orderBy('match_number')->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field']);
                }
            ])->findOrFail($id);

            return ApiResponse::success('Chi tiết vòng đấu', $round);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy vòng đấu', null, 404);
        }
    }

    // Cập nhật vòng đấu (tên, ngày tháng)
    public function update(Request $request, string $id)
    {
        try {
            $round = TournamentRound::with('tournament')->findOrFail($id);
            $user = Auth::user();

            if ($round->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền cập nhật vòng đấu', null, 403);
            }

            $validated = $request->validate([
                'round_name'  => 'sometimes|string|max:50',
                'round_type'  => 'sometimes|in:group_stage,round_of_32,round_of_16,quarter_final,semi_final,third_place,final',
                'round_order' => 'sometimes|integer|min:1',
                'start_date'  => 'nullable|date',
                'end_date'    => 'nullable|date|after_or_equal:start_date',
                'is_knockout' => 'nullable|boolean',
            ]);

            $round->update($validated);

            return ApiResponse::success('Cập nhật vòng đấu thành công', $round);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Xóa vòng đấu (chỉ được xóa nếu chưa có trận nào completed)
    public function destroy(string $id)
    {
        try {
            $round = TournamentRound::with(['tournament', 'matches'])->findOrFail($id);
            $user = Auth::user();

            if ($round->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền xóa vòng đấu', null, 403);
            }

            if ($round->matches->where('status', 'completed')->count() > 0) {
                return ApiResponse::error('Không thể xóa vòng đấu đã có trận hoàn thành', null, 400);
            }

            $round->delete();

            return ApiResponse::success('Xóa vòng đấu thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
