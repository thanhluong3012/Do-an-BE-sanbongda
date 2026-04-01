<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentGroup;
use App\Models\Tournament;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TournamentGroupController extends Controller
{
    // Lấy danh sách bảng đấu (theo tournament_id)
    public function index(Request $request)
    {
        try {
            $query = TournamentGroup::with([
                'standings' => function ($q) {
                    $q->orderBy('ranking')->with('team');
                },
                'matches' => function ($q) {
                    $q->orderBy('match_number')->with(['homeTeam', 'awayTeam', 'field']);
                }
            ]);

            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            $groups = $query->orderBy('group_name')->get();

            return ApiResponse::success('Danh sách bảng đấu', $groups);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Tạo bảng đấu mới
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tournament_id' => 'required|exists:tournaments,id',
                'group_name'    => 'required|string|max:10',
            ]);

            $tournament = Tournament::findOrFail($validated['tournament_id']);
            $user = Auth::user();

            if ($tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền tạo bảng đấu', 403);
            }

            $group = TournamentGroup::create($validated);

            return ApiResponse::success('Tạo bảng đấu thành công', $group, 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    // Chi tiết bảng đấu (bảng xếp hạng + trận)
    public function show(string $id)
    {
        try {
            $group = TournamentGroup::with([
                'tournament',
                'standings' => function ($q) {
                    $q->orderBy('ranking')->with('team');
                },
                'matches' => function ($q) {
                    $q->orderBy('match_number')->with(['homeTeam', 'awayTeam', 'winnerTeam', 'field']);
                }
            ])->findOrFail($id);

            return ApiResponse::success('Chi tiết bảng đấu', $group);
        } catch (\Exception $e) {
            return ApiResponse::error('Không tìm thấy bảng đấu', 404);
        }
    }

    // Xóa bảng đấu
    public function destroy(string $id)
    {
        try {
            $group = TournamentGroup::with('tournament')->findOrFail($id);
            $user = Auth::user();

            if ($group->tournament->organizer_id !== $user->id) {
                return ApiResponse::error('Chỉ người tổ chức giải mới có quyền xóa bảng đấu', 403);
            }

            $group->delete();

            return ApiResponse::success('Xóa bảng đấu thành công', null);
        } catch (\Exception $e) {
            return ApiResponse::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
}
