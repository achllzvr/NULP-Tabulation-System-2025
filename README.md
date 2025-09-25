# NULP Tabulation System (2025 Refactor)

## Overview
This codebase was refactored from a set of ad‑hoc service-style PHP classes into a lightweight procedural architecture ("CHED-Pages" style) with a single bootstrap, unified API endpoint, and a curated set of helper modules. Tailwind CSS class strings in markup were preserved verbatim.

## Core Principles Implemented
1. Single bootstrap include that:
   - Starts the session exactly once (`includes/bootstrap.php`).
   - Loads procedural helper files.
   - Defines global escaping helper `esc()`.
   - Enables dev error reporting (can be toggled for production).
2. One database access layer: `classes/database.php` (PDO singleton via `Database::get()`).
3. Unified API endpoint: `api/api.php` using an `action` switch for round control, scoring, and leaderboard retrieval (extensible).
4. Procedural domain helpers (stateless functions):
   - `classes/auth.php` – login/logout, session user, role guards.
   - `classes/pageant.php` – pageant context, selection, listing.
   - `classes/rounds.php` – round lifecycle (open/close/create), criteria management, status summaries.
   - `classes/scores.php` – scoring persistence & aggregation.
   - `classes/awards.php` – (baseline placeholder) awards related helpers.
5. Standardized session keys: `$_SESSION['user_id']`, `$_SESSION['user_role']`, `$_SESSION['pageant_id']` (set or ensured by login + `pageant_ensure_session()`).
6. Output escaping: Always via `esc()` (all former `Util::escape` references removed).
7. Flow guards enforce coherent UI state transitions.

## Flow Guards Summary
| Area | Guard Condition | Behavior |
|------|-----------------|----------|
| Judge scoring (`judge_active.php`) | Requires an OPEN round | Otherwise shows "No Active Round" card |
| Leaderboard (`leaderboard.php`) | Shows only last CLOSED round | If OPEN round active or none closed, shows info message |
| Advancement (`advancement.php`) | Preliminary CLOSED AND final NOT OPEN | Otherwise informational guard message |
| Final round scoring (`final_round.php`) | Final round OPEN | Else shows guard message |
| Awards (`awards.php`) | Final round CLOSED | Else shows guard message |
| Public prelim/final pages | (Graceful fallback) | Show placeholder if data not yet public |

## File Map (Key Components)
```
includes/bootstrap.php       # Central session + helper includes + esc()
api/api.php                  # Unified JSON API (action switch)
classes/database.php         # PDO singleton
classes/auth.php             # Authentication & role guards
classes/pageant.php          # Pageant context functions
classes/rounds.php           # Round lifecycle & criteria helpers
classes/scores.php           # Score storage & aggregation
classes/awards.php           # Awards placeholder helpers
partials/*.php               # Shared layout pieces (head, nav, footer)
components/*.php             # Small UI component partials
```

## Current API Actions
| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `open_round` | POST | `round_id` | Opens a round (auto-closes any other OPEN round). |
| `close_round` | POST | `round_id` | Closes currently OPEN round. |
| `submit_score` | POST | `round_id`, `criterion_id`, `participant_id`, `value` | Upserts a judge score. |
| `leaderboard` | GET | `round_id` | Returns aggregated participant scores (weighted). |

Extend by adding new cases to the switch (keep them small and explicit).

## Escaping & Security
- Always use `esc($value)` for any dynamic HTML output.
- API returns JSON with `Content-Type: application/json` and catches exceptions broadly (improve granularity later).
- Role checks happen inside action handlers or page guards (`auth_require_login()`, explicit role comparisons, or future helper improvements).

## Development vs Production
`includes/bootstrap.php` enables verbose error reporting. For production deployment toggle or wrap with an environment check.

Example adjustment:
```php
if (!isset($GLOBALS['APP_DEV_INITIALIZED'])) {
	if (getenv('APP_ENV') !== 'prod') {
		ini_set('display_errors',1);
		ini_set('display_startup_errors',1);
		error_reporting(E_ALL);
	} else {
		ini_set('display_errors',0);
	}
	$GLOBALS['APP_DEV_INITIALIZED']=true;
}
```

## Typical Page Pattern
```php
require __DIR__.'/includes/bootstrap.php';
auth_require_login();
pageant_ensure_session();
$pageTitle = 'Some Page';
require __DIR__.'/includes/head.php';
// Page logic + esc() outputs
require __DIR__.'/includes/footer.php';
```

## Migration Completed
Removed legacy OOP service classes:
`AuthService.php, PageantService.php, RoundService.php, ScoreService.php, ParticipantService.php, JudgeService.php, PublicService.php, SessionManager.php, Services.php, Util.php`.

## Known Gaps / Next Steps
1. Persist advancement selections (implement storage in `advancements` table if present or create one).
2. Awards assignment persistence and public award display pages.
3. Final round scoring UI parity with judge_active (reuse criteria + participants listing logic).
4. Additional API endpoints for bulk participant/judge management (move selective legacy functions from `api/api.php` lower section or retire them if obsolete).
5. Tighter permission helpers (e.g., `auth_require_role(['ADMIN'])`).
6. Add CSRF mitigation (hidden token + session validation) for POST forms.
7. Improve error surfacing in UI (central flash messaging partial).

## Testing Walkthrough (Logical)
1. Login as ADMIN ⇒ open prelim round.
2. Login as JUDGE ⇒ score participants in `judge_active.php`.
3. ADMIN closes round ⇒ `leaderboard.php` shows results.
4. ADMIN visits `advancement.php` ⇒ selects finalists (placeholder now).
5. ADMIN opens final round ⇒ judges score final.
6. ADMIN closes final round ⇒ `awards.php` enables award assignment.
7. Public pages show safe fallback if data withheld.

## Contributing
Keep new business logic inside the appropriate procedural helper file; pages should stay thin (retrieve data + render). When adding a new feature:
1. Add helper function in a `classes/*.php` module.
2. Include call sites in pages or API switch.
3. Preserve Tailwind classes (do not rename or restructure unless purely additive).

## License
Internal / TBD.

---
Refactor summary authored automatically to document the 2025 architectural transition.
