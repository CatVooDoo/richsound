-- Phase 5: Add created_at to subscriptions for subscriber growth charts
ALTER TABLE subscriptions
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
