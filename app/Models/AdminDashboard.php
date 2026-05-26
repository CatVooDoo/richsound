<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminDashboard extends Model
{
    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $statement = $this->db()->query(
            "SELECT
                (SELECT COUNT(*) FROM users) AS users_total,
                (SELECT COUNT(*) FROM users WHERE role = 'listener') AS listeners_total,
                (SELECT COUNT(*) FROM users WHERE role = 'author') AS authors_total,
                (SELECT COUNT(*) FROM users WHERE role = 'admin') AS admins_total,
                (SELECT COUNT(*) FROM albums) AS albums_total,
                (SELECT COUNT(*) FROM tracks) AS tracks_total,
                (SELECT COUNT(*) FROM playlists) AS playlists_total,
                (SELECT COUNT(*) FROM likes) AS likes_total,
                (SELECT COUNT(*) FROM listens) AS listens_total,
                (SELECT COUNT(*) FROM subscriptions) AS subscriptions_total"
        );

        $row = $statement->fetch() ?: [];

        return array_map(static fn(mixed $value): int => (int) $value, $row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listensByDay(int $days = 7): array
    {
        $intervalDays = max(0, $days - 1);
        $statement = $this->db()->query(
            'SELECT DATE(listened_at) AS day, COUNT(*) AS total
             FROM listens
             WHERE listened_at >= DATE_SUB(CURDATE(), INTERVAL ' . $intervalDays . ' DAY)
             GROUP BY DATE(listened_at)
             ORDER BY day ASC'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function registrationsByDay(int $days = 7): array
    {
        $intervalDays = max(0, $days - 1);
        $statement = $this->db()->query(
            'SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . $intervalDays . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC'
        );

        return $statement->fetchAll();
    }

    /**
     * Top N authors ranked by total listen count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topAuthors(int $limit = 5): array
    {
        $statement = $this->db()->prepare(
            'SELECT
                u.id,
                u.name,
                COUNT(li.id)              AS listens_count,
                COUNT(DISTINCT li.user_id) AS unique_listeners
             FROM users u
             INNER JOIN tracks t  ON t.author_id = u.id
             LEFT  JOIN listens li ON li.track_id = t.id
             WHERE u.role IN (\'author\', \'admin\')
             GROUP BY u.id
             ORDER BY listens_count DESC, unique_listeners DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Top N tracks by listen count within the last N days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topTracksByPeriod(int $days, int $limit = 5): array
    {
        $intervalDays = max(0, $days - 1);
        $statement = $this->db()->prepare(
            'SELECT
                t.id,
                t.title,
                u.name AS author_name,
                COUNT(DISTINCT l.user_id, l.track_id) AS likes_count,
                COUNT(DISTINCT li.id)                  AS listens_count
             FROM tracks t
             INNER JOIN users u ON u.id = t.author_id
             LEFT  JOIN likes   l  ON l.track_id  = t.id
             LEFT  JOIN listens li ON li.track_id  = t.id
                         AND li.listened_at >= DATE_SUB(CURDATE(), INTERVAL ' . $intervalDays . ' DAY)
             GROUP BY t.id
             ORDER BY listens_count DESC, likes_count DESC, t.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topTracks(int $limit = 5): array
    {
        $statement = $this->db()->prepare(
            'SELECT
                t.id,
                t.title,
                u.name AS author_name,
                COUNT(DISTINCT l.user_id, l.track_id) AS likes_count,
                COUNT(DISTINCT li.id) AS listens_count
             FROM tracks t
             INNER JOIN users u ON u.id = t.author_id
             LEFT JOIN likes l ON l.track_id = t.id
             LEFT JOIN listens li ON li.track_id = t.id
             GROUP BY t.id
             ORDER BY listens_count DESC, likes_count DESC, t.id DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
