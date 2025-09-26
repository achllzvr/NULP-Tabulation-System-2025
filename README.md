# NULP Tabulation System 2025

A comprehensive pageant tabulation and scoring system built with pure PHP and Tailwind CSS. This system provides a complete solution for managing pageant competitions, from participant registration to final award announcements.

## 🎯 Project Purpose

The NULP Pageant Tabulation System is designed to streamline the entire pageant competition process, providing:

- **Comprehensive Scoring**: Multi-round scoring with customizable criteria
- **Real-time Leaderboards**: Live updates of participant rankings
- **Role-based Access**: Separate interfaces for administrators and judges
- **Public Display**: Public-facing pages for result announcements
- **Award Management**: Automated and manual award assignment
- **Tie Resolution**: Built-in tools for handling score ties

## 🛠 Tech Stack

- **Backend**: Pure PHP 8+ (no frameworks)
- **Frontend**: Tailwind CSS 3.4.3 (CDN)
- **Database**: MySQL/MariaDB with PDO
- **Session Management**: Native PHP sessions
- **API**: JSON REST endpoints

## 📁 Folder Structure

```
/
├── classes/               # Core PHP classes
│   ├── database.php      # Database connection and utilities
│   ├── auth.php         # Authentication functions
│   ├── pageant.php      # Pageant context management
│   ├── rounds.php       # Round management
│   ├── scores.php       # Scoring functions
│   └── awards.php       # Award management
├── includes/             # Shared includes
│   ├── bootstrap.php    # Session, classes, helpers
│   ├── head.php        # HTML head with Tailwind
│   ├── footer.php      # HTML footer
│   └── nav.php         # Navigation component
├── api/                 # API endpoints
│   └── api.php         # Main API router
├── assets/js/           # JavaScript files (placeholder)
├── components/          # Reusable UI components (placeholder)
├── storage/logs/        # Application logs
├── *.php               # Main application pages
├── public_*.php        # Public-facing pages
├── .env.example        # Environment variables template
└── README.md           # This file
```

## 🚀 Setup Instructions

### 1. Environment Configuration

1. Copy the environment template:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=nulp_tabulation
   DB_USER=your_username
   DB_PASS=your_password
   APP_ENV=development
   APP_DEBUG=true
   ```

### 2. Database Setup

1. Create a MySQL database named `nulp_tabulation`
2. Import the database schema (will be provided separately)
3. Create initial admin user (instructions to follow)

### 3. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. File Permissions

Ensure the `storage/logs/` directory is writable:
```bash
chmod 755 storage/logs/
```

## 📋 Application Pages

### Administrative Pages
- **Dashboard** (`dashboard.php`) - Overview and quick actions
- **Participants** (`participants.php`) - Manage pageant participants
- **Judges** (`judges.php`) - Manage judge panel
- **Rounds** (`rounds.php`) - Control competition rounds
- **Advancement** (`advancement.php`) - Manage round advancement
- **Final Round** (`final_round.php`) - Final competition management
- **Awards** (`awards.php`) - Award assignment and management
- **Tie Resolution** (`tie_resolution.php`) - Handle scoring ties
- **Settings** (`settings.php`) - System configuration

### Judge Pages
- **Scoring** (`scoring.php`) - Judge scoring workspace
- **Leaderboard** (`leaderboard.php`) - Current standings

### Public Pages
- **Preliminary Results** (`public_prelim.php`) - Public preliminary results
- **Final Results** (`public_final.php`) - Public final results
- **Awards** (`public_awards.php`) - Public award announcements

### Authentication
- **Login** (`login.php`) - User authentication
- **Logout** (`logout.php`) - Session termination

## 🔌 API Endpoints

The system provides a RESTful API at `/api/api.php` with the following actions:

- `POST /api/api.php` with `action=login` - User authentication
- `POST /api/api.php` with `action=logout` - Session termination
- `POST /api/api.php` with `action=open_round` - Open scoring round
- `POST /api/api.php` with `action=close_round` - Close scoring round
- `POST /api/api.php` with `action=submit_score` - Submit judge scores
- `GET /api/api.php?action=leaderboard&round_id=X` - Get leaderboard
- `POST /api/api.php` with `action=set_award_result_manual` - Set awards
- `GET /api/api.php?action=health` - System health check

All API responses follow the format:
```json
{
  "success": boolean,
  "data": object|null,
  "error": string|null
}
```

## 👥 User Roles

- **Admin**: Full system access, can manage all aspects
- **Judge**: Can submit scores and view leaderboards
- **Public**: Can view public result pages (no authentication required)

## 🔧 Development Workflow

### Adding New Pages
1. Create PHP file in root directory
2. Start with `require __DIR__ . '/includes/bootstrap.php';`
3. Use `auth_require_login()` for protected pages
4. Set `$pageTitle` and include `head.php`
5. Add page content with Tailwind classes
6. Include `footer.php` at the end

### Extending API
1. Add new case to switch statement in `api/api.php`
2. Implement corresponding function in appropriate class
3. Return JSON response using `json_response()`

### Database Functions
- Use the `database` class for all database operations
- Call `$db->opencon()` to get PDO instance
- Follow existing patterns in class files

## 🔒 Security Considerations

- Environment variables for sensitive configuration
- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input validation and output escaping

## 🐛 Development Notes

- Set `APP_ENV=development` in `.env` for error display
- Check `storage/logs/` for application logs
- Use browser developer tools for API debugging
- All times use Asia/Manila timezone by default

## 📈 Future Enhancements

This baseline provides the foundation for:
- Custom UI themes based on Figma designs
- Real-time updates with WebSockets
- Mobile-responsive judge interfaces
- Export functionality for results
- Automated backup systems
- Integration with external services

## 🤝 Contributing

1. Follow existing code patterns
2. Test all changes thoroughly
3. Update documentation as needed
4. Ensure security best practices

## 📄 License

This project is proprietary software for NULP use.