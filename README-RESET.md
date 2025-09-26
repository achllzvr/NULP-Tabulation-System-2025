# NULP Tabulation System 2025 - Clean Reset

## Overview

This document describes the complete reset of the NULP-Tabulation-System-2025 codebase to a clean, maintainable PHP + Tailwind CSS structure modeled after the achllzvr/CHED-Pages pattern.

## Reset Rationale

The previous codebase contained over-fragmented converted artifacts that made maintenance and development difficult. This reset introduces:

- **Cohesive Architecture**: Single `/classes` + `/includes` pattern
- **Simplified Structure**: Eliminated micro-service explosion 
- **Consistent Patterns**: Per-call PDO connection following CHED-Pages style
- **Modern UI**: Tailwind CSS CDN integration
- **Clean Sessions**: Centralized session management in bootstrap.php

## New Structure

### Core Classes (`/classes/`)

- **`database.php`** - Database connection and utility class with `opencon()` method
- **`auth.php`** - Session-based authentication helpers  
- **`pageant.php`** - Pageant selection and rounds listing functions
- **`rounds.php`** - Round management (open/close/criteria)
- **`scores.php`** - Score saving and leaderboard aggregation
- **`awards.php`** - Awards listing and winner management

### Bootstrap & Includes (`/includes/`)

- **`bootstrap.php`** - Single session_start, class loading, error reporting
- **`head.php`** - HTML head with Tailwind CDN and dynamic titles
- **`footer.php`** - Common footer with JavaScript utilities

### API Layer (`/api/`)

- **`api.php`** - Single JSON endpoint with action-based routing

### Page Scaffolds

All pages follow consistent pattern: `require bootstrap.php` → auth guards → placeholder content

**Authentication:**
- `login.php` - Email/password form with session creation
- `logout.php` - Session destruction and redirect

**Management Pages:** (Admin/Organizer only)
- `dashboard.php` - Main dashboard with role-based quick actions
- `participants.php` - Participant management interface
- `judges.php` - Judge management interface  
- `rounds.php` - Round control and monitoring
- `live_control.php` - Real-time pageant control
- `advancement.php` - Participant advancement management
- `final_round.php` - Final round management
- `awards.php` - Awards and winner management
- `tie_resolution.php` - Tie resolution interface
- `settings.php` - System and pageant configuration

**Public Pages:** (No authentication required)
- `public_prelim.php` - Public preliminary results
- `public_final.php` - Public final results  
- `public_awards.php` - Public awards display
- `leaderboard.php` - Current standings

## Session Management

- **Single Source**: Only `includes/bootstrap.php` calls `session_start()`
- **Standard Keys**: `user_id`, `user_role`, `pageant_id`
- **Auth Functions**: `auth_login()`, `auth_logout()`, `auth_user()`, `auth_require_login()`, `auth_require_role()`

## Database Configuration

Default database name: `nulp_tabulation`
Environment variable fallbacks for all connection settings.

## Technology Stack

- **Backend**: PHP (procedural functions + single database class)
- **Frontend**: Tailwind CSS 3.4.3 via CDN
- **Database**: MySQL/MariaDB with PDO
- **Architecture**: Traditional LAMP stack, no frameworks

## Security Features

- **Password Hashing**: `password_hash()` with `PASSWORD_DEFAULT`
- **SQL Injection Protection**: PDO prepared statements
- **XSS Protection**: `esc()` helper function for all output
- **CSRF**: TODO - implement CSRF tokens
- **Input Validation**: TODO - implement validation helpers

## Next Steps for Integration

### 1. Database Schema Setup
Create tables for:
- `users` (user_id, email, password_hash, role, first_name, last_name, active)
- `pageants` (pageant_id, pageant_name, description, active, created_at)
- `participants` (participant_id, pageant_id, contestant_number, first_name, last_name, age, active)
- `rounds` (round_id, pageant_id, round_name, description, round_order, status, opened_at, closed_at)
- `criteria` (criteria_id, round_id, criteria_name, weight, criteria_order)
- `scores` (score_id, judge_id, participant_id, criteria_id, round_id, score, created_at, updated_at)
- `awards` (award_id, pageant_id, award_name, description, award_order, winner_participant_id, awarded_at)

### 2. Figma HTML Integration Points

**Ready for Integration** - Each page has placeholder content divs with TODO markers:

```html
<!-- TODO: Replace with Figma-derived HTML -->
<div class="container max-w-6xl mx-auto p-6">
    <!-- Figma content goes here -->
</div>
```

**Preserved Classes**: All existing Tailwind classes are preserved. New Figma HTML should use Tailwind utility classes for consistency.

### 3. Immediate Development Tasks

1. **Database Setup**: Run schema creation scripts
2. **Initial Data**: Create admin user and sample pageant  
3. **Figma Integration**: Replace placeholder content with designed HTML
4. **Testing**: Verify all authentication flows work
5. **API Completion**: Implement remaining API endpoints marked as TODO
6. **Real-time Features**: Add WebSocket/polling for live updates

### 4. Production Deployment

- Disable error reporting in `bootstrap.php`
- Configure proper database credentials via environment variables
- Set up SSL/HTTPS
- Configure web server (Apache/Nginx) with proper PHP handling
- Set up regular database backups

## File Organization

```
/
├── classes/           # Core PHP classes
├── includes/          # Bootstrap and common includes  
├── api/              # JSON API endpoints
├── *.php             # Page scaffolds (login, dashboard, etc.)
├── .gitignore        # Git ignore rules
└── README-RESET.md   # This documentation
```

## Development Guidelines

- **Minimal Changes**: Only modify what's necessary
- **TODO Markers**: All unimplemented features clearly marked
- **Consistent Patterns**: Follow established conventions
- **Security First**: Always escape output, use prepared statements
- **No Framework Bloat**: Keep it simple and maintainable

## Support and Maintenance

This reset provides a clean foundation for ongoing development. Key benefits:

- **Maintainable**: Clear separation of concerns
- **Extensible**: Easy to add new features following established patterns  
- **Secure**: Built-in security practices from the start
- **Fast**: Minimal overhead, direct database access
- **Flexible**: Easy to customize and integrate with existing systems

---

**Version**: 2025.1  
**Reset Date**: [Current Date]  
**Architecture**: CHED-Pages inspired PHP + Tailwind pattern