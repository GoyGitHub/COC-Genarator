# PHP Form Integration Guide

## How It Works

When a user submits the intern form in `index.php`:

1. Data is saved to SQLite database in `save_intern.php`
2. After successful save, Python certificate generator is called **automatically**
3. Certificate is generated with the form data
4. Page redirects to certificate viewer

## File Structure

```
├── index.php                           # Form page
├── save_intern.php                     # Form handler (MODIFIED)
├── certificate.php                     # Certificate viewer
├── python/
│   ├── main.py                        # CLI tool
│   ├── api_handler.py                 # API interface
│   ├── certificate_generator.py       # Core generator (FIXED)
│   ├── config.py                      # Configuration
│   └── db_handler.py                  # Database handler
└── Certificates/                      # Output folder
```

## What Changed

### 1. **Template Fixed** ✓
- File: `template/CERTIFICATION-COLLEGE.docx`
- Now contains placeholders: `{{FULL_NAME}}`, `{{SCHOOL}}`, etc.
- Automatically replaced with form data

### 2. **Placeholder Replacement Fixed** ✓  
- File: `python/certificate_generator.py`
- Method: `_replace_text_in_paragraph()`
- Handles text split across multiple document runs

### 3. **PHP Integration Added** ✓
- File: `save_intern.php` (modified)
- Automatically calls Python generator after form save
- Functions:
  - `generateCertificateAsync()` - Calls Python in background
  - `getPythonExecutable()` - Finds Python on system

## Testing the Integration

### Option 1: Use Web Form
1. Go to http://localhost/HRMO/
2. Fill in the form:
   - Select: College
   - Name: Jane Smith
   - Gender: Female
   - School: University of the Philippines
   - Course: Bachelor of Science in Business Administration
   - Hours: 160
   - Department: Human Resource Management Office
   - Start: 2024-01-15
   - End: 2024-04-15
3. Click Submit
4. Certificate is generated automatically
5. Redirected to certificate.php to view

### Option 2: Use Python CLI
```bash
cd python
python main.py --id <intern_id>
```

## Certificate Output

Generated files are saved to: `Certificates/`

File format: `COC-{FULL_NAME}-{YYYYMMDD}.docx`

Examples:
- `COC-Jane-Smith-20260430.docx`
- `COC-John-Reyes-20260430.docx`

## Customizing the Template

To edit the certificate design:

1. Open `template/CERTIFICATION-COLLEGE.docx` in Microsoft Word
2. Add your organization's letterhead, logo, colors
3. **Keep the placeholders** in the document:
   - `{{FULL_NAME}}`
   - `{{SCHOOL}}`
   - `{{COURSE}}`
   - `{{DEPARTMENT}}`
   - `{{HOURS_RENDERED}}`
   - `{{START_DATE}}`
   - `{{END_DATE}}`
   - `{{ISSUED_DATE}}`
   - `{{PRONOUN_HER_HIS}}`
4. Save as `.docx`
5. System will auto-replace placeholders

## Troubleshooting

### Certificate not generating
1. Check `php_errors.log` for errors
2. Verify Python is installed: `python --version`
3. Check database: `python db_setup.py verify`
4. Test manually: `python main.py --id 1`

### Wrong data in certificate  
- Ensure form data is being saved correctly
- Check database: `sqlite3 database/hrmo.db` → `SELECT * FROM interns;`
- Check template placeholders are exact: `{{FULL_NAME}}` (not `{{Full_Name}}`)

### PDF not generating
- PDF conversion requires LibreOffice
- Download from: https://www.libreoffice.org
- Or use .docx files (also generated)

## Advanced: Adding More Certificate Types

To create a "SHS Certificate" template:

1. Create `template/CERTIFICATION-SHS.docx` with same placeholders
2. Update `python/config.py`:
   ```python
   TEMPLATES = {
       'college': TEMPLATE_DIR / 'CERTIFICATION-COLLEGE.docx',
       'shs': TEMPLATE_DIR / 'CERTIFICATION-SHS.docx',
   }
   ```
3. System automatically uses correct template based on `intern_level`

## API Endpoint

If you want to call certificate generation from other pages:

```php
<?php
require_once 'generate_certificate_api.php';

// Get certificate info
$result = generateCertificateFromDB(1, 'college');

if ($result['success']) {
    echo "Certificate: " . $result['file'];
}
?>
```

## Next Steps

1. ✅ Test the form submission
2. ✅ Verify certificates are generated in `Certificates/` folder
3. ✅ Customize the template with your branding
4. ✅ Deploy to production

---

**All set!** Your form now automatically generates certificates. 🎉
