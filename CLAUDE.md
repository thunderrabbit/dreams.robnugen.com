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

## Dream Analysis System

This application provides a comprehensive dream analysis platform with import, keyword analysis, and writing capabilities.

### Dream Data Sources

- **Primary Location**: Dream entries stored in `/home/barefoot_rob/robnugen.com/journal/journal/`
- **File Pattern**: Dreams identified by filenames containing `*dream*.html` or `*dream*.md`
- **Date Range**: Entries span from 1985 to 2025, providing 40+ years of dream data
- **Structure**: Files organized in `YYYY/MM/DDtitle-slug.md` format with Hugo frontmatter
- **Content**: Each file contains timestamped dream narratives with Hugo-style YAML metadata

### Dream Import System

**Classes**: `DreamScanner`, `DreamImporter`
- **Batch Processing**: Imports dreams in configurable batches (default 50)
- **Pointer System**: Tracks last processed file to enable resumable imports
- **Error Handling**: Failed files tracked separately and skipped on subsequent runs
- **Encoding Support**: Handles legacy encodings (UTF-8, Windows-1252, EUC-JP, etc.)
- **Directory Structure**: Only processes files in `YYYY/MM/` directories
- **Filtering**: Excludes files with "castle" to avoid false positives

### Dream Writing Interface

**Location**: Main page (`/index.php`) when logged in
**Class**: `QuickPoster` (copied from Quick website)
- **Dark Theme UI**: Fully integrated with site's dark theme
- **Form Fields**: Time, date, title, tags, content textarea
- **Paragraph Wrapping**: Select text and click buttons to wrap with semantic HTML:
  - 💭 `<p class="dream">` - Standard dream content
  - 👁️ `<p class="lucid">` - Lucid dream segments  
  - 😱 `<p class="nightmare">` - Nightmare content
- **Toggle Tags**: Quick tag buttons that add/remove tags (lucid, nightmare, recurring, etc.)
- **File Creation**: Saves directly to journal filesystem with proper Hugo frontmatter
- **Auto-Import Ready**: Files automatically detected by import system

### Keyword Analysis System

**Classes**: `DreamKeywordAnalyzer`
- **Smart Text Processing**: Preserves contractions, removes stop words and HTML entities
- **Incremental Analysis**: Pointer-based system to analyze only new dreams
- **Frequency Tracking**: Counts keyword occurrences across all dreams with date ranges
- **Visual Dashboard**: 2D grid heat maps showing temporal patterns
- **Search Functionality**: Pattern-based keyword search
- **Database Storage**: Results stored in `dream_keywords` table

### Configuration Management

**Critical Path Configuration**: All hardcoded paths moved to Config to avoid duplication
- `post_path_journal`: Journal directory path (shared by QuickPoster and DreamScanner)
- `dreams_import_pointer_file`: Import progress tracking
- `dreams_failed_files`: Failed file tracking
- **Validation**: DreamScanner constructor validates all required config properties

### Navigation System

**Dropdown Menus**: CSS-based dropdown navigation with dark theme styling
- **Dreams Import ▾**: Dream import system, Keyword analysis
- **Profile ▾**: Profile settings, Logout
- **Menu CSS**: Separate `menu.css` file for reusable dropdown components

### Related Systems

- **Quick Website** (`quick.robnugen.com`): Full-featured journal writing with git integration
- **Base Template** (`new-DH-user-site`): Framework foundation for creating new sites
- **Public Journal** (`robnugen.com/journal/`): Static site generated from journal files
- **Integration**: Dreams site writes files, Quick site handles git operations
