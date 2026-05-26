<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Playlist extends Model
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, u.name AS owner_name
             FROM playlists p
             INNER JOIN users u ON u.id = p.user_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, COUNT(pt.track_id) AS tracks_count
             FROM playlists p
             LEFT JOIN playlist_tracks pt ON pt.playlist_id = p.id
             WHERE p.user_id = :u
             GROUP BY p.id
             ORDER BY p.id DESC'
        );
        $stmt->execute(['u' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Returns the playlist with its tracks pre-loaded.
     *
     * @return array<string, mixed>|null
     */
    public function findWithTracks(int $id): ?array
    {
        $playlist = $this->findById($id);

        if ($playlist === null) {
            return null;
        }

        $stmt = $this->db()->prepare(
            'SELECT t.*,
                    u.name  AS author_name,
                    a.title AS album_title,
                    COUNT(DISTINCT lk.user_id, lk.track_id) AS likes_count,
                    pt.sort_order
             FROM playlist_tracks pt
             INNER JOIN tracks t ON t.id  = pt.track_id
             INNER JOIN users  u ON u.id  = t.author_id
             LEFT  JOIN albums a ON a.id  = t.album_id
             LEFT  JOIN likes  lk ON lk.track_id = t.id
             WHERE pt.playlist_id = :id
             GROUP BY t.id, pt.sort_order
             ORDER BY pt.sort_order ASC, pt.track_id ASC'
        );
        $stmt->execute(['id' => $id]);
        $playlist['tracks'] = $stmt->fetchAll();

        return $playlist;
    }

    public function create(int $userId, string $title, bool $isPublic = true): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO playlists (user_id, title, is_public) VALUES (:u, :t, :p)'
        );
        $stmt->execute(['u' => $userId, 't' => $title, 'p' => $isPublic ? 1 : 0]);

        return (int) $this->db()->lastInsertId();
    }

    public function updateById(int $id, string $title, bool $isPublic): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE playlists SET title = :t, is_public = :p WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 't' => $title, 'p' => $isPublic ? 1 : 0]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM playlists WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function hasTrack(int $playlistId, int $trackId): bool
    {
        $stmt = $this->db()->prepare(
            'SELECT 1 FROM playlist_tracks WHERE playlist_id = :p AND track_id = :t LIMIT 1'
        );
        $stmt->execute(['p' => $playlistId, 't' => $trackId]);

        return $stmt->fetch() !== false;
    }

    public function addTrack(int $playlistId, int $trackId): void
    {
        if ($this->hasTrack($playlistId, $trackId)) {
            return;
        }

        $stmt = $this->db()->prepare(
            'SELECT COALESCE(MAX(sort_order), -1) + 1 FROM playlist_tracks WHERE playlist_id = :p'
        );
        $stmt->execute(['p' => $playlistId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $this->db()->prepare(
            'INSERT INTO playlist_tracks (playlist_id, track_id, sort_order) VALUES (:p, :t, :o)'
        );
        $stmt->execute(['p' => $playlistId, 't' => $trackId, 'o' => $nextOrder]);
    }

    public function removeTrack(int $playlistId, int $trackId): void
    {
        $stmt = $this->db()->prepare(
            'DELETE FROM playlist_tracks WHERE playlist_id = :p AND track_id = :t'
        );
        $stmt->execute(['p' => $playlistId, 't' => $trackId]);
    }
}
