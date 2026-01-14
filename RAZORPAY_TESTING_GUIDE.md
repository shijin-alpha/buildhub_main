# Razorpay Testing Guide 🧪

## Quick Test Commands

### 1. Check System Status
```bash
php backend/test_razorpay_system.php
```
**Expected:** All tests pass ✅

### 2. Check Payment ID 14
```bash
php backend/check_payment_14_status.php
```
**Expected:** Status = paid, no blocking payments ✅

### 3. Verify Razorpay Order
```bash
php backend/verify_razorpay_order.php
```
**Expected:** Order status = paid ✅

### 4. Check Payment Completion
```bash
php backend/check_payment_completion.php
```
**Expected:** Payment captured, database synced ✅

## Browser Testing

### Step 1: Clear Cache
```
1. Press Ctrl + Shift + Delete
2. Select "Cached images and files"
3. Click "Clear data"
4. Press Ctrl + F5 to hard refresh
```

### Step 2: Login as Homeowner
```
Username: [homeowner email]
Password: [homeowner password]
```

### Step 3: Navigate to Payments
```
1. Go to "Construction Progress" section
2. Click "Payment Requests" tab
3. Find any unpaid payment request
```

### Step 4: Test Razorpay Payment
```
1. Click "Pay with Razorpay" button
2. Razorpay checkout should open (no 400 error)
3. Complete payment with test card
4. Payment should succeed
5. Check "Payment History" tab
6. Payment should appear as "Paid"
```

## Test Card Details (Razorpay Test Mode)

### Successful Payment
```
Card Number: 4111 1111 1111 1111
CVV: Any 3 digits
Expiry: Any future date
Name: Any name
```

### Failed Payment (for testing)
```
Card Number: 4000 0000 0000 0002
CVV: Any 3 digits
Expiry: Any future date
```

### Test UPI
```
UPI ID: success@razorpay
```

### Test Netbanking
```
Bank: Any test bank
Status: Select "Success"
```

## Amount Testing

### Valid Amounts (Should Work ✅)
```
₹1 (minimum)
₹250
₹1,000
₹10,000
₹1,00,000
₹10,00,000
₹20,00,000 (maximum in test mode)
```

### Invalid Amounts (Should Fail ❌)
```
₹0 (below minimum)
₹0.50 (below minimum)
₹20,00,001 (above test mode limit)
```

## Database Verification

### Check Payment Request Status
```sql
SELECT * FROM stage_payment_requests WHERE id = 14;
```
**Expected:** status = 'paid'

### Check Transaction Status
```sql
SELECT * FROM stage_payment_transactions WHERE payment_request_id = 14;
```
**Expected:** payment_status = 'completed', razorpay_payment_id not NULL

### Check Alternative Payments
```sql
SELECT * FROM alternative_payments 
WHERE reference_id = 14 AND payment_type = 'stage_payment';
```
**Expected:** All cancelled or no blocking payments

## Troubleshooting

### Issue: 400 Error from Razorpay
**Check:**
1. Are there pending alternative payments?
   ```bash
   php backend/check_payment_14_status.php
   ```
2. Is the amount valid (₹1 to ₹20 lakhs)?
3. Are Razorpay keys configured correctly?

**Fix:**
- Alternative payments are automatically cancelled
- Check payment_limits.php for amount validation
- Verify razorpay_config.php has correct keys

### Issue: Payment Successful but Database Not Updated
**Check:**
1. Is verify_stage_payment.php using correct table names?
2. Did the verification endpoint get called?
3. Check browser console for errors

**Fix:**
- Table names fixed: `stage_payment_requests` (not `project_stage_payment_requests`)
- Frontend rebuilt with correct API calls
- Clear browser cache and retry

### Issue: Payment Stuck in "Created" Status
**Check:**
1. Was payment actually completed on Razorpay?
   ```bash
   php backend/check_payment_completion.php
   ```
2. Did verification endpoint fail?

**Fix:**
- Use sync script to update database:
  ```bash
  php backend/sync_payment_14.php
  ```

