# 404 Error Troubleshooting Guide

## Quick Diagnosis Steps

### 1. Test Basic PHP Access

Navigate to: `http://localhost/Hotel/test.php`

- This will show you if PHP is working and identify specific issues

### 2. Check XAMPP Status

- Ensure Apache is running in XAMPP Control Panel
- Check if MySQL is running
- Verify port 80 (Apache) and 3306 (MySQL) are not blocked

### 3. Common Causes & Solutions

#### A. XAMPP Not Running

**Symptoms:** Can't access any localhost pages
**Solution:** Start Apache and MySQL in XAMPP Control Panel

#### B. Wrong URL Path

**Symptoms:** 404 on main page
**Correct URL:** `http://localhost/Hotel/` or `http://localhost/Hotel/index.php`
**Wrong URLs:**

- `http://localhost/hotel/` (case sensitive)
- `http://localhost/Hotel` (missing trailing slash)

#### C. Database Connection Issues

**Symptoms:** Page loads but shows database errors
**Check:**

- MySQL service is running
- Database 'hotel' exists
- User 'root' has no password (default XAMPP setup)

#### D. File Permission Issues

**Symptoms:** Files exist but can't be accessed
**Solution:** Ensure web server can read PHP files

#### E. .htaccess Issues

**Symptoms:** Page loads but redirects fail
**Check:** mod_rewrite is enabled in Apache

### 4. Step-by-Step Resolution

#### Step 1: Verify XAMPP

1. Open XAMPP Control Panel
2. Start Apache (should show green)
3. Start MySQL (should show green)
4. Check for error messages

#### Step 2: Test Basic Access

1. Go to `http://localhost/` (should show XAMPP welcome page)
2. Go to `http://localhost/Hotel/test.php` (should show diagnostic info)

#### Step 3: Check Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Verify 'hotel' database exists
3. Check if tables are present

#### Step 4: Verify File Structure

1. Confirm files are in correct location: `C:\xampp\htdocs\Hotel\`
2. Check if `index.php` exists in root directory
3. Verify includes directory structure

### 5. Advanced Troubleshooting

#### Check Apache Error Logs

Location: `C:\xampp\apache\logs\error.log`
Look for:

- PHP syntax errors
- File not found errors
- Permission denied errors

#### Check PHP Error Logs

Location: `C:\xampp\php\logs\php_error_log`
Look for:

- Fatal errors
- Warning messages
- Include path issues

#### Test Individual Components

1. **Database only:** `http://localhost/Hotel/db.php`
2. **Settings only:** `http://localhost/Hotel/includes/common/hotel_settings.php`
3. **Header only:** `http://localhost/Hotel/includes/header.php`

### 6. Quick Fixes

#### Fix 1: Restart Services

```bash
# In XAMPP Control Panel
1. Stop Apache
2. Stop MySQL
3. Wait 10 seconds
4. Start MySQL
5. Start Apache
```

#### Fix 2: Clear Browser Cache

- Press Ctrl+F5 to force refresh
- Clear browser cache and cookies
- Try incognito/private browsing mode

#### Fix 3: Check File Permissions

- Ensure all PHP files are readable
- Check if .htaccess is readable

### 7. When to Seek Help

Contact support if:

- XAMPP services won't start
- Database connection fails consistently
- PHP syntax errors persist
- Files exist but still get 404

### 8. Prevention

- Always start XAMPP before accessing localhost
- Keep XAMPP updated
- Don't modify system files
- Use proper file paths in includes
- Test changes incrementally

---

**Next Steps:**

1. Run the test.php file first
2. Check XAMPP status
3. Follow the step-by-step resolution
4. Check error logs if issues persist
