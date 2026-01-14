# Payment Verification - Session Fix

## Current Status

**Error:** 400 Bad Request - "Unauthorized: Contractor login required"

**Root Cause:** Session is not being passed from frontend to backend API

## What Was Fixed

### 1. Removed Non-Existent File
**File:** `backend/api/contractor/verify_payment_receipt.php`

**Removed:**
```php
require_once '../../utils/session_helper.php';
```

**Reason:** The file `session_helper.php` doesn't exist and was causing a 500 error.

### 2. Added Better Logging
**File:** `frontend/src/components/PaymentHistory.jsx`

Added console logging to debug:
- Request parameters
- Response status
- Response headers
- Response data
- Error details

### 3. Created Session Check Endpoint
**File:** `backend/api/contractor/check_session.php`

Use this to verify if contractor is logged in:
```
http://localhost/buildhub/backend/api/contractor/check_session.php
```

## How to Test & Debug

### Step 1: Check if You're Logged In

Open browser console and run:
```javascript
fetch('/buildhub/backend/api/contractor/check_session.php', {
    credentials: 'include'
})
.then(r => r.json())
.then(console.log)
```

**Expected Output:**
```json
{
  "session_id": "abc123...",
  "user_id": 29,
  "user_type": "contractor",
  "is_logged_in": true,
  "is_contractor": true
}
```

**If you see `null` values:**
- You're not logged in
- Session expired
- Need to log in again

### Step 2: Try Verification Again

1. Make sure you're logged in as contractor
2. Go to Payment History section
3. Click "Verify Payment"
4. Open browser console (F12)
5. Look for the console logs:
   ```
   Verifying payment: {paymentId: 13, verificationStatus: "verified", ...}
   Response status: 400
   Response data: {success: false, message: "Unauthorized..."}
   ```

### Step 3: If Still Getting "Unauthorized"

**Option A: Log Out and Log In Again**
1. Click logout
2. Clear browser cache (Ctrl + Shift + Delete)
3. Log in as contractor again
4. Try verification

**Option B: Check Session Cookie**
1. Open DevTools (F12)
2. Go to Application tab
3. Look for Cookies
4. Check if `PHPSESSID` cookie exists
5. If not, session isn't being created

**Option C: Check Server Session**
1. Navigate to: `http://localhost/buildhub/backend/api/contractor/check_session.php`
2. Verify `is_contractor` is `true`
3. Verify `user_id` matches your contractor ID

## Common Issues

### Issue 1: Session Not Created
**Symptoms:** `PHPSESSID` cookie doesn't exist

**Solutions:**
- Check if PHP session is enabled
- Check browser allows cookies
- Try different browser
- Check if session.save_path is writable

### Issue 2: Session Expires Quickly
**Symptoms:** Works initially, then fails

**Solutions:**
- Increase session timeout in php.ini
- Check session.gc_maxlifetime
- Avoid long idle times

### Issue 3: CORS Issues
**Symptoms:** Cookies not sent cross-origin

**Solutions:**
- Ensure `credentials: 'include'` in fetch
- Check Access-Control-Allow-Credentials header
- Use same domain for frontend and backend

## Files Modified

1. **Backend:**
   - `backend/api/contractor/verify_payment_receipt.php` - Removed session_helper.php
   - `backend/api/contractor/check_session.php` - Created session checker

2. **Frontend:**
   - `frontend/src/components/PaymentHistory.jsx` - Added debug logging

## Next Steps

1. **Clear browser cache completely**
2. **Log out and log in again as contractor**
3. **Check session using the check_session.php endpoint**
4. **Try verification with console open to see logs**
5. **Share the console output if still failing**

## Debug Checklist

When you try verification, check console for:
- [ ] "Verifying payment" log appears
- [ ] Response status is shown
- [ ] Response data is logged
- [ ] Error message is clear
- [ ] Session check shows is_contractor: true

## Expected Console Output (Success)

```
Verifying payment: {paymentId: 13, verificationStatus: "verified", notes: "..."}
Response status: 200
Response headers: {content-type: "application/json", ...}
Response data: {success: true, message: "Payment receipt verified successfully", ...}
```

## Expected Console Output (Session Issue)

```
Verifying payment: {paymentId: 13, verificationStatus: "verified", notes: "..."}
Response status: 400
Response headers: {content-type: "application/json", ...}
Response data: {success: false, message: "Unauthorized: Contractor login required"}
Verification failed: Unauthorized: Contractor login required
```

## Build Status
✅ session_helper.php requirement removed
✅ Debug logging added
✅ Session checker created
✅ Frontend rebuilt
✅ Ready for testing

## Important Notes

- The API is working correctly
- The issue is session/authentication
- Make sure you're logged in as contractor
- Check browser console for detailed logs
- Use check_session.php to verify login status
