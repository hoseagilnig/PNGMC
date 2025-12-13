# Application Workflow System

This document describes the application workflow system based on the PNG Maritime College application process flowchart.

## Workflow Steps

### 1. Expression of Interest Submission
- **Page:** `apply.php` (Public)
- **Action:** Students submit expression of interest to study Cadet Officers Program
- **Requirements:** 
  - Personal information
  - Grade 12 completion status
  - Grades in Maths, Physics, and English
- **Status:** `submitted`

### 2. Student Admin Assessment
- **Page:** `pages/applications.php` (Admin)
- **Action:** Student Admin assesses and processes candidate documents
- **Options:**
  - Accept → Forward to HOD Review
  - Reject → Mark as Ineligible
- **Status:** `under_review` → `hod_review` or `ineligible`

### 3. HOD Decision
- **Page:** `pages/applications.php` (Admin)
- **Action:** Head of Department reviews and makes decision
- **Options:**
  - Approve → Proceed to Correspondence
  - Reject → Mark as Ineligible
- **Status:** `hod_review` → `accepted` or `ineligible`

### 4. Send Correspondence & Invoice
- **Page:** `pages/applications.php` (Admin)
- **Action:** Send requirements letter and invoice to accepted candidates
- **Status:** `accepted` → `correspondence_sent`

### 5. Mandatory Checks
- **Page:** `pages/applications.php` (Admin)
- **Action:** Track mandatory checks completion
- **Checks include:**
  - Medical clearance
  - Police clearance
  - Academic verification
  - Identity verification
  - Financial clearance
- **Status:** `correspondence_sent` → `checks_pending` → `checks_completed`

### 6. Enrollment
- **Page:** `pages/applications.php` (Admin)
- **Action:** Enroll student after all checks are completed
- **Result:** Creates student record and links to application
- **Status:** `checks_completed` → `enrolled`

## Database Tables

### applications
Stores all application information and workflow status.

### application_documents
Tracks documents submitted by applicants.

### mandatory_checks
Tracks completion of mandatory checks.

### correspondence
Records all correspondence sent to applicants.

### application_notes
Internal notes and comments on applications.

## Access Points

- **Public Application Form:** `http://localhost/sms2/apply.php`
- **Admin Application Management:** `http://localhost/sms2/pages/applications.php`
- **Application Details:** `http://localhost/sms2/pages/application_details.php?id=X`

## Status Flow

```
submitted → under_review → hod_review → accepted → correspondence_sent → 
checks_pending → checks_completed → enrolled
```

Any step can result in `ineligible` status if rejected.

