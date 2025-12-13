# Archive System Documentation
## PNG Maritime College - Student Management System

**Date:** January 2025  
**Status:** ✅ Implemented

---

## Overview

The Archive System provides comprehensive data archiving functionality for the Student Management System. It allows administrators to archive old records (applications, students, invoices, documents) to maintain database performance while preserving historical data.

---

## Features

### ✅ Implemented Features

1. **Archive Database Tables**
   - `archived_applications` - Stores archived application records
   - `archived_students` - Stores archived student records
   - `archived_invoices` - Stores archived invoice records
   - `archived_application_documents` - Stores archived document references
   - `archive_log` - Tracks all archive actions
   - `archive_settings` - Configurable archive settings

2. **Archive Helper Functions**
   - `archiveApplication()` - Archive an application and its documents
   - `archiveStudent()` - Archive a student record
   - `archiveInvoice()` - Archive an invoice record
   - `getArchiveStatistics()` - Get archive statistics
   - `getArchiveSetting()` - Get archive configuration
   - `updateArchiveSetting()` - Update archive configuration
   - `logArchiveAction()` - Log archive operations

3. **Archive Management Interface**
   - View archived applications, students, and invoices
   - Search and filter archived records
   - Configure auto-archive settings
   - View archive statistics

4. **Auto-Archive Settings**
   - Configurable automatic archiving rules
   - Time-based archiving (days since completion)
   - Enable/disable auto-archiving per record type

---

## Database Schema

### Archive Tables

#### archived_applications
Stores complete application data at time of archiving.

**Key Fields:**
- `original_application_id` - Reference to original application
- `archived_by` - User who performed the archive
- `archived_at` - Timestamp of archiving
- `archive_reason` - Reason for archiving
- All original application fields preserved

#### archived_students
Stores complete student data at time of archiving.

**Key Fields:**
- `original_student_id` - Reference to original student
- `archived_by` - User who performed the archive
- `archived_at` - Timestamp of archiving
- `archive_reason` - Reason for archiving
- All original student fields preserved

#### archived_invoices
Stores complete invoice data at time of archiving.

**Key Fields:**
- `original_invoice_id` - Reference to original invoice
- `archived_by` - User who performed the archive
- `archived_at` - Timestamp of archiving
- All original invoice fields preserved

#### archive_log
Tracks all archive operations for audit purposes.

**Fields:**
- `archive_type` - Type of record (application, student, invoice, document)
- `original_id` - ID of archived record
- `action` - Action performed (archived, restored, deleted)
- `performed_by` - User who performed the action
- `performed_at` - Timestamp
- `reason` - Reason for action
- `notes` - Additional notes

---

## Usage

### Manual Archiving

#### Archive an Application

1. Navigate to **Applications** page
2. Find the application to archive
3. Click **Archive** button (Admin only)
4. Provide archive reason and notes
5. Confirm archive action

**Note:** Only administrators can archive applications.

#### Archive a Student

1. Navigate to **Student Records** page
2. Find the student to archive
3. Click **Archive** button (Admin only)
4. Provide archive reason and notes
5. Confirm archive action

#### Archive an Invoice

1. Navigate to **Invoices** page
2. Find the invoice to archive
3. Click **Archive** button (Admin only)
4. Confirm archive action

### Viewing Archived Records

1. Navigate to **Archive Management** (Admin Dashboard)
2. Select tab: Applications, Students, or Invoices
3. Use search to find specific archived records
4. View archive details including:
   - Original record data
   - Who archived it
   - When it was archived
   - Archive reason

### Auto-Archive Configuration

1. Navigate to **Archive Management** → **Settings** tab
2. Configure auto-archive rules:
   - **Applications:** Archive completed/rejected applications older than X days
   - **Students:** Archive inactive/graduated students older than X days
   - **Invoices:** Archive paid invoices older than X days
3. Enable/disable auto-archiving per record type
4. Save settings

**Default Settings:**
- Applications: Archive after 365 days (disabled by default)
- Students: Archive after 730 days (disabled by default)
- Invoices: Archive after 180 days (disabled by default)

---

