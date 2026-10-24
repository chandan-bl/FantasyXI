<?php
require_once __DIR__ . '/Scoring.php';

/**
 * Orchestrates one contest's scoring run: reads every player's entered
 * match_stats, computes base fantasy points via the fixed Scoring rubric,
 * writes those back onto match_stats.points for transparency, then walks
 * every manager's contest_entry, applies the captain/vice-captain
 * multiplier per player, and persists both per-player and entry totals.
 * Marks the contest completed at the end. Fully re-runnable (idempotent) --
 * re-running after fixing a stat correction just recomputes everything.
 */
class ScoringRun
{
    public static function run(int $contestId): void
    {
        $pdo = Database::connect();

        // Player roles are needed for the duck-penalty role restriction.
        $roleStmt = $pdo->prepare(
            'SELECT players.id, players.role FROM players
             JOIN match_stats ON match_stats.player_id = players.id
             WHERE match_stats.contest_id = ?'
        );
        $roleStmt->execute([$contestId]);
        $roleByPlayer = [];
        foreach ($roleStmt->fetchAll() as $r) {
            $roleByPlayer[(int)$r['id']] = $r['role'];
        }

        $statsStmt = $pdo->prepare('SELECT * FROM match_stats WHERE contest_id = ?');
        $statsStmt->execute([$contestId]);
        $pointsByPlayer = [];
        foreach ($statsStmt->fetchAll() as $stat) {
            $playerId = (int)$stat['player_id'];
            $role = $roleByPlayer[$playerId] ?? 'batter';
            $points = Scoring::pointsFor($stat, $role);
            $pointsByPlayer[$playerId] = $points;
            MatchStat::setComputedPoints($contestId, $playerId, $points);
        }

        $entries = ContestEntry::entriesForContest($contestId);
        foreach ($entries as $entry) {
            $entryPlayers = ContestEntry::playersForEntry((int)$entry['id']);
            $total = 0.0;
            foreach ($entryPlayers as $ep) {
                $playerId = (int)$ep['player_id'];
                $base = $pointsByPlayer[$playerId] ?? 0.0;
                $multiplier = Scoring::multiplierFor((bool)$ep['is_captain'], (bool)$ep['is_vice_captain']);
                $final = round($base * $multiplier, 1);
                ContestEntry::updateEntryPlayerPoints((int)$ep['id'], $base);
                $total += $final;
            }
            ContestEntry::updateEntryTotal((int)$entry['id'], round($total, 1));
        }

        Contest::markCompleted($contestId);
    }
}
