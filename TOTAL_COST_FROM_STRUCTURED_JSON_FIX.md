# Total Cost Extraction from Structured JSON

## Problem
The `contractor_send_estimates` table has a `total_cost` column that is NULL for all records. The actual cost data is stored in the `structured` column as JSON, making it inaccessible for queries and display in the payment request form.

## Root Cause
When estimates are submitted, the system stores all cost details in a JSON `structured` field but doesn't populate the `total_cost` column. This causes:
1. Payment request form shows "No estimate"
2. Cannot query or filter by cost
3. Cannot use cost in calculations
4. Poor database performance for cost-related queries

## Solution
Implemented a three-part solution:

### 1. Migration Script (One-Time)
Extract existing grand totals from JSON and populate `total_cost` column.

### 2. API Update (Future Submissions)
Automatically extract and save `total_cost` when new estimates are submitted.

### 3. Runtime Extraction (Fallback)
Extract `total_cost` from JSON at query time if still NULL.

## Implementation Details

### Part 1: Migration Script
**File:** `backend/migrate_total_cost_from_structured.php`

**Purpose:** One-time migration to populate `total_cost` for existing records.

**How it works:**
1. Reads all records with `structured` JSON data
2. Parses JSON and extracts grand total from multiple possible locations:
   - `totals.grand`
   - `totals.grandTotal`
   - `totals.total`
   - `grand`
   - `grandTotal`
   - Calculated from `totals.materials + totals.labor + totals.utilities + totals.misc`
3. Updates `total_cost` column with extracted value
4. Reports success/failure for each record

**Usage:**
```bash
php backend/migrate_total_cost_from_structured.php
```

Or via browser:
```
http://localhost/buildhub/backend/migrate_total_cost_from_structured.php
```

**Expected Output:**
```
=== Migrating Total Cost from Structured JSON ===

Found 15 records with structured data

✅ Record ID 1: Updated total_cost to ₹4,500,000.00
✅ Record ID 2: Updated total_cost to ₹2,500,000.00
...

=== Migration Complete ===
Total Records: 15
✅ Updated: 15
⚠️  Skipped: 0
❌ Errors: 0
```

### Part 2: API Update
**File:** `backend/api/contractor/submit_estimate_for_send.php`

**Changes:** Added automatic extraction of `total_cost` from `structured` JSON during submission.

**Logic:**
```php
// Extract total_cost from structured JSON if not provided
$final_total_cost = $total_cost !== '' ? (float)$total_cost : null;

if (($final_total_cost === null || $final_total_cost == 0) && isset($_POST['structured'])) {
    $structured_data = json_decode($_POST['structured'], true);
    if ($structured_data && json_last_error() === JSON_ERROR_NONE) {
        // Try multiple locations for grand total
        if (isset($structured_data['totals']['grand'])) {
            $final_total_cost = floatval($structured_data['totals']['grand']);
        }
        // ... other locations ...
    }
}
```

**Benefits:**
- All future estimates automatically have `total_cost` populated
- No manual intervention needed
- Backward compatible with existing code

### Part 3: Runtime Extraction
**File:** `backend/api/contractor/get_contractor_projects.php`

**Changes:** Added fallback extraction when fetching estimates.

**Logic:**
```php
// Extract total_cost from structured JSON if NULL
foreach ($legacy_accepted_estimates as &$estimate) {
    if (($estimate['estimate_cost'] === null || $estimate['estimate_cost'] == 0) 
        && $estimate['structured']) {
        $structured = json_decode($estimate['structured'], true);
        // Extract grand total from JSON
        // ...
    }
}
```

**Benefits:**
- Works even if migration hasn't run
- Handles edge cases
- No data loss

## JSON Structure Examples

### Example 1: Standard Format
```json
{
  "project_name": "Modern Villa",
  "totals": {
    "materials": 2000000,
    "labor": 1500000,
    "utilities": 500000,
    "misc": 500000,
    "grand": 4500000
  }
}
```

### Example 2: Alternative Format
```json
{
  "project_name": "Apartment Renovation",
  "totals": {
    "materials_total": 1000000,
    "labor_total": 800000,
    "utilities_total": 300000,
    "misc_total": 400000,
    "grandTotal": 2500000
  }
}
```

### Example 3: Calculated Format
```json
{
  "project_name": "Custom Home",
  "totals": {
    "materials": 3000000,
    "labor": 2000000,
    "utilities": 800000,
    "miscellaneous": 700000
  }
}
```
*Grand total calculated: 3000000 + 2000000 + 800000 + 700000 = 6500000*

## Database Schema

### Before Fix
```sql
CREATE TABLE contractor_send_estimates (
    id INT PRIMARY KEY,
    send_id INT,
    contractor_id INT,
    total_cost DECIMAL(15,2) NULL,  -- Always NULL!
    structured LONGTEXT,             -- Contains all cost data
    ...
);
```

### After Fix
```sql
CREATE TABLE contractor_send_estimates (
    id INT PRIMARY KEY,
    send_id INT,
    contractor_id INT,
    total_cost DECIMAL(15,2) NULL,  -- Now populated!
    structured LONGTEXT,             -- Still contains detailed data
    ...
);
```

## Testing

### Test Migration Script
```bash
# Run migration
php backend/migrate_total_cost_from_structured.php

# Verify results
mysql -u root buildhub -e "
SELECT id, total_cost, status 
FROM contractor_send_estimates 
WHERE total_cost IS NOT NULL 
LIMIT 10;
"
```

