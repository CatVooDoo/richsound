<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Like extends Model
{
    public function toggle(int $userId, int $trackId): bool
    {
        if ($this->isLiked($userId, $trackId)) {
            $stmt = $this->db()->prepare(
                'DELETE FROM likes WHERE user_id = :u AND track_id = :t'
            );
            $stmt->execute(['u' => $userId, 't' => $trackId]);

            return false;
        }

        $stmt = $this->db()->prepare(
            'INSERT INTO likes (user_id, track_id) VALUES (:u, :t)'
        );
        $stmt->execute(['u' => $userId, 't' => $trackId]);

        return true;
    }

    public function isLiked(int $userId, int $trackId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT 1 FROM likes WHERE user_id = :u AND track_id = :t LIMIT 1'
        );
        $stmt->execute(['u' => $userId, 't' => $trackId]);

        return $stmt->fetch() !== false;
    }

    public function countByTrack(int $trackId): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM likes WHERE track_id = :t'
        );
        $stmt->execute(['t' => $trackId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT t.*,
                    u.name  AS author_name,
                    a.title AS album_title,
                    COUNT(DISTINCT lk2.user_id, lk2.track_id) AS likes_count,
                    COUNT(DISTINCT li.id) AS listens_count
             FROM likes lk
             INNER JOIN tracks t  ON t.id  = lk.track_id
             INNER JOIN users  u  ON u.id  = t.author_id
             LEFT  JOIN albums a  ON a.id  = t.album_id
             LEFT  JOIN likes  lk2 ON lk2.track_id = t.id
             LEFT  JOIN listens li ON li.track_id  = t.id
             WHERE lk.user_id = :u
             GROUP BY t.id
             ORDER BY t.created_at DESC, t.id DESC'
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Returns a flat list of liked track IDs for a given user.
     *
     * @return int[]
     */
    public function getTrackIdsByUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT track_id FROM likes WHERE user_id = :u'
        );
        $stmt->execute(['u' => $userId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'track_id'));
    }
}
