# Schedule Tracking System - Visual Guide

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    SCHEDULE TRACKING SYSTEM                      │
│                   (Backward Compatible Enhancement)              │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────┐         ┌──────────────────┐
│   CONTRACTOR     │         │    HOMEOWNER     │
│   Dashboard      │         │    Dashboard     │
└────────┬─────────┘         └────────┬─────────┘
         │                            │
         │ Can Edit                   │ Read Only
         │                            │
         ▼                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND COMPONENTS                       │
├──────────────────────────────┬──────────────────────────────────┤
│  ContractorScheduleInput.jsx │  HomeownerScheduleView.jsx       │
│  - Set planned dates         │  - View planned schedule         │
│  - Record actual dates       │  - View actual progress          │
│  - View performance metrics  │  - See performance metrics       │
└──────────────┬───────────────┴──────────────┬───────────────────┘
               │                              │
               │ API Calls                    │ API Calls
               │                              │
               ▼                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                          API LAYER                               │
├──────────────────────────────┬──────────────────────────────────┤
│  update_planned_schedule.php │  get_schedule_summary.php        │
│  update_actual_dates.php     │  (All roles)                     │
│  (Contractor only)           │                                  │
└──────────────┬───────────────┴──────────────┬───────────────────┘
               │                              │
               │ SQL Queries                  │ SQL Queries
               │                              │
               ▼                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       DATABASE LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│  construction_projects table (6 new columns)                    │
│  - planned_start_date, planned_end_date                         │
│  - actual_start_date, actual_end_date                           │
│  - actual_time_overrun_percentage, schedule_locked              │
├─────────────────────────────────────────────────────────────────┤
│  schedule_change_audit table (audit trail)                      │
├─────────────────────────────────────────────────────────────────┤
│  TRIGGERS:                                                       │
│  - lock_planned_dates_on_actual_start                           │
│  - auto_calculate_overrun_on_completion                         │
├─────────────────────────────────────────────────────────────────┤
│  STORED PROCEDURE:                                               │
│  - calculate_time_overrun(project_id)                           │
├─────────────────────────────────────────────────────────────────┤
│  VIEW:                                                           │
│  - project_schedule_summary                                     │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

### Scenario 1: Setting Planned Schedule

```
┌──────────────┐
│ Contractor   │
│ enters dates │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────┐
│ update_planned_schedule.php         │
│ - Validates dates                   │
│ - Checks if schedule is locked      │
│ - Verifies contractor ownership     │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Database UPDATE                     │
│ SET planned_start_date = ?          │
│     planned_end_date = ?            │
│ WHERE id = ? AND contractor_id = ?  │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Audit Log INSERT                    │
│ Records who changed what and when   │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Response with calculated duration   │
│ planned_duration_days = DATEDIFF()  │
└─────────────────────────────────────┘
```

### Scenario 2: Starting Project (Locks Planned Dates)

```
┌──────────────┐
│ Contractor   │
│ sets actual  │
│ start date   │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────┐
│ update_actual_dates.php             │
│ - Validates date                    │
│ - Checks contractor ownership       │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Database UPDATE                     │
│ SET actual_start_date = ?           │
│     schedule_locked = 1             │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ TRIGGER: lock_planned_dates_on_     │
│          actual_start               │
│ - Sets schedule_locked = 1          │
│ - Prevents future planned changes   │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Audit Log INSERT                    │
│ Records actual_start_date change    │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Response: schedule_locked = true    │
└─────────────────────────────────────┘
```

### Scenario 3: Completing Project (Auto-Calculate Overrun)

```
┌──────────────┐
│ Contractor   │
│ sets actual  │
│ end date     │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────┐
│ update_actual_dates.php             │
│ - Validates date > start date       │
│ - Updates status to 'completed'     │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Database UPDATE                     │
│ SET actual_end_date = ?             │
│     status = 'completed'            │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ TRIGGER: auto_calculate_overrun_    │
│          on_completion              │
│ - Calls calculate_time_overrun()    │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ STORED PROCEDURE:                   │
│ calculate_time_overrun(project_id)  │
│                                     │
│ planned_duration = DATEDIFF(        │
│   planned_end, planned_start)       │
│                                     │
│ actual_duration = DATEDIFF(         │
│   actual_end, actual_start)         │
│                                     │
│ overrun_pct = ((actual - planned)   │
│   / planned) * 100                  │
│                                     │
│ UPDATE actual_time_overrun_         │
│        percentage = overrun_pct     │
└──────┬──────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────┐
│ Response with all metrics:          │
│ - delay_days                        │
│ - actual_time_overrun_percentage    │
│ - schedule_status                   │
└─────────────────────────────────────┘
```

## Timeline Visualization

### Project Lifecycle States

