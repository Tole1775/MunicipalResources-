#!/usr/bin/env python3
"""
MunicipalResources Secure Word Processor & Document Manager Core (`municipal_doc_manager.py`)
Provides backend text compilation, multi-language spell checking across Western languages,
cryptographic checksum generation, and native .docx / .pdf export support.
"""

import os
import sys
import json
import hashlib
import argparse
from datetime import datetime

# Optional dependency imports with graceful fallbacks for TrustShell environment
try:
    from docx import Document
    DOCX_AVAILABLE = True
except ImportError:
    DOCX_AVAILABLE = False

try:
    from reportlab.lib.pagesizes import letter
    from reportlab.pdfgen import canvas
    PDF_AVAILABLE = True
except ImportError:
    PDF_AVAILABLE = False

try:
    from spellchecker import SpellChecker
    SPELL_AVAILABLE = True
except ImportError:
    SPELL_AVAILABLE = False


class MunicipalDocumentManager:
    def __init__(self, output_dir="output_documents"):
        self.output_dir = output_dir
        os.makedirs(self.output_dir, exist_ok=True)

    def check_spelling(self, text, language='en'):
        """Performs multi-language spell checking across Western languages (en, es, fr, de, pt, it)."""
        supported_langs = ['en', 'es', 'fr', 'de', 'pt', 'it']
        if language not in supported_langs:
            language = 'en'

        if not SPELL_AVAILABLE:
            return {"status": "warning", "message": "pyspellchecker module not installed. Returning raw text.", "misspelled": []}

        spell = SpellChecker(language=language)
        words = text.split()
        misspelled = spell.unknown(words)
        corrections = {word: spell.correction(word) for word in misspelled}

        return {
            "status": "success",
            "language": language,
            "word_count": len(words),
            "misspelled_count": len(misspelled),
            "corrections": corrections
        }

    def generate_cryptographic_checksum(self, content):
        """Generates a secure SHA-256 cryptographic checksum for document integrity."""
        return hashlib.sha256(content.encode('utf-8')).hexdigest()

    def export_to_txt(self, title, content):
        filename = f"{title.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
        filepath = os.path.join(self.output_dir, filename)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        return filepath

    def export_to_docx(self, title, content):
        if not DOCX_AVAILABLE:
            raise RuntimeError("python-docx is not installed in the TrustShell runtime environment.")
        
        filename = f"{title.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.docx"
        filepath = os.path.join(self.output_dir, filename)
        
        doc = Document()
        doc.add_heading(title, level=1)
        for paragraph in content.split('\n'):
            if paragraph.strip():
                doc.add_paragraph(paragraph)
        doc.save(filepath)
        return filepath

    def export_to_pdf(self, title, content):
        if not PDF_AVAILABLE:
            raise RuntimeError("reportlab is not installed in the TrustShell runtime environment.")

        filename = f"{title.replace(' ', '_')}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
        filepath = os.path.join(self.output_dir, filename)

        c = canvas.Canvas(filepath, pagesize=letter)
        width, height = letter
        
        c.setFont("Helvetica-Bold", 16)
        c.drawString(54, height - 54, title)
        
        c.setFont("Helvetica", 11)
        text_object = c.beginText(54, height - 90)
        text_object.setLeading(15)

        for line in content.split('\n'):
            if text_object.getY() < 54:
                c.drawText(text_object)
                c.showPage()
                text_object = c.beginText(54, height - 54)
                text_object.setFont("Helvetica", 11)
                text_object.setLeading(15)
            text_object.textLine(line)

        c.drawText(text_object)
        c.save()
        return filepath

    def process_document(self, title, content, language='en', format_type='txt'):
        """Main processing pipeline for word processor compilation, spell check, and export."""
        spell_results = self.check_spelling(content, language)
        checksum = self.generate_cryptographic_checksum(content)

        format_type = format_type.lower()
        if format_type == 'docx':
            filepath = self.export_to_docx(title, content)
        elif format_type == 'pdf':
            filepath = self.export_to_pdf(title, content)
        else:
            filepath = self.export_to_txt(title, content)

        return {
            "status": "success",
            "title": title,
            "format": format_type,
            "filepath": filepath,
            "sha256_checksum": checksum,
            "spell_check": spell_results
        }


def main():
    parser = argparse.ArgumentParser(description="MunicipalResources Word Processor & Document Manager")
    parser.add_argument("--title", required=True, help="Document title")
    parser.add_argument("--content-file", required=True, help="Path to input text file")
    parser.add_argument("--lang", default="en", help="Language code (en, es, fr, de, pt, it)")
    parser.add_argument("--format", default="txt", choices=["txt", "docx", "pdf"], help="Export format")
    
    args = parser.parse_args()

    if not os.path.exists(args.content_file):
        print(json.dumps({"status": "error", "message": f"Content file not found: {args.content_file}"}))
        sys.exit(1)

    with open(args.content_file, 'r', encoding='utf-8') as f:
        content = f.read()

    manager = MunicipalDocumentManager()
    result = manager.process_document(args.title, content, args.lang, args.format)
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
