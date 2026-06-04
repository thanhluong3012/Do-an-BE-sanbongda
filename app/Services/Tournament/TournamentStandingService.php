<?php

namespace App\Services\Tournament;

use App\Models\TournamentGroupStanding;
use App\Models\TournamentMatch;
use App\Models\MatchPlayerPenalty;
use Illuminate\Support\Collection;

/**
 * Recalculates group standings from scratch by scanning all completed matches.
 * This avoids the double-counting bug that occurs when updateGroupStandings
 * increments stats and the same match score is updated more than once.
 */
class TournamentStandingService
{
    /**
     * Fully recalculate standings for a given tournament group.
     * Deletes old stat columns and rebuilds from all completed matches.
     */
    public function recalculate(int $groupId): void
    {
        // Reset all standing rows to zero
        TournamentGroupStanding::where('tournament_group_id', $groupId)
            ->update([
                'matches_played'   => 0,
                'wins'             => 0,
                'draws'            => 0,
                'losses'           => 0,
                'goals_for'        => 0,
                'goals_against'    => 0,
                'goal_difference'  => 0,
                'points'           => 0,
                'fair_play_points' => 0,
                'ranking'          => 0,
            ]);

        // Fetch all completed matches in this group
        $matches = TournamentMatch::where('tournament_group_id', $groupId)
            ->where('status', 'completed')
            ->get();

        // Accumulate per-team stats
        $stats = [];

        foreach ($matches as $match) {
            $home = $match->home_team_id;
            $away = $match->away_team_id;

            if (!$home || !$away) {
                continue;
            }

            $homeGoals = (int) $match->home_score;
            $awayGoals = (int) $match->away_score;

            foreach ([$home, $away] as $teamId) {
                if (!isset($stats[$teamId])) {
                    $stats[$teamId] = [
                        'matches_played'   => 0,
                        'wins'             => 0,
                        'draws'            => 0,
                        'losses'           => 0,
                        'goals_for'        => 0,
                        'goals_against'    => 0,
                        'fair_play_points' => 0,
                    ];
                }
                $stats[$teamId]['matches_played']++;
            }

            if ($homeGoals > $awayGoals) {
                // Home wins
                $stats[$home]['wins']++;
                $stats[$away]['losses']++;
            } elseif ($homeGoals < $awayGoals) {
                // Away wins
                $stats[$away]['wins']++;
                $stats[$home]['losses']++;
            } else {
                // Draw
                $stats[$home]['draws']++;
                $stats[$away]['draws']++;
            }

            $stats[$home]['goals_for']      += $homeGoals;
            $stats[$home]['goals_against']  += $awayGoals;
            $stats[$away]['goals_for']      += $awayGoals;
            $stats[$away]['goals_against']  += $homeGoals;
        }

        // Calculate fair_play_points based on player card penalties
        $matchIds = $matches->pluck('id')->toArray();
        foreach ($stats as $teamId => $s) {
            $penalties = MatchPlayerPenalty::whereIn('match_id', $matchIds)
                ->where('team_id', $teamId)
                ->get()
                ->groupBy('match_id')
                ->map(function ($matchPenalties) {
                    // Group by player within this match to detect 2 yellows
                    return $matchPenalties->groupBy('player_id');
                });

            $fairPlayPoints = 0;
            foreach ($penalties as $matchId => $playerPenalties) {
                foreach ($playerPenalties as $playerId => $cards) {
                    $yellowCount = $cards->where('card_type', 'yellow')->count();
                    $redCount = $cards->where('card_type', 'red')->count();

                    // 1 red = -3 points
                    if ($redCount > 0) {
                        $fairPlayPoints -= 3;
                    }
                    // 2 yellows in 1 match = -3 points
                    elseif ($yellowCount >= 2) {
                        $fairPlayPoints -= 3;
                    }
                    // 1 yellow = -1 point
                    elseif ($yellowCount === 1) {
                        $fairPlayPoints -= 1;
                    }
                }
            }

            $stats[$teamId]['fair_play_points'] = $fairPlayPoints;
        }

        // Write accumulated stats back
        foreach ($stats as $teamId => $s) {
            $gd     = $s['goals_for'] - $s['goals_against'];
            $points = $s['wins'] * 3 + $s['draws'];

            TournamentGroupStanding::where('tournament_group_id', $groupId)
                ->where('team_id', $teamId)
                ->update([
                    'matches_played'   => $s['matches_played'],
                    'wins'             => $s['wins'],
                    'draws'            => $s['draws'],
                    'losses'           => $s['losses'],
                    'goals_for'        => $s['goals_for'],
                    'goals_against'    => $s['goals_against'],
                    'goal_difference'  => $gd,
                    'points'           => $points,
                    'fair_play_points' => $s['fair_play_points'],
                ]);
        }

        // Recalculate rankings (points → goal_difference → goals_for → wins → name)
        $standings = TournamentGroupStanding::where('tournament_group_id', $groupId)
            ->with('team')
            ->get()
            ->sort(function ($a, $b) {
                return $b->points <=> $a->points
                    ?: $b->goal_difference <=> $a->goal_difference
                    ?: $b->goals_for <=> $a->goals_for
                    ?: $b->wins <=> $a->wins
                    ?: $b->fair_play_points <=> $a->fair_play_points;
            })
            ->values();

        foreach ($standings as $rank => $standing) {
            $standing->ranking = $rank + 1;
            $standing->save();
        }
    }
}