```
STATE 1: Not Scheduled
┌─────────────────────────────────────┐
│ Project Created                     │
│ planned_start_date: NULL            │
│ planned_end_date: NULL              │
│ actual_start_date: NULL             │
│ actual_end_date: NULL               │
│ schedule_locked: 0                  │
└─────────────────────────────────────┘

        ↓ Contractor sets planned dates

STATE 2: Scheduled
┌─────────────────────────────────────┐
│ Planned Schedule Set                │
│ planned_start_date: 2026-03-01      │
│ planned_end_date: 2026-09-01        │
│ actual_start_date: NULL             │
│ actual_end_date: NULL               │
│ schedule_locked: 0                  │
│ ✏️  Planned dates can still be edited│
└─────────────────────────────────────┘

        ↓ Contractor records actual start

STATE 3: In Progress (Locked)
┌─────────────────────────────────────┐
│ Work Has Begun                      │
│ planned_start_date: 2026-03-01      │
│ planned_end_date: 2026-09-01        │
│ actual_start_date: 2026-03-05       │
│ actual_end_date: NULL               │
│ schedule_locked: 1 🔒               │
│ ⚠️  Planned dates are now locked    │
└─────────────────────────────────────┘

        ↓ Contractor records completion

STATE 4: Completed (With Metrics)
┌─────────────────────────────────────┐
│ Project Completed                   │
│ planned_start_date: 2026-03-01      │
│ planned_end_date: 2026-09-01        │
│ actual_start_date: 2026-03-05       │
│ actual_end_date: 2026-10-15         │
│ schedule_locked: 1 🔒               │
│                                     │
│ CALCULATED METRICS:                 │
│ planned_duration: 184 days          │
│ actual_duration: 224 days           │
│ delay_days: +44 days                │
│ time_overrun: +21.74%               │
└─────────────────────────────────────┘
```

## Visual Timeline Comparison

```
PLANNED SCHEDULE:
Mar 1 ────────────────────────────────────────────────► Sep 1
       ◄──────────── 184 days ──────────────►

ACTUAL EXECUTION:
Mar 5 ──────────────────────────────────────────────────────► Oct 15
       ◄──────────────── 224 days ────────────────────►

ANALYSIS:
├─ Started 4 days late
├─ Took 40 more days than planned
└─ Completed 44 days behind schedule
   Time Overrun: +21.74%
```

## User Interface Mockup

### Contractor View

```
┌─────────────────────────────────────────────────────────────┐
│ 📅 Project Schedule Management                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Planned Schedule                              🔒 Locked │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ ⚠️  Planned dates are locked because actual work has    │ │
│ │    begun.                                               │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Planned Start Date:  [2026-03-01] (disabled)           │ │
│ │ Planned End Date:    [2026-09-01] (disabled)           │ │
│ │                                                         │ │
│ │ Planned Duration: 184 days                             │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Actual Dates                                            │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Actual Start Date:   [2026-03-05] ✓ Already recorded   │ │
│ │ Actual End Date:     [          ] (optional)            │ │
│ │                                                         │ │
│ │ [Update Actual Dates]                                   │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📊 Schedule Performance                                 │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Delay: 4 days behind                                    │ │
│ │ Status: In Progress                                     │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Homeowner View

```
┌─────────────────────────────────────────────────────────────┐
│ 📅 Project Schedule                    [In Progress]        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ℹ️  Project is currently 4 days behind schedule            │
│                                                             │
│ ┌──────────────────────┐  ┌──────────────────────┐        │
│ │ 📋 Planned Schedule  │  │ ✅ Actual Progress   │        │
│ ├──────────────────────┤  ├──────────────────────┤        │
│ │ Start: Mar 1, 2026   │  │ Started: Mar 5, 2026 │        │
│ │ End:   Sep 1, 2026   │  │ Expected: Sep 1, 2026│        │
│ │ Duration: 184 days   │  │                      │        │
│ └──────────────────────┘  └──────────────────────┘        │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ 📊 Schedule Performance                                 │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ ⏰ Schedule Status: 4 days behind                       │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                             │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Timeline Comparison                                     │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Planned  ████████████████████████████████████ 184 days │ │
│ │ Actual   ████████████████████████████████████ (ongoing)│ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Database Relationships

```
┌─────────────────────────────────────────────────────────────┐
│ construction_projects                                       │
├─────────────────────────────────────────────────────────────┤
│ id (PK)                                                     │
│ contractor_id (FK → users)                                  │
│ homeowner_id (FK → users)                                   │
│ ...existing fields...                                       │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ NEW SCHEDULE FIELDS:                                    │ │
│ │ planned_start_date          DATE NULL                   │ │
│ │ planned_end_date            DATE NULL                   │ │
│ │ actual_start_date           DATE NULL                   │ │
│ │ actual_end_date             DATE NULL                   │ │
│ │ actual_time_overrun_percentage  DECIMAL(10,2) NULL      │ │
│ │ schedule_locked             TINYINT(1) DEFAULT 0        │ │
│ └─────────────────────────────────────────────────────────┘ │
└──────────────┬──────────────────────────────────────────────┘
               │
               │ 1:N relationship
               │
               ▼
┌─────────────────────────────────────────────────────────────┐
│ schedule_change_audit                                       │
├─────────────────────────────────────────────────────────────┤
│ id (PK)                                                     │
│ project_id (FK → construction_projects)                     │
│ changed_by_user_id (FK → users)                             │
│ changed_by_role (contractor/admin)                          │
│ field_changed (planned_start_date, etc.)                    │
│ old_value                                                   │
│ new_value                                                   │
│ change_reason                                               │
│ changed_at                                                  │
└─────────────────────────────────────────────────────────────┘
```

