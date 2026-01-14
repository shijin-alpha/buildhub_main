# Estimate Cost Retrieval Fix

## Problem
The payment request form was showing "No estimate" even though contractors had sent estimates to homeowners and they were accepted. The approved estimate cost was not being retrieved and displayed in the payment request form.

## Root Cause
The `get_contractor_projects.php` API was only querying the `construction_projects` table, which contains formal construction projects. However, accepted estimates are stored in two separate tables:
1. `contractor_estimates` - New estimation form submissions
2. `contractor_send_estimates` - Legacy estimate submissions

When a homeowner accepts an estimate, it gets marked with `status = 'accepted'` in these tables, but it doesn't automatically create a record in `construction_projects` until the project is formally started.

## Solution
Updated the `get_contractor_projects.php` API to fetch accepted estimates from all three sources:

### 1. Construction Projects (Existing)
```sql
SELECT * FROM construction_projects 
WHERE contractor_id = ? 
AND status IN ('created', 'in_progress')
```

### 2. Accepted Estimates (New)
```sql
SELECT 
    ce.id,
    ce.project_name,
    ce.total_cost as estimate_cost,
    ce.timeline,
    'ready_for_construction' as status,
    ce.homeowner_id,
    ...
FROM contractor_estimates ce
WHERE ce.contractor_id = ? 
AND ce.status = 'accepted'
```

### 3. Legacy Accepted Estimates (New)
```sql
SELECT 
    cse.id,
    cse.total_cost as estimate_cost,
    cse.timeline,
    'ready_for_construction' as status,
    cls.homeowner_id,
    ...
FROM contractor_send_estimates cse
LEFT JOIN contractor_layout_sends cls ON cls.id = cse.send_id
WHERE cse.contractor_id = ? 
AND cse.status = 'accepted'
```

## Changes Made

### File: `backend/api/contractor/get_contractor_projects.php`

#### 1. Added Table Creation
Ensured all required tables exist before querying:
- `contractor_estimates`
- `contractor_send_estimates`
- `contractor_layout_sends`

#### 2. Added Accepted Estimates Query
Fetches accepted estimates from `contractor_estimates` table with:
- Project name
- Total cost (as estimate_cost)
- Homeowner details
- Timeline
- Status marked as 'ready_for_construction'
- Source marked as 'contractor_estimate'

#### 3. Added Legacy Estimates Query
Fetches accepted estimates from `contractor_send_estimates` table with:
- Total cost (as estimate_cost)
- Homeowner details via join with `contractor_layout_sends`
- Status marked as 'ready_for_construction'
- Source marked as 'contractor_send_estimate'

#### 4. Merged Results
All three sources are merged into a single projects array:
```php
$projects = array_merge($projects, $accepted_estimates, $legacy_accepted_estimates);
```

#### 5. Added Source Tracking
Each project now includes:
- `source`: Indicates where the data came from
  - `'construction_project'` - Formal construction project
  - `'contractor_estimate'` - Accepted estimate from new form
  - `'contractor_send_estimate'` - Accepted estimate from legacy system
- `needs_project_creation`: Boolean flag indicating if this is an estimate that needs to be converted to a formal project

## Benefits

### 1. Accurate Cost Display
- Approved estimate costs are now automatically populated in the payment request form
- No more "No estimate" messages when estimates exist
- Contractors can immediately start requesting payments based on accepted estimates

### 2. Better Project Tracking
- All accepted estimates appear as available projects
- Clear indication of which projects need formal creation
- Source tracking helps understand project origin

### 3. Backward Compatibility
- Works with both new and legacy estimate systems
- Existing construction projects continue to work
- No data migration required

### 4. Improved User Experience
- Contractors see all their accepted work in one place
- Automatic cost population saves time
- Clear status indicators for each project

## Data Flow

### Before Fix
```
Contractor sends estimate → Homeowner accepts → Estimate stored in contractor_estimates
                                                ↓
                                          Payment form shows "No estimate"
```

### After Fix
```
Contractor sends estimate → Homeowner accepts → Estimate stored in contractor_estimates
                                                ↓
                                          get_contractor_projects.php fetches it
                                                ↓
                                          Payment form shows estimate cost
```

## Testing

### Test File
**Path:** `backend/test_contractor_projects_with_estimates.php`

**Test Coverage:**
1. ✅ Checks for accepted estimates in `contractor_estimates`
2. ✅ Checks for accepted estimates in `contractor_send_estimates`
3. ✅ Checks for construction projects
4. ✅ Tests the API endpoint
5. ✅ Verifies estimate costs are returned
6. ✅ Creates sample data if none exists

### Running the Test
```bash
php backend/test_contractor_projects_with_estimates.php
```

Or access via browser:
```
http://localhost/buildhub/backend/test_contractor_projects_with_estimates.php
```

