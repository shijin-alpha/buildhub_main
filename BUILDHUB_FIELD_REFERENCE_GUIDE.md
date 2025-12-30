# BuildHub Field Reference Guide

## Quick Reference by Feature

### Authentication Fields
```
LOGIN:
- email (required, email format)
- password (required, 8+ chars, special char)

REGISTER:
- firstName (required, text)
- lastName (required, text)
- email (required, unique email)
- password (required, complex)
- confirmPassword (required, must match)
- role (required, select: homeowner/contractor/architect)
- license (conditional, file for contractors)
- architectLicense (conditional, file for architects)
- portfolio (conditional, file for architects)
```

### Project Request Fields
```
PRELIMINARY:
- plot_size (required, decimal > 0)
- plot_unit (required, select)
- building_size (required, decimal > 0)
- budget_range (required, select)
- custom_budget (conditional, if Custom selected)

SITE:
- plot_shape (required, select)
- topography (required, select)
- development_laws (required, select)
- num_floors (required, integer 1-10)
- orientation (optional, text)
- site_considerations (optional, textarea)

FAMILY:
- rooms (required, multi-select array)
- family_needs (optional, multi-select array)
- room_requirements (optional, object)

PREFERENCES:
- aesthetic (required, select)
- material_preferences (optional, array)
- budget_allocation (optional, textarea)

ARCHITECT:
- selected_architect_ids (required, array)

SUBMIT:
- layout_type (required, select)
- selected_layout_id (conditional, if library)
- requirements (optional, textarea)
- location (optional, text)
- timeline (optional, text)
- reference_images (optional, array)
- site_images (optional, array)
- room_images (optional, object)
```

### Progress Tracking Fields
```
DAILY UPDATE:
- project_id (required, integer)
- contractor_id (required, integer)
- update_date (required, date)
- construction_stage (required, select)
- work_done_today (required, textarea 10-1000 chars)
- incremental_completion_percentage (required, 0-100)
- working_hours (required, 0-16)
- materials_used (optional, textarea)
- weather_condition (required, select)
- site_issues (optional, textarea)
- latitude (optional, decimal)
- longitude (optional, decimal)

LABOUR ENTRY:
- worker_type (required, select)
- worker_count (required, 0-100)
- hours_worked (required, 0-12)
- overtime_hours (optional, 0-8)
- absent_count (optional, 0-50)
- hourly_rate (optional, 0-2000)
- total_wages (auto-calculated)
- productivity_rating (optional, 1-5)
- safety_compliance (optional, select)
- remarks (optional, textarea)

WEEKLY SUMMARY:
- week_start_date (required, Monday)
- week_end_date (required, Sunday)
- stages_worked (required, array)
- start_progress_percentage (required, 0-100)
- end_progress_percentage (required, 0-100)
- total_labour_used (optional, json)
- delays_and_reasons (optional, textarea)
- weekly_remarks (required, 20-2000 chars)

MONTHLY REPORT:
- report_month (required, 1-12)
- report_year (required, 2020-2050)
- planned_progress_percentage (required, 0-100)
- actual_progress_percentage (required, 0-100)
- milestones_achieved (optional, array)
- labour_summary (optional, json)
- material_summary (optional, json)
- delay_explanation (optional, textarea)
- contractor_remarks (required, 50-3000 chars)
```

### Geo-Photo Fields
```
CAPTURE:
- project_id (required, integer)
- contractor_id (required, integer)
- photo (required, file JPG/PNG max 5MB)
- latitude (required, -90 to 90)
- longitude (required, -180 to 180)
- accuracy (required, meters)
- altitude (optional, meters)
- heading (optional, 0-360)
- speed (optional, m/s)
- placeName (required, text)
- timestamp (required, ISO datetime)
```

### Support Fields
```
CREATE ISSUE:
- user_id (required, integer)
- subject (required, max 255 chars)
- category (required, select)
- message (required, max 5000 chars)

REPLY:
- issue_id (required, integer)
- admin_id (required, integer)
- reply_message (required, max 5000 chars)
- status (optional, select)
```

