# Playwright Testing Setup for BuildHub

## Installation

```bash
npm install
npx playwright install
```

## Running Tests

### Run all tests
```bash
npm test
```

### Run tests in headed mode (see browser)
```bash
npm run test:headed
```

### Run tests in UI mode (interactive)
```bash
npm run test:ui
```

### Debug tests
```bash
npm run test:debug
```

### Run specific browser
```bash
npm run test:chromium
npm run test:firefox
npm run test:webkit
npm run test:mobile
```

### View test report
```bash
npm run test:report
```

## Test Structure

```
tests/
├── e2e/
│   ├── homeowner-dashboard.spec.js    # Homeowner dashboard tests
│   ├── contractor-dashboard.spec.js   # Contractor dashboard tests
│   ├── inspector-dashboard.spec.js    # Inspector dashboard tests
│   ├── payment-flow.spec.js           # Payment system tests
│   ├── ai-features.spec.js            # AI features tests
│   └── helpers/
│       └── auth.js                    # Authentication helpers
└── fixtures/
    └── sample-receipt.pdf             # Test fixtures
```

## Test Coverage

- **Homeowner Dashboard**: Login, project overview, payment requests
- **Contractor Dashboard**: Progress reports, receipt uploads, documents
- **Inspector Dashboard**: Inspection reports, project assignments
- **Payment Flow**: Custom requests, blockchain verification, history
- **AI Features**: Room improvement, risk assessment, schedule tracking

## Configuration

Edit `playwright.config.js` to customize:
- Base URL
- Timeout settings
- Browser configurations
- Screenshot/video settings
- Parallel execution

## Best Practices

1. Use page object models for complex pages
2. Keep tests independent and isolated
3. Use meaningful test descriptions
4. Add proper waits for async operations
5. Use fixtures for test data
6. Clean up test data after runs

## Debugging Tips

- Use `page.pause()` to pause execution
- Enable trace viewer: `trace: 'on'`
- Take screenshots: `await page.screenshot({ path: 'screenshot.png' })`
- Check console logs: `page.on('console', msg => console.log(msg.text()))`

## CI/CD Integration

The configuration includes CI-specific settings:
- Retries on failure
- Single worker for stability
- HTML reporter for artifacts
