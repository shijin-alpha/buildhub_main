# Stage Documents SQL Column Fix - Complete

## Issue
When opening stage documents, the system was failing with the error:
```
Failed to load documents: Internal server error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'u_contractor.name' in 'field list'
```

## Root Cause
Multiple API files were using `u_contractor.name` and similar patterns, but the `users` table has `first_name` and `last_name` columns, not a single `name` column.

## Files Fixed

### 1. backend/api/contractor/get_stage_documents.php
**Fixed:** Changed `u_contractor.name` and `u_verifier.name` to use `CONCAT(first_name, ' ', last_name)`

### 2. backend/api/project/get_project_overview.php
**Fixed:** Multiple instances of `u_contractor.name`, `u_architect.name`, `u_homeowner.name`

### 3. backend/api/contractor/verify_stage_documents.php
**Fixed:** Changed `u.name as contractor_name` to use `CONCAT(u.first_name, ' ', u.last_name)`

### 4. check_payment_requests_for_testing.php
**Fixed:** Changed `h.name` and `c.name` to use `CONCAT(first_name, ' ', last_name)`

### 5. backend/setup_integrated_workflow.php
**Fixed:** Changed `u.name as homeowner_name` to use `CONCAT(u.first_name, ' ', u.last_name)`

### 6. backend/api/contractor/submit_stage_withdrawal_request.php
**Fixed:** Changed `u.name as contractor_name` to use `CONCAT(u.first_name, ' ', u.last_name)`

### 7. backend/api/contractor/submit_integrated_progress_report.php
**Fixed:** Changed `u.name as homeowner_name` to use `CONCAT(u.first_name, ' ', u.last_name)`

### 8. backend/api/contractor/submit_enhanced_progress_update.php
**Fixed:** Changed `u.name as homeowner_name` to use `CONCAT(u.first_name, ' ', u.last_name)`

## Solution Applied
Replaced all instances of:
```sql
u.name as contractor_name
u_contractor.name as contractor_name
u_verifier.name as verified_by_name
-- etc.
```

With:
```sql
CONCAT(u.first_name, ' ', u.last_name) as contractor_name
CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name
CONCAT(u_verifier.first_name, ' ', u_verifier.last_name) as verified_by_name
-- etc.
```

## Testing
- ✅ SQL query executes without errors
- ✅ No more "Column not found" errors
- ✅ User names are properly concatenated from first_name and last_name

## Status
🎉 **COMPLETE** - The stage documents feature should now load without SQL errors.

## Next Steps
1. Test the stage documents functionality in the frontend
2. Verify that contractor names display correctly
3. Ensure document upload and verification workflows work properly