---

## Validation Rules by Field Type

### Email Fields
- Pattern: `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`
- No spaces
- Unique check
- Example: user@example.com

### Password Fields
- Min 8 characters
- Must include: letter, number, special character
- No spaces
- Example: MyPass123!

### Numeric Fields
- Completion %: 0-100
- Hours: 0-16 (daily), 0-12 (regular), 0-8 (overtime)
- Workers: 0-100
- Rate: 0-2000 INR
- Rating: 1-5

### Date Fields
- Format: YYYY-MM-DD
- Daily: ±7 days from today
- Weekly: Monday-Sunday
- Monthly: Valid month/year

### GPS Fields
- Latitude: -90 to 90
- Longitude: -180 to 180
- Accuracy: Meters (prefer ≤100m)
- Verification: Within project radius

### File Fields
- Types: JPG, PNG, PDF, WebP, GIF
- Max size: 5MB
- Max files: 10
- Filename: Max 100 chars

### Text Fields
- Min: 10-50 chars (context)
- Max: 255-3000 chars (context)
- No leading/trailing spaces
- Must contain letters (descriptions)

---

## Enum/Select Options

### Construction Stages
1. Foundation
2. Structure
3. Brickwork
4. Roofing
5. Electrical
6. Plumbing
7. Finishing
8. Other

### Weather Conditions
1. Sunny
2. Cloudy
3. Rainy
4. Stormy
5. Foggy
6. Hot
7. Cold
8. Windy

### Worker Types (19 options)
1. Mason
2. Helper
3. Electrician
4. Plumber
5. Carpenter
6. Painter
7. Supervisor
8. Welder
9. Crane Operator
10. Excavator Operator
11. Steel Fixer
12. Tile Worker
13. Plasterer
14. Roofer
15. Security Guard
16. Site Engineer
17. Quality Inspector
18. Safety Officer
19. Other

### Safety Compliance
1. excellent
2. good
3. average
4. poor
5. needs_improvement

### Room Types (15 options)
1. Master Bedroom (max 1/floor)
2. Bedrooms (max 8)
3. Attached Bathrooms (max 8)
4. Common Bathrooms (max 6)
5. Living Room (max 3)
6. Dining Room (max 2)
7. Kitchen (max 2, ground only)
8. Study Area (max 3)
9. Prayer Area (max 2)
10. Guest Room (max 3)
11. Store Room (max 4, ground only)
12. Balcony (max 5, upper only)
13. Terrace (max 2, top only)
14. Garage (max 3, ground only)
15. Utility Area (max 2)

### Budget Ranges
1. 5-10 Lakhs
2. 10-20 Lakhs
3. 20-30 Lakhs
4. 30-50 Lakhs
5. 50-75 Lakhs
6. 75 Lakhs - 1 Crore
7. 1-2 Crores
8. 2-5 Crores
9. 5+ Crores
10. Custom

### Plot Shapes
- Regular
- Irregular
- L-shaped
- T-shaped
- U-shaped
- Triangular
- Trapezoidal

### Topography
- Flat
- Sloped
- Hilly
- Valley
- Elevated

### House Styles
- Modern
- Traditional
- Contemporary
- Eco-Friendly
- Minimalist
- Victorian
- Mediterranean
- Colonial

---

## Standard Hourly Rates (INR)

| Worker Type | Rate |
|-------------|------|
| Mason | 500 |
| Helper | 300 |
| Electrician | 600 |
| Plumber | 550 |
| Carpenter | 450 |
| Painter | 400 |
| Supervisor | 800 |
| Welder | 650 |
| Crane Operator | 900 |
| Excavator Operator | 850 |
| Steel Fixer | 520 |
| Tile Worker | 480 |
| Plasterer | 420 |
| Roofer | 580 |
| Security Guard | 250 |
| Site Engineer | 1000 |
| Quality Inspector | 700 |
| Safety Officer | 750 |
| Other | 350 |

---

## Cross-Field Validation Rules

### Date Validations
- End date > Start date
- Weekly: Start = Monday, End = Sunday
- Daily: Within ±7 days
- Monthly: Valid month (1-12)

