# 📅 Schedule Tracking System

> Backward-compatible planned vs actual schedule tracking for BuildHub construction management system

## 🎯 What This Does

Adds structured schedule tracking to your construction projects without breaking anything. Contractors can set planned dates, record actual dates, and the system automatically calculates time overruns. Homeowners get transparent visibility into project timelines.

## ⚡ Quick Start

```bash
# 1. Apply database changes (takes 30 seconds)
php apply_schedule_tracking_migration.php

# 2. Verify everything worked
php verify_schedule_tracking_migration.php

# 3. Test the system
# Open test_schedule_tracking_system.html in your browser

# 4. Done! Start using it.
```

## 📦 What You Get

### For Contractors
- Set planned start and end dates for projects
- Record actual start date (locks planned dates)
- Record actual completion date (auto-calculates overrun)
- Track your performance over time
- Professional accountability

### For Homeowners
- See planned project timeline
- Monitor actual progress
- View delay information
- Understand time overruns
- Transparent communication

### For Admins
- System-wide schedule analytics
- Contractor performance metrics
- Delay trend analysis
- Complete audit trails
- Override capabilities

## 🔑 Key Features

✅ **100% Backward Compatible** - All existing projects work without changes  
✅ **Optional Fields** - Schedule data is completely optional  
✅ **Automatic Calculations** - Time overrun computed automatically  
✅ **Automatic Locking** - Planned dates lock when work starts  
✅ **Complete Audit Trail** - Every change is logged  
✅ **Role-Based Access** - Contractors edit, homeowners view  
✅ **No Breaking Changes** - Zero disruption to existing workflows  

## 📊 How It Works

```
1. Project Approved
   ↓
2. Contractor Sets Planned Dates
   - Planned Start: Feb 1, 2026
   - Planned End: May 1, 2026
   - Duration: 90 days
   ↓
3. Work Begins (Actual Start Recorded)
   - Actual Start: Feb 5, 2026
   - 🔒 Planned dates now locked
   ↓
4. Project Completes (Actual End Recorded)
   - Actual End: May 15, 2026
   - Duration: 100 days
   - ⚠️ Overrun: +11.11%
   - ⚠️ Delay: 14 days
```

## 📁 Files Included

### Core Implementation
- `backend/api/schedule_tracking.php` - API endpoints
- `frontend/src/components/ScheduleTrackingPanel.jsx` - React component
- `backend/database/add_schedule_tracking_fields.sql` - SQL migration

### Installation & Testing
- `apply_schedule_tracking_migration.php` - Run this to install
- `verify_schedule_tracking_migration.php` - Verify installation
- `test_schedule_tracking_system.html` - Interactive testing

### Documentation
- `SCHEDULE_TRACKING_IMPLEMENTATION.md` - Complete technical guide
- `SCHEDULE_TRACKING_QUICK_START.md` - Quick reference
- `SCHEDULE_TRACKING_SUMMARY.md` - Executive summary
- `schedule_tracking_visual_guide.html` - Visual documentation
- `IMPLEMENTATION_CHECKLIST.md` - Deployment checklist
- `SCHEDULE_TRACKING_README.md` - This file

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB database
- Existing BuildHub installation
- React 16.8+ (for frontend component)

### Step 1: Database Migration

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

### Step 2: Verify Installation

```bash
php verify_schedule_tracking_migration.php
```

Should show:
```
✓ All 6 schedule tracking columns found!
✓ project_schedule_audit table exists
✓ idx_schedule_tracking index exists
✓ idx_time_overrun index exists
✓ All existing projects remain compatible
```

### Step 3: Integrate Frontend

Add to contractor dashboard:
```jsx
import ScheduleTrackingPanel from './components/ScheduleTrackingPanel';

<ScheduleTrackingPanel 
    projectId={project.id} 
    userRole="contractor" 
    userId={contractorId} 
/>
```

Add to homeowner dashboard:
```jsx
<ScheduleTrackingPanel 
    projectId={project.id} 
    userRole="homeowner" 
    userId={homeownerId} 
/>
```

## 📖 Usage Examples

### Contractor: Set Planned Schedule

```javascript
// POST to /backend/api/schedule_tracking.php
const formData = new FormData();
formData.append('action', 'update_planned_dates');
formData.append('project_id', 1);
formData.append('planned_start_date', '2026-02-01');
formData.append('planned_end_date', '2026-05-01');

const response = await fetch(API_URL, {
    method: 'POST',
    body: formData
});
```

### Contractor: Record Actual Start

```javascript
formData.append('action', 'update_actual_start');
formData.append('project_id', 1);
formData.append('actual_start_date', '2026-02-05');
// This locks planned dates permanently
```

### Contractor: Complete Project

```javascript
formData.append('action', 'update_actual_end');
formData.append('project_id', 1);
formData.append('actual_end_date', '2026-05-15');
// Automatically calculates time overrun
```

### Anyone: View Schedule

```javascript
// GET from /backend/api/schedule_tracking.php
const response = await fetch(`${API_URL}?project_id=1`);
const data = await response.json();

console.log(data.data.project.actual_time_overrun_percentage); // 11.11
console.log(data.data.delay_days); // 14
console.log(data.data.is_delayed); // true
```

## 🧮 Understanding the Metrics

### Time Overrun Percentage

```
Formula: ((actual_duration - planned_duration) / planned_duration) × 100
```

**Examples:**
- `+15.56%` = Project took 15.56% longer (delayed)
- `-10.00%` = Project completed 10% faster (early)
- `0.00%` = Project completed exactly on schedule

