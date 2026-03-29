# Quick Test Guide - Cost & Time Overrun System

## 🚀 Fastest Way to Test (3 Steps)

### Step 1: Start Backend Server
```bash
node server.js
```
This starts the server on port 8000.

### Step 2: Run the Test
Open in your browser:
```
http://localhost:8000/run_overrun_test.php
```

### Step 3: View Results
The page will automatically:
- ✅ Create a test project
- ✅ Simulate complete construction lifecycle
- ✅ Calculate time overrun (11.11%)
- ✅ Calculate cost overrun (6.0%)
- ✅ Display all results

## 📊 What You'll See

### Expected Results:
- **Time Overrun:** 11.11% (100 days vs 90 days planned)
- **Cost Overrun:** 6.0% (₹26,50,000 vs ₹25,00,000 budget)
- **All 6 tests:** PASSED ✅

### Test Breakdown:
1. ✅ Project Creation
2. ✅ Schedule Tracking (with locking)
3. ✅ Budget Tracking (stage + custom payments)
4. ✅ Time Overrun Calculation
5. ✅ Cost Overrun Calculation
6. ✅ Final Verification

## 🔄 Alternative Methods

### Method 1: Using Batch File
```bash
test-overrun-system.bat
```

### Method 2: Direct PHP
```bash
php run_overrun_test.php > results.html
```

### Method 3: API Only (JSON)
```bash
curl http://localhost:8000/test_cost_time_overrun_system.php
```

## ✅ Success Criteria

Your system works correctly if:
- ✅ All 6 tests show "PASSED"
- ✅ Time overrun = 11.11% (±0.5%)
- ✅ Cost overrun = 6.0% (±0.5%)
- ✅ Schedule locks after actual start
- ✅ No errors displayed

## 🧹 Cleanup

After testing, you can delete the test project:
```sql
-- Use the project ID shown in results
DELETE FROM daily_progress_reports WHERE project_id = [ID];
DELETE FROM stage_payment_requests WHERE project_id = [ID];
DELETE FROM custom_payment_requests WHERE project_id = [ID];
DELETE FROM construction_projects WHERE id = [ID];
```

## 🎯 Why This Works

The test simulates a complete project in seconds:
- Creates project with ₹25L budget
- Sets 90-day schedule
- Records 100-day actual duration (10 days late)
- Adds ₹22L stage payments + ₹4.5L custom payments
- Calculates overruns automatically

This proves your system works without waiting months for real construction!

## 📞 Troubleshooting

### "Connection refused"
→ Start the backend server: `node server.js`

### "Database error"
→ Check `backend/config/database.php` settings

### "Tests failed"
→ Check the error messages in the results page

### "Wrong percentages"
→ Verify the formulas in the test script match your system

## 🎉 Next Steps

Once tests pass:
1. ✅ System is verified working
2. ✅ Safe to use with real projects
3. ✅ Can demonstrate to stakeholders
4. ✅ Ready for production

---

**Quick Access:**
- Test Page: `http://localhost:8000/run_overrun_test.php`
- API Test: `http://localhost:8000/test_cost_time_overrun_system.php`
- Verification: `http://localhost:8000/verify_overrun_apis.php`
