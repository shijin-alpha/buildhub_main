# Project Info Update Fix - Contractor Dashboard

## Issue
When switching between different projects in the contractor dashboard's progress update section, the project details card was not updating. It kept showing the same project information regardless of which project was selected.

## Root Cause
The `getSelectedProjectInfo()` function was being called during render without proper memoization. This caused:
1. Multiple unnecessary function calls during each render
2. React not detecting changes when `selectedProject` changed
3. The project info card displaying stale data

## Solution
Converted `getSelectedProjectInfo()` from a regular function to a memoized value using `React.useMemo`:

### Before:
```javascript
const getSelectedProjectInfo = () => {
  // Function logic...
  return project || null;
};

// Called during render:
const projectInfo = getSelectedProjectInfo();
```

### After:
```javascript
const selectedProjectInfo = React.useMemo(() => {
  // Same logic...
  return project || null;
}, [selectedProject, projects]);

// Used directly:
const projectInfo = selectedProjectInfo;
```

## Benefits
1. **Proper reactivity**: The project info now updates immediately when `selectedProject` changes
2. **Performance**: Computation only happens when dependencies (`selectedProject` or `projects`) change
3. **Cleaner code**: No function calls during render
4. **Better debugging**: Console logs show when the computation actually happens

## Testing
To verify the fix:
1. Open contractor dashboard
2. Navigate to Progress Update section
3. Select different projects from the dropdown
4. Verify that the project info card updates with the correct details for each project
5. Check console logs - should see "Computing selectedProjectInfo" only when project changes

## Files Modified
- `frontend/src/components/EnhancedProgressUpdate.jsx`
  - Converted `getSelectedProjectInfo()` to `selectedProjectInfo` useMemo hook
  - Updated render logic to use memoized value
