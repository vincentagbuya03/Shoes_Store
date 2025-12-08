# 🌐 Shoes Store - Hosting Setup Guide for Ezyro/ProFreeHost

## Quick Setup (5 Steps)

### Step 1: Export Your Database
1. Open Laragon phpMyAdmin: http://localhost/phpmyadmin
2. Click on `shoestore` database
3. Click **Export** tab
4. Choose **SQL** format
5. Click **Go** to download the `shoestore.sql` file

### Step 2: Upload Files to Hosting
1. Download FileZilla or use Ezyro's File Manager
2. Connect to your hosting with FTP credentials (from Ezyro panel)
3. Upload **ALL** files to your `public_html` folder
   - Include: index.php, user-interface.php, admin/, api/, etc.
   - Include: all CSS, JS, and images
   - **Important**: Make sure `db_connection.php` and `admin/db_connection.php` are uploaded

### Step 3: Create Database on Hosting
1. Log into Ezyro Control Panel
2. Go to **MySQL Databases**
3. Create a new database named: `ezyro_40608014_shoestore_db`
   - Database name: ezyro_40608014_shoestore_db
   - Username: ezyro_40608014
   - Password: 2fcd0ad
4. Note these details (already in your db_connection.php files)

### Step 4: Import Database
1. Go to **phpMyAdmin** in Ezyro Control Panel
2. Select your newly created `ezyro_40608014_shoestore_db` database
3. Click **Import** tab
4. Click **Choose File** and select `shoestore.sql`
5. Click **Go** to import

### Step 5: Test Your Website
1. Visit: http://shoetakels.unaux.com/hosting_check.php
2. Check if all items show ✓
3. If tables are missing, redo Step 4 (Import Database)

---

## If You Get HTTP 500 Error:

1. **First**, check: http://shoetakels.unaux.com/hosting_check.php
2. **Most common issues**:
   - ❌ Database connection failed → Re-check credentials in db_connection.php
   - ❌ Tables missing → Import database (Step 4)
   - ❌ File permissions → Contact hosting support
   
3. **For detailed errors**, temporarily add this to the top of `index.php` or `user-interface.php`:
   ```php
   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
   Then try again and note any error messages.

---

## Files to Upload

**Root folder files:**
- db_connection.php ✓ (Already updated with Ezyro credentials)
- index.php
- user-interface.php
- shoes.php
- login.php
- signup.php
- checkout.php
- product-detail.php
- all other .php files

**Folders to upload:**
- `admin/` (entire folder)
- `delivery_rider/` (entire folder)
- `api/` (entire folder)
- `asset/` (entire folder)
- `inc/` (entire folder)
- `partials/` (entire folder)
- `scripts/` (entire folder)
- `upload/` (entire folder with images)

---

## Database Credentials for Ezyro

- **Hostname:** sql311.ezyro.com
- **Database Name:** ezyro_40608014_shoestore_db
- **Username:** ezyro_40608014
- **Password:** 2fcd0ad

These are already in your updated db_connection.php files.

---

## Troubleshooting

### Issue: "Connection failed"
- Check if Ezyro database credentials are correct in db_connection.php
- Verify database was created in Ezyro panel

### Issue: Tables not found
- Check hosting_check.php - it will show if tables are missing
- Import your database using Step 4 above

### Issue: Pages show blank
- Check hosting_check.php for errors
- Enable error reporting (see section above)
- Check Ezyro error logs in control panel

### Issue: File upload not working
- Contact Ezyro support to make upload/ directories writable
- Usually requires chmod 755 or 777

---

## After Hosting is Working

✓ Delete these files from your hosting for security:
- hosting_check.php
- test_connection.php
- test_admin_notification.php

---

## For Updates to Your Website

1. Make changes in your local Laragon setup
2. Test locally
3. Upload changed files via FTP/File Manager
4. Database changes: Export from local → Import to hosting

---

**Questions?** Your Ezyro hosting support: Check the control panel for support tickets
