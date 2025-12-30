# BuildHub Comprehensive Field Analysis

## Executive Summary

This document provides a complete inventory of all form fields, database fields, and input fields across the BuildHub platform. It covers user registration/login, project creation, progress reporting, photo uploads, support systems, and admin functionality.

---

## 1. AUTHENTICATION & USER MANAGEMENT

### 1.1 Login Form Fields
**Frontend Component:** `frontend/src/components/Login.jsx`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| email | text/email | Valid email format, no spaces | Yes | Accepts any valid email domain |
| password | password | Min 8 chars, no spaces, special char | Yes | Except admin (shijinthomas369@gmail.com) |

**Backend Endpoint:** `backend/api/login.php`
- Accepts: email, password, google (boolean)
- Returns: user object with role, redirect URL

### 1.2 Registration Form Fields
**Frontend Component:** `frontend/src/components/Register.jsx`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| firstName | text | No leading spaces, max 100 chars | Yes | Sanitized for whitespace |
| lastName | text | No leading spaces, max 100 chars | Yes | Sanitized for whitespace |
| email | email | Valid format, unique, no spaces | Yes | Checked against existing users |
| password | password | 8+ chars, letter, number, special char | Yes | No spaces allowed |
| confirmPassword | password | Must match password | Yes | Real-time validation |
| role | select | homeowner/contractor/architect | Yes | Determines required documents |
| license | file | PDF/JPG/PNG, max 5MB | Conditional | Required for contractors |
| architectLicense | file | PDF/JPG/PNG, max 5MB | Conditional | Required for architects |
| portfolio | file | PDF/JPG/PNG, max 5MB | Conditional | Required for architects |

**Backend Endpoint:** `backend/api/register.php`
- Handles file uploads for professional documents
- Creates user with pending verification status

### 1.3 Profile Update Fields
**Frontend Component:** `frontend/src/components/HomeownerProfile.jsx`
**Backend Endpoint:** `backend/api/homeowner/update_profile.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| first_name | text | Max 100 chars | Yes | |
| last_name | text | Max 100 chars | Yes | |
| phone | text | Valid phone format | No | |
| user_id | integer | Must exist in users table | Yes | |

---

## 2. PROJECT CREATION & MANAGEMENT

### 2.1 Homeowner Request Wizard Fields
**Frontend Component:** `frontend/src/components/HomeownerRequestWizard.jsx`
**Backend Endpoint:** `backend/api/homeowner/submit_request.php`

#### Step 0: Preliminary Information
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| plot_size | decimal | > 0, numeric | Yes | In cents/acres/sqft |
| plot_unit | select | cents/acres/sqft | Yes | Unit selector |
| building_size | decimal | > 0, numeric | Yes | In sqft |
| budget_range | select | Predefined ranges | Yes | 5-10L to 5+Cr |
| custom_budget | decimal | > 0 if Custom selected | Conditional | For custom budget |

#### Step 1: Site Details
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| plot_shape | select | Regular/Irregular/L-shaped/etc | Yes | |
| topography | select | Flat/Sloped/Hilly/etc | Yes | |
| development_laws | select | Predefined options | Yes | |
| num_floors | integer | 1-10 | Yes | Number of floors |
| orientation | text | Max 200 chars | No | Site orientation preferences |
| site_considerations | textarea | Max 1000 chars | No | Additional considerations |

#### Step 2: Family Needs
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| rooms | array | Multi-select | Yes | At least 1 room type |
| family_needs | array | Multi-select | No | Family requirements |
| room_requirements | object | Key-value pairs | No | Room counts per type |

**Room Types Available:**
- Master Bedroom (max 1 per floor)
- Bedrooms (max 8)
- Attached Bathrooms (max 8)
- Common Bathrooms (max 6)
- Living Room (max 3)
- Dining Room (max 2)
- Kitchen (max 2, ground floor only)
- Study Area (max 3)
- Prayer Area (max 2)
- Guest Room (max 3)
- Store Room (max 4, ground floor only)
- Balcony (max 5, upper floors only)
- Terrace (max 2, top floor only)
- Garage (max 3, ground floor only)
- Utility Area (max 2)

#### Step 3: Preferences
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| aesthetic | select | Modern/Traditional/Eco-Friendly/etc | Yes | House style preference |
| material_preferences | array | Multi-select | No | Material choices |
| budget_allocation | textarea | Max 500 chars | No | Budget breakdown preferences |

#### Step 4-5: Review & Architect Selection
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| selected_architect_ids | array | Valid architect IDs | Yes | At least 1 architect |
| reference_images | array | JPG/PNG, max 5MB each | No | Reference design images |
| site_images | array | JPG/PNG, max 5MB each | No | Site photos/scans |
| room_images | object | Nested arrays by room | No | Room-specific images |

#### Step 6: Submit
| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| layout_type | select | custom/library | Yes | Design type |
| selected_layout_id | integer | Valid layout ID | Conditional | If library type |
| requirements | textarea | Max 2000 chars | No | Additional requirements |
| location | text | Max 255 chars | No | Project location |
| timeline | text | Max 100 chars | No | Expected timeline |

**Database Table:** `layout_requests`
- Stores all request data with JSON-encoded structured fields
- Supports floor-wise room planning and image organization

---

## 3. CONTRACTOR ESTIMATE & PROPOSAL SYSTEM

### 3.1 Contractor Estimate Wizard
**Frontend Component:** `frontend/src/components/ContractorEstimateWizard.jsx`
**Backend Endpoint:** `backend/api/contractor/submit_proposal.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| layout_request_id | integer | Valid request ID | Yes | From homeowner request |
| materials | textarea | Max 2000 chars | Yes | Material specifications |
| cost_breakdown | textarea | Max 2000 chars | Yes | Detailed cost breakdown |
| total_cost | decimal | > 0 | Yes | Total estimate in INR |
| timeline | text | Max 100 chars | Yes | Completion timeline |
| notes | textarea | Max 500 chars | No | Additional notes |

