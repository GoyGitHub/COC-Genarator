# HRMO Certificate System - Quick Start Guide

## System Overview

Your certificate generation system now has a clean, organized architecture:

```
HRMO/
├── python/                           # New Python certificate system
│   ├── __init__.py                  # Package initialization
│   ├── config.py                    # All configuration settings
│   ├── db_handler.py                # Database operations
│   ├── certificate_generator.py     # Core generation logic
│   ├── api_handler.py               # JSON API interface
│   ├── main.py                      # Command-line tool
│   ├── system_check.py              # Setup verification
│   ├── CertificateGeneratorPHP.php  # PHP integration wrapper
│   ├── requirements.txt             # Python dependencies
│   ├── setup.bat                    # Windows setup script
│   ├── setup.sh                     # Linux/Mac setup script
│   └── README.md                    # Full documentation
│
├── Certificates/                    # Output folder (auto-created)
│   └── *.pdf, *.docx               # Generated certificates
│
├── template/                        # Certificate templates
│   └── CERTIFICATION-COLLEGE.docx  # Your docx template
│
└── [Other existing files...]
```

## Quick Start (3 Steps)

### Step 1: Setup (One Time Only)

**Windows:**
```bash
cd python
setup.bat
```

**Linux/Mac:**
```bash
cd python
chmod +x setup.sh
./setup.sh
```

### Step 2: Configure Database

Your SQLite database should be at `database/hrmo.db`. If you need to create it from the schema:

```bash
# Windows (from python folder)
sqlite3 ..\database\hrmo.db < ..\database\schema.sqlite.sql

# Linux/Mac
sqlite3 ../database/hrmo.db < ../database/schema.sqlite.sql
```

Or copy your existing SQLite database file to `database/hrmo.db` and the system will use it automatically.

### Step 3: Generate Certificates

**Command Line (after setup):**
```bash
# Single certificate
python main.py --id 1

# All interns
python main.py --all

# All college students
python main.py --level college
```

**From PHP Code:**
```php
require_once 'python/CertificateGeneratorPHP.php';

$gen = new CertificateGenerator();
$result = $gen->generateCertificate(1, 'college');

if ($result['success']) {
    header('Location: ' . $result['download_url']);
}
```

## Template Setup

Your `template/CERTIFICATION-COLLEGE.docx` should contain these placeholders:

- `{{FULL_NAME}}` - Replaced with intern's full name
- `{{COURSE}}` - Course/program
- `{{SCHOOL}}` - School name
- `{{HOURS_RENDERED}}` - Number of hours
- `{{DEPARTMENT}}` - Department name
- `{{START_DATE}}` - Start date (formatted)
- `{{END_DATE}}` - End date (formatted)
- `{{ISSUED_DATE}}` - Current date
- `{{PRONOUN_HER_HIS}}` - Her/His based on gender

### To Create/Edit Template:

1. Open `template/CERTIFICATION-COLLEGE.docx` in Microsoft Word
2. Add your organization's letterhead, logo, margins, fonts
3. Insert the placeholders where needed
4. Make sure document looks professional
5. Save as `.docx`
6. System will auto-fill with intern data

## Output Files

Generated certificates are saved to `Certificates/` folder:
- **Format:** `COC-{Name}-{YYYYMMDD}.pdf` and `.docx`
- **Examples:**
  - `COC-Juan-Dela-Cruz-20240315.pdf`
  - `COC-Maria-Santos-20240315.pdf`
- **Both formats** are generated (docx + pdf)
- **Easy download** - serve from web with link

## Integration with Existing PHP System

To add certificate generation to your PHP pages:

```php
<?php
require_once 'python/CertificateGeneratorPHP.php';

// Get intern data from your PHP
$internId = $_GET['id'] ?? 0;

if ($internId > 0) {
    $generator = new CertificateGenerator();
    $result = $generator->generateCertificate($internId, 'college');
    
    if ($result['success']) {
        echo "<a href='{$result['download_url']}'>Download Certificate</a>";
    } else {
        echo "Error: " . $result['message'];
    }
}
?>
```

## Architecture Benefits

✓ **Separation of Concerns**: Certificate logic isolated in Python
✓ **Reusable**: Call from CLI, PHP, or APIs
✓ **Maintainable**: Organized into focused modules
✓ **Testable**: Each component can be tested independently
✓ **Scalable**: Easily add new certificate types
✓ **Type-Safe**: Python type hints and docstrings
✓ **Documented**: Comprehensive README and docstrings
✓ **Error Handling**: Graceful error messages and fallbacks

## Common Tasks

### Generate Single Certificate from CLI
```bash
python main.py --id 1
```

### Generate All Certificates Batch
```bash
python main.py --all
```

### Check System Status
```bash
python system_check.py
```

### List Generated Certificates (Python)
```bash
python api_handler.py list
```

### Clear All Certificates (Python)
```bash
python api_handler.py clear
```

## Troubleshooting

### "Python not found"
- Make sure Python is installed: `python --version`
- Add Python to PATH or use full path: `C:\Python312\python.exe main.py`

### "Module not found: docx"
- Run: `pip install -r requirements.txt`
- Ensure you're in the `python/` directory

### "Database File Not Found"
- Make sure your SQLite database is at `database/hrmo.db`
- Create it from schema: `sqlite3 database/hrmo.db < database/schema.sqlite.sql`
- Or copy your existing `.db` file to the `database/` folder

### "Template not found"
- Check `template/CERTIFICATION-COLLEGE.docx` exists
- Verify exact filename matches config

### "PDF conversion failed"
- Install LibreOffice (fallback generates .docx instead)
- Windows: Download from https://www.libreoffice.org
- Linux: `sudo apt-get install libreoffice`
- macOS: `brew install libreoffice`

## File Structure Explanation

| File | Purpose |
|------|---------|
| `config.py` | Central configuration (credentials, paths, templates) |
| `db_handler.py` | Database queries (reusable singleton) |
| `certificate_generator.py` | Template filling and PDF generation |
| `api_handler.py` | JSON API for external calls |
| `main.py` | User-friendly CLI tool |
| `system_check.py` | Verification of setup |
| `CertificateGeneratorPHP.php` | PHP wrapper for web integration |

## Next Steps

1. ✅ **Setup** - Run `setup.bat` or `setup.sh`
2. ✅ **Configure** - Update credentials in `config.py`
3. ✅ **Prepare Template** - Edit `CERTIFICATION-COLLEGE.docx`
4. ✅ **Verify** - Run `python system_check.py`
5. ✅ **Generate** - Run `python main.py --id 1`
6. ✅ **Integrate** - Add PHP wrapper to your web pages
7. ✅ **Deploy** - Copy to production server

## Additional Help

- Full documentation: See `python/README.md`
- Each module has docstrings: Read the source code
- Test on development first before deploying

---

**Ready to use!** Your system is now organized and ready for production.
