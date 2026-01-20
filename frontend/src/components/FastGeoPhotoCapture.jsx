import React, { useState, useRef, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/GeoPhotoCapture.css';

const FastGeoPhotoCapture = ({ projectId, contractorId, onPhotosCaptured, onClose }) => {
  const toast = useToast();
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);
  
  const [capturedPhotos, setCapturedPhotos] = useState([]);
  const [location, setLocation] = useState(null);
  const [locationLoading, setLocationLoading] = useState(false);
  const [cameraStream, setCameraStream] = useState(null);
  const [cameraMode, setCameraMode] = useState('environment');
  const [uploading, setUploading] = useState(false);
  const [cameraReady, setCameraReady] = useState(false);

  // Initialize camera and get location quickly
  useEffect(() => {
    initializeCamera();
    getQuickLocation();
    
    return () => {
      if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
      }
    };
  }, [cameraMode]);

  const initializeCamera = async () => {
    try {
      if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
      }

      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: cameraMode,
          width: { ideal: 1280 },
          height: { ideal: 720 }
        },
        audio: false
      });

      setCameraStream(stream);
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        videoRef.current.onloadedmetadata = () => {
          setCameraReady(true);
        };
      }
    } catch (error) {
      console.error('Camera error:', error);
      toast.error('Camera not available. You can still upload photos from gallery.');
      setCameraReady(false);
    }
  };

  const getQuickLocation = () => {
    if (!navigator.geolocation) {
      toast.warning('Location not available - photos will be uploaded without GPS');
      return;
    }

    setLocationLoading(true);
    
    // Quick location request - prioritize speed over accuracy
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        
        const locationData = {
          latitude,
          longitude,
          accuracy,
          placeName: `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`,
          timestamp: new Date().toISOString()
        };
        
        setLocation(locationData);
        setLocationLoading(false);
        toast.success(`📍 Location captured (±${Math.round(accuracy)}m)`);
      },
      (error) => {
        console.error('Location error:', error);
        setLocationLoading(false);
        toast.warning('Location not available - photos will be uploaded without GPS');
      },
      {
        enableHighAccuracy: false, // Use network location for speed
        timeout: 5000, // 5 seconds timeout
        maximumAge: 600000 // Accept location up to 10 minutes old
      }
    );
  };

  const capturePhoto = () => {
    if (!videoRef.current || !canvasRef.current || !cameraReady) {
      toast.error('Camera not ready');
      return;
    }

    const video = videoRef.current;
    const canvas = canvasRef.current;
    const context = canvas.getContext('2d');

    // Set canvas dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Add simple location overlay if available
    if (location) {
      addSimpleLocationOverlay(context, canvas.width, canvas.height);
    }

    // Convert to blob
    canvas.toBlob((blob) => {
      if (blob) {
        const photoId = Date.now();
        const photo = {
          id: photoId,
          blob,
          url: URL.createObjectURL(blob),
          location: location || null,
          timestamp: new Date().toISOString(),
          filename: `photo_${photoId}.jpg`
        };

        setCapturedPhotos(prev => [...prev, photo]);
        toast.success('📸 Photo captured!');
        
        // Flash effect
        const flashDiv = document.createElement('div');
        flashDiv.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: white;
          opacity: 0.8;
          z-index: 9999;
          pointer-events: none;
        `;
        document.body.appendChild(flashDiv);
        setTimeout(() => {
          document.body.removeChild(flashDiv);
        }, 150);
      }
    }, 'image/jpeg', 0.8);
  };

  const addSimpleLocationOverlay = (context, width, height) => {
    const overlayHeight = 80;
    
    // Dark overlay
    context.fillStyle = 'rgba(0, 0, 0, 0.7)';
    context.fillRect(0, height - overlayHeight, width, overlayHeight);
    
    // White text
    context.fillStyle = 'white';
    context.font = 'bold 14px Arial';
    context.textAlign = 'left';
    
    const padding = 10;
    let yPos = height - overlayHeight + 20;
    
    // Location
    if (location.placeName) {
      context.fillText(`📍 ${location.placeName}`, padding, yPos);
      yPos += 20;
    }
    
    // Coordinates
    context.fillText(`GPS: ${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`, padding, yPos);
    yPos += 20;
    
    // Timestamp
    context.font = '12px Arial';
    context.fillText(`🕒 ${new Date().toLocaleString()}`, padding, yPos);
  };

  const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    
    files.forEach(file => {
      if (file.type.startsWith('image/')) {
        const photoId = Date.now() + Math.random();
        const photo = {
          id: photoId,
          blob: file,
          url: URL.createObjectURL(file),
          location: location || null,
          timestamp: new Date().toISOString(),
          filename: file.name,
          isUploaded: true
        };
        
        setCapturedPhotos(prev => [...prev, photo]);
      }
    });
    
    event.target.value = '';
    toast.success(`${files.length} photo(s) added`);
  };

  const removePhoto = (photoId) => {
    setCapturedPhotos(prev => {
      const updated = prev.filter(photo => photo.id !== photoId);
      const photoToRemove = prev.find(photo => photo.id === photoId);
      if (photoToRemove) {
        URL.revokeObjectURL(photoToRemove.url);
      }
      return updated;
    });
  };

  const switchCamera = () => {
    setCameraMode(prev => prev === 'user' ? 'environment' : 'user');
  };

  const uploadPhotos = async () => {
    if (capturedPhotos.length === 0) {
      toast.error('No photos to upload');
      return;
    }

    if (!projectId || !contractorId) {
      toast.error('Project information missing');
      return;
    }

    setUploading(true);
    const uploadResults = [];

    try {
      for (const photo of capturedPhotos) {
        const formData = new FormData();
        formData.append('photo', photo.blob, photo.filename);
        formData.append('project_id', projectId);
        formData.append('contractor_id', contractorId);
        formData.append('location_data', JSON.stringify(photo.location || {}));
        formData.append('timestamp', photo.timestamp);

        try {
          const response = await fetch('/buildhub/backend/api/contractor/upload_geo_photo.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
          });

          const result = await response.json();
          
          if (result.success) {
            uploadResults.push({
              success: true,
              photoId: photo.id,
              data: result.data
            });
          } else {
            uploadResults.push({
              success: false,
              photoId: photo.id,
              error: result.message
            });
          }
        } catch (error) {
          console.error('Upload error:', error);
          uploadResults.push({
            success: false,
            photoId: photo.id,
            error: 'Network error'
          });
        }
      }

      const successCount = uploadResults.filter(r => r.success).length;
      const failCount = uploadResults.filter(r => !r.success).length;

      if (successCount > 0) {
        toast.success(`${successCount} photo(s) sent to homeowner successfully!`);
        
        if (onPhotosCaptured) {
          onPhotosCaptured(uploadResults.filter(r => r.success));
        }
        
        setCapturedPhotos([]);
      }

      if (failCount > 0) {
        toast.error(`${failCount} photo(s) failed to upload`);
      }

    } catch (error) {
      console.error('Upload error:', error);
      toast.error('Failed to upload photos');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="geo-photo-capture">
      <div className="capture-header">
        <h3>📸 Quick Photo Capture</h3>
        <button className="close-btn" onClick={onClose}>×</button>
      </div>

      {/* Simple Location Status */}
      <div className="location-status">
        {locationLoading ? (
          <div className="location-loading">
            <span>📍 Getting location...</span>
          </div>
        ) : location ? (
          <div className="location-info success">
            <span>📍 Location: {location.placeName} (±{Math.round(location.accuracy)}m)</span>
            <button className="refresh-location-btn" onClick={getQuickLocation}>
              🔄 Refresh
            </button>
          </div>
        ) : (
          <div className="location-info warning">
            <span>⚠️ No GPS - photos will upload without location</span>
            <button className="retry-location-btn" onClick={getQuickLocation}>
              📍 Try Again
            </button>
          </div>
        )}
      </div>

      {/* Camera Section */}
      <div className="camera-section">
        <div className="camera-container">
          {cameraReady ? (
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="camera-video"
            />
          ) : (
            <div className="camera-placeholder">
              <p>📷 Camera loading...</p>
            </div>
          )}
          <canvas ref={canvasRef} style={{ display: 'none' }} />
          
          <div className="camera-controls">
            <button
              className="camera-switch-btn"
              onClick={switchCamera}
              disabled={!cameraReady}
              title="Switch Camera"
            >
              🔄
            </button>
            
            <button
              className="capture-btn"
              onClick={capturePhoto}
              disabled={!cameraReady}
              title="Take Photo"
            >
              📸
            </button>
            
            <button
              className="upload-file-btn"
              onClick={() => fileInputRef.current?.click()}
              title="Upload from Gallery"
            >
              📁
            </button>
          </div>
        </div>

        <input
          ref={fileInputRef}
          type="file"
          accept="image/*"
          multiple
          onChange={handleFileUpload}
          style={{ display: 'none' }}
        />
      </div>

      {/* Captured Photos */}
      {capturedPhotos.length > 0 && (
        <div className="captured-photos">
          <div className="photos-header">
            <h4>📷 Photos ({capturedPhotos.length})</h4>
            <button
              className="upload-all-btn"
              onClick={uploadPhotos}
              disabled={uploading}
            >
              {uploading ? '⏳ Uploading...' : '📤 Send to Homeowner'}
            </button>
          </div>

          <div className="photos-grid">
            {capturedPhotos.map((photo) => (
              <div key={photo.id} className="photo-item">
                <div className="photo-preview">
                  <img src={photo.url} alt="Captured photo" />
                  <button
                    className="remove-photo-btn"
                    onClick={() => removePhoto(photo.id)}
                    disabled={uploading}
                  >
                    ×
                  </button>
                </div>
                
                <div className="photo-info">
                  {photo.location ? (
                    <div className="photo-location">
                      📍 {photo.location.placeName}
                    </div>
                  ) : (
                    <div className="photo-location">
                      📍 No GPS location
                    </div>
                  )}
                  <div className="photo-timestamp">
                    🕒 {new Date(photo.timestamp).toLocaleString()}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Simple Instructions */}
      <div className="capture-instructions">
        <h4>📋 Quick Tips</h4>
        <ul>
          <li>📸 <strong>Camera:</strong> Point and click to capture photos</li>
          <li>📁 <strong>Gallery:</strong> Upload existing photos from your device</li>
          <li>📍 <strong>Location:</strong> Works best outdoors with GPS enabled</li>
          <li>📤 <strong>Send:</strong> All photos are automatically sent to homeowner</li>
        </ul>
      </div>
    </div>
  );
};

export default FastGeoPhotoCapture;