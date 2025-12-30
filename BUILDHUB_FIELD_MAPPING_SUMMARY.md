# BuildHub Field Mapping Summary

## Complete Field Inventory by Module

### Module 1: Authentication (2 Forms)

#### Login Form
```
Frontend: Login.jsx
Backend: /api/login.php
Fields: 2 required
├── email (email)
└── password (password)
```

#### Registration Form
```
Frontend: Register.jsx
Backend: /api/register.php
Fields: 7 total (5 required, 2 conditional)
├── firstName (text) - required
├── lastName (text) - required
├── email (email) - required, unique
├── password (password) - required, complex
├── confirmPassword (password) - required, match
├── role (select) - required
├── license (file) - conditional (contractor)
├── architectLicense (file) - conditional (architect)
└── portfolio (file) - conditional (architect)
```

---

### Module 2: User Profile (1 Form)

#### Profile Update
```
Frontend: HomeownerProfile.jsx
Backend: /api/homeowner/update_profile.php
Fields: 4 total (2 required, 2 optional)
├── first_name (text) - required
├── last_name (text) - required
├── phone (text) - optional
└── user_id (integer) - required (system)
```

---

### Module 3: Project Request (6-Step Wizard)

#### Step 0: Preliminary
```
Fields: 4 total (3 required, 1 conditional)
├── plot_size (decimal) - required
├── plot_unit (select) - required
├── building_size (decimal) - required
├── budget_range (select) - required
└── custom_budget (decimal) - conditional
```

#### Step 1: Site Details
```
Fields: 6 total (4 required, 2 optional)
├── plot_shape (select) - required
├── topography (select) - required
├── development_laws (select) - required
├── num_floors (integer) - required
├── orientation (text) - optional
└── site_considerations (textarea) - optional
```

#### Step 2: Family Needs
```
Fields: 3 total (1 required, 2 optional)
├── rooms (multi-select) - required
├── family_needs (multi-select) - optional
└── room_requirements (object) - optional
```

#### Step 3: Preferences
```
Fields: 3 total (1 required, 2 optional)
├── aesthetic (select) - required
├── material_preferences (array) - optional
└── budget_allocation (textarea) - optional
```

#### Step 4: Review
```
Auto-generated summary of previous steps
```

#### Step 5: Architect Selection
```
Fields: 1 required
└── selected_architect_ids (array) - required
```

#### Step 6: Submit
```
Fields: 8 total (1 required, 7 optional)
├── layout_type (select) - required
├── selected_layout_id (integer) - conditional
├── requirements (textarea) - optional
├── location (text) - optional
├── timeline (text) - optional
├── reference_images (array) - optional
├── site_images (array) - optional
└── room_images (object) - optional
```

**Total Request Wizard Fields: 28 fields**

---

### Module 4: Contractor Estimate (5-Step Wizard)

#### Step 0: Project
```
Fields: 2 total (1 required, 1 optional)
├── layout_request_id (integer) - required
└── notes (textarea) - optional
```

#### Step 1: Materials
```
Fields: 1 required
└── materials (textarea) - required
```

#### Step 2: Cost Breakdown
```
Fields: 1 required
└── cost_breakdown (textarea) - required
```

#### Step 3: Totals
```
Fields: 2 required
├── total_cost (decimal) - required
└── timeline (text) - required
```

#### Step 4: Review
```
Auto-generated summary
```

#### Step 5: Submit
```
Submission confirmation
```

**Total Estimate Fields: 6 fields**

---

### Module 5: Progress Tracking (3 Report Types)

#### Daily Progress Update
```
Frontend: EnhancedProgressUpdate.jsx
Backend: /api/contractor/submit_daily_progress.php
Fields: 12 total (8 required, 4 optional)
├── project_id (integer) - required
├── contractor_id (integer) - required
├── update_date (date) - required
├── construction_stage (select) - required
├── work_done_today (textarea) - required
├── incremental_completion_percentage (decimal) - required
├── working_hours (decimal) - required
├── materials_used (textarea) - optional
├── weather_condition (select) - required
├── site_issues (textarea) - optional
├── latitude (decimal) - optional
└── longitude (decimal) - optional
```

#### Labour Tracking (per daily update)
```
Database: daily_labour_tracking
Fields: 11 per entry (5 required, 6 optional)
├── daily_progress_id (integer) - required
├── worker_type (select) - required
├── worker_count (integer) - required
├── hours_worked (decimal) - required
├── overtime_hours (decimal) - optional
├── absent_count (integer) - optional
├── hourly_rate (decimal) - optional
├── total_wages (decimal) - auto-calculated
├── productivity_rating (integer) - optional
├── safety_compliance (select) - optional
└── remarks (textarea) - optional
```

