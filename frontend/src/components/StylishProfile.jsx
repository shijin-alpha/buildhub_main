import React, { useState, useRef } from 'react';
import './StylishProfile.css';

const StylishProfile = ({ 
  user, 
  profile, 
  setProfile, 
  onSave, 
  onReset, 
  loading, 
  saving,
  reviews = [],
  reviewCount = 0,
  avgRating = 0,
  onUserUpdate
}) => {
  const [isEditing, setIsEditing] = useState(false);
  const [profileImage, setProfileImage] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const fileInputRef = useRef(null);

  const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (file) {
      setProfileImage(file);
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagePreview(e.target.result);
      };
      reader.readAsDataURL(file);

      // Upload to server
      try {
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('user_id', user?.id || '');

        const response = await fetch('/buildhub/backend/api/upload_avatar.php', {
          method: 'POST',
          body: formData,
          credentials: 'include'
        });

        const result = await response.json();
        if (result.success) {
          // Update user data with new avatar URL
          const updatedUser = { ...user, avatar_url: result.avatar_url };
          localStorage.setItem('bh_user', JSON.stringify(updatedUser));
          
          // Update the user state in the parent component
          if (typeof onUserUpdate === 'function') {
            onUserUpdate(updatedUser);
          }
          
          // Clear the imagePreview since we now have the URL
          setImagePreview(null);
          
          console.log('Avatar uploaded successfully:', result.avatar_url);
        } else {
          console.error('Avatar upload failed:', result.message);
          // Clear preview on failure
          setImagePreview(null);
        }
      } catch (error) {
        console.error('Error uploading avatar:', error);
        // Clear preview on error
        setImagePreview(null);
      }
    }
  };

  const handleEdit = () => {
    setIsEditing(true);
  };

  const handleCancel = () => {
    setIsEditing(false);
    onReset();
  };

  const handleSave = (e) => {
    e.preventDefault();
    onSave(e);
    setIsEditing(false);
  };

  const getInitials = () => {
    if (user?.first_name && user?.last_name) {
      return `${user.first_name.charAt(0)}${user.last_name.charAt(0)}`.toUpperCase();
    }
    return 'A';
  };

  const renderStars = (rating) => {
    return [1, 2, 3, 4, 5].map(star => (
      <span 
        key={star} 
        className={`star ${rating >= star ? 'filled' : ''}`}
      >
        ★
      </span>
    ));
  };

  return (
    <div className="stylish-profile">
      {/* Profile Header */}
      <div className="profile-header">
        <div className="profile-cover">
          <div className="cover-gradient"></div>
        </div>
        
        <div className="profile-info-section">
          <div className="profile-avatar-container">
            <div className="profile-avatar-large">
              {imagePreview ? (
                <img src={imagePreview} alt="Profile" className="avatar-image" />
              ) : user?.avatar_url ? (
                <img src={user.avatar_url} alt="Profile" className="avatar-image" />
              ) : (
                <div className="avatar-initials">{getInitials()}</div>
              )}
              {isEditing && (
                <button 
                  className="avatar-upload-btn"
                  onClick={() => fileInputRef.current?.click()}
                >
                  📷
                </button>
              )}
            </div>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/*"
              onChange={handleImageUpload}
              style={{ display: 'none' }}
            />
          </div>
          
          <div className="profile-details">
            <h1 className="profile-name">
              {user?.first_name} {user?.last_name}
            </h1>
            <p className="profile-title">Architect</p>
            <div className="profile-stats">
              <div className="stat-item">
                <span className="stat-value">{avgRating.toFixed(1)}</span>
                <span className="stat-label">Rating</span>
                <div className="stars">{renderStars(avgRating)}</div>
              </div>
              <div className="stat-item">
                <span className="stat-value">{reviewCount}</span>
                <span className="stat-label">Reviews</span>
              </div>
              <div className="stat-item">
                <span className="stat-value">{profile.experience_years || 0}</span>
                <span className="stat-label">Years Experience</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Profile Content */}
      <div className="profile-content">
        <div className="profile-actions">
          {!isEditing ? (
            <button className="edit-btn" onClick={handleEdit}>
              ✏️ Edit Profile
            </button>
          ) : (
            <div className="edit-actions">
              <button className="cancel-btn" onClick={handleCancel}>
                Cancel
              </button>
              <button 
                className="save-btn" 
                onClick={handleSave}
                disabled={saving}
              >
                {saving ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          )}
        </div>

        <div className="profile-sections">
          {/* Basic Information */}
          <div className="profile-section">
            <h3 className="section-title">Basic Information</h3>
            <div className="info-grid">
              <div className="info-item">
                <label>Email</label>
                {isEditing ? (
                  <input 
                    type="email" 
                    value={profile.email} 
                    disabled
                    className="info-input disabled"
                  />
                ) : (
                  <span className="info-value">{profile.email}</span>
                )}
              </div>
              
              <div className="info-item">
                <label>Phone</label>
                {isEditing ? (
                  <input 
                    type="text" 
                    value={profile.phone} 
                    onChange={(e) => setProfile({...profile, phone: e.target.value})}
                    className="info-input"
                    placeholder="Enter phone number"
                  />
                ) : (
                  <span className="info-value">{profile.phone || 'Not provided'}</span>
                )}
              </div>
              
              <div className="info-item">
                <label>City</label>
                {isEditing ? (
                  <input 
                    type="text" 
                    value={profile.city} 
                    onChange={(e) => setProfile({...profile, city: e.target.value})}
                    className="info-input"
                    placeholder="Enter city"
                  />
                ) : (
                  <span className="info-value">{profile.city || 'Not provided'}</span>
                )}
              </div>
            </div>
          </div>

          {/* Professional Information */}
          <div className="profile-section">
            <h3 className="section-title">Professional Information</h3>
            <div className="info-grid">
              <div className="info-item full-width">
                <label>Specialization</label>
                {isEditing ? (
                  <div className="specialization-input-container">
                    <input 
                      type="text" 
                      value={profile.specialization} 
                      onChange={(e) => setProfile({...profile, specialization: e.target.value})}
                      className="info-input"
                      placeholder="e.g., Residential Architect, Commercial Architect"
                      list="specialization-options"
                    />
                    <datalist id="specialization-options">
                      <option value="Residential Architect" />
                      <option value="Commercial Architect" />
                      <option value="Interior Designer" />
                      <option value="Landscape Architect" />
                      <option value="Urban Planner" />
                      <option value="Industrial Architect" />
                      <option value="Healthcare Architect" />
                      <option value="Educational Architect" />
                      <option value="Restoration/Conservation Architect" />
                      <option value="Sustainable/Green Architect" />
                      <option value="Hospitality Architect" />
                      <option value="Transport Architect" />
                    </datalist>
                  </div>
                ) : (
                  <span className="info-value">{profile.specialization || 'Not specified'}</span>
                )}
              </div>
              
              <div className="info-item">
                <label>Experience (Years)</label>
                {isEditing ? (
                  <input 
                    type="number" 
                    min="0"
                    value={profile.experience_years} 
                    onChange={(e) => setProfile({...profile, experience_years: e.target.value})}
                    className="info-input"
                    placeholder="0"
                  />
                ) : (
                  <span className="info-value">{profile.experience_years || 0} years</span>
                )}
              </div>
            </div>
          </div>

          {/* Reviews Section */}
          {reviews.length > 0 && (
            <div className="profile-section">
              <h3 className="section-title">Recent Reviews</h3>
              <div className="reviews-list">
                {reviews.slice(0, 3).map((review, index) => (
                  <div key={index} className="review-card">
                    <div className="review-header">
                      <div className="reviewer-info">
                        <span className="reviewer-name">{review.author || 'Anonymous'}</span>
                        <div className="review-stars">{renderStars(review.rating)}</div>
                      </div>
                      <span className="review-date">
                        {new Date(review.created_at).toLocaleDateString()}
                      </span>
                    </div>
                    {review.comment && (
                      <p className="review-comment">{review.comment}</p>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default StylishProfile;
