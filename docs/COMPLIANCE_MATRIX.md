# Compliance Matrix

| Category | Requirement | Implementation Status | Notes |
|----------|-------------|-----------------------|-------|
| Architecture | Single endpoint API dispatcher | Implemented (`api.php`) | Action-based routing with JSON responses |
| Roles | Admin & Judge separation | Implemented | `AuthService::requireRole` gating |
| Sessions | Regeneration & inactivity timeout | Implemented | 15 min inactivity, ID regeneration on login |
| Rounds | Open/Close states with weight validation | Implemented | `RoundService::canOpenRound` enforces total weight=100 & criteria existence |
| Scoring | Raw + override support | Partial | Override write path pending UI; computation uses `COALESCE(override_score, raw_score)` |
| Score Validation | Clamp to criterion max | Implemented | via insertion logic & `ScoreService` max_score usage |
| Leaderboard | Weighted aggregation with dense rank | Implemented | Fallback query + optional normalization |
| Normalization | Z_SCORE & MIN_MAX_PER_JUDGE | Implemented (algorithms) | Auto-applied if `rounds.score_normalization_strategy` set |
| Advancement | Preview & commit with tie boundary block | Implemented | `AdvancementService` + `TieService` boundary detection |
| Ties | Boundary tie detection & advisory resolution | Implemented (basic) | No persistent tie group table yet |
| Awards | Auto-compute across modes | Implemented | `AwardsService::computeAward/computeAll` + API preview/persist |
| People Choice | View-based votes integration | Partial | Requires `v_people_choice_votes` view or source table |
| Visibility | Public gating / masking | Pending | Placeholder public endpoints (to implement reveal flags) |
| Audit | Central logging for major actions | Implemented | `AuditLogger` writes to `audit_logs` |
| Security | CSRF for state-changing actions | Implemented | Token via `csrf_token()` + header validation |
| API | JSON uniform error handling | Implemented | `respond()` helper centralizes |
| Performance | SQL view optimization (participant totals) | Implemented (view usage) | Falls back if view missing |
| Documentation | Compliance matrix & normalization spec | This document / AWARDS_NORMALIZATION.md | Living documents |
| Password Reset | force_password_reset flow | Pending | Field updated on login; reset flow not built |
| Caching | Leaderboard caching/invalidation | Pending | Potential optimization |
| UI Awards | Admin preview & persist, public display | Implemented | `awards.php`, `public_awards.php` JS wiring |

## Pending / Next
- Visibility masking flags implementation.
- Persistent tie resolution artifact storage.
- Password reset & force reset UI.
- Leaderboard caching & invalidation strategy.
- Enhanced audit search UI.
- Extended award configuration UI (criterion selector, round set editors).
