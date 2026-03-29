import React, { useState } from 'react';
import ArchitectProfileButton from './ArchitectProfileButton';

const ProfileButtonTest = () => {
  const [activeTab, setActiveTab] = useState('dashboard');
  
  // Mock user data
  const user = {
    first_name: 'Shijin',
    last_name: 'Thomas',
    role: 'architect',
    email: 'shijin@example.com'
  };

  const handleLogout = async () => {
    console.log('Logout clicked');
    // Simulate logout
    alert('Logout functionality triggered!');
  };

  const handleProfileClick = () => {
    console.log('Profile setup clicked');
    setActiveTab('profile');
    alert('Profile setup clicked! Active tab set to: ' + activeTab);
  };

  return (
    <div style={{ padding: '20px', background: '#f5f5f5', minHeight: '100vh' }}>
      <h1>Profile Button Test</h1>
      
      <div style={{ marginBottom: '20px' }}>
        <h2>Current Active Tab: {activeTab}</h2>
      </div>

      <div style={{ marginBottom: '20px' }}>
        <h3>Architect Profile Button</h3>
        <ArchitectProfileButton 
          user={user}
          onProfileClick={handleProfileClick}
          onLogout={handleLogout}
          position="bottom-right"
        />
      </div>

      <div style={{ marginTop: '20px', padding: '20px', background: 'white', borderRadius: '8px' }}>
        <h3>Test Instructions:</h3>
        <ol>
          <li>Click the profile button above</li>
          <li>Click "Profile Setup" - should show alert and change active tab</li>
          <li>Click the profile button again</li>
          <li>Click "Logout" - should show logout alert</li>
        </ol>
      </div>
    </div>
  );
};

export default ProfileButtonTest;




