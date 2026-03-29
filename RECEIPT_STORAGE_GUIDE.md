# Receipt Storage Guide

## Where Your Receipts Are Saved

When you upload a receipt, it gets saved in **TWO places**:

### 1. Database Storage
Your receipt information is stored in these database tables:

#### **stage_payment_requests** table
- **Column**: `receipt_file_path`
- **Format**: JSON array containing file information
- **Used for**: Stage payments (Foundation, Structure, etc.)

#### **custom_payment_requests** table  
- **Column**: `receipt_file_path`
- **Format**: JSON array containing file information
- **Used for**: Custom payment requests

### 2. File System Storage
The actual receipt files are stored on the server:

**Location**: `backend/uploads/payment_receipts/[payment_id]/`

**Example paths**:
- `backend/uploads/payment_receipts/13/receipt_1769956928_0.jpeg`
- `backend/uploads/payment_receipts/15/receipt_1769958117_0.jpeg`
- `backend/uploads/payment_receipts/16/receipt_1769961852_0.jpeg`

## How to Check Your Uploaded Receipts

### Method 1: Run the Check Script
```bash
php check_uploaded_receipts.php
```

### Method 2: Use the Web Viewer
Open `view_uploaded_receipts.html` in your browser to see:
- All uploaded receipts
- File details and storage locations
- Verification status
- Direct links to view files

### Method 3: Database Query
```sql
SELECT 
    id,
    stage_name,
    requested_amount,
    receipt_file_path,
    verification_status,
    payment_date
FROM stage_payment_requests 
WHERE receipt_file_path IS NOT NULL 
AND receipt_file_path != '' 
AND receipt_file_path != 'null';
```

## Current Receipts Found

Based on the latest check, you have **3 receipts** uploaded:

1. **Payment #16** - Test Payment for Receipt Upload
   - Amount: ₹50,000.00
   - File: `1000114039.jpeg` (94,573 bytes)
   - Status: ✅ Verified
   - Location: `uploads/payment_receipts/16/receipt_1769961852_0.jpeg`

2. **Payment #13** - Foundation Work  
   - Amount: ₹376,161.00
   - File: `1000114039.jpeg` (94,573 bytes)
   - Status: ✅ Verified
   - Location: `uploads/payment_receipts/13/receipt_1769956928_0.jpeg`

3. **Payment #15** - Foundation
   - Amount: ₹213,949.00
   - File: `1000114039.jpeg` (94,573 bytes)
   - Status: ⏳ Pending
   - Location: `uploads/payment_receipts/15/receipt_1769958117_0.jpeg`

## Receipt Upload Process

1. **Upload**: Files are uploaded via `backend/api/homeowner/upload_payment_receipt.php`
2. **Storage**: Files saved to `backend/uploads/payment_receipts/[payment_id]/`
3. **Database**: File info stored as JSON in `receipt_file_path` column
4. **Verification**: Contractor can verify the receipt
5. **Status**: Updates to 'verified', 'pending', or 'rejected'

## File Information Stored

Each receipt file stores this information:
```json
{
    "original_name": "1000114039.jpeg",
    "stored_name": "receipt_1769961852_0.jpeg", 
    "file_path": "uploads/payment_receipts/16/receipt_1769961852_0.jpeg",
    "file_size": 94573,
    "file_type": "image/jpeg"
}
```

## Troubleshooting

If you can't see your receipts:

1. **Check the correct table**: Stage payments vs Custom payments
2. **Verify file exists**: Check the file path on disk
3. **Check JSON format**: Ensure `receipt_file_path` contains valid JSON
4. **Verify permissions**: Ensure upload directory is writable
5. **Check session**: Make sure you're logged in as the correct user

## Summary

✅ **Your receipts ARE being saved correctly**
✅ **Files exist on disk in the uploads folder**  
✅ **Database records are properly stored**
✅ **The system is working as expected**

The receipts are stored in the `stage_payment_requests` table in the `receipt_file_path` column as JSON data, with the actual files saved in the `backend/uploads/payment_receipts/` directory.