#### Weekly Progress Summary
```
Backend: /api/contractor/submit_weekly_summary.php
Fields: 8 total (6 required, 2 optional)
├── project_id (integer) - required
├── contractor_id (integer) - required
├── homeowner_id (integer) - required
├── week_start_date (date) - required
├── week_end_date (date) - required
├── stages_worked (array) - required
├── start_progress_percentage (decimal) - required
├── end_progress_percentage (decimal) - required
├── total_labour_used (json) - optional
├── delays_and_reasons (textarea) - optional
└── weekly_remarks (textarea) - required
```

#### Monthly Progress Report
```
Backend: /api/contractor/submit_monthly_report.php
Fields: 9 total (6 required, 3 optional)
├── project_id (integer) - required
├── contractor_id (integer) - required
├── homeowner_id (integer) - required
├── report_month (integer) - required
├── report_year (integer) - required
├── planned_progress_percentage (decimal) - required
├── actual_progress_percentage (decimal) - required
├── milestones_achieved (array) - optional
├── labour_summary (json) - optional
├── material_summary (json) - optional
├── delay_explanation (textarea) - optional
└── contractor_remarks (textarea) - required
```

**Total Progress Fields: 40+ fields (including labour entries)**

---

### Module 6: Geo-Photo System

#### Photo Capture
```
Frontend: GeoPhotoCapture.jsx
Backend: /api/contractor/upload_geo_photo.php
Fields: 10 total (8 required, 2 optional)
├── project_id (integer) - required
├── contractor_id (integer) - required
├── photo (file) - required
├── latitude (decimal) - required
├── longitude (decimal) - required
├── accuracy (decimal) - required
├── altitude (decimal) - optional
├── heading (decimal) - optional
├── speed (decimal) - optional
├── placeName (text) - required
└── timestamp (datetime) - required
```

**Total Geo-Photo Fields: 10 fields**

---

### Module 7: Support System

#### Create Support Issue
```
Frontend: Support form
Backend: /api/support/create_issue.php
Fields: 4 total (3 required, 1 optional)
├── user_id (integer) - required (system)
├── subject (text) - required
├── category (select) - required
└── message (textarea) - required
```

#### Admin Reply
```
Backend: /api/support/admin_reply.php
Fields: 4 total (3 required, 1 optional)
├── issue_id (integer) - required
├── admin_id (integer) - required
├── reply_message (textarea) - required
└── status (select) - optional
```

**Total Support Fields: 8 fields**

---

### Module 8: Notifications

#### Create Notification
```
Backend: /api/homeowner/create_notification.php
Fields: 5 total (4 required, 1 optional)
├── user_id (integer) - required
├── title (text) - required
├── message (textarea) - required
├── type (select) - required
└── reference_id (integer) - optional
```

**Total Notification Fields: 5 fields**

---

### Module 9: Admin Dashboard

#### Material Management
```
Backend: /api/admin/add_material.php
Fields: 5 total (4 required, 1 optional)
├── material_name (text) - required
├── category (select) - required
├── unit_price (decimal) - required
├── unit (select) - required
└── description (textarea) - optional
```

#### User Status Management
```
Backend: /api/admin/update_user_status.php
Fields: 3 total (2 required, 1 optional)
├── user_id (integer) - required
├── status (select) - required
└── notes (textarea) - optional
```

**Total Admin Fields: 8 fields**

---

## Grand Total Field Count

| Module | Forms | Fields | Required | Optional |
|--------|-------|--------|----------|----------|
| Authentication | 2 | 9 | 7 | 2 |
| User Profile | 1 | 4 | 2 | 2 |
| Project Request | 1 | 28 | 12 | 16 |
| Contractor Estimate | 1 | 6 | 4 | 2 |
| Progress Tracking | 3 | 40+ | 20+ | 20+ |
| Geo-Photo | 1 | 10 | 8 | 2 |
| Support System | 2 | 8 | 6 | 2 |
| Notifications | 1 | 5 | 4 | 1 |
| Admin Dashboard | 2 | 8 | 5 | 3 |
| **TOTAL** | **14** | **118+** | **68+** | **50+** |

---

## Database Table Field Count

| Table | Fields | Key Fields | Indexed |
|-------|--------|-----------|---------|
| users | 12 | id, email | 5 |
| layout_requests | 20 | id, homeowner_id | 6 |
| contractor_proposals | 10 | id, contractor_id | 4 |
| construction_progress_updates | 15 | id, project_id | 7 |
| daily_progress_updates | 18 | id, project_id | 8 |
| daily_labour_tracking | 12 | id, daily_progress_id | 5 |
| weekly_progress_summary | 12 | id, project_id | 6 |
| monthly_progress_report | 13 | id, project_id | 6 |
| progress_milestones | 10 | id, project_id | 5 |
| progress_notifications | 10 | id, homeowner_id | 6 |
| support_issues | 8 | id, user_id | 4 |
| geo_photos | 12 | id, project_id | 5 |
| project_locations | 8 | id, project_id | 3 |

**Total Database Fields: 160+**

---

## Validation Coverage

