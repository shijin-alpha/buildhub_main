# Site Inspection 401 Unauthorized Error - FIXED

## Problem
The admin module's site inspection section was showing a 401 Unauthorized error when trying to load projects. The error was:
```
Failed to load resource: the server responded with a status of 401 (Unauthorized)
buildhub/backend/api/inspector/get_assigned_projects.php:1
```

## Root Cause Analysis
1. **Authentication Mismatch**: The site inspection API endpoints were checking for `$_SESSION['user_id']` and `$_SESSION['role'] === 'site_inspector'`
2. **Admin Session Variables**: Admin login sets `$_SESSION['admin_logged_in']` and `$_SESSION['admin_username']` but not the user_id/role variables
3. **Missing Database Tables**: The site inspection system tables didn't exist in the database
4. **No Test Data**: There were no site inspectors or project assignments to display

## Solutions Implemented

### 1. Fixed Authentication in API Endpoints
Updated the following files to allow both admin and site inspector access:

**backend/api/inspector/get_assigned_projects.php**
- Modified authentication to check for both admin session and site inspector session
- Updated queries to show all projects for admin, assigned projects for inspectors
- Added proper statistics for both user types

**backend/api/inspector/get_project_details.php**
- Added admin authentication support
- Modified access control to allow admin to view any project
- Updated queries to handle both admin and inspector access

**backend/api/inspector/create_inspection_report.php**
- Added admin authentication support
- Modified inspector ID handling for admin users
- Maintained proper access control for site inspectors

### 2. Created Missing Database Tables
Set up the complete site inspection database schema:
- `site_inspector_assignments` - Links inspectors to projects
- `inspection_reports` - Stores inspection findings and reports
- `inspection_photos` - Stores inspection photos and documentation
- `inspection_checklist_items` - Detailed inspection checklist items
- `inspection_notifications` - Notification system for inspection updates

### 3. Updated User Role System
- Modified `users` table to support `site_inspector` role
- Created sample site inspector user for testing
- Assigned inspector to existing projects for demonstration

### 4. Created Test Data
- Site Inspector Login: `inspector@buildhub.com` / `inspector123`
- Assigned inspector to all existing construction projects
- Projects now show up in the admin site inspection section

## Authentication Logic
```php
// Check if user is logged in and is a site inspector OR admin
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isSiteInspector = isset($_SESSION['user_id']) && $_SESSION['role'] === 'site_inspector';

if (!$isAdmin && !$isSiteInspector) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}
```

## Admin vs Inspector Access
- **Admin**: Can view all projects and their inspection status, create reports on behalf of assigned inspectors
- **Site Inspector**: Can only view assigned projects and create their own inspection reports

## Testing
1. Login as admin and navigate to Site Inspection section
2. Projects should now load without 401 errors
3. Admin can view project details and inspection history
4. Site inspector can login separately and see only assigned projects

## Files Modified
- `backend/api/inspector/get_assigned_projects.php`
- `backend/api/inspector/get_project_details.php`
- `backend/api/inspector/create_inspection_report.php`
- `backend/database/site_inspector_schema.sql` (executed)

## Database Changes
- Added `site_inspector` to users.role enum
- Created 5 new tables for site inspection functionality
- Added sample site inspector user and project assignments

The site inspection section should now work properly for admin users without any 401 Unauthorized errors.