## Archive Process

### What Happens When Archiving

1. **Data Copy:** Complete record is copied to archive table
2. **Document Preservation:** Document file paths are preserved (files not deleted)
3. **Logging:** Archive action is logged in `archive_log`
4. **Original Deletion:** Original record is removed from active table
5. **Relationships:** Foreign key relationships are preserved in archive

### Data Preservation

- ✅ All original data fields are preserved
- ✅ Original timestamps are preserved
- ✅ Document file paths are preserved (files remain on disk)
- ✅ Archive metadata (who, when, why) is recorded
- ✅ Complete audit trail in archive_log

---

## Access Control

### Archive Permissions

- **Archive Applications:** Admin only
- **Archive Students:** Admin only
- **Archive Invoices:** Admin only
- **View Archives:** Admin only
- **Configure Settings:** Admin only

### Security

- CSRF protection on all archive actions
- Input validation and sanitization
- Prepared statements for database operations
- Audit logging of all archive operations

---

## Files Created

1. **Database:**
   - `database/create_archive_tables.sql` - Archive table definitions

2. **PHP Functions:**
   - `pages/includes/archive_helper.php` - Archive helper functions

3. **Interface:**
   - `pages/archive_management.php` - Archive management interface

4. **Documentation:**
   - `ARCHIVE_SYSTEM_DOCUMENTATION.md` - This file

---

## Setup Instructions

### 1. Create Archive Tables

Run the SQL script to create archive tables:

```bash
mysql -u root -p sms2_db < database/create_archive_tables.sql
```

Or via MySQL client:
```sql
source database/create_archive_tables.sql;
```

### 2. Verify Tables Created

Check that these tables exist:
- `archived_applications`
- `archived_students`
- `archived_invoices`
- `archived_application_documents`
- `archive_log`
- `archive_settings`

### 3. Access Archive Management

1. Login as Administrator
2. Navigate to **Archive Management** from admin dashboard
3. Configure settings as needed

---

## Archive Statistics

The system tracks:
- Total archived applications
- Total archived students
- Total archived invoices
- Total archived documents

Statistics are displayed on the Archive Management page.

---

## Best Practices

### When to Archive

**Applications:**
- Applications completed/rejected more than 1 year ago
- Applications that resulted in enrollment (student record exists)
- Duplicate or test applications

**Students:**
- Students graduated more than 2 years ago
- Students withdrawn/inactive for more than 2 years
- Students who completed all programs

**Invoices:**
- Paid invoices older than 6 months
- Cancelled invoices
- Historical invoices for reporting purposes

### Archive Reasons

Use descriptive archive reasons:
- "Completed application - student enrolled"
- "Graduated student - 2 years inactive"
- "Paid invoice - 6 months old"
- "Test/duplicate record"

### Before Archiving

1. ✅ Verify record is complete
2. ✅ Ensure no active dependencies
3. ✅ Check if student record exists (for applications)
4. ✅ Confirm invoice is paid (for invoices)
5. ✅ Document reason for archiving

---

## Future Enhancements

### Planned Features

1. **Restore Functionality**
   - Restore archived records back to active tables
   - Restore with original relationships

2. **Automatic Archiving**
   - Scheduled cron job for auto-archiving
   - Email notifications for archive operations

3. **Archive Reports**
   - Generate archive reports
   - Export archived data

4. **Archive Retention Policies**
   - Configurable retention periods
   - Automatic deletion of old archives

---

## Troubleshooting

### Common Issues

**Issue:** Archive button not visible
- **Solution:** Ensure you're logged in as Administrator

**Issue:** Archive fails with database error
- **Solution:** Verify archive tables exist and have correct structure

**Issue:** Documents not found after archiving
- **Solution:** Document files are preserved; check file paths in archive table

**Issue:** Cannot view archived records
- **Solution:** Ensure you have admin access and archive tables exist

---

## Support

For issues or questions:
1. Check archive_log table for error details
2. Verify database table structure
3. Check file permissions for document storage
4. Review archive settings configuration

---

**Status:** ✅ Production Ready  
**Last Updated:** January 2025

