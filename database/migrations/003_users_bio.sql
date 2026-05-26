-- Phase 6: Add bio field to users for author profiles
ALTER TABLE users
    ADD COLUMN bio TEXT DEFAULT NULL AFTER avatar;
