import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/HomeownerProfile.css';
import { toast } from 'react-toastify';

const HomeownerProfile = () => {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [viewMode, setViewMode] = useState('view'); // view | edit
  
  // Form state
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    location: '',
    avatar_url: ''
  });
  
  // File upload state
  const [selectedFile, setSelectedFile] = useState(null);
  const [previewUrl, setPreviewUrl] = useState('');
  const [uploadProgress, setUploadProgress] = useState(0);
  const [isUploading, setIsUploading] = useState(false);

  // Cropper state
  const [cropSrc, setCropSrc] = useState('');
  const [showCropper, setShowCropper] = useState(false);
  const [cropBox, setCropBox] = useState({ x: 50, y: 50, size: 200 });
  const [isDragging, setIsDragging] = useState(false);
  const [dragOffset, setDragOffset] = useState({ x: 0, y: 0 });
  const imageRef = React.useRef(null);
  const cropContainerRef = React.useRef(null);
  
  // Track if component is mounted
  const isMountedRef = React.useRef(false);

  // Detect location helper
  const getUserLocation = () => {
    if (!('geolocation' in navigator)) {
      toast.error('Geolocation not supported');
      return;
    }
    navigator.geolocation.getCurrentPosition(async (pos) => {
      try {
        const { latitude, longitude } = pos.coords;
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
        const data = await res.json();
        const display = data?.display_name || `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
        setFormData(prev => ({ ...prev, location: display }));
      } catch {
        setFormData(prev => ({ ...prev, location: `${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)}` }));
      }
    }, () => {
      toast.error('Unable to get location');
    });
  };

  // Fetch user data on component mount
  useEffect(() => {
    isMountedRef.current = true;
    const fetchUserData = async () => {
      try {
        setLoading(true);
        setError('');
        const sessionUser = JSON.parse(sessionStorage.getItem('user') || '{}');
        console.log('Session user:', sessionUser);
        if (!sessionUser.id) {
          setError('User not found. Please log in again.');
          setTimeout(() => navigate('/login'), 2000);
          return;
        }
        console.log('Fetching profile for user_id:', sessionUser.id);
        const response = await fetch(`/buildhub/backend/api/homeowner/get_profile.php?user_id=${sessionUser.id}`, {
          method: 'GET',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include'
        });
        if (!response.ok) throw new Error('Failed to fetch user data');
        const responseData = await response.json();
        if (!responseData.success) throw new Error(responseData.message || 'Failed to fetch user data');

        setUser(responseData.data);
        setFormData({
          first_name: responseData.data.first_name || '',
          last_name: responseData.data.last_name || '',
          email: responseData.data.email || '',
          phone: responseData.data.phone || '',
          location: responseData.data.location || '',
          avatar_url: responseData.data.avatar_url || ''
        });
        if (responseData.data.avatar_url) setPreviewUrl(responseData.data.avatar_url);
      } catch (err) {
        if (isMountedRef.current) {
          console.error('Error fetching user data:', err);
          toast.error('Unable to load profile data.');
          setError('Failed to load profile data. Please try again later.');
        }
      } finally {
        if (isMountedRef.current) setLoading(false);
      }
    };

    if (isMountedRef.current) fetchUserData();
    return () => { isMountedRef.current = false; };
  }, [navigate]);
  
  // Handle input changes
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };
  
  // Handle file selection -> open cropper
  const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
      setError('Please select a valid image file (JPEG, PNG, GIF, WEBP)');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      setError('Image size should be less than 5MB');
      return;
    }
    const reader = new FileReader();
    reader.onloadend = () => {
      setCropSrc(reader.result || '');
      setShowCropper(true);
      setCropBox({ x: 50, y: 50, size: 200 });
    };
    reader.readAsDataURL(file);
    setError('');
  };

  // Cropper mouse handlers
  const onCropMouseDown = (e) => {
    setIsDragging(true);
    const rect = cropContainerRef.current?.getBoundingClientRect();
    if (!rect) return;
    setDragOffset({ x: e.clientX - rect.left - cropBox.x, y: e.clientY - rect.top - cropBox.y });
    e.preventDefault();
  };
  const onCropMouseMove = (e) => {
    if (!isDragging) return;
    const rect = cropContainerRef.current?.getBoundingClientRect();
    if (!rect) return;
    const nx = Math.max(0, Math.min((e.clientX - rect.left) - dragOffset.x, rect.width - cropBox.size));
    const ny = Math.max(0, Math.min((e.clientY - rect.top) - dragOffset.y, rect.height - cropBox.size));
    setCropBox(prev => ({ ...prev, x: nx, y: ny }));
  };
  const onCropMouseUp = () => setIsDragging(false);

  // Confirm crop
  const confirmCrop = async () => {
    try {
      const img = imageRef.current;
      const cont = cropContainerRef.current;
      if (!img || !cont) return;
      const contRect = cont.getBoundingClientRect();
      const imgRect = img.getBoundingClientRect();

      const scaleX = img.naturalWidth / imgRect.width;
      const scaleY = img.naturalHeight / imgRect.height;

      const offsetX = imgRect.left - contRect.left;
      const offsetY = imgRect.top - contRect.top;
      const sx = Math.max(0, (cropBox.x - offsetX) * scaleX);
      const sy = Math.max(0, (cropBox.y - offsetY) * scaleY);
      const sSize = cropBox.size * Math.max(scaleX, scaleY);

      const canvas = document.createElement('canvas');
      const outSize = 256;
      canvas.width = outSize;
      canvas.height = outSize;
      const ctx = canvas.getContext('2d');
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(img, sx, sy, sSize, sSize, 0, 0, outSize, outSize);
      const dataUrl = canvas.toDataURL('image/png');

      setPreviewUrl(dataUrl);

      canvas.toBlob((blob) => {
        if (!blob) return;
        const croppedFile = new File([blob], 'avatar.png', { type: 'image/png' });
        setSelectedFile(croppedFile);
      }, 'image/png', 0.92);

      setShowCropper(false);
      toast.success('Image cropped');
    } catch (e) {
      toast.error('Failed to crop image');
    }
  };

  const cancelCrop = () => {
    setShowCropper(false);
    setCropSrc('');
  };
  
  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      setError('');
      setSuccess('');
      let avatarUrl = formData.avatar_url;
      if (selectedFile) {
        setIsUploading(true);
        const fd = new FormData();
        fd.append('avatar', selectedFile);
        fd.append('user_id', user.id);
        const uploadResponse = await fetch('/buildhub/backend/api/upload_avatar.php', {
          method: 'POST',
          body: fd,
          credentials: 'include'
        });
        if (!uploadResponse.ok) throw new Error('Failed to upload profile picture');
        const uploadResult = await uploadResponse.json();
        if (!uploadResult.success) throw new Error(uploadResult.message || 'Failed to upload profile picture');
        avatarUrl = uploadResult.avatar_url;
        setIsUploading(false);
        setUploadProgress(100);
        setTimeout(() => setUploadProgress(0), 1000);
      }

      const updateResponse = await fetch(`/buildhub/backend/api/homeowner/update_profile.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: user.id,
          first_name: formData.first_name,
          last_name: formData.last_name,
          phone: formData.phone,
          location: formData.location,
          avatar_url: avatarUrl
        }),
        credentials: 'include'
      });
      if (!updateResponse.ok) {
        const errorData = await updateResponse.json().catch(() => ({}));
        throw new Error(errorData.message || 'Failed to update profile');
      }
      const updatedUser = await updateResponse.json();
      if (!updatedUser.success) throw new Error(updatedUser.message || 'Failed to update profile');

      const sessionUser = JSON.parse(sessionStorage.getItem('user') || '{}');
      sessionStorage.setItem('user', JSON.stringify({
        ...sessionUser,
        ...updatedUser.data,
        avatar_url: avatarUrl
      }));

      setSuccess('Profile updated successfully!');
      toast.success('Profile updated successfully!');
      setUser(updatedUser.data);
      setFormData({
        first_name: updatedUser.data.first_name || '',
        last_name: updatedUser.data.last_name || '',
        email: updatedUser.data.email || '',
        phone: updatedUser.data.phone || '',
        location: updatedUser.data.location || '',
        avatar_url: avatarUrl || updatedUser.data.avatar_url || ''
      });
      setViewMode('view');
      navigate('/homeowner-dashboard', { replace: true });
    } catch (err) {
      console.error('Error updating profile:', err);
      const errorMessage = err.message || 'Failed to update profile. Please try again later.';
      setError(errorMessage);
      toast.error(errorMessage);
    } finally {
      setLoading(false);
      setIsUploading(false);
    }
  };
  
  // Handle cancel button
  const handleCancel = () => {
    setViewMode('view');
  };
  
  if (loading && !user) {
    return (
      <div className="profile-container">
        <div className="profile-card">
          <div className="loading-spinner">Loading...</div>
        </div>
      </div>
    );
  }
  
  return (
    <div>
      <div style={{padding:'10px 12px'}}>
        <button type="button" className="btn btn-secondary" onClick={() => navigate(-1)} title="Back">← Back</button>
      </div>
      <div className="profile-container">
        <div className="profile-header" style={{ background:'#eef2ff', border:'1px solid #e0e7ff', borderRadius:12, padding:'16px 18px', display:'flex', justifyContent:'space-between', alignItems:'center' }}>
          <div>
            <h1 style={{ margin:0, color:'#1e40af' }}>My Profile</h1>
            <p style={{ margin:'6px 0 0 0', color:'#475569' }}>Manage your account and personal information</p>
          </div>
          {viewMode === 'view' ? (
            <button className="btn btn-primary" onClick={() => setViewMode('edit')} style={{ background:'#2563eb', borderColor:'#2563eb' }}>Edit</button>
          ) : (
            <button className="btn btn-secondary" onClick={() => setViewMode('view')}>Back</button>
          )}
        </div>
        
        <div className="profile-card" style={{ border:'1px solid #e5e7eb', borderRadius:12 }}>
          {error && <div className="alert alert-error">{error}</div>}
          {success && <div className="alert alert-success">{success}</div>}
          
          {viewMode === 'view' ? (
            <div style={{ display:'grid', gridTemplateColumns:'220px 1fr', gap:18 }}>
              <div className="profile-avatar-section">
                <div className="avatar-container" style={{ width:180, height:180 }}>
                  <img 
                    src={formData.avatar_url || '/images/logo.png'} 
                    alt="Profile Avatar" 
                    className="profile-avatar"
                    style={{ width:'100%', height:'100%', objectFit:'cover', borderRadius:'50%', border:'2px solid #e0e7ff' }}
                  />
                </div>
              </div>
              <div>
                <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:12 }}>
                  <div>
                    <div style={{ fontSize:12, color:'#64748b' }}>First name</div>
                    <div style={{ fontWeight:600, color:'#0f172a' }}>{formData.first_name || '-'}</div>
                  </div>
                  <div>
                    <div style={{ fontSize:12, color:'#64748b' }}>Last name</div>
                    <div style={{ fontWeight:600, color:'#0f172a' }}>{formData.last_name || '-'}</div>
                  </div>
                  <div>
                    <div style={{ fontSize:12, color:'#64748b' }}>Email</div>
                    <div style={{ fontWeight:600, color:'#0f172a' }}>{formData.email || '-'}</div>
                  </div>
                  <div>
                    <div style={{ fontSize:12, color:'#64748b' }}>Phone</div>
                    <div style={{ fontWeight:600, color:'#0f172a' }}>{formData.phone || '-'}</div>
                  </div>
                  <div style={{ gridColumn:'1 / -1' }}>
                    <div style={{ fontSize:12, color:'#64748b' }}>Location</div>
                    <div style={{ fontWeight:600, color:'#0f172a' }}>{formData.location || '-'}</div>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit}>
              <div className="profile-avatar-section">
                <div className="avatar-container">
                  <img 
                    src={previewUrl || formData.avatar_url || '/images/logo.png'} 
                    alt="Profile Avatar" 
                    className="profile-avatar"
                    style={{ width:'100%', height:'100%', objectFit:'cover', borderRadius:'50%' }}
                  />
                  <div className="avatar-overlay">
                    <label htmlFor="avatar-upload" className="avatar-upload-label">
                      Change Photo
                    </label>
                  </div>
                </div>
                <input 
                  type="file" 
                  id="avatar-upload" 
                  className="avatar-upload" 
                  onChange={handleFileChange}
                  accept="image/jpeg,image/png,image/gif,image/webp"
                />
                {isUploading && (
                  <div className="upload-progress">
                    <div className="progress-bar" style={{ width: `${uploadProgress}%` }}></div>
                  </div>
                )}
              </div>
              
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="first_name">First Name</label>
                  <input 
                    type="text" 
                    id="first_name" 
                    name="first_name" 
                    value={formData.first_name} 
                    onChange={handleInputChange}
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="last_name">Last Name</label>
                  <input 
                    type="text" 
                    id="last_name" 
                    name="last_name" 
                    value={formData.last_name} 
                    onChange={handleInputChange}
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="email">Email</label>
                  <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value={formData.email} 
                    onChange={handleInputChange}
                    required
                    disabled
                  />
                  <small>Email cannot be changed</small>
                </div>
                
                <div className="form-group">
                  <label htmlFor="phone">Phone Number</label>
                  <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value={formData.phone} 
                    onChange={handleInputChange}
                    placeholder="Enter your phone number"
                  />
                </div>
                
                <div className="form-group full-width">
                  <label htmlFor="location">Location</label>
                  <div className="location-input-group">
                    <input 
                      type="text" 
                      id="location" 
                      name="location" 
                      value={formData.location} 
                      onChange={handleInputChange}
                      placeholder="Enter your city, state, or address"
                    />
                    <button 
                      type="button" 
                      className="btn btn-secondary location-detect-btn"
                      onClick={getUserLocation}
                      title="Detect my location"
                    >
                      📍 Detect
                    </button>
                  </div>
                </div>
              </div>
              
              <div className="form-actions">
                <button type="button" className="btn btn-secondary" onClick={handleCancel}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading || isUploading}>
                  {loading ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>

      {showCropper && (
        <div className="form-modal" onMouseMove={onCropMouseMove} onMouseUp={onCropMouseUp} onMouseLeave={onCropMouseUp}>
          <div className="form-content" style={{ maxWidth:'min(820px, 96vw)' }}>
            <div className="form-header">
              <h3>Crop Profile Photo</h3>
              <p>Adjust the square to fit your avatar</p>
            </div>
            <div ref={cropContainerRef} style={{ position:'relative', width:'100%', maxHeight:'70vh', border:'1px solid #e5e7eb', background:'#000', overflow:'hidden', borderRadius:8 }}>
              <img ref={imageRef} src={cropSrc} alt="Crop source" style={{ display:'block', width:'100%', height:'auto', userSelect:'none' }} />
              <div
                role="button"
                aria-label="Crop box"
                onMouseDown={onCropMouseDown}
                style={{
                  position:'absolute',
                  left: cropBox.x,
                  top: cropBox.y,
                  width: cropBox.size,
                  height: cropBox.size,
                  border:'2px solid #3b82f6',
                  boxShadow:'0 0 0 9999px rgba(0,0,0,0.35)',
                  cursor:'move',
                  borderRadius:6
                }}
              />
            </div>
            <div className="form-actions">
              <button type="button" className="btn btn-secondary" onClick={cancelCrop}>Cancel</button>
              <button type="button" className="btn btn-primary" onClick={confirmCrop}>Crop & Use</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default HomeownerProfile;