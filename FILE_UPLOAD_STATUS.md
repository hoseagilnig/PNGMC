# File Upload Status - Returning Candidate Application

## ✅ **How Files Are Saved**

When a Returning Candidate submits their application, files are saved in **TWO places**:

### **1. File System (Physical Storage)**
- **Location:** `uploads/continuing_students/`
- **Files saved:**
  - `nmsa_approval_letter` → Saved as `nmsa_[timestamp]_[unique].pdf`
  - `sea_service_record` → Saved as `sea_service_[timestamp]_[unique].pdf`

### **2. Database (Metadata)**
- **Table:** `applications`
  - `nmsa_approval_letter_path` → Path to file
  - `sea_service_record_path` → Path to file

- **Table:** `application_documents` (if exists)
  - Stores document metadata (type, name, path)
  - Links documents to applications

---

## 🔍 **Verify Files Are Being Saved**

### **On Linux Server:**

**1. Check if files exist:**
```bash
ls -la /var/www/html/pngmc/uploads/continuing_students/
```

**2. Check database:**
```bash
mysql -u root -p sms2_db -e "SELECT application_number, nmsa_approval_letter_path, sea_service_record_path FROM applications WHERE application_type = 'continuing' ORDER BY application_id DESC LIMIT 5;"
```

**3. Check application_documents table:**
```bash
mysql -u root -p sms2_db -e "SELECT * FROM application_documents WHERE document_type IN ('nmsa_approval_letter', 'sea_service_record') ORDER BY uploaded_at DESC LIMIT 10;"
```

---

## 📋 **What Happens When Form is Submitted**

1. ✅ **Files uploaded** → Saved to `uploads/continuing_students/`
2. ✅ **Application created** → Record in `applications` table
3. ✅ **File paths saved** → Stored in `nmsa_approval_letter_path` and `sea_service_record_path` columns
4. ✅ **Document records created** → Entries in `application_documents` table (if table exists)
5. ✅ **Requirements created** → Entries in `continuing_student_requirements` table

---

## 🔧 **Check Upload Directory Permissions**

**On Linux Server:**
```bash
# Check permissions
ls -la /var/www/html/pngmc/uploads/continuing_students/

# Fix permissions if needed
sudo chown -R www-data:www-data /var/www/html/pngmc/uploads/
sudo chmod -R 755 /var/www/html/pngmc/uploads/
```

---

## 🐛 **Troubleshooting**

### **If files aren't being saved:**

**1. Check PHP upload settings:**
```bash
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

**2. Check Apache error log:**
```bash
sudo tail -50 /var/log/apache2/pngmc_error.log | grep -i upload
```

**3. Check if directory exists:**
```bash
ls -la /var/www/html/pngmc/uploads/
```

**4. Check PHP error log:**
```bash
sudo tail -50 /var/log/apache2/pngmc_error.log | grep "Apply Continuing"
```

---

## 📊 **View Uploaded Files**

Files can be viewed by staff in:
- **Application Details Page** (`pages/application_details.php`)
- **Continuing Students Page** (`pages/continuing_students.php`)
- **Document Viewer** (`pages/view_document.php`)

---

## ✅ **Current Status**

Based on the code:
- ✅ Files are uploaded to filesystem
- ✅ File paths are saved to database
- ✅ Documents are saved to `application_documents` table
- ✅ Error logging is in place

**To verify everything is working, check the uploads directory and database after submitting a test application!**

