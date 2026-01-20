import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/GeoPhotoViewer.css';

const GeoPhotoViewer = ({ projectId, homeownerId }) => {
  const toast = useToast();
  const [photos, setPhotos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPhoto, setSelectedPhoto] = useState(null);
  const [projectSummary, setProjectSummary] = useState(null);
  const [filter, setFilter] = useState('all'); // 'all', 'unviewed', 'recent'
  const [sortBy, setSortBy] = useState('newest'); // 'newest', 'oldest', 'location'

  useEffect(() => {
    loadPhotos();
  }, [projectId, filter, sortBy]);

  const loadPhotos = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        limit: '50',
        offset: '0'
      });
      
      if (projectId) {
        params.append('project_id', projectId);
      }

      const response = await fetch(`/buildhub/backend/api/homeowner/get_geo_photos.php?${params}`, {
        credentials: 'include'
      });
      
      const data = await response.json();
      
      if (data.success) {
        let filteredPhotos = data.data.photos || [];
        
        // Apply filters
        if (filter === 'unviewed') {
          filteredPhotos = filteredPhotos.filter(photo => !photo.viewing.viewed);
        } else if (filter === 'recent') {
          const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
          filteredPhotos = filteredPhotos.filter(photo => 
            new Date(photo.timestamps.uploaded) > oneDayAgo
          );
        }
        
        // Apply sorting
        if (sortBy === 'oldest') {
          filteredPhotos.sort((a, b) => new Date(a.timestamps.uploaded) - new Date(b.timestamps.uploaded));
        } else if (sortBy === 'location') {
          filteredPhotos.sort((a, b) => (a.location.place_name || '').localeCompare(b.location.place_name || ''));
        }
        // 'newest' is default from API
        
        setPhotos(filteredPhotos);
        setProjectSummary(data.data.project_summary);
      } else {
        toast.error('Failed to load photos: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading photos:', error);
      toast.error('Error loading photos');
    } finally {
      setLoading(false);
    }
  };

  const markPhotoViewed = async (photoId) => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/mark_photo_viewed.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ photo_id: photoId })
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Update local state
        setPhotos(prev => prev.map(photo => 
          photo.id === photoId 
            ? { ...photo, viewing: { ...photo.viewing, viewed: true, viewed_at: new Date().toISOString() } }
            : photo
        ));
      }
    } catch (error) {
      console.error('Error marking photo as viewed:', error);
    }
  };

  const openPhotoModal = (photo) => {
    setSelectedPhoto(photo);
    if (!photo.viewing.viewed) {
      markPhotoViewed(photo.id);
    }
  };

  const closePhotoModal = () => {
    setSelectedPhoto(null);
  };

  const openInMaps = (latitude, longitude, placeName) => {
    const lat = parseFloat(latitude);
    const lng = parseFloat(longitude);
    
    if (!isNaN(lat) && !isNaN(lng)) {
      const url = `https://www.google.com/maps?q=${lat},${lng}`;
      window.open(url, '_blank');
    } else {
      toast.error('Location coordinates not available');
    }
  };

  const downloadPhoto = (photo) => {
    if (photo.photo_url) {
      const link = document.createElement('a');
      link.href = photo.photo_url;
      link.download = photo.original_filename;
      link.click();
    }
  };

  const deletePhoto = async (photo) => {
    if (!window.confirm(`Are you sure you want to delete this photo?\n\nFilename: ${photo.original_filename}\nLocation: ${photo.location.place_name || 'Unknown'}\n\nThis action cannot be undone.`)) {
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/homeowner/delete_geo_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ photo_id: photo.id })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Photo deleted successfully');
        // Remove photo from local state
        setPhotos(prev => prev.filter(p => p.id !== photo.id));
        // Close modal if this photo was selected
        if (selectedPhoto && selectedPhoto.id === photo.id) {
          setSelectedPhoto(null);
        }
        // Reload photos to update counts
        loadPhotos();
      } else {
        toast.error('Failed to delete photo: ' + data.message);
      }
    } catch (error) {
      console.error('Error deleting photo:', error);
      toast.error('Error deleting photo');
    }
  };

  const getFilteredCount = (filterType) => {
    switch (filterType) {
      case 'unviewed':
        return photos.filter(photo => !photo.viewing.viewed).length;
      case 'recent':
        const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
        return photos.filter(photo => new Date(photo.timestamps.uploaded) > oneDayAgo).length;
      default:
        return photos.length;
    }
  };

  if (loading) {
    return (
      <div className="geo-photo-viewer loading">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading construction photos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="geo-photo-viewer">
      <div className="viewer-header">
        <div className="header-content">
          <h3>📸 Construction Progress Photos</h3>
          {projectSummary && (
            <div className="project-info">
              <span className="contractor-name">by {projectSummary.contractor_name}</span>
              <div className="photo-stats">
                <span className="stat">
                  {projectSummary.photo_stats.total_photos} photos
                </span>
                {projectSummary.photo_stats.unviewed_photos > 0 && (
                  <span className="stat unviewed">
                    {projectSummary.photo_stats.unviewed_photos} new
                  </span>
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Filters and Controls */}
      <div className="viewer-controls">
        <div className="filter-controls">
          <div className="filter-group">
            <label>Filter:</label>
            <select value={filter} onChange={(e) => setFilter(e.target.value)}>
              <option value="all">All Photos ({photos.length})</option>
              <option value="unviewed">Unviewed ({getFilteredCount('unviewed')})</option>
              <option value="recent">Recent (24h) ({getFilteredCount('recent')})</option>
            </select>
          </div>
          
          <div className="filter-group">
            <label>Sort by:</label>
            <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
              <option value="location">Location</option>
            </select>
          </div>
        </div>

        <button className="refresh-btn" onClick={loadPhotos}>
          🔄 Refresh
        </button>
      </div>

      {/* Main Content Area */}
      <div className="viewer-main-content">
        {/* Photos Grid */}
        <div className="photos-section">
          {photos.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📷</div>
              <h3>No Photos Yet</h3>
              <p>Construction photos from your contractor will appear here with location details.</p>
            </div>
          ) : (
            <div className="photos-grid">
              {photos.map((photo) => (
                <div 
                  key={photo.id} 
                  className={`photo-card ${!photo.viewing.viewed ? 'unviewed' : ''} ${selectedPhoto && selectedPhoto.id === photo.id ? 'selected' : ''}`}
                  onClick={() => openPhotoModal(photo)}
                >
                  {!photo.viewing.viewed && <div className="new-badge">NEW</div>}
                  
                  <div className="photo-preview">
                    {photo.file_exists && photo.photo_url ? (
                      <img src={photo.photo_url} alt="Construction progress" />
                    ) : (
                      <div className="photo-unavailable">
                        <span>📷</span>
                        <p>Photo unavailable</p>
                      </div>
                    )}
                  </div>
                  
                  <div className="photo-info">
                    <div className="location-info">
                      <div className="place-name">
                        📍 {photo.location.place_name || 'Location unavailable'}
                      </div>
                      {photo.location.latitude && photo.location.longitude && (
                        <div className="coordinates">
                          {!isNaN(parseFloat(photo.location.latitude)) && !isNaN(parseFloat(photo.location.longitude)) ? 
                            `${parseFloat(photo.location.latitude).toFixed(6)}, ${parseFloat(photo.location.longitude).toFixed(6)}` :
                            'Coordinates unavailable'
                          }
                        </div>
                      )}
                    </div>
                    
                    <div className="timestamp-info">
                      <div className="upload-time">
                        🕒 {photo.timestamps.time_ago}
                      </div>
                      <div className="contractor-name">
                        👷 {photo.contractor.name}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Side Panel for Photo Details */}
        <div className={`photo-details-panel ${selectedPhoto ? 'visible' : ''}`}>
          {selectedPhoto ? (
            <>
              <div className="panel-header">
                <h3>📸 Photo Details</h3>
                <button className="close-panel-btn" onClick={() => setSelectedPhoto(null)}>×</button>
              </div>
              
              <div className="panel-content">
                {/* Photo Preview in Panel */}
                <div className="panel-photo-preview">
                  {selectedPhoto.file_exists && selectedPhoto.photo_url ? (
                    <img src={selectedPhoto.photo_url} alt="Construction progress" />
                  ) : (
                    <div className="photo-unavailable-panel">
                      <span>📷</span>
                      <p>Photo not available</p>
                    </div>
                  )}
                </div>

                {/* Photo Details */}
                <div className="photo-details">
                  <div className="detail-section">
                    <h4>📍 Location Information</h4>
                    <div className="detail-grid">
                      <div className="detail-item">
                        <span className="label">Place:</span>
                        <span className="value">{selectedPhoto.location.place_name || 'Not available'}</span>
                      </div>
                      {selectedPhoto.location.latitude && selectedPhoto.location.longitude && (
                        <>
                          <div className="detail-item">
                            <span className="label">Coordinates:</span>
                            <span className="value">
                              {!isNaN(parseFloat(selectedPhoto.location.latitude)) && !isNaN(parseFloat(selectedPhoto.location.longitude)) ? 
                                `${parseFloat(selectedPhoto.location.latitude).toFixed(6)}, ${parseFloat(selectedPhoto.location.longitude).toFixed(6)}` :
                                'Coordinates unavailable'
                              }
                            </span>
                          </div>
                          <div className="detail-item">
                            <span className="label">Accuracy:</span>
                            <span className="value">±{Math.round(selectedPhoto.location.accuracy || 0)}m</span>
                          </div>
                        </>
                      )}
                    </div>
                    
                    {selectedPhoto.location.latitude && selectedPhoto.location.longitude && (
                      <button 
                        className="maps-btn"
                        onClick={() => openInMaps(
                          selectedPhoto.location.latitude, 
                          selectedPhoto.location.longitude, 
                          selectedPhoto.location.place_name
                        )}
                      >
                        🗺️ View on Maps
                      </button>
                    )}
                  </div>
                  
                  <div className="detail-section">
                    <h4>🕒 Timing Information</h4>
                    <div className="detail-grid">
                      <div className="detail-item">
                        <span className="label">Photo Taken:</span>
                        <span className="value">
                          {selectedPhoto.timestamps.photo_taken_formatted || 'Not available'}
                        </span>
                      </div>
                      <div className="detail-item">
                        <span className="label">Uploaded:</span>
                        <span className="value">{selectedPhoto.timestamps.uploaded_formatted}</span>
                      </div>
                      <div className="detail-item">
                        <span className="label">Time Ago:</span>
                        <span className="value">{selectedPhoto.timestamps.time_ago}</span>
                      </div>
                    </div>
                  </div>
                  
                  <div className="detail-section">
                    <h4>👷 Contractor Information</h4>
                    <div className="detail-grid">
                      <div className="detail-item">
                        <span className="label">Name:</span>
                        <span className="value">{selectedPhoto.contractor.name}</span>
                      </div>
                      <div className="detail-item">
                        <span className="label">Email:</span>
                        <span className="value">{selectedPhoto.contractor.email}</span>
                      </div>
                      {selectedPhoto.contractor.phone && (
                        <div className="detail-item">
                          <span className="label">Phone:</span>
                          <span className="value">{selectedPhoto.contractor.phone}</span>
                        </div>
                      )}
                    </div>
                  </div>
                  
                  <div className="detail-section">
                    <h4>📁 File Information</h4>
                    <div className="detail-grid">
                      <div className="detail-item">
                        <span className="label">Filename:</span>
                        <span className="value">{selectedPhoto.original_filename}</span>
                      </div>
                      <div className="detail-item">
                        <span className="label">Size:</span>
                        <span className="value">{selectedPhoto.file_size_formatted}</span>
                      </div>
                      <div className="detail-item">
                        <span className="label">Type:</span>
                        <span className="value">{selectedPhoto.mime_type}</span>
                      </div>
                    </div>
                    
                    <div className="action-buttons">
                      {selectedPhoto.file_exists && selectedPhoto.photo_url && (
                        <button 
                          className="download-btn"
                          onClick={() => downloadPhoto(selectedPhoto)}
                        >
                          💾 Download Photo
                        </button>
                      )}
                      
                      <button 
                        className="delete-btn"
                        onClick={() => deletePhoto(selectedPhoto)}
                      >
                        🗑️ Delete Photo
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </>
          ) : (
            <div className="panel-placeholder">
              <div className="placeholder-icon">📸</div>
              <h4>Select a Photo</h4>
              <p>Click on any photo to view its details, location information, and manage the file.</p>
            </div>
          )}
        </div>
      </div>

      {/* Keep Modal for Full-Screen View (Optional) */}
      {selectedPhoto && (
        <div className="photo-modal-overlay" onClick={closePhotoModal} style={{ display: 'none' }}>
          {/* Modal content remains the same but hidden by default */}
        </div>
      )}
    </div>
  );
};

export default GeoPhotoViewer;