## Expected Console Output

### Successful Payment Flow

#### 1. Initiate Payment
```javascript
POST /api/homeowner/initiate_stage_payment.php
Response: {
  success: true,
  data: {
    razorpay_order_id: "order_...",
    razorpay_key_id: "rzp_test_...",
    amount: 25000 // in paise
  }
}
```

#### 2. Razorpay Checkout
```javascript
Razorpay checkout opened
Payment method selected
Payment processing...
Payment successful
```

#### 3. Verify Payment
```javascript
POST /api/homeowner/verify_stage_payment.php
Response: {
  success: true,
  message: "Payment completed successfully",
  data: {
    razorpay_payment_id: "pay_...",
    payment_date: "2026-01-14 ..."
  }
}
```

#### 4. UI Update
```javascript
Payment moved to Payment History
Status: Paid
Verification: Pending
```

## Common Errors and Solutions

### Error: "Payment request not found"
**Cause:** Payment request ID doesn't exist or already paid
**Solution:** Check payment request status in database

### Error: "Missing Razorpay payment details"
**Cause:** Verification called without payment details
**Solution:** Ensure Razorpay success callback passes all parameters

### Error: "Payment signature verification failed"
**Cause:** Invalid signature or wrong keys
**Solution:** Verify Razorpay keys in razorpay_config.php

### Error: "Payment amount exceeds limit"
**Cause:** Amount above ₹20 lakhs in test mode
**Solution:** Use split payment system or switch to live mode

## Success Indicators

### ✅ Payment Successful When:
1. Razorpay checkout opens without 400 error
2. Payment completes on Razorpay
3. Database updates automatically
4. Payment appears in Payment History
5. Status shows as "Paid"
6. Contractor receives notification

### ❌ Payment Failed When:
1. 400 error from Razorpay API
2. Payment stuck in "created" status
3. Payment not appearing in history
4. Database not updated
5. Contractor not notified

## Quick Fixes

### Fix 1: Clear Alternative Payments
```sql
UPDATE alternative_payments 
SET payment_status = 'cancelled'
WHERE reference_id = 14 
AND payment_type = 'stage_payment'
AND payment_status IN ('initiated', 'pending');
```

### Fix 2: Sync Orphaned Payment
```bash
php backend/sync_payment_14.php
```

### Fix 3: Rebuild Frontend
```bash
cd frontend
npm run build
```

### Fix 4: Clear Browser Cache
```
Ctrl + Shift + Delete
Ctrl + F5
```

## Test Checklist

### Before Testing:
- [ ] Razorpay keys configured
- [ ] Database connected
- [ ] Frontend built
- [ ] Browser cache cleared

### During Testing:
- [ ] Razorpay checkout opens
- [ ] Payment completes successfully
- [ ] No console errors
- [ ] Database updates

### After Testing:
- [ ] Payment in Payment History
- [ ] Status shows "Paid"
- [ ] Contractor notified
- [ ] Receipt can be verified

## Support

### Check Logs:
```bash
# PHP error log
tail -f C:/xampp/php/logs/php_error_log

# Apache error log
tail -f C:/xampp/apache/logs/error.log
```

### Run Diagnostics:
```bash
php backend/test_razorpay_system.php
```

### Database Query:
```sql
SELECT spr.id, spr.stage_name, spr.requested_amount, spr.status,
       spt.razorpay_payment_id, spt.payment_status,
       ap.payment_method, ap.payment_status as alt_status
FROM stage_payment_requests spr
LEFT JOIN stage_payment_transactions spt ON spr.id = spt.payment_request_id
LEFT JOIN alternative_payments ap ON spr.id = ap.reference_id
WHERE spr.id = 14;
```

## Summary

All Razorpay issues fixed:
1. ✅ Alternative payment conflicts resolved
2. ✅ Verification endpoint corrected
3. ✅ Database sync working
4. ✅ Payment flow complete
5. ✅ Ready for testing

**Test the system and verify everything works!**

---

**Last Updated:** January 14, 2026
**Status:** Ready for Testing ✅
