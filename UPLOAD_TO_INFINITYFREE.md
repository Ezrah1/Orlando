# 🚀 Upload to InfinityFree - Complete Guide

## **📋 Prerequisites**
- FileZilla installed and connected to your InfinityFree account
- All files ready in your local Hotel folder

## **🔗 FTP Connection Details**
- **Host**: ftpupload.net
- **Username**: if0_39842749
- **Password**: 6LLK5O9akZAKiZi
- **Port**: 21
- **Domain**: orlandointernationalresorts.great-site.net

## **🗄️ Database Details**
- **Host**: sql300.infinityfree.com
- **Username**: if0_39842749
- **Password**: 6LLK5O9akZAKiZi
- **Database**: if0_39842749_XXX
- **Port**: 3306

## **📁 FileZilla Upload Steps**

### **1. Connect to FTP**
- Open FileZilla
- Enter the FTP details above
- Click "Quickconnect"

### **2. Navigate to Root Directory**
- On the right side (remote site), navigate to:
  - `htdocs/` or `public_html/` folder
  - This is your website's root directory

### **3. Upload Files**
- On the left side (local site), navigate to your Hotel project folder
- Select ALL files and folders (except .git folder)
- Drag and drop to the right side (remote site)
- **Important**: Upload to the root of htdocs/public_html

### **4. Set File Permissions**
After upload, set these permissions:
- **Folders**: 755 (rwxr-xr-x)
- **Files**: 644 (rw-r--r--)
- **Special files**: 777 for upload/writable folders

## **🗄️ Database Import Steps**

### **1. Access phpMyAdmin**
- Go to: https://app.infinityfree.com/
- Login with your credentials
- Click "phpMyAdmin"

### **2. Import Database**
- Select your database: `if0_39842749_XXX`
- Click "Import" tab
- Choose your `hotel (1).sql` file
- Click "Go" to import

## **🔧 Post-Upload Configuration**

### **1. Update Database Name**
- Replace `if0_39842749_XXX` with your actual database name
- Check in InfinityFree control panel for exact name

### **2. Test Your Site**
- Visit: orlandointernationalresorts.great-site.net
- Test admin panel: orlandointernationalresorts.great-site.net/admin
- Test guest booking system

### **3. Common Issues & Fixes**
- **500 Error**: Check file permissions
- **Database Connection**: Verify database name and credentials
- **404 Error**: Ensure files are in correct directory

## **📱 Admin Access**
- **URL**: orlandointernationalresorts.great-site.net/admin
- **Default Admin**: Check your database for admin credentials
- **Create Admin**: Use the admin creation script if needed

## **✅ Success Checklist**
- [ ] All files uploaded to htdocs/public_html
- [ ] Database imported successfully
- [ ] Site loads without errors
- [ ] Admin panel accessible
- [ ] Booking system functional
- [ ] File permissions set correctly

## **🆘 Need Help?**
- Check InfinityFree error logs
- Verify database connection
- Test with simple PHP file first
- Contact InfinityFree support if needed

---
**Your Hotel Management System will be live at: orlandointernationalresorts.great-site.net** 🎯
