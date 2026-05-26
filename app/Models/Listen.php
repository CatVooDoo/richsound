<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Listen extends Model
{
    public function create(?int $userId, int $trackId, ?int $listenedSeconds, bool $isCompleted): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO listens (user_id, track_id, listened_seconds, is_completed, listened_at)
             VALUES (:user_id, :track_id, :listened_seconds, :is_completed, NOW())'
        );

        $statement->execute([
            'user_id' => $userId,
            'track_id' => $trackId,
            'listened_seconds' => $listenedSeconds,
            'is_completed' => $isCompleted ? 1 : 0,
        ]);
    }

    /**
     * Listens per day for the given author over the last N days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAuthorStats(int $authorId, int $days): array
    {
        $interval = max(0, $days - 1);
        $stmt = $this->db()->prepare(
            'SELECT DATE(li.listened_at) AS day, COUNT(*) AS total
             FROM listens li
             INNER JOIN tracks t ON t.id = li.track_id
             WHERE t.author_id = :author_id
               AND li.listened_at >= DATE_SUB(CURDATE(), INTERVAL ' . $interval . ' DAY)
             GROUP BY DATE(li.listened_at)
             ORDER BY day ASC'
        );
        $stmt->execute(['author_id' => $authorId]);

        return $stmt->fetchAll();
    }

    /**
     * Top N tracks for this author ranked by listen count.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTopByAuthor(int $authorId, int $limit): array
    {
        $stmt = $this->db()->prepare(
            'SELECT t.id, t.title, t.plays_count,
                    COUNT(li.id) AS listens_count
             FROM tracks t
             LEFT JOIN listens li ON li.track_id = t.id
             WHERE t.author_id = :author_id
             GROUP BY t.id
             ORDER BY listens_count DESC, t.plays_count DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':author_id', $authorId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Number of unique authenticated listeners for this author (all-time).
     */
    public function getUniqueListenerCount(int $authorId): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(DISTINCT li.user_id) AS cnt
             FROM listens li
             INNER JOIN tracks t ON t.id = li.track_id
             WHERE t.author_id = :author_id
               AND li.user_id IS NOT NULL'
        );
        $stmt->execute(['author_id' => $authorId]);
        $row = $stmt->fetch();

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Unique authenticated listeners per day for this author over the last N days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUniqueListenersByDay(int $authorId, int $days): array
    {
        $interval = max(0, $days - 1);
        $stmt = $this->db()->prepare(
            'SELECT DATE(li.listened_at) AS day, COUNT(DISTINCT li.user_id) AS total
             FROM listens li
             INNER JOIN tracks t ON t.id = li.track_id
             WHERE t.author_id = :author_id
               AND li.user_id IS NOT NULL
               AND li.listened_at >= DATE_SUB(CURDATE(), INTERVAL ' . $interval . ' DAY)
             GROUP BY DATE(li.listened_at)
             ORDER BY day ASC'
        );
        $stmt->execute(['author_id' => $authorId]);

        return $stmt->fetchAll();
    }

    /**
     * Ratio of completed listens to total listens for this author (0.0–1.0).
     */
    public function getCompletionRate(int $authorId): float
    {
        $stmt = $this->db()->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN li.is_completed = 1 THEN 1 ELSE 0 END) AS completed
             FROM listens li
             INNER JOIN tracks t ON t.id = li.track_id
             WHERE t.author_id = :author_id'
        );
        $stmt->execute(['author_id' => $authorId]);
        $row = $stmt->fetch();

        if ($row === false || (int) $row['total'] === 0) {
            return 0.0;
        }

        return round((int) $row['completed'] / (int) $row['total'], 4);
    }
}
