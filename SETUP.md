# KitsDB Setup Instructions

## Initial Setup

### 1. Environment Configuration

After cloning this repository, you need to configure your environment variables:

```bash
# Copy the example environment file
cp .env.example .env
```

Then edit `.env` and fill in your actual credentials:

```env
# Primary Database (Netsons)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password

# Alternative Database (Aruba) - Optional
DB_ARUBA_HOST=your_aruba_host
DB_ARUBA_PORT=3306
DB_ARUBA_NAME=your_aruba_database
DB_ARUBA_USER=your_aruba_user
DB_ARUBA_PASS=your_aruba_password

# Admin Account
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_secure_admin_password
```

### 2. Database Setup

Import the database schema:

```bash
mysql -u your_user -p your_database < info/YOUR_DB_NAME_HERE.sql
```

### 3. Create Admin User

Run the setup script to create the admin user:

```bash
php setup_admin.php
```

This will create an admin user with the credentials specified in your `.env` file.

### 4. Test Database Connection

```bash
php testconn.php
```

### 5. Start Local Development Server

```bash
php -S localhost:8000
```

Access the application at: `http://localhost:8000/kits_list.php`

## Security Notes

- **NEVER commit the `.env` file to git** - It contains sensitive credentials
- The `.env` file is already in `.gitignore` to prevent accidental commits
- Always use `.env.example` as a template and create your own `.env` locally
- Change all default passwords after initial setup

## Updating Credentials

If you need to change credentials:

1. Update them in your `.env` file
2. Update them in your hosting control panel (cPanel/database)
3. If changing admin password, run `php setup_admin.php` again
