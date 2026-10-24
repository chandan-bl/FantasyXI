<?php

class Contest
{
    public static function all(): array
    {
        $sql = "SELECT c.*, fa.name AS franchise_a_name, fa.short_code AS franchise_a_code,
                       fb.name AS franchise_b_name, fb.short_code AS franchise_b_code,
                       (SELECT COUNT(*) FROM contest_entries ce WHERE ce.contest_id = c.id) AS entry_count
                FROM contests c
                JOIN franchises fa ON fa.id = c.franchise_a_id
                JOIN franchises fb ON fb.id = c.franchise_b_id
                ORDER BY c.match_datetime DESC";
        return Database::connect()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT c.*, fa.name AS franchise_a_name, fa.short_code AS franchise_a_code,
                    fb.name AS franchise_b_name, fb.short_code AS franchise_b_code
             FROM contests c
             JOIN franchises fa ON fa.id = c.franchise_a_id
             JOIN franchises fb ON fb.id = c.franchise_b_id
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Contests a manager can still join: not already joined, not locked, not completed. */
    public static function openForManager(int $managerId): array
    {
        $sql = "SELECT c.*, fa.name AS franchise_a_name, fa.short_code AS franchise_a_code,
                       fb.name AS franchise_b_name, fb.short_code AS franchise_b_code
                FROM contests c
                JOIN franchises fa ON fa.id = c.franchise_a_id
                JOIN franchises fb ON fb.id = c.franchise_b_id
                WHERE c.status = 'scheduled' AND c.match_datetime > NOW()
                  AND c.id NOT IN (SELECT contest_id FROM contest_entries WHERE manager_id = ?)
                ORDER BY c.match_datetime ASC";
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$managerId]);
        return $stmt->fetchAll();
    }

    public static function create(int $franchiseAId, int $franchiseBId, string $matchDatetime, ?string $venue): int
    {
        $stmt = Database::connect()->prepare(
            "INSERT INTO contests (franchise_a_id, franchise_b_id, match_datetime, venue, status)
             VALUES (?, ?, ?, ?, 'scheduled')"
        );
        $stmt->execute([$franchiseAId, $franchiseBId, $matchDatetime, $venue]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, int $franchiseAId, int $franchiseBId, string $matchDatetime, ?string $venue): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE contests SET franchise_a_id = ?, franchise_b_id = ?, match_datetime = ?, venue = ? WHERE id = ?'
        );
        $stmt->execute([$franchiseAId, $franchiseBId, $matchDatetime, $venue, $id]);
    }

    public static function markCompleted(int $id): void
    {
        $stmt = Database::connect()->prepare("UPDATE contests SET status = 'completed' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function isLocked(array $contest): bool
    {
        return $contest['status'] === 'completed' || is_contest_locked($contest['match_datetime']);
    }

    public static function managerHasJoined(int $contestId, int $managerId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM contest_entries WHERE contest_id = ? AND manager_id = ?');
        $stmt->execute([$contestId, $managerId]);
        return (bool)$stmt->fetch();
    }
}
