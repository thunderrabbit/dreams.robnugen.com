# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a minimalist PHP web application framework designed for DreamHost deployment. It's a custom template-based site with admin dashboard functionality, database migration system, and user authentication using cookies stored in the database.

**Primary Purpose**: This application is designed for AI-powered analysis of dreams over time to identify patterns, symbols, and recurring themes from decades of dream journal entries.

## Key Architecture

### Core Components

- **Template System**: `classes/Template.php` - Custom templating engine with layout nesting via `grabTheGoods()` method
- **Database Layer**: `classes/Database/` - PDO-based database abstraction with migration system
- **Authentication**: `classes/Auth/` - Cookie-based login system with IP tracking
- **Configuration**: Must create `classes/Config.php` from `classes/ConfigSample.php` with actual database credentials
- **Bootstrap**: `prepend.php` - Application initialization, autoloader, and database checks

### Database Migration System

- Migrations stored in `db_schemas/` with numbered prefixes (00_, 01_, 02_, etc.)
- `DBExistaroo` class handles automatic schema application and tracking
- Applied migrations tracked in `applied_DB_versions` table
- Manual rollbacks via PHPMyAdmin (no automated rollback system)

### Project Structure

- `wwwroot/` - Public web directory (DreamHost web root points here)
- `templates/` - Template files (.tpl.php) organized by feature
- `classes/` - PHP classes with PSR-4-style autoloading via `Mlaphp\Autoloader`
- `db_schemas/` - Database migration files

## Development Workflow

### Deployment

- Uses `scp_files_to_dh.sh` for automatic file watching and deployment to DreamHost
- Script monitors file changes and syncs to remote server via SSH/SCP
- Target configured for user "barefoot_rob" on "drc" host

### Initial Setup

1. Copy `classes/ConfigSample.php` to `classes/Config.php` and configure database credentials
2. First visit to site triggers automatic schema creation and admin user setup
3. Database must exist before application runs (checked by `DBExistaroo`)

### Authentication Flow

- Session-based with database-stored cookies
- First-time setup redirects to admin user creation unless visiting `/login/register_admin.php`
- IP address tracking via `Auth\IPBin` class
- Login state managed by `Auth\IsLoggedIn` class

## Important Files

- `prepend.php` - Main application bootstrap (included by all pages)
- `wwwroot/index.php` - Site entry point with hardcoded DreamHost path
- `classes/Template.php` - Core templating functionality
- `classes/Database/DBExistaroo.php` - Database existence/migration manager
- `classes/Database/Base.php` - PDO connection and utility methods

## Development Notes

- No package manager (composer/npm) - pure PHP with custom autoloader
- Debug mode available via `?debug=1` URL parameter
- Uses `print_rob()` function for debugging output
- Error display enabled in `prepend.php` for development
- Templates use `.tpl.php` extension and PHP template syntax

### Including prepend.php

All PHP files (except templates and prepend.php itself) must include prepend.php. Use this consistent pattern regardless of directory depth:

```php
# Extract DreamHost project root: /home/username/domain.com
preg_match('#^(/home/[^/]+/[^/]+)#', __DIR__, $matches);
include_once $matches[1] . '/prepend.php';
```

This leverages DreamHost's consistent `/home/username/domain.com/` path structure to dynamically find the project root.

## Database Schema Management

- Automatic application of schemas with prefixes "00" and "01"
- Manual migration application via admin interface (`/admin/migrate_tables.php`)
- Schema files must follow `create_*.sql` naming convention
- Each schema directory represents a version (e.g., `00_bedrock/`, `01_gumdrop_cloud/`)

### CRITICAL DATABASE RULES

**ALWAYS CHECK TABLE SCHEMAS BEFORE WRITING CODE:**
1. **Never assume column names** - Check existing table definitions in `db_schemas/` files
2. **Use descriptive primary keys** - Use `table_name_id` format (e.g., `dream_id`, `user_id`, `kap_id`)
3. **Verify column names** - Read the actual CREATE TABLE statements, don't guess
4. **Check existing data** - Look at how other code references the same tables

**Example: Before writing queries for `dreams` table:**
- ✅ Check `db_schemas/02_dreams/create_dreams.sql` to see it uses `dream_id` not `id`
- ✅ Use `SELECT dream_id, content_clean FROM dreams WHERE dream_id > ?`
- ❌ Never assume `SELECT id, content FROM dreams WHERE id > ?`

**Primary Key Naming Convention:**
- `dreams` table → `dream_id` (not `id`)
- `users` table → `user_id` (not `id`)
- `keyword_analysis_pointer` table → `kap_id` (not `id`)
- This makes joins and debugging much clearer

## Source Data: Dream Journal Entries

The dream analysis functionality draws from a rich source of historical data:

- **Source Location**: Dream entries are stored in `/home/thunderrabbit/work/rob/robnugen.com/journal/journal/`
- **File Pattern**: Dreams are identified by filenames containing `*dream*.html` or `*dream*.md`
- **Date Range**: Entries span from 1985 to 2025, providing decades of dream data
- **Structure**: Files are organized chronologically in year/month directory structure
- **Content**: Each file contains timestamped dream narratives with personal observations

**Related Systems**:
- The `robnugen.com/journal/` directory contains Perl scripts that generate static HTML indexes
- These scripts parse all entries from `journal/journal/` to create the public site at https://robnugen.com/journal/
- The `quick.robnugen.com` directory provides a web interface for quickly adding or editing existing markdown files into `journal/journal/YYYY/MM/` directories
- Dreams are part of a larger personal journaling system spanning multiple decades
