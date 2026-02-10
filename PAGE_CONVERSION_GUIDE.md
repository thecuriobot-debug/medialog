# Page Conversion Guide

## How to Convert Pages to Unified Header

All pages should use `includes/header.php` for consistent navigation with integrated search.

### Conversion Pattern

**Before (Old Pattern):**
```php
<?php
require_once 'config.php';
// ... page logic ...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Page Title</title>
    <style>
        /* Custom styles */
    </style>
</head>
<body>
    <nav class="top-nav">
        <!-- Custom navigation -->
    </nav>
    
    <!-- Page content -->
</body>
</html>
```

**After (New Pattern):**
```php
<?php
require_once 'config.php';
// ... page logic ...

$pageTitle = "Page Title";
include 'includes/header.php';
?>

<style>
    /* Page-specific styles only */
</style>

<!-- Page content starts here -->
<!-- No need for </body></html> - footer handles it -->

<?php include 'includes/footer.php'; ?>
```

---

## Benefits of Unified Header

✅ **Integrated Search** - Search box in navigation on every page  
✅ **Consistent Design** - Same nav, same look everywhere  
✅ **Easy Updates** - Change nav once, updates everywhere  
✅ **Shared Styles** - Common CSS in `includes/shared-styles.css`  
✅ **Less Code** - No duplicate navigation code  

---

## Pages Currently Using Unified Header

- ✅ books.php
- ✅ movies.php  
- ✅ search.php

## Pages To Convert

- ⏳ index.php
- ⏳ stats.php
- ⏳ insights.php
- ⏳ authors.php
- ⏳ directors.php
- ⏳ review.php
- ⏳ movie.php

---

## Conversion Steps

### 1. Backup the page
```bash
cp page.php page-backup-$(date +%Y%m%d).php
```

### 2. Find the opening tags
Look for:
```php
?>
<!DOCTYPE html>
<html>
<head>
```

### 3. Replace with header include
```php
?>
<?php
$pageTitle = "Your Page Title";
include 'includes/header.php';
?>
```

### 4. Remove custom navigation
Delete the `<nav class="top-nav">...</nav>` section

### 5. Keep page-specific styles
Move custom styles into a `<style>` block after the header include

### 6. Add footer (if not present)
At the end:
```php
<?php include 'includes/footer.php'; ?>
```

---

## Shared Styles Available

The following styles are in `includes/shared-styles.css`:

- `.container` - Main page container
- `.card` - White card boxes
- `.stats-grid` - Stats display grid
- `.stat-card` - Individual stat cards
- `.media-item` - Book/movie list items
- `.badge` - Colored badges
- `.grid` - General purpose grid
- And more...

Use these instead of duplicating styles!

---

## Example: Converting stats.php

**Find this:**
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statistics - MediaLog</title>
    <style>
        /* All the styles */
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <ul class="nav-links">
                <!-- links -->
            </ul>
        </div>
    </nav>
```

**Replace with:**
```php
<?php
$pageTitle = "Statistics";
include 'includes/header.php';
?>

<style>
    /* Only stats-specific styles here */
</style>
```

---

## Testing After Conversion

1. Check navigation appears correctly
2. Test search box works
3. Verify page-specific styles still work
4. Test on mobile
5. Check all links work

---

## Rollback if Needed

If something breaks:
```bash
cp page-backup-YYYYMMDD.php page.php
```

All backup files are saved with dates.

---

## Questions?

- Header file: `includes/header.php`
- Footer file: `includes/footer.php`
- Shared CSS: `includes/shared-styles.css`
- Examples: Check `books.php`, `movies.php`, `search.php`

---

**Last Updated:** February 9, 2026
