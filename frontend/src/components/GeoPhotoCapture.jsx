import React, { useState, useRef, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/GeoPhotoCapture.css';

const GeoPhotoCapture = ({ projectId, contractorId, onPhotosCaptured, onClose }) => {
  const toast = useToast();
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);
  
  const [location, setLocation] = useState(null);
  const [locationLoading, setLocationLoading] = useState(false);
  const [cameraStream, setCameraStream] = useState(null);
  const [cameraMode, setCameraMode] = useState('environment');
  const [cameraReady, setCameraReady] = useState(false);

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
        setCameraStream(null);
      }

      setCameraReady(false);

      const stream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
      });

      setCameraStream(stream);

      if (videoRef.current) {
        const video = videoRef.current;
        video.srcObject = stream;
        video.muted = true;
        video.playsInline = true;
        video.autoplay = true;

        video.onloadedmetadata = () => {
          video.play().then(() => {
            setCameraReady(true);
          }).catch(() => {
            toast.error('Camera display issue');
          });
        };
      }
    } catch (error) {
      setCameraReady(false);
      if (error.name === 'NotAllowedError') {
        toast.error('Please allow camera access');
      } else {
        toast.error('Camera not available');
      }
    }
  };

  const getQuickLocation = () => {
    if (!navigator.geolocation) {
      return;
    }

    setLocationLoading(true);
    
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude, accuracy } = position.coords;
        
        try {
          const placeName = await getSimplePlaceName(latitude, longitude);
          
          const locationData = {
            latitude,
            longitude,
            accuracy,
            placeName,
            timestamp: new Date().toISOString()
          };
          
          setLocation(locationData);
          setLocationLoading(false);
          toast.success(`Location captured: ${placeName}`);
        } catch (error) {
          const fallbackLocation = {
            latitude,
            longitude,
            accuracy,
            placeName: `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`,
            timestamp: new Date().toISOString()
          };
          setLocation(fallbackLocation);
          setLocationLoading(false);
        }
      },
      () => {
        setLocationLoading(false);
      },
      {
        enableHighAccuracy: false,
        timeout: 8000,
        maximumAge: 300000
      }
    );
  };

  const getSimplePlaceName = async (latitude, longitude) => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=14`,
        {
          headers: {
            'User-Agent': 'BuildHub-App/1.0'
          }
        }
      );
      
      if (response.ok) {
        const data = await response.json();
        if (data && data.display_name) {
          const parts = data.display_name.split(',');
          if (parts.length >= 3) {
            return parts.slice(0, 3).join(', ').trim();
          }
          return data.display_name;
        }
      }
    } catch (error) {
      // Silent fallback
    }
    
    return `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
  };

  const capturePhoto = () => {
    if (!videoRef.current || !canvasRef.current || !cameraReady) {
      toast.error('Camera not ready');
      return;
    }

    const video = videoRef.current;
    const canvas = canvasRef.current;
    const context = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    if (location) {
      addVerificationOverlay(context, canvas.width, canvas.height);
    } else {
      addNoLocationOverlay(context, canvas.width, canvas.height);
    }

    canvas.toBlob((blob) => {
      if (blob) {
        const photoId = Date.now();
        const enhancedLocation = location ? {
          ...location,
          capturedAt: new Date().toISOString()
        } : null;
        
        const photo = {
          id: photoId,
          blob,
          url: URL.createObjectURL(blob),
          location: enhancedLocation,
          timestamp: new Date().toISOString(),
          filename: `verified_photo_${photoId}_${location ? location.latitude.toFixed(6) + '_' + location.longitude.toFixed(6) : 'no_gps'}.jpg`
        };

        // Immediately attach to form and close camera
        if (onPhotosCaptured) {
          onPhotosCaptured([{ success: true, photoId: photo.id, data: photo }]);
        }
        
        if (location) {
          toast.success(`Photo captured and attached to form`);
        } else {
          toast.success('Photo captured and attached to form');
        }
        
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
          // Auto-close camera after capture
          onClose();
        }, 300);
      }
    }, 'image/jpeg', 0.9);
  };

  const addVerificationOverlay = (context, width, height) => {
    const overlayHeight = 120;
    
    context.fillStyle = 'rgba(0, 0, 0, 0.85)';
    context.fillRect(0, height - overlayHeight, width, overlayHeight);
    
    context.fillStyle = 'rgba(0, 255, 0, 0.6)';
    context.fillRect(0, height - overlayHeight, width, 3);
    
    context.fillStyle = 'white';
    context.textAlign = 'left';
    
    const padding = 15;
    let yPos = height - overlayHeight + 20;
    
    context.font = 'bold 16px Arial';
    context.fillStyle = '#00FF00';
    context.fillText('✓ GPS VERIFIED', padding, yPos);
    yPos += 25;
    
    context.font = 'bold 14px Arial';
    context.fillStyle = 'white';
    context.fillText(`${location.placeName}`, padding, yPos);
    yPos += 20;
    
    context.font = 'bold 12px Arial';
    context.fillStyle = '#00FFFF';
    context.fillText(`${location.latitude.toFixed(6)}°, ${location.longitude.toFixed(6)}°`, padding, yPos);
    yPos += 18;
    
    context.font = '11px Arial';
    context.fillStyle = '#FFFF00';
    context.fillText(`Accuracy: ±${Math.round(location.accuracy)}m`, padding, yPos);
    
    context.textAlign = 'right';
    context.fillStyle = 'white';
    const timestamp = new Date().toLocaleString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    context.fillText(timestamp, width - padding, yPos);
    
    context.textAlign = 'center';
    context.font = 'bold 10px Arial';
    context.fillStyle = 'rgba(255, 255, 255, 0.8)';
    context.fillText('BuildHub Verified Photo', width / 2, height - 8);
    
    context.textAlign = 'left';
  };

  const addNoLocationOverlay = (context, width, height) => {
    const overlayHeight = 60;
    
    context.fillStyle = 'rgba(0, 0, 0, 0.85)';
    context.fillRect(0, height - overlayHeight, width, overlayHeight);
    
    context.fillStyle = 'rgba(255, 165, 0, 0.8)';
    context.fillRect(0, height - overlayHeight, width, 3);
    
    const padding = 15;
    let yPos = height - overlayHeight + 25;
    
    context.font = 'bold 14px Arial';
    context.fillStyle = '#FFA500';
    context.fillText('⚠ Location Unverified', padding, yPos);
    
    context.font = '11px Arial';
    context.fillStyle = 'white';
    const timestamp = new Date().toLocaleString('en-IN');
    context.fillText(timestamp, padding, yPos + 20);
    
    context.textAlign = 'center';
    context.font = 'bold 10px Arial';
    context.fillStyle = 'rgba(255, 255, 255, 0.8)';
    context.fillText('BuildHub Photo', width / 2, height - 8);
    
    context.textAlign = 'left';
  };

  const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    
    if (files.length > 0) {
      const attachedPhotos = [];
      
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
          
          attachedPhotos.push({ success: true, photoId: photo.id, data: photo });
        }
      });
      
      if (attachedPhotos.length > 0) {
        // Attach to form and close camera
        if (onPhotosCaptured) {
          onPhotosCaptured(attachedPhotos);
        }
        
        toast.success(`${attachedPhotos.length} photo(s) attached to form`);
        
        // Auto-close camera after upload
        setTimeout(() => {
          onClose();
        }, 500);
      }
    }
    
    event.target.value = '';
  };

  const switchCamera = () => {
    setCameraMode(prev => prev === 'user' ? 'environment' : 'user');
  };

  return (
    <div className="geo-photo-capture">
      <div className="capture-header">
        <h3>📸 Photo Capture</h3>
        <button className="close-btn" onClick={onClose}>×</button>
      </div>

      {/* Location Status */}
      <div className="location-status">
        {locationLoading ? (
          <div className="location-loading">
            <span>Getting location...</span>
          </div>
        ) : location ? (
          <div className="location-info success">
            <div className="location-details">
              <div className="place-name">📍 {location.placeName}</div>
              <div className="coordinates">
                {location.latitude.toFixed(6)}°, {location.longitude.toFixed(6)}°
              </div>
            </div>
          </div>
        ) : (
          <div className="location-info warning">
            <span>Location unavailable</span>
          </div>
        )}
      </div>

      {/* Camera Section */}
      <div className="camera-section">
        <div className="camera-container">
          <video
            ref={videoRef}
            autoPlay
            playsInline
            muted
            className="camera-video"
          />
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

      {/* Instructions */}
      <div className="capture-instructions">
        <div className="instruction-item">
          <strong>📸 Take Photo:</strong> Capture with GPS verification
        </div>
        <div className="instruction-item">
          <strong>📁 Upload:</strong> Select photos from your gallery
        </div>
        <div className="instruction-item">
          <strong>✅ Auto-Attach:</strong> Photos automatically attach to your progress form
        </div>
      </div>
    </div>
  );
};

export default GeoPhotoCapture;