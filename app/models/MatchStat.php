<?php

class MatchStat
{
    private const FIELDS = [
        'played', 'runs', 'fours', 'sixes', 'is_out',
        'wickets', 'wickets_bowled_lbw', 'maidens',
        'catches', 'stumpings', 'run_outs_direct', 'run_outs_assisted',
    ];

    /** Existing stats for a contest, keyed by player_id. */
    public static function forContest(int $contestId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM match_stats WHERE contest_id = ?');
        $stmt->execute([$contestId]);
        $rows = $stmt->fetchAll();
        $byPlayer = [];
        foreach ($rows as $row) {
            $byPlayer[(int)$row['player_id']] = $row;
        }
        return $byPlayer;
    }

    /**
     * Upsert one player's raw match performance for a contest. Values are
     * expected already sanitized/cast by the controller.
     */
    public static function upsert(int $contestId, int $playerId, array $data): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            'SELECT id FROM match_stats WHERE contest_id = ? AND player_id = ?'
        );
        $stmt->execute([$contestId, $playerId]);
        $existing = $stmt->fetch();

        $values = [];
        foreach (self::FIELDS as $f) {
            $values[$f] = $data[$f] ?? 0;
        }

        if ($existing) {
            $set = implode(', ', array_map(fn($f) => "$f = ?", self::FIELDS));
            $stmt = $pdo->prepare("UPDATE match_stats SET $set WHERE id = ?");
            $stmt->execute([...array_values($values), $existing['id']]);
        } else {
            $cols = implode(', ', self::FIELDS);
            $placeholders = implode(', ', array_fill(0, count(self::FIELDS), '?'));
            $stmt = $pdo->prepare(
                "INSERT INTO match_stats (contest_id, player_id, $cols) VALUES (?, ?, $placeholders)"
            );
            $stmt->execute([$contestId, $playerId, ...array_values($values)]);
        }
    }

    public static function forContestPlayer(int $contestId, int $playerId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM match_stats WHERE contest_id = ? AND player_id = ?');
        $stmt->execute([$contestId, $playerId]);
        return $stmt->fetch() ?: null;
    }

    public static function setComputedPoints(int $contestId, int $playerId, float $points): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE match_stats SET points = ? WHERE contest_id = ? AND player_id = ?'
        );
        $stmt->execute([$points, $contestId, $playerId]);
    }
}
