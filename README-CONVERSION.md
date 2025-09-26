# Pageant Tabulation System (PHP + Tailwind)

This project is a server-rendered PHP implementation using Tailwind CSS and a single API endpoint pattern (`api/api.php?action=...`).

## Structure

- `index.php` Landing
- `dashboard.php` Admin dashboard
- `participants.php` Manage participants
- `judges.php` Manage judges
- `rounds.php` Rounds & criteria (view for MVP)
- `live_control.php` Open/close rounds
- `leaderboard.php` Leaderboard snapshot
- `advancement.php` Advancement review (Top 5 etc.)
- `final_round.php` Final round control
- `awards.php` Award management
- `tie_resolution.php` Tie groups
- `settings.php` Visibility toggles
- `public_*.php` Public display pages

### Directories
- `classes/` Database + service classes (AuthService, PageantService, ScoreService, AwardsService)
- `components/` Reusable UI partials
- `partials/` Layout/shared fragments
- `assets/js/` Small helper scripts (api, toast, scoring)
- `public/css/` (placeholder for compiled Tailwind if moving off CDN)

## Services
See PHPDoc headers inside each class for method responsibilities. Extend with additional validation & business logic (locking, permissions) as you implement the API actions.

## API Pattern
All dynamic requests POST to `api/api.php?action=ACTION_NAME` returning JSON `{ success: bool, ... }`.

## Security Notes
- Always wrap echo output with `htmlspecialchars` (already applied in components).
- Add CSRF token generation & verification (TODO) for form submissions.
- Ensure session cookie flags: `HttpOnly`, `SameSite=Lax`.

## Next Steps
1. Implement missing API actions in `api/api.php` mapping to service layer.
2. Add real data fetching in page scripts (currently placeholders).
3. Enforce authentication and role gating (uncomment `requireLogin()` / `requireRole()`).
4. Build schema from finalized ERD (pending) and run migrations.
5. Add round state management + locking logic.
6. Implement leaderboard aggregation query.
7. Implement advancement logic and tie resolution storage.

## Tailwind
Currently uses CDN for rapid iteration. For production consider local build with Purge for performance.

## Accessibility
Follow-ups: ARIA attributes on modals, keyboard focus trapping, button labels.

---
Generated scaffold; extend with finalized schema & logic.
