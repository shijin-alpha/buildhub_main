# Construction Progress Button Tests

## Overview
This test suite verifies that all buttons in the Construction Progress section of the Homeowner Dashboard are clickable and functioning correctly.

## Test Coverage

### 1. Refresh Button Test
- **Location**: Progress Overview widget
- **Verifies**:
  - Button is visible and enabled
  - Clicking triggers data reload
  - Loading spinner appears during refresh
  - Content updates after refresh completes

### 2. View Details Button Test
- **Location**: Budget Tracker widget
- **Verifies**:
  - Button is visible and enabled
  - Clicking displays budget details alert
  - Alert contains correct budget information

### 3. All Buttons Interactive Test
- **Verifies**:
  - All icon buttons are found (minimum 2)
  - Each button is visible and enabled
  - Button titles are properly set

### 4. Refresh Data Update Test
- **Verifies**:
  - Content changes after refresh
  - Loading states work correctly
  - Data is properly reloaded

### 5. Hover States Test
- **Verifies**:
  - Buttons have CSS hover effects
  - Buttons remain functional during hover
  - Visual feedback is provided

### 6. Accessibility Test
- **Verifies**:
  - Title attributes exist for tooltips
  - Proper accessibility attributes are set

### 7. Multiple Clicks Test
- **Verifies**:
  - Buttons work after repeated clicks
  - No state corruption occurs
  - Loading states handle rapid clicks

## Running the Tests

### Run all homeowner dashboard tests:
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js
```

### Run only construction progress button tests:
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js -g "Construction Progress Buttons"
```

### Run a specific button test:
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js -g "refresh button"
```

### Run with UI mode (visual debugging):
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js --ui
```

### Run in headed mode (see browser):
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js --headed
```

### Generate HTML report:
```bash
npx playwright test tests/e2e/homeowner-dashboard.spec.js
npx playwright show-report
```

## Prerequisites

1. Server must be running on `http://localhost:3000`
2. Valid homeowner credentials configured
3. Database with test data populated
4. Backend API endpoints functional

## Test Data Requirements

The tests expect:
- Valid homeowner account (shijinthomas123@gmail.com)
- At least one project with progress data
- Budget information available
- API endpoint: `backend/api/homeowner/get_dashboard_data.php`

## Buttons Tested

| Button | Location | Function | Test Coverage |
|--------|----------|----------|---------------|
| Refresh (↻) | Progress Overview | Reload progress data | ✅ Full |
| View Details (👁️) | Budget Tracker | Show budget alert | ✅ Full |

## Expected Behavior

### Refresh Button
1. Click → Loading spinner appears
2. API call to fetch fresh data
3. Content updates with new data
4. Spinner disappears

### View Details Button
1. Click → Alert dialog appears
2. Shows budget summary information
3. User can dismiss alert
4. No page navigation occurs

## Troubleshooting

### Test Fails: Button Not Found
- Verify server is running
- Check if dashboard loads correctly
- Ensure widgets are rendered

### Test Fails: Timeout
- Increase timeout values
- Check API response times
- Verify network connectivity

### Test Fails: Dialog Not Detected
- Ensure dialog handler is set before click
- Check browser console for errors
- Verify showBudgetDetails() function exists

## Future Enhancements

- [ ] Test keyboard navigation (Tab, Enter)
- [ ] Test screen reader compatibility
- [ ] Test mobile responsive button behavior
- [ ] Test error handling when API fails
- [ ] Test button states during network errors
