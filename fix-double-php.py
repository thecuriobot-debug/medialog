#!/usr/bin/env python3
"""
Fix double ?> issue in converted pages
"""

import re

files = ['authors.php', 'directors.php', 'review.php', 'movie.php']

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find the pattern: ?>\n<?php\n$pageTitle
    pattern = r'\?>\s*<\?php\s+\$pageTitle'
    replacement = r'\n$pageTitle'
    
    new_content = re.sub(pattern, replacement, content)
    
    # Also remove extra whitespace before <div class="container">
    new_content = re.sub(r'\?>\s+<div class="container">', r'?>\n\n<div class="container">', new_content)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print(f"✅ Fixed {filepath}")

print("\n✅ All pages fixed!")
