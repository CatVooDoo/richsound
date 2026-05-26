<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Album extends Model
{
    public function findById(int $id): ?array
    {
        $statement = $this->db()->prepare('SELECT * FROM albums WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $album = $statement->fetch();

        return $album ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllWithStats(): array
    {
        $statement = $this->db()->query(
            'SELECT
                a.*,
                u.name AS author_name,
                COUNT(DISTINCT t.id) AS tracks_count
            FROM albums a
            INNER JOIN users u ON u.id = a.author_id
            LEFT JOIN tracks t ON t.album_id = a.id
            GROUP BY a.id
            ORDER BY a.released_at DESC, a.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByAuthorIdWithStats(int $authorId): array
    {
        $statement = $this->db()->prepare(
            'SELECT
                a.*,
                u.name AS author_name,
                COUNT(DISTINCT t.id) AS tracks_count
            FROM albums a
            INNER JOIN users u ON u.id = a.author_id
            LEFT JOIN tracks t ON t.album_id = a.id
            WHERE a.author_id = :author_id
            GROUP BY a.id
            ORDER BY a.released_at DESC, a.id DESC'
        );
        $statement->execute(['author_id' => $authorId]);

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByAuthorIdForSelection(int $authorId): array
    {
        $statement = $this->db()->prepare(
            'SELECT id, title FROM albums WHERE author_id = :author_id ORDER BY title ASC, id ASC'
        );
        $statement->execute(['author_id' => $authorId]);

        return $statement->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllForSelection(): array
    {
        $statement = $this->db()->query(
            'SELECT id, title
             FROM albums
             ORDER BY title ASC, id ASC'
        );

        return $statement->fetchAll();
    }

    /**
     * @param array{author_id: int, title: string, cover_path: ?string, released_at: ?string} $data
     */
    public function create(array $data): void
    {
        $statement = $this->db()->prepare(
            'INSERT INTO albums (author_id, title, cover_path, released_at)
             VALUES (:author_id, :title, :cover_path, :released_at)'
        );

        $statement->execute([
            'author_id' => $data['author_id'],
            'title' => $data['title'],
            'cover_path' => $data['cover_path'],
            'released_at' => $data['released_at'],
        ]);
    }

    /**
     * @param array{author_id: int, title: string, cover_path: ?string, released_at: ?string} $data
     */
    public function updateById(int $id, array $data): void
    {
        $statement = $this->db()->prepare(
            'UPDATE albums
             SET author_id = :author_id, title = :title, cover_path = :cover_path, released_at = :released_at
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'author_id' => $data['author_id'],
            'title' => $data['title'],
            'cover_path' => $data['cover_path'],
            'released_at' => $data['released_at'],
        ]);
    }

    public function deleteById(int $id): void
    {
        $statement = $this->db()->prepare('DELETE FROM albums WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function findPublicById(int $id): ?array
    {
        $statement = $this->db()->prepare(
            'SELECT a.*, u.name AS author_name, COUNT(DISTINCT t.id) AS tracks_count
             FROM albums a
             INNER JOIN users u ON u.id = a.author_id
             LEFT JOIN tracks t ON t.album_id = a.id
             WHERE a.id = :id
             GROUP BY a.id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $album = $statement->fetch();

        return $album ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findTracksByAlbumId(int $albumId): array
    {
        $statement = $this->db()->prepare(
            'SELECT t.*,
                    u.name AS author_name,
                    COUNT(DISTINCT l.user_id, l.track_id) AS likes_count,
                    COUNT(DISTINCT li.id) AS listens_count
             FROM tracks t
             INNER JOIN users u ON u.id = t.author_id
             LEFT JOIN likes l ON l.track_id = t.id
             LEFT JOIN listens li ON li.track_id = t.id
             WHERE t.album_id = :album_id
             GROUP BY t.id
             ORDER BY t.created_at ASC, t.id ASC'
        );
        $statement->execute(['album_id' => $albumId]);

        return $statement->fetchAll();
    }
}
