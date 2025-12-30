# Testing the OCR Verification System

## ✅ System Status - UPDATED
- **Frontend Server**: ✅ Running on http://localhost:3001/ (Port changed!)
- **OCR Dependencies**: ✅ Installed and working
- **Backend APIs**: ✅ Ready for testing
- **Missing Files**: ✅ Fixed (added index.html)
- **Google Button**: ✅ Fixed CSS issues

## How to Test the OCR Verification Feature

### Step 1: Access the Admin Panel
1. Open your browser and go to `http://localhost:3001/` (Note: Port 3001!)
2. You should now see the BuildHub homepage with Google Sign-In button
3. Navigate to `http://localhost:3001/admin` for admin login
4. Or click on admin login from the main page
5. Login with admin credentials
6. Navigate to "Pending User Approvals"

### Step 2: Test with Existing Users
If you have pending users with uploaded documents:
1. Look for users with document icons (📄)
2. Click the "🔍 Verify OCR Data" button
3. The OCR Verification Modal will open

### Step 3: Test OCR Processing
If no OCR data exists:
1. Click "Process Document with OCR" button
2. Wait for processing to complete
3. The modal will show extracted data and comparison results

### Step 4: Verify Comparison Results
The system will show:
- **Overall Match Score**: Percentage and level (HIGH/MEDIUM/LOW)
- **Field Comparisons**: Name vs OCR extracted name
- **Visual Indicators**: ✅ ⚠️ ❌ for match status
- **Recommendations**: System suggestions for approval/rejection

### Step 5: Make Verification Decision
1. Review the comparison results
2. Add admin notes explaining your decision
3. Choose one of three actions:
   - **✅ Approve User**: If name matches well
   - **📄 Request Reupload**: If document is unclear
   - **❌ Reject User**: If significant mismatches

### Step 6: Verify Email Notifications
Check that users receive appropriate emails based on your decision.

## Expected Behavior

### For Good Matches (Name similarity > 80%)
- Overall match score: HIGH
- Name field: EXACT_MATCH or STRONG_MATCH (✅)
- Recommendation: "Strong match - recommend approval"

### For Poor Matches (Name similarity < 50%)
- Overall match score: LOW
- Name field: NO_MATCH (❌)
- Recommendation: "Poor match - recommend rejection"

### For Unclear Documents
- OCR confidence: LOW
- Few or no extracted fields
- Recommendation: "Request reupload for better quality"

## API Endpoints to Test

### 1. Get OCR Verification Data
```
GET /buildhub/backend/api/admin/get_ocr_verification.php?user_id=1
```

### 2. Trigger OCR Processing
```
POST /buildhub/backend/api/admin/trigger_ocr.php
Body: {"user_id": 1}
```

### 3. Submit Verification Decision
```
POST /buildhub/backend/api/admin/verify_ocr_data.php
Body: {
  "user_id": 1,
  "action": "approve",
  "admin_notes": "Name matches perfectly"
}
```

## Troubleshooting

### If OCR Processing Fails
1. Check if Tesseract is installed: `tesseract --version`
2. Verify Python dependencies: `pip list | grep -E "(opencv|pillow|pytesseract)"`
3. Check document file exists and is readable

### If Modal Doesn't Open
1. Check browser console for JavaScript errors
2. Verify API endpoints are accessible
3. Check network tab for failed requests

### If No Fields Are Extracted
1. Document may be too blurry or low quality
2. Text may be in unsupported language/format
3. Try with a clearer document

## Sample Test Documents
For testing, use documents with:
- **Clear text**: High resolution, good contrast
- **Standard formats**: Aadhaar cards, licenses, certificates
- **Readable fonts**: Avoid handwritten or stylized text

## Success Criteria
✅ OCR processing completes without errors
✅ Name comparison shows accurate similarity scores
✅ Visual indicators display correctly
✅ Admin can make verification decisions
✅ Users receive appropriate email notifications
✅ Audit trail is logged in database

The OCR verification system is now ready for testing! The key feature is the intelligent comparison between user-entered names and OCR-extracted names from documents, helping admins make informed verification decisions.