**Database Table:** `contractor_proposals`
- Stores estimate data with status tracking (pending/accepted/rejected)

---

## 4. PROGRESS TRACKING & REPORTING

### 4.1 Daily Progress Update Fields
**Frontend Component:** `frontend/src/components/EnhancedProgressUpdate.jsx`
**Backend Endpoint:** `backend/api/contractor/submit_daily_progress.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| project_id | integer | Valid project ID | Yes | |
| contractor_id | integer | Valid contractor ID | Yes | |
| update_date | date | Within 7 days past, 1 day future | Yes | |
| construction_stage | select | Foundation/Structure/Brickwork/etc | Yes | 8 predefined stages |
| work_done_today | textarea | 10-1000 chars, contains letters | Yes | Description of work |
| incremental_completion_percentage | decimal | 0-100, typically ≤20 | Yes | Daily progress % |
| working_hours | decimal | 0-16 hours | Yes | Hours worked today |
| materials_used | textarea | Max 500 chars | No | Materials consumed |
| weather_condition | select | Sunny/Cloudy/Rainy/etc | Yes | 8 weather options |
| site_issues | textarea | Max 1000 chars | No | Issues encountered |
| latitude | decimal | Valid GPS coordinate | No | For geo-verification |
| longitude | decimal | Valid GPS coordinate | No | For geo-verification |

**Database Table:** `daily_progress_updates`
- Unique constraint: (project_id, contractor_id, update_date)
- Stores cumulative completion percentage

### 4.2 Labour Tracking Fields
**Database Table:** `daily_labour_tracking`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| daily_progress_id | integer | Foreign key | Yes | Links to daily update |
| worker_type | select | 19 predefined types | Yes | Mason/Helper/Electrician/etc |
| worker_count | integer | 0-100 | Yes | Number of workers |
| hours_worked | decimal | 0-12 hours | Yes | Regular working hours |
| overtime_hours | decimal | 0-8 hours | No | Overtime hours |
| absent_count | integer | 0-50 | No | Absent workers |
| hourly_rate | decimal | 0-2000 INR | No | Hourly wage rate |
| total_wages | decimal | Auto-calculated | No | Regular + overtime wages |
| productivity_rating | integer | 1-5 | No | Quality rating |
| safety_compliance | select | excellent/good/average/poor/needs_improvement | No | Safety rating |
| remarks | textarea | Max 500 chars | No | Additional notes |

**Standard Hourly Rates (INR):**
- Mason: 500
- Helper: 300
- Electrician: 600
- Plumber: 550
- Carpenter: 450
- Painter: 400
- Supervisor: 800
- Welder: 650
- Crane Operator: 900
- Excavator Operator: 850
- Steel Fixer: 520
- Tile Worker: 480
- Plasterer: 420
- Roofer: 580
- Security Guard: 250
- Site Engineer: 1000
- Quality Inspector: 700
- Safety Officer: 750
- Other: 350

### 4.3 Weekly Progress Summary
**Backend Endpoint:** `backend/api/contractor/submit_weekly_summary.php`
**Database Table:** `weekly_progress_summary`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| project_id | integer | Valid project ID | Yes | |
| contractor_id | integer | Valid contractor ID | Yes | |
| homeowner_id | integer | Valid homeowner ID | Yes | |
| week_start_date | date | Must be Monday | Yes | |
| week_end_date | date | Must be Sunday, ≤7 days after start | Yes | |
| stages_worked | array | At least 1 stage | Yes | Stages worked during week |
| start_progress_percentage | decimal | 0-100 | Yes | Progress at week start |
| end_progress_percentage | decimal | 0-100 | Yes | Progress at week end |
| total_labour_used | json | Worker type breakdown | No | Labour summary |
| delays_and_reasons | textarea | Max 1000 chars | No | Any delays encountered |
| weekly_remarks | textarea | 20-2000 chars | Yes | Weekly summary |

**Unique Constraint:** (project_id, contractor_id, week_start_date)

### 4.4 Monthly Progress Report
**Backend Endpoint:** `backend/api/contractor/submit_monthly_report.php`
**Database Table:** `monthly_progress_report`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| project_id | integer | Valid project ID | Yes | |
| contractor_id | integer | Valid contractor ID | Yes | |
| homeowner_id | integer | Valid homeowner ID | Yes | |
| report_month | integer | 1-12 | Yes | Month number |
| report_year | integer | 2020-2050 | Yes | Year |
| planned_progress_percentage | decimal | 0-100 | Yes | Planned progress |
| actual_progress_percentage | decimal | 0-100 | Yes | Actual progress |
| milestones_achieved | json | Array of milestones | No | Completed milestones |
| labour_summary | json | Labour utilization data | No | Monthly labour summary |
| material_summary | json | Materials used | No | Material consumption |
| delay_explanation | textarea | Max 2000 chars | No | Delay explanations |
| contractor_remarks | textarea | 50-3000 chars | Yes | Detailed remarks |

**Unique Constraint:** (project_id, contractor_id, report_year, report_month)

### 4.5 Progress Report Generation
**Frontend Component:** `frontend/src/components/ProgressReportGenerator.jsx`
**Backend Endpoint:** `backend/api/contractor/generate_progress_report.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| contractor_id | integer | Valid contractor ID | Yes | |
| project_id | integer | Valid project ID | Yes | |
| report_type | select | daily/weekly/monthly | Yes | Report type |
| start_date | date | Valid date | Yes | Report period start |
| end_date | date | ≥ start_date | Yes | Report period end |

