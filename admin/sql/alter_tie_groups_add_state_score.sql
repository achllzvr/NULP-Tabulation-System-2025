-- Add state and score columns to tie_groups
ALTER TABLE tie_groups
  ADD COLUMN state ENUM('pending','in_progress','closed','finalized') NOT NULL DEFAULT 'pending' AFTER division_id,
  ADD COLUMN score DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER state;

-- Add index for state for fast lookup
CREATE INDEX idx_tie_groups_state ON tie_groups(state);