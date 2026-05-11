from docx import Document

doc = Document('template/CERTIFICATION-COLLEGE.docx')
for i, p in enumerate(doc.paragraphs):
    if '{{' in p.text or 'ISSUED' in p.text or 'this' in p.text:
        print('PARA', i, repr(p.text))
        for j, r in enumerate(p.runs):
            print('  RUN', j, repr(r.text), 'bold=', r.bold, 'italic=', r.italic, 'underline=', r.underline)
        print('--------')
