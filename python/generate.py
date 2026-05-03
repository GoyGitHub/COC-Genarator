#!/usr/bin/env python3
"""
Simple Certificate Generator - Populates DOCX template and saves to Certificates folder
"""
import sys
import sqlite3
import shutil
from pathlib import Path
from datetime import datetime
from docx import Document

# Configuration
BASE_DIR = Path(__file__).parent.parent
DB_PATH = BASE_DIR / 'storage' / 'hrmo_coc.sqlite'  # SAME DATABASE AS WEB FORM
TEMPLATE_PATH = BASE_DIR / 'template' / 'CERTIFICATION-COLLEGE.docx'
CERTIFICATES_DIR = BASE_DIR / 'Certificates'
OUTPUT_DIR = CERTIFICATES_DIR

# Ensure Certificates folder exists
CERTIFICATES_DIR.mkdir(exist_ok=True)


def format_date_long(date_str: str) -> str:
    """Convert Y-m-d to Month Day, Year"""
    try:
        dt = datetime.strptime(date_str, '%Y-%m-%d')
        return dt.strftime('%B %d, %Y')
    except:
        return date_str


def get_pronoun(gender: str, female_word: str, male_word: str) -> str:
    """Return pronoun based on gender"""
    return female_word if gender.lower() == 'female' else male_word


def get_intern_data(intern_id: int) -> dict:
    """Get intern data from database"""
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    
    cursor.execute('SELECT * FROM interns WHERE id = ?', (intern_id,))
    row = cursor.fetchone()
    conn.close()
    
    if not row:
        raise Exception(f'Intern ID {intern_id} not found')
    
    return dict(row)


def replace_text_in_paragraph(paragraph, replacements):
    """Replace text in paragraph, handling split runs"""
    # Get all text from runs
    full_text = ''.join([run.text for run in paragraph.runs])
    
    # Check if any replacement is needed
    if not any(placeholder in full_text for placeholder in replacements.keys()):
        return
    
    # Apply replacements to full text
    for placeholder, value in replacements.items():
        full_text = full_text.replace(placeholder, value)
    
    # Clear all runs
    for run in paragraph.runs:
        run.text = ''
    
    # Add text as single run
    if paragraph.runs:
        paragraph.runs[0].text = full_text
    else:
        paragraph.add_run(full_text)


def generate_certificate(intern_id: int) -> str:
    """Generate certificate DOCX file"""
    
    # Get intern data
    intern = get_intern_data(intern_id)
    
    # Build course text
    course_text = ''
    if intern.get('course'):
        course_text = intern['course'] + ', '
    
    # Build replacements dict
    replacements = {
        '{{FULL_NAME}}': intern['full_name'].upper(),
        '{{COURSE}}': intern['course'] or '',
        '{{SCHOOL}}': intern['school'].upper(),
        '{{HOURS_RENDERED}}': str(intern['hours_rendered']),
        '{{DEPARTMENT}}': intern['department'],
        '{{START_DATE}}': format_date_long(intern['start_date']),
        '{{END_DATE}}': format_date_long(intern['end_date']),
        '{{ISSUED_DATE}}': datetime.now().strftime('%B %d, %Y'),
        '{{PRONOUN_HER_HIS}}': get_pronoun(intern['gender'], 'her', 'his'),
    }
    
    # Copy template to preserve original
    name_cleaned = intern['full_name'].replace(' ', '-')
    date_str = datetime.now().strftime('%Y%m%d')
    output_filename = f"COC-{name_cleaned}-{date_str}.docx"
    output_path = OUTPUT_DIR / output_filename
    
    # Copy template
    shutil.copy2(TEMPLATE_PATH, output_path)
    
    # Load and modify
    doc = Document(output_path)
    
    # Replace in all paragraphs
    for paragraph in doc.paragraphs:
        replace_text_in_paragraph(paragraph, replacements)
    
    # Replace in tables
    for table in doc.tables:
        for row in table.rows:
            for cell in row.cells:
                for paragraph in cell.paragraphs:
                    replace_text_in_paragraph(paragraph, replacements)
    
    # Save
    doc.save(output_path)
    
    return str(output_filename)


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print('Usage: python generate.py <intern_id>')
        sys.exit(1)
    
    try:
        intern_id = int(sys.argv[1])
        filename = generate_certificate(intern_id)
        print(f'✓ Certificate generated: {filename}')
        sys.exit(0)
    except Exception as e:
        print(f'Error: {str(e)}', file=sys.stderr)
        sys.exit(1)
