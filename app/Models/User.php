<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * @param array{name: string, email: string, password: string, role: string} $data
     */
    public function create(array $data): array
    {
        $statement = $this->db()->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
        ]);

        return $this->findById((int) $this->db()->lastInsertId()) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithStats(): array
    {
        $statement = $this->db()->query(
            'SELECT
                u.*,
                COUNT(DISTINCT a.id) AS albums_count,
                COUNT(DISTINCT t.id) AS tracks_count,
                COUNT(DISTINCT s.subscriber_id) AS subscribers_count
            FROM users u
            LEFT JOIN albums a ON a.author_id = u.id
            LEFT JOIN tracks t ON t.author_id = u.id
            LEFT JOIN subscriptions s ON s.author_id = u.id
            GROUP BY u.id
            ORDER BY u.created_at DESC, u.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAuthorsForSelection(): array
    {
        $statement = $this->db()->query(
            "SELECT id, name, email, role
             FROM users
             WHERE role IN ('author', 'admin')
             ORDER BY name ASC, id ASC"
        );

        return $statement->fetchAll();
    }

    public function updateById(int $id, array $data): void
    {
        if (($data['password'] ?? '') !== '') {
            $statement = $this->db()->prepare(
                'UPDATE users
                 SET name = :name, email = :email, role = :role, password = :password
                 WHERE id = :id'
            );

            $statement->execute([
                'id' => $id,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            ]);

            return;
        }

        $statement = $this->db()->prepare(
            'UPDATE users
             SET name = :name, email = :email, role = :role
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);
    }

    public function deleteById(int $id): void
    {
        $statement = $this->db()->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function countAdmins(): int
    {
        $statement = $this->db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");

        return (int) $statement->fetchColumn();
    }

    public function findPublicAuthorById(int $id): ?array
    {
        $statement = $this->db()->prepare(
            "SELECT u.id, u.name, u.avatar, u.bio, u.role, u.created_at,
                    COUNT(DISTINCT t.id)             AS tracks_count,
                    COUNT(DISTINCT a.id)             AS albums_count,
                    COUNT(DISTINCT s.subscriber_id)  AS subscribers_count
             FROM users u
             LEFT JOIN tracks t       ON t.author_id     = u.id
             LEFT JOIN albums a       ON a.author_id     = u.id
             LEFT JOIN subscriptions s ON s.author_id    = u.id
             WHERE u.id = :id AND u.role IN ('author','admin')
             GROUP BY u.id
             LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $author = $statement->fetch();

        return $author ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchAuthors(string $q): array
    {
        $q = trim($q);

        if ($q === '') {
            return [];
        }

        $safe = preg_replace('/[+\-><()\[\]~*"@]+/', ' ', $q);
        $safe = trim((string) $safe);

        if ($safe === '') {
            return [];
        }

        $term = '"' . $safe . '*"';

        $statement = $this->db()->prepare(
            "SELECT u.id, u.name, u.avatar,
                    COUNT(DISTINCT t.id)            AS tracks_count,
                    COUNT(DISTINCT s.subscriber_id) AS subscribers_count
             FROM (
                 SELECT id, name, avatar
                 FROM users
                 WHERE role IN ('author', 'admin')
                   AND MATCH(name) AGAINST(:term IN BOOLEAN MODE)
                 LIMIT 10
             ) u
             LEFT JOIN tracks t        ON t.author_id  = u.id
             LEFT JOIN subscriptions s ON s.author_id  = u.id
             GROUP BY u.id
             ORDER BY tracks_count DESC"
        );
        $statement->execute(['term' => $term]);

        return $statement->fetchAll();
    }

    public function updateProfile(int $id, array $data): void
    {
        if (!empty($data['new_password'])) {
            $stmt = $this->db()->prepare(
                'UPDATE users SET name = :name, bio = :bio, password = :password WHERE id = :id'
            );
            $stmt->execute([
                'id'       => $id,
                'name'     => $data['name'],
                'bio'      => $data['bio'],
                'password' => password_hash((string) $data['new_password'], PASSWORD_DEFAULT),
            ]);

            return;
        }

        $stmt = $this->db()->prepare(
            'UPDATE users SET name = :name, bio = :bio WHERE id = :id'
        );
        $stmt->execute([
            'id'   => $id,
            'name' => $data['name'],
            'bio'  => $data['bio'],
        ]);
    }

    public function updateAvatar(int $id, string $avatarPath): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
        $stmt->execute(['id' => $id, 'avatar' => $avatarPath]);
    }
}
