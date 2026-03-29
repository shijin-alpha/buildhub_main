# Cost & Time Overrun System - Testing Guide

## 🎯 Purpose

This guide helps you test the cost and time overrun system **without waiting for real construction to complete**. You can verify the system works correctly by simulating a complete project lifecycle with known overruns.

---

## 📋 What Gets Tested

### 1. **Time Overrun Calculation**
- ✅ Planned dates can be set
- ✅ Actual start date locks the schedule
- ✅ Actual end date triggers overrun calculation
- ✅ Formula: `((Actual Duration - Planned Duration) / Planned Duration) × 100`
- ✅ Expected Result: **11.11% overrun** (100 days vs 90 days)

### 2. **Cost Overrun Calculation**
- ✅ Original estimate is tracked
- ✅ Stage payments are summed correctly
- ✅ Custom payments are summed correctly
- ✅ Formula: `((Total Cost - Original Estimate) / Original Estimate) × 100`
- ✅ Expected Result: **6.0% overrun** (₹26,50,000 vs ₹25,00,000)

### 3. **Schedule Locking**
- ✅ Planned dates can be modified before work starts
- ✅ Once actual start date is set, planned dates are locked
- ✅ Prevents manipulation of baseline schedule

### 4. **Budget Tracking**
- ✅ Stage payments (Foundation, Structure, Finishing, Completion)
- ✅ Custom payments (Extra work, changes, additions)
- ✅ Real-time total cost calculation
- ✅ Overrun/underrun detection

---

## 🚀 Quick Start - Run the Test

### Method 1: Web Interface (Recommended)

1. **Open the test page in your browser:**
   ```
   http://localhost:3000/test_cost_time_overrun_system.html
   ```

2. **Click "Run Complete Test Suite"**

3. **View the results:**
   - Test summary with pass/fail status
   - Detailed breakdown of each test
   - Expected vs actual values
   - Verification messages

### Method 2: Direct API Call

```bash
# Using curl
curl http://localhost:3000/test_cost_time_overrun_system.php

# Or open in browser
http://localhost:3000/test_cost_time_overrun_system.php
```

### Method 3: Command Line (PHP)

```bash
php test_cost_time_overrun_system.php
```

---

## 📊 Test Scenarios

### Test Project Details

The test creates a project with these known parameters:

#### Original Estimate
```
₹25,00,000 (₹2,500,000)
```

#### Planned Schedule
```
Start Date:  February 1, 2026
End Date:    May 1, 2026
Duration:    90 days
```

#### Actual Schedule (Delayed)
```
Start Date:  February 5, 2026  (+4 days late)
End Date:    May 15, 2026      (+14 days late)
Duration:    100 days
```

#### Stage Payments (₹22,00,000)
| Stage | Amount | Status |
|-------|--------|--------|
| Foundation | ₹5,00,000 | Paid |
| Structure | ₹7,00,000 | Paid |
| Finishing | ₹6,00,000 | Pending |
| Completion | ₹4,00,000 | Pending |

#### Custom Payments (₹4,50,000) - Causes Overrun
| Description | Amount | Status |
|-------------|--------|--------|
| Extra bathroom addition | ₹1,50,000 | Paid |
| Balcony extension | ₹2,00,000 | Paid |
| Landscaping work | ₹1,00,000 | Pending |

---

## ✅ Expected Results

### Time Overrun
```
Planned Duration:  90 days
Actual Duration:   100 days
Delay:             10 days
Time Overrun:      11.11%

Formula: ((100 - 90) / 90) × 100 = 11.11%
```

### Cost Overrun
```
Original Estimate:      ₹25,00,000
Stage Payments:         ₹22,00,000
Custom Payments:        ₹4,50,000
Total Cost:             ₹26,50,000
Budget Difference:      ₹1,50,000
Cost Overrun:           6.0%

Formula: ((26,50,000 - 25,00,000) / 25,00,000) × 100 = 6.0%
```

---

## 🔍 What Each Test Verifies

### Test 1: Project Creation
- ✅ Creates test project in database
- ✅ Sets homeowner and contractor
- ✅ Sets original estimate
- ✅ Returns project ID for subsequent tests

### Test 2: Schedule Tracking
- ✅ Sets planned start and end dates
- ✅ Calculates planned duration (90 days)
- ✅ Sets actual start date (4 days late)
- ✅ Verifies schedule is locked after actual start
- ✅ Prevents modification of planned dates

### Test 3: Budget Tracking
- ✅ Creates 4 stage payment requests
- ✅ Creates 3 custom payment requests
- ✅ Sums stage payments correctly
- ✅ Sums custom payments correctly
- ✅ Calculates total project cost

### Test 4: Time Overrun Calculation
- ✅ Sets actual end date (14 days late)
- ✅ Calculates actual duration (100 days)
- ✅ Computes time overrun percentage
- ✅ Stores result in database
- ✅ Verifies calculation is accurate (11.11%)

### Test 5: Cost Overrun Calculation
- ✅ Retrieves original estimate
- ✅ Sums all stage payments
- ✅ Sums all custom payments
- ✅ Calculates total cost
- ✅ Computes cost overrun percentage
- ✅ Verifies calculation is accurate (6.0%)

### Test 6: Final Verification
- ✅ Schedule is locked
- ✅ All dates are present
- ✅ Time overrun is calculated
- ✅ Project status is 'completed'
- ✅ All data is consistent