### Delay in Days

```
Formula: actual_end_date - planned_end_date
```

**Examples:**
- `+14 days` = Completed 14 days late
- `-7 days` = Completed 7 days early
- `0 days` = Completed on time

## 🔒 Security

- ✅ Role-based access control on all endpoints
- ✅ Project ownership verification
- ✅ SQL injection protection (prepared statements)
- ✅ Input validation and sanitization
- ✅ Complete audit trail with user tracking
- ✅ IP address logging for accountability

## 📊 Database Schema

### New Columns in `construction_projects`

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `planned_start_date` | DATE | YES | Contractor-set planned start |
| `planned_end_date` | DATE | YES | Contractor-set planned end |
| `actual_start_date` | DATE | YES | Actual start (locks planned) |
| `actual_end_date` | DATE | YES | Actual end (triggers calc) |
| `actual_time_overrun_percentage` | DECIMAL(10,2) | YES | Calculated overrun % |
| `planned_dates_locked` | TINYINT(1) | NO | Lock flag (default 0) |

### New Table: `project_schedule_audit`

Tracks all schedule changes with:
- Project ID
- User ID and role
- Field changed
- Old and new values
- Change reason
- IP address
- Timestamp

## 🐛 Troubleshooting

### "Only contractors can set planned dates"
**Cause**: User role is not 'contractor' or doesn't own the project  
**Solution**: Verify session role and project ownership

### "Planned dates are locked"
**Cause**: Actual start date has been recorded  
**Solution**: This is intentional - planned dates cannot be changed after work begins

### Time overrun is NULL
**Cause**: Not all required dates are set  
**Solution**: This is normal - calculation requires all 4 dates

### Migration fails
**Cause**: Database connection issue  
**Solution**: Check `backend/config/database.php` configuration

## 📈 Reporting Queries

### Find Delayed Projects
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

### Contractor Performance
```sql
SELECT 
    contractor_id,
    COUNT(*) as total_projects,
    AVG(actual_time_overrun_percentage) as avg_overrun,
    SUM(CASE WHEN actual_time_overrun_percentage <= 0 THEN 1 ELSE 0 END) as on_time_count
FROM construction_projects
WHERE actual_time_overrun_percentage IS NOT NULL
GROUP BY contractor_id;
```

### Schedule Compliance Rate
```sql
SELECT 
    COUNT(CASE WHEN actual_time_overrun_percentage <= 0 THEN 1 END) * 100.0 / COUNT(*) as compliance_rate
FROM construction_projects
WHERE actual_time_overrun_percentage IS NOT NULL;
```

## 🎓 Training Resources

### For Developers
- Read `SCHEDULE_TRACKING_IMPLEMENTATION.md` for technical details
- Review API endpoints in `backend/api/schedule_tracking.php`
- Check React component in `frontend/src/components/ScheduleTrackingPanel.jsx`

### For Contractors
- Read `SCHEDULE_TRACKING_QUICK_START.md` for quick reference
- Open `schedule_tracking_visual_guide.html` for visual guide
- Use `test_schedule_tracking_system.html` to practice

### For Homeowners
- View `schedule_tracking_visual_guide.html` for understanding metrics
- Ask contractor to explain planned vs actual dates
- Check project dashboard for schedule information

## ✅ Verification Checklist

After installation, verify:

- [ ] Migration completed successfully
- [ ] All 6 columns added to construction_projects
- [ ] project_schedule_audit table created
- [ ] Indexes created (idx_schedule_tracking, idx_time_overrun)
- [ ] Existing projects still work (backward compatibility)
- [ ] API endpoints respond correctly
- [ ] Frontend component renders properly
- [ ] Role-based access control works
- [ ] Calculations are accurate
- [ ] Audit logging captures changes

## 🔮 Future Enhancements

Potential additions (not in current version):
- Milestone-based schedule tracking
- Automated delay notifications via email/SMS
- Weather data integration for delay justification
- Predictive completion date estimation
- Mobile app for schedule updates
- Integration with calendar systems

## 📞 Support

### Documentation
- Technical: `SCHEDULE_TRACKING_IMPLEMENTATION.md`
- Quick Start: `SCHEDULE_TRACKING_QUICK_START.md`
- Summary: `SCHEDULE_TRACKING_SUMMARY.md`
- Visual: `schedule_tracking_visual_guide.html`

### Testing
- Interactive: `test_schedule_tracking_system.html`
- Verification: `verify_schedule_tracking_migration.php`

### Issues
1. Check documentation first
2. Review API error messages
3. Check audit logs for change history
4. Verify database migration completed

## 📝 License & Credits

**Version**: 1.0.0  
**Release Date**: February 16, 2026  
**Status**: Production Ready  
**Backward Compatible**: ✅ Yes  
**Breaking Changes**: ❌ None  

## 🎉 Summary

This implementation adds professional schedule tracking to BuildHub while maintaining 100% backward compatibility. All existing functionality remains intact, and the new features are entirely optional.

**Key Benefits:**
- ✅ Track planned vs actual schedules
- ✅ Automatic time overrun calculations
- ✅ Complete transparency for homeowners
- ✅ Professional accountability for contractors
- ✅ Data-driven performance insights
- ✅ Zero disruption to existing projects

**Ready to use!** 🚀

---

For detailed technical information, see `SCHEDULE_TRACKING_IMPLEMENTATION.md`  
For quick reference, see `SCHEDULE_TRACKING_QUICK_START.md`  
For visual guide, open `schedule_tracking_visual_guide.html`