## Calculation Formula Visualization

```
TIME OVERRUN PERCENTAGE CALCULATION:

Step 1: Calculate Planned Duration
┌─────────────────────────────────────┐
│ planned_duration =                  │
│   DATEDIFF(planned_end_date,        │
│            planned_start_date)      │
│                                     │
│ Example: DATEDIFF('2026-09-01',     │
│                   '2026-03-01')     │
│        = 184 days                   │
└─────────────────────────────────────┘

Step 2: Calculate Actual Duration
┌─────────────────────────────────────┐
│ actual_duration =                   │
│   DATEDIFF(actual_end_date,         │
│            actual_start_date)       │
│                                     │
│ Example: DATEDIFF('2026-10-15',     │
│                   '2026-03-05')     │
│        = 224 days                   │
└─────────────────────────────────────┘

Step 3: Calculate Overrun Percentage
┌─────────────────────────────────────┐
│ overrun_percentage =                │
│   ((actual_duration -               │
│     planned_duration) /             │
│    planned_duration) × 100          │
│                                     │
│ Example: ((224 - 184) / 184) × 100  │
│        = (40 / 184) × 100           │
│        = 0.2174 × 100               │
│        = 21.74%                     │
└─────────────────────────────────────┘

INTERPRETATION:
┌─────────────────────────────────────┐
│ +21.74% means:                      │
│ Project took 21.74% MORE time       │
│ than originally planned             │
│                                     │
│ Negative values would mean:         │
│ Project completed FASTER            │
│ than planned                        │
└─────────────────────────────────────┘
```

## Security & Access Control

```
┌─────────────────────────────────────────────────────────────┐
│                    ACCESS CONTROL MATRIX                     │
├──────────────┬──────────────┬──────────────┬───────────────┤
│ Action       │ Contractor   │ Homeowner    │ Admin         │
├──────────────┼──────────────┼──────────────┼───────────────┤
│ View         │ ✅ Yes       │ ✅ Yes       │ ✅ Yes        │
│ Schedule     │              │              │               │
├──────────────┼──────────────┼──────────────┼───────────────┤
│ Set Planned  │ ✅ Yes       │ ❌ No        │ ❌ No         │
│ Dates        │ (before lock)│              │               │
├──────────────┼──────────────┼──────────────┼───────────────┤
│ Set Actual   │ ✅ Yes       │ ❌ No        │ ❌ No         │
│ Dates        │              │              │               │
├──────────────┼──────────────┼──────────────┼───────────────┤
│ View Audit   │ ✅ Yes       │ ❌ No        │ ✅ Yes        │
│ Trail        │              │              │               │
└──────────────┴──────────────┴──────────────┴───────────────┘

VALIDATION LAYERS:
┌─────────────────────────────────────┐
│ 1. Session Authentication           │
│    - Verify user is logged in       │
│    - Check user role                │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 2. API Authorization                │
│    - Verify contractor owns project │
│    - Check schedule lock status     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 3. Database Triggers                │
│    - Enforce lock on planned dates  │
│    - Prevent unauthorized changes   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ 4. Audit Logging                    │
│    - Record all changes             │
│    - Track who, what, when          │
└─────────────────────────────────────┘
```

## Installation Flow

```
START
  │
  ▼
┌─────────────────────────────────────┐
│ Run install_schedule_tracking.bat   │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Apply database schema               │
│ - Add 6 columns                     │
│ - Create audit table                │
│ - Create stored procedure           │
│ - Create triggers                   │
│ - Create view                       │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Verify installation                 │
│ - Check columns exist               │
│ - Check triggers created            │
│ - Check procedure exists            │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Test APIs                           │
│ - Open test_schedule_tracking.html  │
│ - Test with sample project          │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Integrate frontend components       │
│ - Import React components           │
│ - Add to dashboards                 │
└──────────────┬──────────────────────┘
               │
               ▼
             DONE
```

## Summary

This visual guide demonstrates:
- ✅ Complete system architecture
- ✅ Data flow for all scenarios
- ✅ Timeline state transitions
- ✅ UI mockups for both roles
- ✅ Database relationships
- ✅ Calculation formulas
- ✅ Security layers
- ✅ Installation process

The system is production-ready and fully backward compatible!
