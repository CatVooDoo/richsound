<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\RedisConnection;

final class Track extends Model
{
    private function baseListQuery(): string
    {
        return 'SELECT
                t.*,
                u.name AS author_name,
                a.title AS album_title,
                COUNT(DISTINCT l.user_id, l.track_id) AS likes_count,
                COUNT(DISTINCT li.id) AS listens_count
            FROM tracks t
            INNER JOIN users u ON u.id = t.author_id
            LEFT JOIN albums a ON a.id = t.album_id
            LEFT JOIN likes l ON l.track_id = t.id
            LEFT JOIN listens li ON li.track_id = t.id';
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM tracks WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $track = $statement->fetch();

        return $track ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithStats(): array
    {
        $statement = $this->db()->query(
            $this->baseListQuery() . '
            GROUP BY t.id
            ORDER BY t.created_at DESC, t.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findLatestForHome(int $limit = 8): array
    {
        $cacheKey = 'track:home:latest:' . $limit;
        $redis    = RedisConnection::get();

        if ($redis !== null) {
            $cached = $redis->get($cacheKey);
            if ($cached !== false) {
                return json_decode($cached, true) ?: [];
            }
        }

        $statement = $this->db()->prepare(
            $this->baseListQuery() . '
            GROUP BY t.id
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetchAll();

        if ($redis !== null) {
            $redis->setex($cacheKey, 60, json_encode($result));
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPopularForHome(int $limit = 6): array
    {
        $cacheKey = 'track:home:popular:' . $limit;
        $redis    = RedisConnection::get();

        if ($redis !== null) {
            $cached = $redis->get($cacheKey);
            if ($cached !== false) {
                return json_decode($cached, true) ?: [];
            }
        }

        $statement = $this->db()->prepare(
            $this->baseListQuery() . '
            GROUP BY t.id
            ORDER BY t.plays_count DESC, listens_count DESC, t.created_at DESC, t.id DESC
            LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetchAll();

        if ($redis !== null) {
            $redis->setex($cacheKey, 60, json_encode($result));
        }

        return $result;
    }

    public function invalidateHomeCache(): void
    {
        $redis = RedisConnection::get();
        if ($redis === null) {
            return;
        }
        foreach ([6, 8] as $limit) {
            $redis->del('track:home:latest:' . $limit);
            $redis->del('track:home:popular:' . $limit);
        }
    }

    public function countAll(): int
    {
        $statement = $this->db()->query('SELECT COUNT(*) FROM tracks');
        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPaginatedForHome(int $limit, int $offset): array
    {
        $statement = $this->db()->prepare(
            $this->baseListQuery() . '
            GROUP BY t.id
            ORDER BY t.created_at DESC, t.id DESC
            LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByAuthorIdWithStats(int $authorId): array
    {
        $statement = $this->db()->prepare(
            $this->baseListQuery() . '
            WHERE t.author_id = :author_id
            GROUP BY t.id
            ORDER BY t.created_at DESC, t.id DESC'
        );
        $statement->execute(['author_id' => $authorId]);

        return $statement->fetchAll();
    }

    public function findDetailedById(int $id): ?array
    {
        $statement = $this->db()->prepare(
            $this->baseListQuery() . '
            WHERE t.id = :id
            GROUP BY t.id
            LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $track = $statement->fetch();

        return $track ?: null;
    }

    /**
     * @param array{author_id: int, album_id: ?int, title: string, file_path: string, cover_path: ?string, duration: ?int, plays_count: int} $data
     */
    public function create(array $data): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO tracks (author_id, album_id, title, file_path, cover_path, duration, plays_count)
             VALUES (:author_id, :album_id, :title, :file_path, :cover_path, :duration, :plays_count)'
        );

        $statement->execute([
            'author_id' => $data['author_id'],
            'album_id' => $data['album_id'],
            'title' => $data['title'],
            'file_path' => $data['file_path'],
            'cover_path' => $data['cover_path'],
            'duration' => $data['duration'],
            'plays_count' => $data['plays_count'],
        ]);

        $this->invalidateHomeCache();
    }

    /**
     * @param array{author_id: int, album_id: ?int, title: string, file_path: string, cover_path: ?string, duration: ?int, plays_count: int} $data
     */
    public function updateById(int $id, array $data): void
    {
        $statement = $this->db()->prepare(
            'UPDATE tracks
             SET author_id = :author_id,
                 album_id = :album_id,
                 title = :title,
                 file_path = :file_path,
                 cover_path = :cover_path,
                 duration = :duration,
                 plays_count = :plays_count
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'author_id' => $data['author_id'],
            'album_id' => $data['album_id'],
            'title' => $data['title'],
            'file_path' => $data['file_path'],
            'cover_path' => $data['cover_path'],
            'duration' => $data['duration'],
            'plays_count' => $data['plays_count'],
        ]);

        $this->invalidateHomeCache();
    }

    public function deleteById(int $id): void
    {
        $statement = $this->db()->prepare('DELETE FROM tracks WHERE id = :id');
        $statement->execute(['id' => $id]);
        $this->invalidateHomeCache();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q): array
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
            $this->baseListQuery() . '
            WHERE MATCH(t.title) AGAINST(:term IN BOOLEAN MODE)
            GROUP BY t.id
            ORDER BY MATCH(t.title) AGAINST(:term2 IN BOOLEAN MODE) DESC, t.plays_count DESC
            LIMIT 20'
        );
        $statement->execute(['term' => $term, 'term2' => $term]);

        return $statement->fetchAll();
    }
}