### Expected Output
```
=== Testing Contractor Projects API with Estimates ===

✅ Testing with Contractor: John Doe (ID: 5)

--- Checking contractor_estimates table ---
Found 2 accepted estimate(s):
  - ID: 1, Project: Modern Villa, Cost: ₹4,500,000, Homeowner: Jane Smith
  - ID: 3, Project: Apartment Renovation, Cost: ₹2,500,000, Homeowner: Bob Johnson

--- Testing get_contractor_projects.php API ---
✅ API returned 2 project(s)

Project Details:
  - Name: Modern Villa
  - Estimate Cost: ₹4,500,000
  - Source: contractor_estimate
  - Needs Project Creation: Yes

✅ SUCCESS: 2 project(s) have estimate cost!
```

## Database Schema

### contractor_estimates Table
```sql
CREATE TABLE contractor_estimates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    project_name VARCHAR(255),
    location VARCHAR(255),
    total_cost DECIMAL(15,2),
    timeline VARCHAR(100),
    status ENUM('draft', 'submitted', 'accepted', 'rejected'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contractor (contractor_id),
    INDEX idx_status (status)
)
```

### contractor_send_estimates Table
```sql
CREATE TABLE contractor_send_estimates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    send_id INT NOT NULL,
    contractor_id INT NOT NULL,
    total_cost DECIMAL(15,2),
    timeline VARCHAR(255),
    status VARCHAR(32) DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(contractor_id),
    INDEX(status)
)
```

## API Response Format

### Before Fix
```json
{
  "success": true,
  "data": {
    "projects": [],
    "total_projects": 0
  }
}
```

### After Fix
```json
{
  "success": true,
  "data": {
    "projects": [
      {
        "id": 1,
        "project_name": "Modern Villa Construction",
        "homeowner_name": "Jane Smith",
        "estimate_cost": 4500000,
        "status": "ready_for_construction",
        "source": "contractor_estimate",
        "needs_project_creation": true,
        "timeline": "6 months",
        "location": "Mumbai"
      }
    ],
    "total_projects": 1
  }
}
```

## Frontend Integration

The payment request form (`SimplePaymentRequestForm.jsx`) already handles the estimate_cost field:

```javascript
if (project.estimate_cost && project.estimate_cost > 0) {
  setTotalProjectCost(project.estimate_cost.toString());
  setManualCostEntry(false);
  toast.success(`Total project cost set to ₹${project.estimate_cost.toLocaleString()} from approved estimate`);
}
```

No frontend changes are required - the fix is entirely on the backend.

## Edge Cases Handled

### 1. Multiple Estimate Sources
- If a project exists in multiple tables, all versions are returned
- Frontend can choose the most recent or appropriate one

### 2. Missing Homeowner Data
- Uses LEFT JOIN to handle cases where homeowner data might be missing
- Gracefully handles NULL values

### 3. No Accepted Estimates
- Falls back to layout requests if no estimates or projects exist
- Maintains backward compatibility

### 4. Decimal Precision
- Uses DECIMAL(15,2) for accurate cost representation
- Converts to float in API response for JSON compatibility

## Future Enhancements

### 1. Automatic Project Creation
When an estimate is accepted, automatically create a construction_projects record:
```php
// In respond_to_estimate.php
if ($action === 'accept') {
    // Create construction project from estimate
    $stmt = $pdo->prepare("
        INSERT INTO construction_projects 
        (estimate_id, contractor_id, homeowner_id, project_name, total_cost, ...)
        SELECT id, contractor_id, homeowner_id, project_name, total_cost, ...
        FROM contractor_estimates
        WHERE id = ?
    ");
    $stmt->execute([$estimate_id]);
}
```

### 2. Estimate Version Tracking
Track multiple versions of estimates for the same project:
```sql
ALTER TABLE contractor_estimates 
ADD COLUMN version INT DEFAULT 1,
ADD COLUMN parent_estimate_id INT NULL;
```

### 3. Cost Change Notifications
Notify contractors when homeowners request cost adjustments:
```php
if ($new_cost !== $original_cost) {
    sendNotification($contractor_id, 'cost_change_request', $details);
}
```

## Files Modified

1. **backend/api/contractor/get_contractor_projects.php**
   - Added queries for accepted estimates
   - Added table creation statements
   - Merged results from multiple sources
   - Added source tracking

## Files Created

1. **backend/test_contractor_projects_with_estimates.php**
   - Comprehensive test script
   - Verifies estimate retrieval
   - Creates sample data if needed

2. **ESTIMATE_COST_RETRIEVAL_FIX.md**
   - This documentation file

## Conclusion

The fix successfully resolves the issue of missing estimate costs in the payment request form. Contractors can now see their accepted estimates with the correct total cost automatically populated, enabling them to immediately start requesting stage payments without manual cost entry.

The solution is backward compatible, handles multiple estimate sources, and provides clear tracking of project origins through the source field.
