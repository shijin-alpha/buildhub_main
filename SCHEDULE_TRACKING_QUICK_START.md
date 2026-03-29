# Schedule Tracking Quick Start Guide

## 🚀 Installation (5 Minutes)

### Step 1: Run Migration
```bash
php apply_schedule_tracking_migration.php
```

Expected output:
```
✓ Database connection established
✓ construction_projects table found
✓ Added 6 schedule tracking columns
✓ Created indexes
✓ Created project_schedule_audit table
✓ MIGRATION COMPLETED SUCCESSFULLY
```

### Step 2: Test API
Open `test_schedule_tracking_system.html` in your browser and test each section.

### Step 3: Integrate Component
Add to your dashboard:
```jsx
import ScheduleTrackingPanel from './components/ScheduleTrackingPanel';

<ScheduleTrackingPanel 
    projectId={projectId} 
    userRole={userRole} 
    userId={userId} 
/>
```

## 📋 Usage Workflow

### For Contractors

**1. Set Planned Schedule (After Project Approval)**
```javascript
POST /backend/api/schedule_tracking.php
{
    action: 'update_planned_dates',
    project_id: 1,
    planned_start_date: '2026-02-01',
    planned_end_date: '2026-05-01'
}
```

**2. Record Actual Start (When Work Begins)**
```javascript
POST /backend/api/schedule_tracking.php
{
    action: 'update_actual_start',
    project_id: 1,
    actual_start_date: '2026-02-05'
}
```
⚠️ This locks planned dates permanently!

**3. Complete Project (When Work Finishes)**
```javascript
POST /backend/api/schedule_tracking.php
{
    action: 'update_actual_end',
    project_id: 1,
    actual_end_date: '2026-05-15'
}
```
✅ Automatically calculates time overrun!

### For Homeowners

**View Schedule (Read-Only)**
```javascript
GET /backend/api/schedule_tracking.php?project_id=1
```

## 🎯 Key Features

| Feature | Description |
|---------|-------------|
| **Backward Compatible** | All existing projects work without changes |
| **Nullable Fields** | Schedule data is optional |
| **Auto-Lock** | Planned dates lock when actual start is recorded |
| **Auto-Calculate** | Time overrun calculated on completion |
| **Audit Trail** | All changes logged with user and timestamp |
| **Role-Based** | Contractors edit, homeowners view |

## 📊 Understanding the Data

### Time Overrun Percentage

```
Formula: ((actual_duration - planned_duration) / planned_duration) × 100
```

**Examples:**
- `+15.56%` = Project took 15.56% longer than planned (delayed)
- `-10.00%` = Project completed 10% faster than planned (early)
- `0.00%` = Project completed exactly on schedule

### Delay in Days

```
Formula: actual_end_date - planned_end_date
```

**Examples:**
- `+14 days` = Completed 14 days late
- `-7 days` = Completed 7 days early
- `0 days` = Completed on time

## 🔍 Common Queries

### Check if project has schedule data
```sql
SELECT 
    id,
    project_name,
    planned_start_date,
    planned_end_date,
    actual_time_overrun_percentage
FROM construction_projects
WHERE id = 1;
```

### Find delayed projects
```sql
SELECT 
    id,
    project_name,
    actual_time_overrun_percentage,
    DATEDIFF(actual_end_date, planned_end_date) as delay_days
FROM construction_projects
WHERE actual_time_overrun_percentage > 0
ORDER BY actual_time_overrun_percentage DESC;
```

### View schedule changes
```sql
SELECT 
    psa.*,
    u.first_name,
    u.last_name
FROM project_schedule_audit psa
JOIN users u ON psa.changed_by_user_id = u.id
WHERE psa.project_id = 1
ORDER BY psa.created_at DESC;
```

## ⚠️ Important Notes

1. **Planned dates can only be set by contractors**
2. **Planned dates lock when actual start is recorded**
3. **All dates are optional (NULL allowed)**
4. **Calculations only run when all required dates exist**
5. **No impact on existing cost or progress tracking**

## 🐛 Troubleshooting

### Issue: "Only contractors can set planned dates"
**Solution**: Ensure session has `role = 'contractor'` and user owns the project

### Issue: "Planned dates are locked"
**Solution**: Actual start date has been recorded. Planned dates cannot be changed.

### Issue: Time overrun is NULL
**Solution**: Not all required dates are set. Need all 4 dates for calculation.

### Issue: Migration fails
**Solution**: Check database connection in `backend/config/database.php`

## 📱 API Response Examples

### Success Response
```json
{
    "success": true,
    "message": "Planned dates updated successfully",
    "data": {
        "planned_start_date": "2026-02-01",
        "planned_end_date": "2026-05-01"
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Planned dates are locked because actual start date has been recorded"
}
```

## 🎨 UI Components

### Schedule Display Card
```jsx
<div className="schedule-card">
    <h4>Planned Schedule</h4>
    <div className="schedule-value">Feb 1, 2026</div>
    <div className="schedule-label">Start Date</div>
    <div className="schedule-value">May 1, 2026</div>
    <div className="schedule-label">End Date</div>
    <div>Duration: 90 days</div>
</div>
```

### Delay Alert
```jsx
{is_delayed && (
    <div className="alert alert-danger">
        ⚠️ Project Delayed - {delay_days} days behind schedule
    </div>
)}
```

## 📈 Reporting

### Generate Schedule Report
```sql
SELECT 
    cp.id,
    cp.project_name,
    cp.planned_start_date,
    cp.planned_end_date,
    cp.actual_start_date,
    cp.actual_end_date,
    cp.actual_time_overrun_percentage,
    DATEDIFF(cp.actual_end_date, cp.planned_end_date) as delay_days,
    CASE 
        WHEN cp.actual_time_overrun_percentage > 0 THEN 'Delayed'
        WHEN cp.actual_time_overrun_percentage < 0 THEN 'Early'
        WHEN cp.actual_time_overrun_percentage = 0 THEN 'On Time'
        ELSE 'In Progress'
    END as status,
    u.first_name as contractor_first_name,
    u.last_name as contractor_last_name
FROM construction_projects cp
JOIN users u ON cp.contractor_id = u.id
WHERE cp.planned_start_date IS NOT NULL
ORDER BY cp.actual_time_overrun_percentage DESC;
```

## ✅ Verification Steps

After installation, verify:

1. **Database Changes**
   ```sql
   SHOW COLUMNS FROM construction_projects LIKE '%date%';
   ```

2. **API Accessibility**
   ```bash
   curl "http://localhost/backend/api/schedule_tracking.php?project_id=1"
   ```

3. **Frontend Component**
   - Open contractor dashboard
   - Check for schedule tracking panel
   - Verify edit functionality

4. **Audit Logging**
   ```sql
   SELECT COUNT(*) FROM project_schedule_audit;
   ```

## 🎓 Training Tips

### For Contractors
1. Set planned dates immediately after project approval
2. Be realistic with timeline estimates
3. Record actual start date on first day of work
4. Update actual end date promptly on completion
5. Review time overrun data to improve future estimates

### For Homeowners
1. Check planned schedule after contractor sets it
2. Monitor progress against planned timeline
3. Communicate with contractor if delays occur
4. Review final time overrun percentage after completion

## 📞 Need Help?

1. Check `SCHEDULE_TRACKING_IMPLEMENTATION.md` for detailed documentation
2. Test with `test_schedule_tracking_system.html`
3. Review API responses for specific error messages
4. Check audit logs for change history

---

**Quick Start Version**: 1.0.0  
**Last Updated**: February 16, 2026  
**Estimated Setup Time**: 5 minutes
