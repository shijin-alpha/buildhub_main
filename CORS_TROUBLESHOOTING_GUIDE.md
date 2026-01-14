# 🔧 CORS & Connection Troubleshooting Guide

## ❌ Problem: CORS Error When Testing

**Error Message:**
```
Access to fetch at 'file:///C:/buildhub/backend/api/homeowner/analyze_room_improvement.php' from origin 'null' has been blocked by CORS policy
```

## 🎯 Root Cause

The issue occurs when opening HTML files directly from the file system (`file://` protocol) instead of through a web server (`http://` protocol).

## ✅ Solutions

### Solution 1: Use Web Server (Recommended)

1. **Start your web server (XAMPP/Apache)**
   ```bash
   # Make sure Apache is running in XAMPP Control Panel
   ```

2. **Place test file in web directory**
   ```
   C:\xampp\htdocs\buildhub\test_collaborative_ai_web.html
   ```

3. **Access via HTTP**
   ```
   http://localhost/buildhub/test_collaborative_ai_web.html
   ```

### Solution 2: Test Connection First

1. **Test basic connection**
   ```
   http://localhost/buildhub/test_connection.php
   ```

2. **Verify AI service is running**
   ```
   http://127.0.0.1:8000/health
   ```

### Solution 3: Check System Status

1. **Verify XAMPP is running**
   - Apache: ✅ Started
   - MySQL: ✅ Started (if needed)

2. **Verify AI service is running**
   ```bash
   cd C:\xampp\htdocs\buildhub\ai_service
   python main.py
   ```

3. **Check ports**
   - Web server: http://localhost (port 80)
   - AI service: http://127.0.0.1:8000 (port 8000)

## 🔍 Step-by-Step Testing

### Step 1: Test Web Server
```
http://localhost/buildhub/test_connection.php
```
**Expected Result:** JSON response with server info

### Step 2: Test AI Service
```
http://127.0.0.1:8000/health
```
**Expected Result:** 
```json
{
  "status": "healthy",
  "components": {
    "object_detector": true,
    "spatial_analyzer": true,
    "visual_processor": true,
    "rule_engine": true,
    "conceptual_generator": true
  }
}
```

### Step 3: Test Collaborative AI Pipeline
```
http://localhost/buildhub/test_collaborative_ai_web.html
```
**Expected Result:** Web interface with system status checks

## 🚨 Common Issues & Fixes

### Issue 1: "AI Service: Offline"
**Fix:**
```bash
cd C:\xampp\htdocs\buildhub\ai_service
python main.py
```

### Issue 2: "Web Server: Check if accessed via HTTP"
**Fix:**
- Don't open HTML files directly (double-click)
- Use: `http://localhost/buildhub/test_collaborative_ai_web.html`

### Issue 3: "404 Not Found" for PHP files
**Fix:**
- Check XAMPP Apache is running
- Verify file path: `C:\xampp\htdocs\buildhub\backend\api\homeowner\analyze_room_improvement.php`

### Issue 4: "Connection refused" to AI service
**Fix:**
```bash
# Check if AI service is running
netstat -an | findstr :8000

# If not running, start it:
cd C:\xampp\htdocs\buildhub\ai_service
python main.py
```

## 📋 Pre-Test Checklist

- [ ] XAMPP Apache is running
- [ ] AI service is running (`python main.py` in ai_service folder)
- [ ] Accessing via HTTP (not file://)
- [ ] Test files are in correct location
- [ ] Gemini API key configured (optional but recommended)

## 🎯 Quick Test Commands

### Test Web Server
```bash
curl http://localhost/buildhub/test_connection.php
```

### Test AI Service
```bash
curl http://127.0.0.1:8000/health
```

### Test Full Pipeline (PowerShell)
```powershell
Invoke-WebRequest -Uri http://localhost/buildhub/test_collaborative_ai_web.html -UseBasicParsing
```

## 🔧 File Locations

### Test Files (Place in web directory)
```
C:\xampp\htdocs\buildhub\
├── test_collaborative_ai_web.html    ← Use this for testing
├── test_connection.php                ← Connection test
└── backend\api\homeowner\
    └── analyze_room_improvement.php   ← Main API endpoint
```

### AI Service Files
```
C:\xampp\htdocs\buildhub\ai_service\
├── main.py                           ← Start with: python main.py
├── .env                              ← Configure API keys here
└── modules\
    └── conceptual_generator_simple.py
```

## ✅ Success Indicators

### Web Interface Loads
- System status shows green indicators
- Form elements are interactive
- No console errors

### AI Service Healthy
```json
{
  "status": "healthy",
  "components": {
    "object_detector": true,
    "spatial_analyzer": true,
    "visual_processor": true,
    "rule_engine": true,
    "conceptual_generator": true
  }
}
```

### Pipeline Test Success
- All 4 stages complete
- Gemini description generated (if API key configured)
- No CORS errors in browser console

## 🎉 Next Steps After Fixing

1. **Configure Gemini API Key** (optional)
   ```
   # Edit: C:\xampp\htdocs\buildhub\ai_service\.env
   GEMINI_API_KEY=your_actual_api_key_here
   ```

2. **Test with Real Room Image**
   - Upload a clear room photo
   - Select appropriate room type
   - Add improvement notes
   - Run full pipeline test

3. **Use Enhanced Room Improvement Assistant**
   - Access main application via HTTP
   - Upload room images as usual
   - Enjoy collaborative AI features

## 📞 Still Having Issues?

If you're still experiencing problems:

1. Check browser console for detailed error messages
2. Verify all services are running (Apache + AI service)
3. Test each component individually
4. Ensure you're using HTTP (not file://) protocol
5. Check Windows Firewall isn't blocking ports 80 or 8000