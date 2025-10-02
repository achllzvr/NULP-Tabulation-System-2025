-- Schema for per-round judge signing
-- Ensure utf8mb4/utf8mb4_unicode_ci for consistency

CREATE TABLE IF NOT EXISTS round_signing (
  id INT AUTO_INCREMENT PRIMARY KEY,
  round_id INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  opened_at DATETIME NOT NULL,
  closed_at DATETIME NULL,
  CONSTRAINT fk_round_signing_round FOREIGN KEY (round_id)
    REFERENCES rounds(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS round_signing_judges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  round_signing_id INT NOT NULL,
  judge_user_id INT NOT NULL,
  confirmed TINYINT(1) NOT NULL DEFAULT 0,
  confirmed_at DATETIME NULL,
  UNIQUE KEY uniq_round_signing_judge (round_signing_id, judge_user_id),
  KEY idx_round_signing_id (round_signing_id),
  KEY idx_round_signing_judge_user (judge_user_id),
  CONSTRAINT fk_round_signing_judges_signing FOREIGN KEY (round_signing_id)
    REFERENCES round_signing(id) ON DELETE CASCADE,
  CONSTRAINT fk_round_signing_judges_user FOREIGN KEY (judge_user_id)
    REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
