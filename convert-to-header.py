#!/usr/bin/env python3
"""
Convert MediaLog pages to use unified header
"""

import re
import sys

def convert_page(filepath, page_title):
    """Convert a page to use unified header"""
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find the DOCTYPE position
    doctype_match = re.search(r'<!DOCTYPE html>', content, re.IGNORECASE)
    if not doctype_match:
        print(f"❌ No DOCTYPE found in {filepath}")
        return False
    
    doctype_pos = doctype_match.start()
    
    # Find the closing ?> before DOCTYPE
    php_close = content.rfind('?>', 0, doctype_pos)
    if php_close == -1:
        print(f"❌ No ?> found before DOCTYPE in {filepath}")
        return False
    
    # Find the <body> tag
    body_match = re.search(r'<body[^>]*>', content)
    if not body_match:
        print(f"❌ No <body> found in {filepath}")
        return False
    
    body_end = body_match.end()
    
    # Find the navigation section
    nav_start = content.find('<nav class="top-nav">', body_end)
    if nav_start == -1:
        nav_start = content.find('<nav', body_end)
    
    if nav_start != -1:
        # Find the end of nav
        nav_end = content.find('</nav>', nav_start) + 6
        nav_content_start = body_end
        nav_content_end = nav_end
    else:
        # No nav found, content starts right after body
        nav_content_start = body_end
        nav_content_end = body_end
    
    # Build new content
    before_php = content[:php_close + 2]
    after_nav = content[nav_content_end:]
    
    # Create header include
    header_include = f'''
<?php
$pageTitle = "{page_title}";
include 'includes/header.php';
?>
'''
    
    new_content = before_php + header_include + after_nav
    
    # Write back
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print(f"✅ Converted {filepath}")
    return True

# Pages to convert
pages = [
    ('stats.php', 'Statistics'),
    ('insights.php', 'Insights'),
    ('authors.php', 'Authors'),
    ('directors.php', 'Directors'),
    ('review.php', 'Book Review'),
    ('movie.php', 'Movie'),
]

print("🔄 Converting pages to unified header...\n")

for filepath, title in pages:
    convert_page(filepath, title)

print("\n✅ All pages converted!")
print("\nPages now use:")
print("  - Unified navigation")
print("  - Integrated search box")
print("  - Shared styles")
