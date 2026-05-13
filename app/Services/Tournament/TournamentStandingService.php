<?php

namespace App\Services\Tournament;

use App\Models\TournamentGroupStanding;
use App\Models\TournamentMatch;
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
                        'matches_played'  => 0,
                        'wins'            => 0,
                        'draws'           => 0,
                        'losses'          => 0,
                        'goals_for'       => 0,
                        'goals_against'   => 0,
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

        // Write accumulated stats back
        foreach ($stats as $teamId => $s) {
            $gd     = $s['goals_for'] - $s['goals_against'];
            $points = $s['wins'] * 3 + $s['draws'];

            TournamentGroupStanding::where('tournament_group_id', $groupId)
                ->where('team_id', $teamId)
                ->update([
                    'matches_played'  => $s['matches_played'],
                    'wins'            => $s['wins'],
                    'draws'           => $s['draws'],
                    'losses'          => $s['losses'],
                    'goals_for'       => $s['goals_for'],
                    'goals_against'   => $s['goals_against'],
                    'goal_difference' => $gd,
                    'points'          => $points,
                ]);
        }

        // Recalculate rankings (points → goal_difference → goals_for → wins → name)
        $standings = TournamentGroupStanding::where('tournament_group_id', $groupId)
            ->with('team')
            ->get()
            ->sortByDesc(function ($s) {
                return [
                    $s->points,
                    $s->goal_difference,
                    $s->goals_for,
                    $s->wins,
                ];
            })
            ->values();

        foreach ($standings as $rank => $standing) {
            $standing->ranking = $rank + 1;
            $standing->save();
        }
    }
}