---

## 📱 Reading the Test Results

### Summary Card
```
✅ Test Summary - ALL TESTS PASSED

Total Tests:    6
Passed:         6
Failed:         0
Success Rate:   100%
```

### Individual Test Cards

Each test shows:
- **Test Name**: What is being tested
- **Status Badge**: ✅ PASSED or ❌ FAILED
- **Details**: Specific values and calculations
- **Verification**: Confirms expected vs actual
- **Warnings**: Any issues or discrepancies

### Example Test Result
```
✅ Time Overrun Calculation - PASSED

Details:
  Planned Start:           2026-02-01
  Planned End:             2026-05-01
  Planned Duration:        90 days
  Actual Start:            2026-02-05
  Actual End:              2026-05-15
  Actual Duration:         100 days
  Delay:                   10 days
  Time Overrun:            11.11%

✅ CORRECT - Time overrun calculated accurately
```

---

## 🔧 Troubleshooting

### Test Fails: "Failed to create test project"
**Cause:** Database connection issue or missing tables

**Solution:**
1. Check database connection in `backend/config/database.php`
2. Verify `construction_projects` table exists
3. Check user permissions

### Test Fails: "Schedule should be locked"
**Cause:** Database trigger not working

**Solution:**
1. Check if trigger exists:
   ```sql
   SHOW TRIGGERS LIKE 'construction_projects';
   ```
2. Apply schedule tracking schema:
   ```bash
   php apply_schedule_tracking_migration.php
   ```

### Test Fails: Incorrect overrun percentage
**Cause:** Calculation logic issue

**Solution:**
1. Check the formulas in the test script
2. Verify database values are correct
3. Check for rounding issues

### Test Fails: Missing API endpoints
**Cause:** Files not present

**Solution:**
1. Run API verification:
   ```bash
   php verify_overrun_apis.php
   ```
2. Check which files are missing
3. Restore missing files from documentation

---

## 🧹 Cleanup After Testing

### Option 1: Delete Test Project (Recommended)
```sql
-- Get the test project ID from test results
DELETE FROM daily_progress_reports WHERE project_id = [TEST_PROJECT_ID];
DELETE FROM stage_payment_requests WHERE project_id = [TEST_PROJECT_ID];
DELETE FROM custom_payment_requests WHERE project_id = [TEST_PROJECT_ID];
DELETE FROM construction_projects WHERE id = [TEST_PROJECT_ID];
```

### Option 2: Keep for Reference
The test project can remain in the database as a reference example. It won't interfere with real projects.

---

## 📈 Verify API Endpoints

Before running tests, verify all required files exist:

```bash
# Check API endpoints and ML models
php verify_overrun_apis.php

# Or open in browser
http://localhost:3000/verify_overrun_apis.php
```

This checks:
- ✅ ML prediction API
- ✅ Budget summary API
- ✅ Schedule tracking API
- ✅ ML models (cost & time)
- ✅ Database schemas
- ✅ Frontend components
- ✅ Python scripts
- ✅ Training datasets

---

## 🎓 Understanding the Results

### What "PASSED" Means
- ✅ The calculation is mathematically correct
- ✅ The database is updated properly
- ✅ The system logic works as designed
- ✅ Expected values match actual values

### What "FAILED" Means
- ❌ Calculation is incorrect
- ❌ Database not updated
- ❌ Logic error in the system
- ❌ Expected ≠ Actual values

### Verification Messages
- **"CORRECT - Time overrun calculated accurately"**
  - System calculated 11.11% as expected
  
- **"CORRECT - Cost overrun calculated accurately"**
  - System calculated 6.0% as expected

---

## 🔄 Running Multiple Tests

You can run the test multiple times. Each run creates a new test project with a unique timestamp in the name.

**Benefits:**
- Test consistency across multiple runs
- Verify system stability
- Check for race conditions
- Validate data integrity

---

## 📊 Real-World Testing

After automated tests pass, test with real user flows:

### 1. Homeowner Flow
1. Login as homeowner (user ID 32)
2. Create custom request
3. View AI risk assessment
4. Submit project

### 2. Contractor Flow
1. Login as contractor (user ID 45)
2. Set planned dates
3. Start work (actual start date)
4. Submit daily progress
5. Request stage payments
6. Request custom payments
7. Complete project (actual end date)

### 3. Verify Results
1. Check homeowner dashboard
2. View budget summary
3. Check time overrun percentage
4. Verify cost overrun percentage

---

## 🎯 Success Criteria

Your system is working correctly if:

✅ All 6 automated tests pass
✅ Time overrun = 11.11% (±0.5%)
✅ Cost overrun = 6.0% (±0.5%)
✅ Schedule locks after actual start
✅ Budget updates in real-time
✅ All calculations are accurate

---

## 📞 Need Help?

If tests fail or you see unexpected results:

1. **Check the error messages** in the test results
2. **Run API verification** to ensure all files exist
3. **Check database schema** is up to date
4. **Review the test details** for specific values
5. **Compare expected vs actual** calculations

---

## 🎉 Next Steps

Once tests pass:

1. ✅ System is verified and working
2. ✅ Safe to use with real projects
3. ✅ Can demonstrate to stakeholders
4. ✅ Ready for production use

The automated test proves your cost and time overrun system works correctly without waiting months for real construction to complete!
