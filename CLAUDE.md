# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

KitsDB is a PHP-based web application for managing football/soccer kits database. The application uses a MySQL database and implements a dark-themed design system with admin authentication for kit management.

## Database Configuration

The application uses two configuration files for different environments:
- `config.php` - Main production configuration with Netsons hosting
- `config_netsons.php` - Alternative hosting configuration (testing/development)

Both files define database connection parameters and provide a `getDb()` function that returns a configured PDO instance.

## Key Application Files

### Authentication & Session Management
- `login.php` - Admin login form with bcrypt password verification using `crypt()`
- `logout.php` - Session cleanup and redirect
- `gen_hash.php` - Utility script for generating bcrypt password hashes

### Main Application Pages  
- `dashboard.php` - Admin dashboard showing kit and photo counts with navigation
- `kit_add.php` - Complex form for adding new kits with team selection, photo upload, and metadata
- `nations.php` - Simple display page for nations lookup table

### Utilities
- `testconn.php` - Database connection testing utility

## Database Schema

The application works with several related tables:
- `kits` - Main kit records with team, season, player, brand, size, condition
- `photos` - Kit photos with classification and titles  
- `teams`, `brands`, `seasons`, `sizes`, `conditions`, `colors` - Lookup tables
- `users` - Admin authentication with bcrypt password hashes
- `nations` - Geographic lookup data

## Design System & Styling

The application implements a comprehensive dark-themed design system defined in `css/styles.css`:

### Color Palette
- `--background: #210B2C` - Dark purple page background
- `--surface: #2A1A38` - Card and form backgrounds  
- `--action-red: #DE3C4B` - Primary buttons and accents
- `--highlight-yellow: #DCF763` - Focus states and highlights

### Typography  
- Display font: 'Barlow Condensed' for headings
- Body font: 'Montserrat' for content and forms
- Comprehensive spacing system with CSS custom properties

### Components
- Responsive grid system with `.row`/`.col` classes
- Card-based layouts with consistent shadows
- Form styling with focus indicators
- Button variants (primary/secondary) with hover animations

## Development Guidelines

### Security Considerations
- All user inputs are properly sanitized using `htmlspecialchars()`
- Database queries use prepared statements with parameter binding
- Admin-only pages include session validation checks
- Password hashing uses bcrypt via PHP's `crypt()` function

### File Upload Handling
The `kit_add.php` page includes photo upload functionality with:
- Multiple file support
- Filename sanitization with timestamp prefixes
- Database storage of file metadata and classifications

### Form Processing Patterns
- POST method validation with `$_SERVER['REQUEST_METHOD']`
- Input extraction and sanitization
- Error/success message display
- Database transactions for complex operations

## Common Development Tasks

### Testing Database Connection
Run `testconn.php` to verify database connectivity and configuration.

### Adding New Admin Users
Use `gen_hash.php` to generate password hashes, then manually insert into the `users` table with appropriate role assignment.

### Design System Extensions
Reference the detailed design specifications in `design.txt` and `design2.txt` for consistent styling additions.

## Architecture Notes

This is a traditional PHP application using:
- Native PHP sessions for authentication
- PDO for database abstraction  
- Server-side form processing and validation
- CSS Grid and Flexbox for responsive layouts
- No JavaScript framework dependencies

The codebase follows a simple file-per-page structure without MVC framework patterns, making it straightforward to modify individual features.