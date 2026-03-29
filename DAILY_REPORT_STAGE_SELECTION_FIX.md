# Daily Report Stage Selection Fix

## Problem
In the daily report section, users can't see any stages in the dropdown even after selecting a project. The dropdown shows "No stages available - select project first" even when a project is selected.

## Root Cause
The issue was in the `EnhancedProgressUpdate.jsx` component. The stage calculation logic only populated available stages when there were existing progress updates (`projectProgressUpdates.length > 0`). For new projects with no previous daily reports, the `availableStages` array remained empty.

## Solution Implemented

### 1. Enhanced Stage Calculation Logic
**File**: `frontend/src/components/EnhancedProgressUpdate.jsx`

Modified the useEffect that calculates available stages to handle both scenarios:
- **Projects with existing progress**: Calculate stages based on progress updates
- **New projects with no progress**: Initialize with Foundation stage as available

### 2. Key Changes Made

```javascript
// Before: Only calculated stages when progress updates existed
useEffect(() => {
  if (projectProgressUpdates.length > 0) {
    // Calculate stages...
  }
}, [projectProgressUpdates]);

// After: Handle both new and existing projects
useEffect(() => {
  if (selectedProject) {
    if (projectProgressUpdates.length > 0) {
      // Project has existing progress - calculate based on updates
      const breakdown = getStageProgressBreakdown(projectProgressUpdates);
      const available = getAvailableStages(breakdown);
      // ... set stages
    } else {
      // New project with no progress - start with Foundation stage
      const initialAvailable = [{
        ...CONSTRUCTION_STAGES[0], // Foundation stage
        is_current: true,
        remaining_percentage: CONSTRUCTION_STAGES[0].percentage
      }];
      setAvailableStages(initialAvailable);
      // ... auto-select Foundation stage
    }
  }
}, [projectProgressUpdates, selectedProject]);
```

### 3. Added Comprehensive Debugging
Added console logging to track:
- Project selection events
- Progress data loading
- Stage calculation process
- Available stages results

### 4. Auto-Selection Enhancement
For new projects, the system now automatically:
- Sets Foundation as the available stage
- Auto-selects Foundation in the dropdown
- Provides proper stage progression info

## Testing

### Manual Testing Steps
1. **Select a Project**: Choose any project from the dropdown
2. **Check Stage Dropdown**: Should now show at least "Foundation" stage
3. **Verify Auto-Selection**: Foundation should be auto-selected for new projects
4. **Check Console**: Debug logs should show the stage calculation process

### Test File Created
- `test_daily_report_stage_selection.html` - Standalone test to verify the fix

### Expected Behavior After Fix
- ✅ New projects show Foundation stage immediately after selection
- ✅ Projects with existing progress show appropriate next stages
- ✅ Stage dropdown never shows "No stages available" for selected projects
- ✅ Auto-selection works for both new and existing projects

## Files Modified
- `frontend/src/components/EnhancedProgressUpdate.jsx`

## Files Created
- `test_daily_report_stage_selection.html`
- `DAILY_REPORT_STAGE_SELECTION_FIX.md`

## Verification
The fix ensures that:
1. When a project is selected, stages are always available
2. New projects start with Foundation stage
3. Existing projects show appropriate progression stages
4. The dropdown provides clear feedback to users
5. Auto-selection improves user experience

## Debug Information
If issues persist, check browser console for:
- "Project selected: [project_id]"
- "Loading project data for: [project_id]"
- "Stage calculation useEffect triggered"
- "Available stages: [array]"

The fix addresses the core issue where stage availability wasn't properly initialized for projects without existing progress updates.