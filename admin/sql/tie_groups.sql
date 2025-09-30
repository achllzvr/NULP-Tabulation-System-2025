-- SQL for tie_groups table
CREATE TABLE IF NOT EXISTS tie_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pageant_id INT NOT NULL,
    score DECIMAL(10,2) NOT NULL,
    state ENUM('pending','in_progress','closed','finalized') NOT NULL DEFAULT 'pending',
    participant_ids TEXT NOT NULL, -- comma-separated participant IDs
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add index for pageant_id for fast lookup
CREATE INDEX idx_tie_groups_pageant_id ON tie_groups(pageant_id);