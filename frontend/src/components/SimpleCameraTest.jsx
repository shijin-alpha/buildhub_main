import React, { useState, useRef, useEffect } from 'react';

const SimpleCameraTest = () => {
  const videoRef = useRef(null);
  const [status, setStatus] = useState('Starting...');
  const [stream, setStream] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    startCamera();
    return () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
      }
    };
  }, []);

  const startCamera = async () => {
    try {
      setStatus('Requesting camera access...');
      setError(null);

      // Check if getUserMedia is available
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('getUserMedia not supported in this browser');
      }

      console.log('Requesting camera...');
      
      // Simple camera request
      const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
      });

      console.log('Camera stream obtained:', mediaStream);
      console.log('Video tracks:', mediaStream.getVideoTracks());

      setStream(mediaStream);
      setStatus('Camera stream obtained, setting up video...');

      if (videoRef.current) {
        const video = videoRef.current;
        
        console.log('Setting video source...');
        video.srcObject = mediaStream;
        
        video.onloadedmetadata = () => {
          console.log('Video metadata loaded');
          setStatus('Video metadata loaded, starting playback...');
          
          video.play()
            .then(() => {
              console.log('Video playing successfully');
              setStatus('✅ Camera working!');
            })
            .catch(playError => {
              console.error('Video play error:', playError);
              setStatus('❌ Video play failed');
              setError(playError.message);
            });
        };

        video.onerror = (videoError) => {
          console.error('Video element error:', videoError);
          setStatus('❌ Video element error');
          setError('Video element failed to load');
        };

      } else {
        setStatus('❌ Video element not found');
        setError('Video ref is null');
      }

    } catch (err) {
      console.error('Camera error:', err);
      setStatus('❌ Camera failed');
      setError(err.message);
      
      // Log detailed error info
      console.log('Error name:', err.name);
      console.log('Error message:', err.message);
      console.log('Error stack:', err.stack);
    }
  };

  const retryCamera = () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      setStream(null);
    }
    startCamera();
  };

  return (
    <div style={{ padding: '20px', maxWidth: '600px', margin: '0 auto' }}>
      <h2>🔧 Camera Debug Test</h2>
      
      <div style={{ marginBottom: '20px' }}>
        <strong>Status:</strong> {status}
      </div>

      {error && (
        <div style={{ 
          backgroundColor: '#f8d7da', 
          color: '#721c24', 
          padding: '10px', 
          borderRadius: '5px',
          marginBottom: '20px'
        }}>
          <strong>Error:</strong> {error}
        </div>
      )}

      <div style={{ 
        width: '100%', 
        height: '300px', 
        backgroundColor: '#000', 
        borderRadius: '8px',
        overflow: 'hidden',
        marginBottom: '20px'
      }}>
        <video
          ref={videoRef}
          style={{
            width: '100%',
            height: '100%',
            objectFit: 'cover'
          }}
          autoPlay
          playsInline
          muted
        />
      </div>

      <button 
        onClick={retryCamera}
        style={{
          padding: '10px 20px',
          backgroundColor: '#007bff',
          color: 'white',
          border: 'none',
          borderRadius: '5px',
          cursor: 'pointer',
          marginRight: '10px'
        }}
      >
        🔄 Retry Camera
      </button>

      <button 
        onClick={() => {
          console.log('=== CAMERA DEBUG INFO ===');
          console.log('Navigator:', navigator);
          console.log('MediaDevices:', navigator.mediaDevices);
          console.log('getUserMedia:', navigator.mediaDevices?.getUserMedia);
          console.log('Video element:', videoRef.current);
          console.log('Current stream:', stream);
          console.log('Video tracks:', stream?.getVideoTracks());
          console.log('Browser:', navigator.userAgent);
          console.log('Protocol:', window.location.protocol);
          console.log('Host:', window.location.host);
        }}
        style={{
          padding: '10px 20px',
          backgroundColor: '#28a745',
          color: 'white',
          border: 'none',
          borderRadius: '5px',
          cursor: 'pointer'
        }}
      >
        🔍 Debug Info
      </button>

      <div style={{ marginTop: '20px', fontSize: '14px', color: '#666' }}>
        <h4>Troubleshooting Steps:</h4>
        <ol>
          <li>Check browser console for detailed error messages</li>
          <li>Ensure you're on HTTPS (required for camera access)</li>
          <li>Allow camera permissions when prompted</li>
          <li>Close other applications using the camera</li>
          <li>Try a different browser</li>
          <li>Check if camera works in other websites</li>
        </ol>
        
        <p><strong>Current URL:</strong> {window.location.href}</p>
        <p><strong>Protocol:</strong> {window.location.protocol}</p>
        <p><strong>Browser:</strong> {navigator.userAgent.split(' ').pop()}</p>
      </div>
    </div>
  );
};

export default SimpleCameraTest;