### Numeric Validations
- Completion % cannot decrease
- Absent count ≤ worker count
- Total hours (regular + overtime) ≤ 16
- Overtime ≤ 8 hours

### Conditional Validations
- Photos required if completion ≥ 10%
- Remarks required if productivity < 3
- Safety remarks required if compliance = poor
- Architect license required for architect role
- Contractor license required for contractor role

### Business Logic
- One master bedroom per floor max
- Kitchen only on ground floor
- Terrace only on top floor
- Balcony not on ground floor
- Store room typically ground floor

---

## Error Messages

### Email Errors
- "Please enter a valid email address."
- "This email is already registered."
- "Email is required."

### Password Errors
- "Password must be at least 8 characters long."
- "Password must include at least one letter."
- "Password must include at least one number."
- "Password must include at least one special character."
- "Password cannot contain spaces."
- "Passwords do not match!"

### Numeric Errors
- "Completion percentage must be between 0 and 100"
- "Progress percentage cannot decrease from previous update"
- "Worker count cannot exceed 100"
- "Hours worked must be between 0 and 12"
- "Overtime hours cannot exceed 8"

### Date Errors
- "Date cannot be more than 7 days in the past"
- "Date cannot be more than 1 day in the future"
- "Start date must be before end date"
- "Week should typically start on Monday"
- "Week duration cannot exceed 7 days"

### File Errors
- "Invalid file type. Only JPG, PNG, WebP, and GIF allowed"
- "File too large. Maximum 5MB allowed"
- "Maximum 10 photos allowed"
- "Duplicate filename"

### Required Field Errors
- "[Field name] is required"
- "Please select [field name]"
- "Please fill in [field name]"

---

## API Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "id": 123,
    "field1": "value1",
    "field2": "value2"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field1": "Field-specific error",
    "field2": "Another error"
  }
}
```

---

## Field Dependencies

### Conditional Fields
- `custom_budget` depends on `budget_range === 'Custom'`
- `license` depends on `role === 'contractor'`
- `architectLicense` depends on `role === 'architect'`
- `portfolio` depends on `role === 'architect'`
- `selected_layout_id` depends on `layout_type === 'library'`

### Auto-Calculated Fields
- `total_wages` = (worker_count × hours_worked × hourly_rate) + (worker_count × overtime_hours × hourly_rate × 1.5)
- `cumulative_completion_percentage` = sum of all daily incremental percentages
- `end_progress_percentage` = calculated from daily updates

### Derived Fields
- `location_verified` = GPS distance ≤ project radius
- `stage_status` = based on completion percentage
- `milestone_status` = based on actual vs planned progress

---

## Data Type Reference

| Type | Format | Example | Validation |
|------|--------|---------|-----------|
| email | string | user@example.com | RFC 5322 |
| password | string | MyPass123! | 8+ chars, complex |
| text | string | "Sample text" | Max length |
| textarea | string | "Multi-line\ntext" | Max length |
| integer | number | 42 | Whole number |
| decimal | number | 42.5 | Float |
| date | string | 2024-01-15 | YYYY-MM-DD |
| datetime | string | 2024-01-15T10:30:00Z | ISO 8601 |
| select | string | "option1" | From enum |
| multi-select | array | ["opt1", "opt2"] | From enum |
| file | blob | [binary data] | Type, size |
| json | object | {"key": "value"} | Valid JSON |
| boolean | boolean | true/false | true or false |
| gps | decimal | 12.9716 | -90 to 90 (lat), -180 to 180 (lng) |

---

## Implementation Checklist

- [ ] Frontend validation for all fields
- [ ] Backend validation for all fields
- [ ] Database constraints for data integrity
- [ ] Error message localization
- [ ] File upload security
- [ ] GPS accuracy verification
- [ ] Rate limiting on API endpoints
- [ ] Audit logging for sensitive fields
- [ ] Data encryption for PII
- [ ] CORS configuration
- [ ] Input sanitization
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF token validation
- [ ] Session management
