# SQLite Migration Guide

Your certificate generation system has been updated to use **SQLite** instead of MySQL.

## What Changed?

### Removed Dependencies
- ❌ `mysql-connector-python` - No longer needed
- ✅ SQLite is built-in to Python (no extra install needed)

### Updated Files
- `config.py` - Now uses SQLite file path instead of MySQL credentials
- `db_handler.py` - Uses `sqlite3` module instead of `mysql.connector`
- `requirements.txt` - Removed MySQL dependency
- `setup.bat` / `setup.sh` - Now initializes SQLite database
- `db_setup.py` - NEW: Database initialization helper

## How to Use

### Quick Setup

```bash
cd python
setup.bat                    # Windows
# or: chmod +x setup.sh && ./setup.sh   # Linux/Mac
```

This will:
1. Install Python dependencies
2. Initialize SQLite database from schema
3. Run system verification

### Manual Database Setup

If you need to manually initialize the database:

```bash
# Windows
sqlite3 ..\database\hrmo.db < ..\database\schema.sqlite.sql

# Linux/Mac
sqlite3 ../database/hrmo.db < ../database/schema.sqlite.sql

# Or use the helper script
python db_setup.py init
```

### Verify Database

```bash
python db_setup.py verify
```

## File Locations

- **Database file**: `database/hrmo.db`
- **Schema file**: `database/schema.sqlite.sql`
- **Output folder**: `Certificates/` (for generated PDFs)

## Benefits of SQLite

✅ **No setup** - Database is just a file  
✅ **No server** - No MySQL daemon needed  
✅ **No credentials** - No username/password management  
✅ **Portable** - Easy to backup/move the `hrmo.db` file  
✅ **Fast** - Suitable for applications like this  
✅ **Lightweight** - Minimal resource usage  

## If You Had MySQL Before

To migrate existing data from MySQL to SQLite:

1. **Export data from MySQL:**
   ```bash
   mysqldump -u root -p HRMO_COC interns > interns_backup.sql
   ```

2. **Import to SQLite:**
   ```bash
   # First create the schema
   sqlite3 database/hrmo.db < database/schema.sqlite.sql
   
   # Then convert and import the data (use a tool or manual SQL)
   ```

Or simply ensure your SQLite database file is placed at `database/hrmo.db` with the correct schema.

## Troubleshooting

### "Database file not found"
```bash
python db_setup.py init
```

### "Table doesn't exist"
```bash
python db_setup.py verify
```

### Check database contents
```bash
sqlite3 database/hrmo.db
sqlite> SELECT * FROM interns;
sqlite> .quit
```

## No Code Changes Needed

If you were calling the certificate generator from PHP or Python before, everything still works the same way!

```php
<?php
// This code works exactly the same
require_once 'python/CertificateGeneratorPHP.php';
$generator = new CertificateGenerator();
$result = $generator->generateCertificate(1, 'college');
?>
```

---

**Migration complete!** Your system now uses SQLite for simpler deployment and maintenance.
