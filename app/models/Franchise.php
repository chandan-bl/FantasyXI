<?php

class Franchise
{
    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM franchises';
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= ' ORDER BY name';
        return Database::connect()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM franchises WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $shortCode): int
    {
        $stmt = Database::connect()->prepare(
            "INSERT INTO franchises (name, short_code, status) VALUES (?, ?, 'active')"
        );
        $stmt->execute([$name, $shortCode]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, string $shortCode): void
    {
        $stmt = Database::connect()->prepare('UPDATE franchises SET name = ?, short_code = ? WHERE id = ?');
        $stmt->execute([$name, $shortCode, $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE franchises SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }
}
