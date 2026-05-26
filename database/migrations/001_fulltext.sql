-- Phase 4: Full-text search indexes
ALTER TABLE tracks ADD FULLTEXT INDEX ft_tracks_title (title);
ALTER TABLE users  ADD FULLTEXT INDEX ft_users_name  (name);
