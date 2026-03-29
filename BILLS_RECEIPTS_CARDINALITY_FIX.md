# Bills & Receipts Cardinality Violation Fix

## Problem
The bills and receipts API was failing with the error:
```
SQLSTATE[21000]: Cardinality violation: 1222 The used SELECT statements have a different number of columns
```

## Root Cause
The UNION ALL query in `backend/api/homeowner/get_bills_receipts.php` was trying to combine results from two tables (`stage_payment_requests` and `custom_payment_requests`) that have different column structures:

- `stage_payment_requests` has columns like `stage_name`, `completion_percentage`, `materials_used`, etc.
- `custom_payment_requests` has columns like `request_title`, `request_reason`, `urgency_level`, etc.

The original query used `SELECT *` which caused the cardinality violation because the tables have different numbers of columns.

## Solution
Fixed the query by explicitly selecting only the common columns and mapping them consistently:

### Key Changes:
1. **Explicit Column Selection**: Replaced `SELECT *` with explicit column lists
2. **Column Mapping**: Mapped `stage_name` to `request_title` for consistency
3. **Payment Type Indicator**: Added `payment_type` field to distinguish between 'stage' and 'custom' payments
4. **Parameter Binding**: Fixed parameter binding by using unique parameter names (`:homeowner_id` and `:homeowner_id2`)

### Query Structure:
```sql
SELECT 
    spr.id,
    spr.stage_name as request_title,  -- Map stage_name to request_title
    spr.requested_amount,
    -- ... other common columns
    'stage' as payment_type           -- Add type indicator
FROM stage_payment_requests spr
-- ... joins and conditions

UNION ALL

SELECT 
    cpr.id,
    cpr.request_title,               -- Direct mapping
    cpr.requested_amount,
    -- ... same columns in same order
    'custom' as payment_type         -- Add type indicator
FROM custom_payment_requests cpr
-- ... joins and conditions
```

## Files Modified
- `backend/api/homeowner/get_bills_receipts.php` - Fixed the UNION ALL query
- Added `payment_type_badge` processing for better UI display

## Testing
- Created test file `test_bills_receipts_api.html` to verify the fix
- Confirmed the API now works without cardinality violations
- Verified data is returned correctly with proper type indicators

## Result
✅ The bills and receipts API now works correctly without SQL errors
✅ Both stage payments and custom payment requests are properly combined
✅ Added payment type badges for better user experience