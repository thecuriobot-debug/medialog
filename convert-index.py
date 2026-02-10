#!/usr/bin/env python3
"""
Convert index.php to use unified header while keeping hero section
"""

with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Find where PHP ends before HTML starts
php_end = content.find('?>\n<!DOCTYPE')
if php_end == -1:
    print("❌ Could not find PHP/HTML boundary")
    exit(1)

# Find where the navigation ends (after </nav>)
nav_end = content.find('</nav>', php_end) + 6

# Find the hero section start
hero_start = content.find('<div class="hero">', nav_end)

# Get the content before PHP ends
before_html = content[:php_end + 2]

# Get everything from hero onwards
after_nav = content[hero_start:]

# Create new header section
new_header = '''
<?php
$pageTitle = "Dashboard";
include 'includes/header.php';
?>

<style>
    /* Hero Section - Index Page Only */
    .hero {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
        backdrop-filter: blur(20px);
        padding: 60px 30px;
        margin: 0 auto 40px;
        max-width: 1400px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    
    .hero h1 {
        font-size: 3em;
        font-weight: 800;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .hero p {
        font-size: 1.2em;
        color: rgba(255,255,255,0.95);
        margin-bottom: 30px;
    }
    
    .hero-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .hero-stat {
        background: rgba(255,255,255,0.15);
        padding: 25px;
        border-radius: 15px;
        backdrop-filter: blur(10px);
    }
    
    .hero-stat-number {
        font-size: 2.5em;
        font-weight: 800;
        color: #d4af37;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .hero-stat-label {
        font-size: 0.9em;
        color: rgba(255,255,255,0.9);
        margin-top: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    /* Index-specific styles */
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .media-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .media-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    
    .media-poster {
        width: 60px;
        height: 85px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }
    
    .media-info {
        flex: 1;
    }
    
    .media-title {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 5px;
        font-size: 0.95em;
    }
    
    .media-meta {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 5px;
    }
    
    .media-stars {
        color: #d4af37;
        font-size: 0.85em;
    }
    
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2em;
        }
        
        .hero p {
            font-size: 1em;
        }
        
        .grid {
            grid-template-columns: 1fr;
        }
    }
</style>

'''

# Combine
new_content = before_html + new_header + after_nav

# Write back
with open('index.php', 'w', encoding='utf-8') as f:
    f.write(new_content)

print("✅ Converted index.php")
print("   - Added unified header with search")
print("   - Kept hero section")
print("   - Added page-specific styles")