### Test API Update
```bash
# Submit a new estimate via API
curl -X POST http://localhost/buildhub/backend/api/contractor/submit_estimate_for_send.php \
  -H "Content-Type: application/json" \
  -d '{
    "send_id": 1,
    "contractor_id": 5,
    "structured": {
      "totals": {
        "materials": 2000000,
        "labor": 1500000,
        "grand": 3500000
      }
    }
  }'

# Verify total_cost was populated
mysql -u root buildhub -e "
SELECT id, total_cost 
FROM contractor_send_estimates 
ORDER BY id DESC 
LIMIT 1;
"
```

### Test Runtime Extraction
```bash
# Test get_contractor_projects API
curl http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=5

# Check response for estimate_cost values
```

## Benefits

### 1. Improved Performance
- Can query by cost without parsing JSON
- Indexes work on `total_cost` column
- Faster sorting and filtering

### 2. Better Data Integrity
- Cost is stored in proper DECIMAL format
- Consistent data representation
- Easier to validate and audit

### 3. Enhanced Functionality
- Payment request form shows correct costs
- Can calculate totals and statistics
- Better reporting capabilities

### 4. Backward Compatibility
- Existing code continues to work
- JSON data preserved for detailed breakdown
- No data migration required (optional)

## Migration Statistics

### Sample Output
```
=== Migration Complete ===
Total Records: 25
✅ Updated: 23
⚠️  Skipped: 2 (no valid grand total found)
❌ Errors: 0

=== Sample of Updated Records ===
ID: 15, Contractor: John Doe, Total: ₹4,500,000.00, Status: accepted
ID: 14, Contractor: Jane Smith, Total: ₹2,500,000.00, Status: accepted
ID: 13, Contractor: Bob Johnson, Total: ₹3,200,000.00, Status: submitted
...
```

## Error Handling

### Invalid JSON
```
❌ Record ID 5: Invalid JSON - Syntax error
```
**Action:** Check and fix JSON in `structured` column

### Missing Grand Total
```
⚠️  Record ID 8: No valid grand total found in structured data
```
**Action:** Manually add grand total to JSON or calculate from categories

### Database Errors
```
❌ Database Error: Table 'contractor_send_estimates' doesn't exist
```
**Action:** Ensure database and tables are created

## Rollback Plan

### If Migration Fails
```sql
-- Reset total_cost to NULL
UPDATE contractor_send_estimates 
SET total_cost = NULL;
```

### If API Update Causes Issues
```bash
# Revert submit_estimate_for_send.php to previous version
git checkout HEAD -- backend/api/contractor/submit_estimate_for_send.php
```

### If Runtime Extraction Causes Issues
```bash
# Revert get_contractor_projects.php to previous version
git checkout HEAD -- backend/api/contractor/get_contractor_projects.php
```

## Future Enhancements

### 1. Automatic Recalculation
Add trigger to recalculate `total_cost` when `structured` is updated:
```sql
CREATE TRIGGER update_total_cost_from_structured
BEFORE UPDATE ON contractor_send_estimates
FOR EACH ROW
BEGIN
    IF NEW.structured IS NOT NULL THEN
        -- Extract and set total_cost from JSON
        SET NEW.total_cost = JSON_EXTRACT(NEW.structured, '$.totals.grand');
    END IF;
END;
```

### 2. Validation
Add validation to ensure `total_cost` matches JSON:
```php
function validateTotalCost($total_cost, $structured) {
    $json = json_decode($structured, true);
    $json_total = $json['totals']['grand'] ?? 0;
    
    if (abs($total_cost - $json_total) > 0.01) {
        throw new Exception('Total cost mismatch');
    }
}
```

### 3. Audit Trail
Log all cost updates for auditing:
```sql
CREATE TABLE cost_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estimate_id INT,
    old_cost DECIMAL(15,2),
    new_cost DECIMAL(15,2),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Files Modified

1. **backend/api/contractor/submit_estimate_for_send.php**
   - Added automatic extraction of `total_cost` from `structured` JSON
   - Handles multiple JSON formats
   - Calculates from category totals if needed

2. **backend/api/contractor/get_contractor_projects.php**
   - Added runtime extraction for NULL `total_cost`
   - Extracts from `structured` JSON on-the-fly
   - Ensures cost is always available

## Files Created

1. **backend/migrate_total_cost_from_structured.php**
   - One-time migration script
   - Comprehensive error handling
   - Detailed reporting

2. **TOTAL_COST_FROM_STRUCTURED_JSON_FIX.md**
   - This documentation file

## Conclusion

The three-part solution ensures that `total_cost` is always available:
1. **Migration script** fixes existing records
2. **API update** handles future submissions
3. **Runtime extraction** provides fallback

This comprehensive approach ensures the payment request form can always display the correct estimate cost, improving user experience and system reliability.

## Quick Start

### Step 1: Run Migration (One-Time)
```bash
php backend/migrate_total_cost_from_structured.php
```

### Step 2: Verify Results
```bash
# Check updated records
mysql -u root buildhub -e "
SELECT COUNT(*) as total, 
       SUM(CASE WHEN total_cost IS NOT NULL THEN 1 ELSE 0 END) as with_cost
FROM contractor_send_estimates;
"
```

### Step 3: Test Payment Form
1. Log in as contractor
2. Go to Payment Requests
3. Select project with accepted estimate
4. Verify cost appears automatically

Done! The system now properly extracts and displays estimate costs.
