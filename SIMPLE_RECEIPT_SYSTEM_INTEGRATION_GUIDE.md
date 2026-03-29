# Simple Receipt System Integration Guide

## Overview
A simple receipt upload system where contractors can upload any receipt and homeowners can view them in their documents section. No complex payment logic - just upload, view, and download.

## ✅ What's Been Created

### Backend APIs
- `backend/api/contractor/upload_simple_receipt.php` - Upload receipts
- `backend/api/homeowner/get_contractor_receipts.php` - View receipts
- `backend/database/contractor_receipts_schema.sql` - Database schema

### Frontend Components
- `frontend/src/components/SimpleReceiptUpload.jsx` - Contractor upload form
- `frontend/src/components/HomeownerReceiptViewer.jsx` - Homeowner viewing interface
- `frontend/src/styles/SimpleReceiptUpload.css` - Upload form styles
- `frontend/src/styles/HomeownerReceiptViewer.css` - Viewer styles

### Database
- `contractor_receipts` table created
- Upload directory: `backend/uploads/contractor_receipts/[PROJECT_ID]/`

## 🔧 Integration Steps

### 1. Add to Contractor Dashboard

Edit `frontend/src/components/ContractorDashboard.jsx`:

```jsx
// Add import
import SimpleReceiptUpload from './SimpleReceiptUpload.jsx';

// Add to the dashboard (example placement)
<div className="dashboard-section">
  <h3>Upload Receipt</h3>
  <SimpleReceiptUpload contractorId={contractorId} />
</div>
```

### 2. Add to Homeowner Dashboard

Edit your homeowner dashboard component:

```jsx
// Add import
import HomeownerReceiptViewer from './HomeownerReceiptViewer.jsx';

// Add to documents/files section
<div className="documents-section">
  <HomeownerReceiptViewer homeownerId={homeownerId} />
</div>
```

### 3. Add Navigation Tab (Optional)

Add a "Documents" or "Files" tab to the homeowner navigation that shows the `HomeownerReceiptViewer` component.

## 🧪 Testing

1. **Test Page**: Open `test_simple_receipt_system.html` in your browser
2. **Manual Testing**:
   - Login as contractor
   - Select a project
   - Upload a receipt (JPG, PNG, or PDF)
   - Login as homeowner
   - View the uploaded receipt in documents section

## 📁 File Structure

```
backend/
├── api/
│   ├── contractor/
│   │   └── upload_simple_receipt.php
│   └── homeowner/
│       └── get_contractor_receipts.php
├── database/
│   └── contractor_receipts_schema.sql
└── uploads/
    └── contractor_receipts/
        └── [PROJECT_ID]/
            └── [receipt_files]

frontend/src/
├── components/
│   ├── SimpleReceiptUpload.jsx
│   └── HomeownerReceiptViewer.jsx
└── styles/
    ├── SimpleReceiptUpload.css
    └── HomeownerReceiptViewer.css
```

## 🔄 Workflow

1. **Contractor uploads receipt**:
   - Selects project from dropdown
   - Chooses file (JPG, PNG, PDF max 10MB)
   - Adds optional description
   - Clicks "Upload Receipt"

2. **System processes upload**:
   - Validates file type and size
   - Creates unique filename
   - Stores in project-specific directory
   - Saves metadata to database

3. **Homeowner views receipts**:
   - Sees all receipts from all contractors
   - Views receipt details (project, contractor, date, description)
   - Can view receipt in modal (images/PDFs)
   - Can download original file

## 🎯 Features

### For Contractors
- ✅ Simple upload form
- ✅ Project selection dropdown
- ✅ File validation (type, size)
- ✅ Optional description field
- ✅ Upload progress feedback

### For Homeowners
- ✅ Grid view of all receipts
- ✅ Receipt statistics (total count, file size, projects)
- ✅ Detailed receipt information
- ✅ Modal viewer for images and PDFs
- ✅ Download functionality
- ✅ Responsive design

## 🔒 Security Features

- ✅ File type validation (only JPG, PNG, PDF)
- ✅ File size limits (10MB max)
- ✅ Project access verification
- ✅ Session-based authentication
- ✅ Unique filename generation
- ✅ Directory traversal protection

## 🚀 Ready to Use

The system is complete and ready for integration. Just add the components to your existing dashboards and test the functionality!

## 📝 Database Schema

```sql
CREATE TABLE contractor_receipts (
  id int(11) AUTO_INCREMENT PRIMARY KEY,
  project_id int(11) NOT NULL,
  contractor_id int(11) NOT NULL,
  homeowner_id int(11) NOT NULL,
  file_path varchar(500) NOT NULL,
  original_filename varchar(255) NOT NULL,
  file_size int(11) NOT NULL,
  mime_type varchar(100) NOT NULL,
  description text DEFAULT NULL,
  upload_date timestamp DEFAULT CURRENT_TIMESTAMP,
  -- Foreign key constraints to existing tables
  FOREIGN KEY (project_id) REFERENCES construction_projects(id),
  FOREIGN KEY (contractor_id) REFERENCES users(id),
  FOREIGN KEY (homeowner_id) REFERENCES users(id)
);
```

That's it! Simple, clean, and functional receipt system ready to use. 🎉