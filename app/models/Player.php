<?php

class Player
{
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT players.*, franchises.name AS franchise_name, franchises.short_code AS franchise_code
                FROM players JOIN franchises ON franchises.id = players.franchise_id';
        if ($activeOnly) {
            $sql .= " WHERE players.status = 'active'";
        }
        $sql .= ' ORDER BY players.name';
        return Database::connect()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT players.*, franchises.name AS franchise_name, franchises.short_code AS franchise_code
             FROM players JOIN franchises ON franchises.id = players.franchise_id
             WHERE players.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Every active player belonging to either of the two given franchises. */
    public static function byFranchises(int $franchiseAId, int $franchiseBId, bool $activeOnly = true): array
    {
        $sql = 'SELECT players.*, franchises.name AS franchise_name, franchises.short_code AS franchise_code
                FROM players JOIN franchises ON franchises.id = players.franchise_id
                WHERE players.franchise_id IN (?, ?)';
        if ($activeOnly) {
            $sql .= " AND players.status = 'active'";
        }
        $sql .= ' ORDER BY players.role, players.name';
        $stmt = Database::connect()->prepare($sql);
        $stmt->execute([$franchiseAId, $franchiseBId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO players (name, franchise_id, role, credit_value, career_runs, career_wickets, career_matches, career_trophies, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'], $data['franchise_id'], $data['role'], $data['credit_value'],
            $data['career_runs'], $data['career_wickets'], $data['career_matches'], $data['career_trophies'],
            $data['status'] ?? 'active',
        ]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE players SET name = ?, franchise_id = ?, role = ?, credit_value = ?,
             career_runs = ?, career_wickets = ?, career_matches = ?, career_trophies = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'], $data['franchise_id'], $data['role'], $data['credit_value'],
            $data['career_runs'], $data['career_wickets'], $data['career_matches'], $data['career_trophies'],
            $id,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE players SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }
}
