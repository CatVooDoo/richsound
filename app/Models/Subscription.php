<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Subscription extends Model
{
    public function toggle(int $subscriberId, int $authorId): bool
    {
        if ($this->isSubscribed($subscriberId, $authorId)) {
            $stmt = $this->db()->prepare(
                'DELETE FROM subscriptions WHERE subscriber_id = :s AND author_id = :a'
            );
            $stmt->execute(['s' => $subscriberId, 'a' => $authorId]);

            return false;
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO subscriptions (subscriber_id, author_id) VALUES (:s, :a)'
        );
        $stmt->execute(['s' => $subscriberId, 'a' => $authorId]);

        return true;
    }

    public function isSubscribed(int $subscriberId, int $authorId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT 1 FROM subscriptions WHERE subscriber_id = :s AND author_id = :a LIMIT 1'
        );
        $stmt->execute(['s' => $subscriberId, 'a' => $authorId]);

        return $stmt->fetch() !== false;
    }

    public function countByAuthor(int $authorId): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM subscriptions WHERE author_id = :a'
        );
        $stmt->execute(['a' => $authorId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * New subscribers per day for the given author over the last N days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDynamics(int $authorId, int $days): array
    {
        $interval = max(0, $days - 1);
        $stmt = $this->db()->prepare(
            'SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM subscriptions
             WHERE author_id = :author_id
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ' . $interval . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC'
        );
        $stmt->execute(['author_id' => $authorId]);

        return $stmt->fetchAll();
    }

    /**
     * Authors the given user is subscribed to, with aggregate stats.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBySubscriber(int $subscriberId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT u.id, u.name, u.avatar,
                    COUNT(DISTINCT s2.subscriber_id) AS subscribers_count,
                    COUNT(DISTINCT t.id)             AS tracks_count
             FROM subscriptions s
             INNER JOIN users u  ON u.id = s.author_id
             LEFT  JOIN subscriptions s2 ON s2.author_id    = u.id
             LEFT  JOIN tracks t         ON t.author_id     = u.id
             WHERE s.subscriber_id = :s
             GROUP BY u.id
             ORDER BY u.name ASC'
        );
        $stmt->execute(['s' => $subscriberId]);

        return $stmt->fetchAll();
    }
}