### By Validation Type
- **Email**: 2 fields
- **Password**: 2 fields
- **Numeric Range**: 25+ fields
- **Date Range**: 8 fields
- **File Upload**: 5 fields
- **Text Length**: 30+ fields
- **Select/Enum**: 20+ fields
- **Multi-Select**: 8 fields
- **GPS Coordinates**: 4 fields
- **Cross-Field**: 15+ rules

### By Complexity
- **Simple (single rule)**: 40 fields
- **Medium (2-3 rules)**: 50 fields
- **Complex (4+ rules)**: 28+ fields

---

## API Endpoint Summary

| Endpoint | Method | Fields | Validation |
|----------|--------|--------|-----------|
| /api/login.php | POST | 2 | Email, Password |
| /api/register.php | POST | 7 | Email, Password, Files |
| /api/homeowner/update_profile.php | POST | 4 | Text, Phone |
| /api/homeowner/submit_request.php | POST | 28 | Multi-step |
| /api/contractor/submit_proposal.php | POST | 6 | Text, Numeric |
| /api/contractor/submit_daily_progress.php | POST | 12 | Numeric, Date, GPS |
| /api/contractor/submit_weekly_summary.php | POST | 8 | Date, Array |
| /api/contractor/submit_monthly_report.php | POST | 9 | Numeric, Text |
| /api/contractor/upload_geo_photo.php | POST | 10 | File, GPS |
| /api/support/create_issue.php | POST | 4 | Text, Select |
| /api/support/admin_reply.php | POST | 4 | Text, Select |
| /api/homeowner/create_notification.php | POST | 5 | Text, Select |
| /api/admin/add_material.php | POST | 5 | Text, Numeric |
| /api/admin/update_user_status.php | POST | 3 | Select, Text |

**Total Endpoints: 14**

---

## Frontend Component Field Distribution

| Component | Fields | Type |
|-----------|--------|------|
| Login.jsx | 2 | Form |
| Register.jsx | 7 | Form |
| HomeownerRequestWizard.jsx | 28 | Multi-step |
| ContractorEstimateWizard.jsx | 6 | Multi-step |
| EnhancedProgressUpdate.jsx | 40+ | Multi-section |
| GeoPhotoCapture.jsx | 10 | Capture |
| ProgressReportGenerator.jsx | 5 | Generator |
| NotificationSystem.jsx | 5 | System |
| AdminDashboard.jsx | 8 | Dashboard |

**Total Components: 9**

---

## Key Statistics

- **Total Form Fields**: 118+
- **Total Database Fields**: 160+
- **Total API Endpoints**: 14
- **Total Frontend Components**: 9
- **Total Database Tables**: 13
- **Required Fields**: 68+
- **Optional Fields**: 50+
- **Validation Rules**: 50+
- **Enum Options**: 100+
- **File Upload Fields**: 5
- **GPS Fields**: 4
- **Multi-Select Fields**: 8
- **Auto-Calculated Fields**: 5

---

## Validation Complexity Matrix

```
SIMPLE (1 rule)
├── Email format
├── Text length
├── Numeric range
└── Select validation

MEDIUM (2-3 rules)
├── Password complexity
├── Date range + format
├── File type + size
└── Cross-field comparison

COMPLEX (4+ rules)
├── Daily progress (date, numeric, GPS, business logic)
├── Labour tracking (numeric, rate deviation, safety)
├── Weekly summary (date range, array, text)
└── Monthly report (numeric, text, conditional)
```

---

## Data Flow Summary

```
USER INPUT
    ↓
FRONTEND VALIDATION
    ↓
API SUBMISSION
    ↓
BACKEND VALIDATION
    ↓
DATABASE CONSTRAINTS
    ↓
STORAGE
    ↓
NOTIFICATION/RESPONSE
```

---

## Implementation Priority

### Phase 1 (Critical)
- Authentication (2 forms, 9 fields)
- User Profile (1 form, 4 fields)
- Project Request (1 form, 28 fields)

### Phase 2 (High)
- Progress Tracking (3 forms, 40+ fields)
- Geo-Photo System (1 form, 10 fields)

### Phase 3 (Medium)
- Contractor Estimate (1 form, 6 fields)
- Support System (2 forms, 8 fields)

### Phase 4 (Low)
- Notifications (1 form, 5 fields)
- Admin Dashboard (2 forms, 8 fields)

---

## Testing Checklist

- [ ] All 118+ fields have validation tests
- [ ] All 50+ validation rules are tested
- [ ] All 14 API endpoints are tested
- [ ] All cross-field validations are tested
- [ ] All file uploads are tested
- [ ] All GPS validations are tested
- [ ] All date range validations are tested
- [ ] All numeric range validations are tested
- [ ] All enum/select validations are tested
- [ ] All error messages are tested
- [ ] All success scenarios are tested
- [ ] All edge cases are tested
- [ ] All security validations are tested
- [ ] All performance validations are tested

---

**Document Version**: 1.0
**Last Updated**: 2024
**Coverage**: 100% of identified fields and endpoints
**Status**: Complete and Ready for Implementation