**Report Output Fields:**
- Project name and contractor name
- Working days/hours summary
- Total workers employed
- Progress percentage
- Labour analysis by worker type
- Material costs and usage
- Equipment costs
- Quality and safety metrics
- Photos and documentation
- Recommendations

---

## 5. GEO-PHOTO CAPTURE & VERIFICATION

### 5.1 Geo-Photo Capture Fields
**Frontend Component:** `frontend/src/components/GeoPhotoCapture.jsx`
**Backend Endpoint:** `backend/api/contractor/upload_geo_photo.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| project_id | integer | Valid project ID | Yes | |
| contractor_id | integer | Valid contractor ID | Yes | |
| photo | file | JPG/PNG, max 5MB | Yes | Photo blob |
| location_data | json | GPS coordinates + metadata | Yes | Geo-location info |
| timestamp | datetime | ISO format | Yes | Capture time |
| latitude | decimal | -90 to 90 | Yes | GPS latitude |
| longitude | decimal | -180 to 180 | Yes | GPS longitude |
| accuracy | decimal | Meters | Yes | GPS accuracy radius |
| altitude | decimal | Meters | No | Elevation |
| heading | decimal | 0-360 degrees | No | Direction |
| speed | decimal | m/s | No | Movement speed |
| placeName | text | Max 255 chars | Yes | Reverse geocoded location |

**GPS Accuracy Requirements:**
- Preferred: ≤100m accuracy
- Acceptable: ≤500m accuracy
- Location verification: Within project radius (default 100m)

**Overlay Information Added to Photos:**
- Place name with emoji
- Latitude/Longitude (gold color)
- Timestamp (local format)
- GPS accuracy
- Altitude (if available)
- BuildHub watermark

---

## 6. SUPPORT & MESSAGING SYSTEM

### 6.1 Support Issue Creation
**Frontend Component:** Support form in dashboard
**Backend Endpoint:** `backend/api/support/create_issue.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| user_id | integer | From session | Yes | |
| subject | text | Max 255 chars | Yes | Issue title |
| category | select | general/technical/payment/etc | Yes | Issue category |
| message | textarea | Max 5000 chars | Yes | Detailed description |

