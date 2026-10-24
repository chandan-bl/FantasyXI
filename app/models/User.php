<?php

class User
{
    public static function findById(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password, string $role = 'manager'): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, "active")'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function allManagers(): array
    {
        $stmt = Database::connect()->query(
            "SELECT * FROM users WHERE role = 'manager' ORDER BY created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "manager"');
        $stmt->execute([$status, $id]);
    }
}
