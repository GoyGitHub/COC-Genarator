# HRMO Certificate Generator

## Stack
- Frontend: HTML, CSS, JavaScript, Bootstrap
- Backend: PHP (PDO)
- Default database: SQLite (`storage/hrmo_coc.sqlite`)

## Project structure
- `index.php` - form UI
- `save_intern.php` - form submit handler
- `certificate.php` - printable certificate
- `app/config.php` - app and database configuration
- `assets/` - JS/CSS files
- `database/schema.sqlite.sql` - SQLite schema reference
- `database/schema.mysql.sql` - MySQL schema reference
- `storage/` - SQLite database file location

## Run
1. Start Apache in XAMPP.
2. Open `http://localhost/HRMO/index.php`.
3. Submit the form. The SQLite DB is auto-created on first save.

## Optional: use MySQL instead
1. Set `DB_DRIVER` to `mysql` in `app/config.php`.
2. Import `database/schema.mysql.sql` in phpMyAdmin.
3. Update DB credentials in `app/config.php`.
