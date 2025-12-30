# ProfileButton Integration Guide

## Overview
The ProfileButton component provides a complete profile button solution with a popup menu containing profile setup and logout options.

## Components Created
1. **ProfileButton.jsx** - Main component with popup functionality
2. **ProfilePopup.jsx** - Popup menu component
3. **ProfileButton.css** - Styling for the button
4. **ProfilePopup.css** - Styling for the popup menu

## Usage

### Basic Usage
```jsx
import ProfileButton from './components/ProfileButton';

// In your component
<ProfileButton user={user} />
```

### Advanced Usage
```jsx
<ProfileButton 
  user={user}
  size="large"           // 'small', 'medium', 'large'
  showRole={true}        // Show/hide role
  position="bottom-right" // 'bottom-right', 'bottom-left', 'top-right', 'top-left'
  className="custom-class"
/>
```

## Replacing Existing Profile Buttons

### Before (HTML structure):
```html
<div class="profile-button">
  <div class="profile-avatar">ST</div>
  <div class="profile-info">
    <div class="profile-name">Shijin Thomas</div>
    <div class="profile-role">Architect</div>
  </div>
</div>
```

### After (React component):
```jsx
<ProfileButton 
  user={{
    first_name: 'Shijin',
    last_name: 'Thomas',
    role: 'architect'
  }}
/>
```

## Features
- ✅ Click to open/close popup menu
- ✅ Click outside to close
- ✅ Profile setup navigation
- ✅ Logout functionality
- ✅ Responsive design
- ✅ Multiple sizes and positions
- ✅ Smooth animations
- ✅ Accessibility support
- ✅ Dark theme support

## Integration Steps
1. Import the ProfileButton component
2. Replace existing profile button HTML with the component
3. Pass user data as props
4. Customize size, position, and styling as needed

## Example Integration
```jsx
// In your dashboard component
import ProfileButton from './components/ProfileButton';

const Dashboard = () => {
  const [user, setUser] = useState(null);

  return (
    <div className="dashboard">
      <header>
        <ProfileButton user={user} />
      </header>
      {/* rest of your content */}
    </div>
  );
};
```




