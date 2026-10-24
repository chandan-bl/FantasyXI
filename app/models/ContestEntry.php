<?php

class ContestEntry
{
    public static function findForManagerContest(int $contestId, int $managerId): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT * FROM contest_entries WHERE contest_id = ? AND manager_id = ?'
        );
        $stmt->execute([$contestId, $managerId]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $entryId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM contest_entries WHERE id = ?');
        $stmt->execute([$entryId]);
        return $stmt->fetch() ?: null;
    }

    /** The 11 picked players for an entry, with player + match-stat detail joined in. */
    public static function playersForEntry(int $entryId): array
    {
        $sql = "SELECT cep.*, p.name, p.role, p.credit_value, p.franchise_id, f.name AS franchise_name, f.short_code AS franchise_code
                FROM contest_entry_players cep
                JOIN players p ON p.id = cep.player_id
                JOIN franchises f ON f.id = p.franchise_id
                WHERE cep.entry_id = ?
                ORDER BY FIELD(p.role, 'wicketkeeper','batter','allrounder','bowler'), p.name";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$entryId]);
        return $stmt->fetchAll();
    }

    /**
     * Create or replace a manager's team for a contest. Re-validated by the
     * caller (TeamController) before this is invoked; this layer additionally
     * relies on the DB's UNIQUE(contest_id, manager_id) constraint as a
     * second line of defense against a race of two simultaneous joins.
     *
     * @param array $playerIds exactly 11 player ids
     */
    public static function save(int $contestId, int $managerId, array $playerIds, int $captainId, int $viceCaptainId, float $totalCredits, ?int $existingEntryId): int
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            if ($existingEntryId !== null) {
                $stmt = $pdo->prepare(
                    'UPDATE contest_entries SET captain_player_id = ?, vice_captain_player_id = ?, total_credits_used = ? WHERE id = ?'
                );
                $stmt->execute([$captainId, $viceCaptainId, $totalCredits, $existingEntryId]);
                $entryId = $existingEntryId;

                $del = $pdo->prepare('DELETE FROM contest_entry_players WHERE entry_id = ?');
                $del->execute([$entryId]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO contest_entries (contest_id, manager_id, captain_player_id, vice_captain_player_id, total_credits_used, total_points)
                     VALUES (?, ?, ?, ?, ?, 0)'
                );
                $stmt->execute([$contestId, $managerId, $captainId, $viceCaptainId, $totalCredits]);
                $entryId = (int)$pdo->lastInsertId();
            }

            $insertPlayer = $pdo->prepare(
                'INSERT INTO contest_entry_players (entry_id, player_id, is_captain, is_vice_captain) VALUES (?, ?, ?, ?)'
            );
            foreach ($playerIds as $pid) {
                $insertPlayer->execute([$entryId, $pid, $pid === $captainId ? 1 : 0, $pid === $viceCaptainId ? 1 : 0]);
            }

            $pdo->commit();
            return $entryId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Every manager's entry for a contest, ranked by total points. */
    public static function leaderboardForContest(int $contestId): array
    {
        $sql = "SELECT ce.*, u.name AS manager_name
                FROM contest_entries ce
                JOIN users u ON u.id = ce.manager_id
                WHERE ce.contest_id = ?
                ORDER BY ce.total_points DESC, ce.id ASC";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$contestId]);
        return $stmt->fetchAll();
    }

    /** Season-cumulative points per manager, across every contest joined. */
    public static function seasonLeaderboard(): array
    {
        $sql = "SELECT u.id AS manager_id, u.name AS manager_name,
                       COUNT(ce.id) AS contests_joined,
                       COALESCE(SUM(ce.total_points), 0) AS season_points
                FROM users u
                JOIN contest_entries ce ON ce.manager_id = u.id
                WHERE u.role = 'manager'
                GROUP BY u.id, u.name
                ORDER BY season_points DESC, contests_joined DESC";
        return Database::connect()->query($sql)->fetchAll();
    }

    /** All entries (managers) that joined a given contest, with player ids they used -- for scoring. */
    public static function entriesForContest(int $contestId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM contest_entries WHERE contest_id = ?');
        $stmt->execute([$contestId]);
        return $stmt->fetchAll();
    }

    public static function updateEntryPlayerPoints(int $entryPlayerRowId, float $points): void
    {
        $stmt = Database::connect()->prepare('UPDATE contest_entry_players SET points_awarded = ? WHERE id = ?');
        $stmt->execute([$points, $entryPlayerRowId]);
    }

    public static function updateEntryTotal(int $entryId, float $total): void
    {
        $stmt = Database::connect()->prepare('UPDATE contest_entries SET total_points = ? WHERE id = ?');
        $stmt->execute([$total, $entryId]);
    }

    /** All manager entries this contest, for a viewer's "my contests" list. */
    public static function forManager(int $managerId): array
    {
        $sql = "SELECT ce.*, c.match_datetime, c.status AS contest_status,
                       fa.name AS franchise_a_name, fa.short_code AS franchise_a_code,
                       fb.name AS franchise_b_name, fb.short_code AS franchise_b_code
                FROM contest_entries ce
                JOIN contests c ON c.id = ce.contest_id
                JOIN franchises fa ON fa.id = c.franchise_a_id
                JOIN franchises fb ON fb.id = c.franchise_b_id
                WHERE ce.manager_id = ?
                ORDER BY c.match_datetime DESC";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$managerId]);
        return $stmt->fetchAll();
    }
}
