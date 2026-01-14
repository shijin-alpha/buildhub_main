# Room Improvement Assistant - Troubleshooting Guide

## Quick Fix Checklist

### 1. **Upload Directory Issues**
```bash
# Check if directory exists
ls -la backend/uploads/room_improvements/

# If not exists, create it
mkdir -p backend/uploads/room_improvements/

# Set proper permissions (Linux/Mac)
chmod 755 backend/uploads/room_improvements/

# For Windows, ensure IIS_IUSRS has write permissions
```

### 2. **PHP Configuration Issues**
Check your PHP settings in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
max_execution_time = 30
```

### 3. **Database Issues**
Ensure the table exists:
```bash
php backend/database/setup_room_improvement.php
```

### 4. **Session Issues**
The API now includes mock session for testing, but for production:
- Ensure user is logged in
- Check session configuration
- Verify user role is 'homeowner'

## Testing Steps

### Step 1: Test Upload Permissions
Visit: `http://localhost/buildhub/backend/test_upload_permissions.php`

Expected output:
```json
{
    "upload_dir": "uploads/room_improvements/",
    "dir_exists": true,
    "dir_readable": true,
    "dir_writable": true,
    "php_upload_max_filesize": "10M",
    "php_post_max_size": "10M",
    "test_write": true
}
```

### Step 2: Test with Debug Version
Open: `test_room_improvement_debug.html`
- Upload a small image (< 1MB)
- Check debug information for detailed error messages

### Step 3: Test with Main Version
Open: `test_room_improvement_assistant.html`
- Should work without debug information

### Step 4: Test in Dashboard
- Navigate to Homeowner Dashboard
- Click "Room Improvement Assistant" in sidebar
- Test the full workflow

## Common Issues & Solutions

### Issue 1: "Failed to upload image"
**Causes:**
- Directory doesn't exist
- No write permissions
- PHP upload limits exceeded

**Solutions:**
1. Create directory: `mkdir backend/uploads/room_improvements`
2. Set permissions: `chmod 755 backend/uploads/room_improvements`
3. Check PHP limits in `php.ini`

### Issue 2: "User not authenticated"
**Causes:**
- No active session
- User not logged in
- Session expired

**Solutions:**
1. For testing: API now includes mock session
2. For production: Ensure proper login flow
3. Check session configuration

### Issue 3: "Network error"
**Causes:**
- Server not running
- Incorrect API path
- CORS issues

**Solutions:**
1. Ensure Apache/Nginx is running
2. Check API path: `/buildhub/backend/api/homeowner/analyze_room_improvement.php`
3. Verify CORS headers in API

### Issue 4: "Only JPG and PNG images are allowed"
**Causes:**
- Wrong file type
- Browser MIME type detection issues

**Solutions:**
1. Use only .jpg, .jpeg, .png files
2. Check file extension matches content
3. Try different image files

### Issue 5: "Image file size must be less than 5MB"
**Causes:**
- File too large
- PHP upload limits

**Solutions:**
1. Resize image before upload
2. Increase PHP limits if needed
3. Use image compression tools

## File Structure Verification

Ensure these files exist:
```
backend/
├── api/homeowner/
│   ├── analyze_room_improvement.php ✓
│   └── debug_room_improvement.php ✓
├── uploads/
│   └── room_improvements/ ✓ (must be writable)
├── database/
│   ├── create_room_improvement_table.sql ✓
│   └── setup_room_improvement.php ✓
└── test_upload_permissions.php ✓

frontend/src/
├── components/
│   ├── RoomImprovementAssistant.jsx ✓
│   └── HomeownerDashboard.jsx ✓ (modified)
└── styles/
    ├── RoomImprovementAssistant.css ✓
    └── HomeownerDashboard.css ✓ (modified)

Root/
├── test_room_improvement_assistant.html ✓
├── test_room_improvement_debug.html ✓
└── ROOM_IMPROVEMENT_TROUBLESHOOTING.md ✓ (this file)
```

## Browser Console Debugging

Open browser Developer Tools (F12) and check:

### Console Tab
Look for JavaScript errors:
- Network request failures
- File validation errors
- API response errors

### Network Tab
Check API requests:
- Request method: POST
- Request URL: correct path
- Response status: 200
- Response content: JSON with success/error

### Application Tab
Check session storage:
- User session data
- Authentication status

## Server-Side Debugging

### PHP Error Logs
Check your PHP error log for:
- File upload errors
- Database connection issues
- Permission errors

### Apache/Nginx Logs
Check web server logs for:
- 404 errors (wrong path)
- 500 errors (server issues)
- Permission denied errors

## Production Deployment Notes

### Security Considerations
1. Remove mock session code from production API
2. Implement proper authentication checks
3. Add rate limiting for file uploads
4. Validate file content, not just extension
5. Use secure file storage location

### Performance Optimization
1. Implement image compression
2. Add file cleanup for old uploads
3. Consider cloud storage for images
4. Add caching for analysis results

### Monitoring
1. Log all upload attempts
2. Monitor disk space usage
3. Track analysis success rates
4. Set up error alerting

## Contact & Support

If issues persist after following this guide:
1. Check the implementation documentation
2. Review the debug output carefully
3. Test with minimal example files
4. Verify server configuration

The feature is designed to be robust and provide clear error messages to help identify and resolve issues quickly.