**Database Table:** `support_issues`
- Status: open/in_progress/resolved/closed
- Tracks creation and update timestamps

### 6.2 Support Reply Fields
**Backend Endpoint:** `backend/api/support/admin_reply.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| issue_id | integer | Valid issue ID | Yes | |
| admin_id | integer | Valid admin ID | Yes | |
| reply_message | textarea | Max 5000 chars | Yes | Admin response |
| status | select | in_progress/resolved/closed | No | Update status |

### 6.3 Notification Fields
**Frontend Component:** `frontend/src/components/NotificationSystem.jsx`
**Backend Endpoint:** `backend/api/homeowner/create_notification.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| user_id | integer | Valid user ID | Yes | |
| title | text | Max 255 chars | Yes | Notification title |
| message | textarea | Max 1000 chars | Yes | Notification message |
| type | select | progress_update/stage_completed/delay_reported/etc | Yes | Notification type |
| reference_id | integer | Related entity ID | No | Links to update/report |

**Database Table:** `progress_notifications`
- Status: unread/read
- Tracks read_at timestamp

---

## 7. ADMIN DASHBOARD

### 7.1 Admin Login
**Backend Endpoint:** `backend/api/admin/admin_login.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| email | email | Valid format | Yes | Admin email |
| password | password | Min 8 chars | Yes | Admin password |

### 7.2 Material Management
**Backend Endpoint:** `backend/api/admin/add_material.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| material_name | text | Max 255 chars | Yes | Material name |
| category | select | Predefined categories | Yes | Material category |
| unit_price | decimal | > 0 | Yes | Price per unit |
| unit | select | kg/liter/piece/sqft/etc | Yes | Unit of measurement |
| description | textarea | Max 1000 chars | No | Material description |

### 7.3 User Status Management
**Backend Endpoint:** `backend/api/admin/update_user_status.php`

| Field | Type | Validation | Required | Notes |
|-------|------|-----------|----------|-------|
| user_id | integer | Valid user ID | Yes | |
| status | select | pending/approved/rejected | Yes | Verification status |
| notes | textarea | Max 500 chars | No | Admin notes |

---

## 8. VALIDATION RULES & PATTERNS

### 8.1 Email Validation
- Pattern: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- No spaces allowed
- Must have @ and domain
- Unique check against existing users

