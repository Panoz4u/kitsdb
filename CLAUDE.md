# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

KitsDB is a PHP-based web application for managing football/soccer kits database. The application uses a MySQL database and implements a dark-themed design system with admin authentication for kit management.

## Development Commands

### Database Setup
- Import database schema: `mysql -u username -p database_name < info/YOUR_DB_NAME_HERE.sql`
- Test database connection: Access `testconn.php` or `testconn2.php` via browser
- Generate admin password hash: Access `gen_hash.php` via browser

### Local Development
This is a traditional PHP application that runs on any PHP server with MySQL support. No build process or package managers are required.
- Start local server: `php -S localhost:8000` (from project root)
- Access application at: `http://localhost:8000/kits_list.php` (main application)
- Admin setup: Access `setup_admin.php` for initial admin user creation

## Database Configuration

The application uses multiple configuration files for different environments:
- `config.php` - Main production configuration with Netsons hosting
- `config_aruba.php` - Alternative hosting configuration (Aruba)

Both files define database connection parameters and provide a `getDb()` function that returns a configured PDO instance.

## Key Application Files

### Authentication & Session Management
- `login.php` - Admin login form with bcrypt password verification using `crypt()`
- `logout.php` - Session cleanup and redirect
- `gen_hash.php` - Utility script for generating bcrypt password hashes

### Main Application Pages  
- `dashboard.php` - Admin dashboard showing kit and photo counts with navigation
- `kits_list.php` - Main kit listing page with search, filters, and preview functionality
- `kit_add.php` - Complex form for adding new kits with team selection, photo upload, and metadata
- `kit_edit.php` - Edit existing kit with pre-populated form fields
- `kit_delete.php` - Delete kit with confirmation
- `nations.php` - Simple display page for nations lookup table

### API Endpoints
- `api/autocomplete.php` - JSON API for team, player, and season autocomplete suggestions
- `api/lookup.php` - JSON API for all lookup table data (brands, categories, conditions, etc.)
- `preview/maglia.php` - Generates dynamic SVG kit previews with colors and numbers

### Utilities
- `testconn.php`/`testconn2.php` - Database connection testing utilities
- `setup_admin.php` - Initial admin user creation script
- `test_upload.php`/`test_simple_upload.php` - File upload testing utilities

## Database Schema

The application uses a comprehensive relational database with the following structure:

### Core Tables
- `kits` - Main kit records with team, season, player, brand, size, condition, colors
- `photos` - Kit photos with classification and titles
- `users` - Admin authentication with bcrypt password hashes

### Lookup Tables
- `teams` - Team data with FMID and nation relationships
- `brands` - Kit manufacturers (Nike, Adidas, etc.)
- `categories` - Kit categories (match, training, etc.)
- `jersey_types` - Type classifications (home, away, third, etc.)
- `seasons` - Season data (2023-24, etc.)
- `sizes` - Size options (XS, S, M, L, XL, XXL)
- `conditions` - Condition ratings with star system
- `colors` - Color definitions with hex codes
- `nations` - Countries with continent relationships
- `continents` - Geographic continent data
- `photo_classifications` - Photo type classifications

The schema is stored in `info/YOUR_DB_NAME_HERE.sql` for database setup and migration.

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

## Authentication Architecture

The application uses a centralized authentication system with the following components:

### Session Management
- `auth.php` - Core authentication functions and session handling
  - `requireAuth()` - Redirects unauthenticated users to login
  - `requireAdmin()` - Enforces admin role requirement
  - `getCurrentUser()` - Returns current user session data
  - `isLoggedIn()` - Boolean check for authentication status

### Authentication Flow
1. All protected pages include `auth.php` and call `requireAuth()`
2. Session data stored: `user_id`, `username`, `role`
3. API endpoints validate authentication before processing requests
4. Login uses `password_verify()` with bcrypt hashed passwords

## Development Guidelines

### Security Considerations
- All user inputs are properly sanitized using `htmlspecialchars()`
- Database queries use prepared statements with parameter binding
- Admin-only pages include session validation checks
- Password hashing uses bcrypt via PHP's `crypt()` function

### File Upload Architecture
The application manages kit photos through a structured upload system:
- Upload directories: `/uploads/front/`, `/uploads/back/`, `/uploads/extra/`
- Temporary staging: `/upload_tmp/` during form processing
- File naming: `[position]_[kit_id].[extension]` (e.g., `front_123.jpg`)
- Supported types: Multiple image formats with validation
- Database integration: Photos table stores file paths and classifications

### Form Processing Patterns
- POST method validation with `$_SERVER['REQUEST_METHOD']`
- Input extraction and sanitization
- Error/success message display
- Database transactions for complex operations

## Common Development Tasks

### Database Operations
- Test connection: Access `testconn.php` or `testconn2.php` via browser
- Import schema: `mysql -u username -p database_name < info/YOUR_DB_NAME_HERE.sql`
- Create admin user: Access `setup_admin.php` or use `gen_hash.php` + manual database insert

### Local Development Server
- Start server: `php -S localhost:8000`
- Main application: `http://localhost:8000/kits_list.php`
- Admin login: `http://localhost:8000/login.php`

### API Testing
- Autocomplete: `api/autocomplete.php?type=teams&q=milan`
- Lookup data: `api/lookup.php?type=brands`
- Kit preview: `preview/maglia.php?id=123`

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