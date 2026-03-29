import React from 'react';
import ProfileButton from './ProfileButton';

const ProfileButtonExample = () => {
  // Example user data
  const user = {
    first_name: 'Shijin',
    last_name: 'Thomas',
    role: 'architect',
    email: 'shijin@example.com'
  };

  return (
    <div style={{ padding: '20px', display: 'flex', flexDirection: 'column', gap: '20px' }}>
      <h2>Profile Button Examples</h2>
      
      {/* Basic Profile Button */}
      <div>
        <h3>Basic Profile Button</h3>
        <ProfileButton user={user} />
      </div>

      {/* Small Profile Button */}
      <div>
        <h3>Small Profile Button</h3>
        <ProfileButton user={user} size="small" />
      </div>

      {/* Large Profile Button */}
      <div>
        <h3>Large Profile Button</h3>
        <ProfileButton user={user} size="large" />
      </div>

      {/* Without Role */}
      <div>
        <h3>Without Role</h3>
        <ProfileButton user={user} showRole={false} />
      </div>

      {/* Compact Version */}
      <div>
        <h3>Compact Version</h3>
        <ProfileButton user={user} className="compact" />
      </div>

      {/* Dark Theme */}
      <div style={{ background: '#1e293b', padding: '20px', borderRadius: '8px' }}>
        <h3 style={{ color: 'white' }}>Dark Theme</h3>
        <ProfileButton user={user} className="dark" />
      </div>

      {/* Different Positions */}
      <div>
        <h3>Different Positions</h3>
        <div style={{ display: 'flex', gap: '20px', flexWrap: 'wrap' }}>
          <div>
            <p>Bottom Right (default)</p>
            <ProfileButton user={user} position="bottom-right" />
          </div>
          <div>
            <p>Bottom Left</p>
            <ProfileButton user={user} position="bottom-left" />
          </div>
          <div>
            <p>Top Right</p>
            <ProfileButton user={user} position="top-right" />
          </div>
          <div>
            <p>Top Left</p>
            <ProfileButton user={user} position="top-left" />
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfileButtonExample;