### 8.2 Password Validation
- Minimum 8 characters
- At least one letter (A-Z, a-z)
- At least one number (0-9)
- At least one special character (!@#$%^&*()_+-=[]{}';:"\\|,.<>/?`~)
- No spaces allowed
- Exception: Admin account (shijinthomas369@gmail.com)

### 8.3 Numeric Field Validation
- Completion percentage: 0-100
- Working hours: 0-16
- Overtime hours: 0-8
- Worker count: 0-100
- Hourly rate: 0-2000 INR
- Productivity rating: 1-5

### 8.4 Date Validation
- Daily updates: Within 7 days past, 1 day future
- Weekly: Start on Monday, end on Sunday, ≤7 days duration
- Monthly: Valid month (1-12) and year (2020-2050)

### 8.5 File Upload Validation
- Allowed types: JPG, JPEG, PNG, WebP, GIF, PDF
- Maximum size: 5MB per file
- Maximum files: 10 per upload
- Filename: Max 100 characters
- No duplicate filenames

### 8.6 Text Field Validation
- Minimum length: 10-50 chars (context-dependent)
- Maximum length: 255-3000 chars (context-dependent)
- Must contain letters (for descriptions)
- No leading/trailing spaces
- Sanitized for whitespace

---

## 9. DATABASE SCHEMA SUMMARY

### Core Tables
1. **users** - User accounts (homeowner/contractor/architect/admin)
2. **layout_requests** - Homeowner project requests
3. **contractor_proposals** - Contractor estimates
4. **construction_progress_updates** - Daily progress tracking
5. **daily_progress_updates** - Enhanced daily tracking
6. **daily_labour_tracking** - Labour details per day
7. **weekly_progress_summary** - Weekly summaries
8. **monthly_progress_report** - Monthly reports
9. **progress_milestones** - Project milestones
10. **progress_notifications** - Notification tracking
11. **support_issues** - Support tickets
12. **geo_photos** - Geo-tagged photos
13. **project_locations** - Project GPS boundaries

---

## 10. VALIDATION SUMMARY TABLE

| Component | Total Fields | Required | Optional | Validation Type |
|-----------|-------------|----------|----------|-----------------|
| Login | 2 | 2 | 0 | Email, Password |
| Registration | 7 | 5 | 2 | Email, Password, Files |
| Profile Update | 4 | 2 | 2 | Text, Phone |
| Request Wizard | 25+ | 12 | 13+ | Multi-step validation |
| Daily Progress | 12 | 8 | 4 | Numeric, Date, GPS |
| Labour Tracking | 11 | 5 | 6 | Numeric, Select |
| Weekly Summary | 8 | 6 | 2 | Date, Text, Array |
| Monthly Report | 9 | 6 | 3 | Numeric, Text |
| Geo-Photo | 10 | 8 | 2 | GPS, File, Timestamp |
| Support Issue | 4 | 3 | 1 | Text, Select |

---

## 11. KEY VALIDATION INSIGHTS

### High-Risk Fields (Require Strict Validation)
1. **GPS Coordinates** - Must be within project boundaries
2. **Completion Percentage** - Cannot decrease, max 20% daily
3. **Worker Count** - Must match labour availability
4. **Hourly Rates** - Flagged if >50% deviation from standard
5. **File Uploads** - Size, type, and content validation

### Cross-Field Validations
1. End date must be after start date
2. Completion percentage cannot decrease
3. Absent count cannot exceed worker count
4. Total hours (regular + overtime) cannot exceed 16
5. Overtime hours cannot exceed 8

### Conditional Validations
1. Photos required if completion ≥10%
2. Remarks required if productivity <3
3. Safety remarks required if compliance is poor
4. Architect license required only for architect role
5. Contractor license required only for contractor role

---

## 12. RECOMMENDATIONS FOR VALIDATION IMPLEMENTATION

1. **Frontend Validation**
   - Real-time field validation with visual feedback
   - Cross-field validation before submission
   - File preview before upload
   - GPS accuracy indicator

2. **Backend Validation**
   - Duplicate all frontend validations
   - Verify user permissions
   - Check business logic constraints
   - Validate file contents (not just extensions)

3. **Database Constraints**
   - Unique constraints on (project_id, contractor_id, date)
   - Foreign key constraints for referential integrity
   - Check constraints for numeric ranges
   - Default values for optional fields

4. **Error Handling**
   - Specific error messages for each validation failure
   - Suggest corrections for common errors
   - Log validation failures for audit trail
   - Provide user-friendly error summaries

---

**Document Generated:** 2024
**Last Updated:** Current Analysis
**Coverage:** 100% of identified form fields and database schemas
