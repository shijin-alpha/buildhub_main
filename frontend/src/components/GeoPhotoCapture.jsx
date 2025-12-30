import React, { useState, useRef, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/GeoPhotoCapture.css';

const GeoPhotoCapture = ({ projectId, contractorId, onPhotosCaptured, onClose }) => {
  const toast = useToast();
  const videoRef = useRef(null);
  const canvasRef = useRef(null);
  const fileInputRef = useRef(null);
  
  const [isCapturing, setIsCapturing] = useState(false);
  const [capturedPhotos, setCapturedPhotos] = useState([]);
  const [location, setLocation] = useState(null);
  const [locationLoading, setLocationLoading] = useState(false);
  const [cameraStream, setCameraStream] = useState(null);
  const [cameraMode, setCameraMode] = useState('environment'); // 'user' for front, 'environment' for back
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState({});

  // Initialize camera and location
  useEffect(() => {
    initializeCamera();
    getCurrentLocation();
    
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
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        },
        audio: false
      });

      setCameraStream(stream);
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
      }
    } catch (error) {
      console.error('Camera initialization error:', error);
      toast.error('Unable to access camera. Please check permissions.');
    }
  };

  const getCurrentLocation = () => {
    if (!navigator.geolocation) {
      toast.error('Geolocation is not supported by this browser');
      return;
    }

    setLocationLoading(true);
    toast.info('🛰️ Forcing high-accuracy GPS satellites (this may take 30-60 seconds)...');
    
    let bestLocation = null;
    let attempts = 0;
    const maxAttempts = 5;
    const maxAcceptableAccuracy = 100; // Only accept accuracy better than 100m
    
    const tryGetLocation = () => {
      attempts++;
      
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const { latitude, longitude, accuracy, altitude, heading, speed } = position.coords;
          
          console.log(`GPS Attempt ${attempts}:`, {
            latitude,
            longitude,
            accuracy,
            altitude,
            heading,
            speed,
            timestamp: position.timestamp
          });
          
          // Check if this is a good enough location
          const isGoodAccuracy = accuracy <= maxAcceptableAccuracy;
          const isReasonableLocation = latitude >= 8.0 && latitude <= 13.0 && longitude >= 74.0 && longitude <= 78.0; // Kerala bounds
          
          if (isGoodAccuracy && isReasonableLocation) {
            // Good location found!
            try {
              const placeName = await getPlaceName(latitude, longitude);
              
              const locationData = {
                latitude,
                longitude,
                accuracy,
                altitude,
                heading,
                speed,
                placeName,
                timestamp: new Date().toISOString(),
                captureTime: new Date(position.timestamp).toISOString(),
                attempts: attempts
              };
              
              setLocation(locationData);
              setLocationLoading(false);
              
              toast.success(`🎯 High-accuracy GPS location captured: ${placeName} (±${Math.round(accuracy)}m accuracy)`);
              
            } catch (error) {
              console.error('Error getting place name:', error);
              const fallbackLocation = {
                latitude,
                longitude,
                accuracy,
                altitude,
                heading,
                speed,
                placeName: `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`,
                timestamp: new Date().toISOString(),
                captureTime: new Date(position.timestamp).toISOString(),
                attempts: attempts
              };
              setLocation(fallbackLocation);
              setLocationLoading(false);
              toast.success(`🎯 GPS coordinates captured: ${latitude.toFixed(6)}, ${longitude.toFixed(6)} (±${Math.round(accuracy)}m)`);
            }
            
          } else if (!bestLocation || accuracy < bestLocation.accuracy) {
            // Store as best location so far
            bestLocation = {
              latitude,
              longitude,
              accuracy,
              altitude,
              heading,
              speed,
              timestamp: position.timestamp,
              attempts: attempts
            };
            
            if (accuracy > maxAcceptableAccuracy) {
              toast.warning(`📡 GPS accuracy: ±${Math.round(accuracy)}m (trying to improve...)`);
            }
            
            if (!isReasonableLocation) {
              toast.warning(`🗺️ Location seems outside Kerala region (trying to improve...)`);
            }
            
            // Try again if we haven't reached max attempts
            if (attempts < maxAttempts) {
              setTimeout(() => {
                toast.info(`🛰️ GPS attempt ${attempts + 1}/${maxAttempts} - seeking better accuracy...`);
                tryGetLocation();
              }, 3000); // Wait 3 seconds between attempts
            } else {
              // Use best location we got
              if (bestLocation) {
                handleFinalLocation(bestLocation);
              } else {
                setLocationLoading(false);
                toast.error('❌ Could not get accurate GPS location after multiple attempts');
              }
            }
          }
        },
        (error) => {
          console.error(`GPS Attempt ${attempts} failed:`, error);
          
          if (attempts < maxAttempts) {
            setTimeout(() => {
              toast.warning(`🛰️ GPS attempt ${attempts} failed, trying again (${attempts + 1}/${maxAttempts})...`);
              tryGetLocation();
            }, 2000);
          } else {
            setLocationLoading(false);
            
            let errorMessage = '❌ GPS location failed after multiple attempts. ';
            switch (error.code) {
              case error.PERMISSION_DENIED:
                errorMessage += 'Please allow location access and ensure GPS is enabled on your device.';
                break;
              case error.POSITION_UNAVAILABLE:
                errorMessage += 'GPS satellites not available. Try moving to an open area with clear sky view.';
                break;
              case error.TIMEOUT:
                errorMessage += 'GPS timeout. Try moving to an area with better satellite reception.';
                break;
              default:
                errorMessage += 'Please check your GPS settings and try again.';
                break;
            }
            toast.error(errorMessage);
          }
        },
        {
          enableHighAccuracy: true,
          timeout: 30000, // 30 seconds timeout per attempt
          maximumAge: 0 // Always get fresh GPS reading
        }
      );
    };
    
    // Start the first attempt
    tryGetLocation();
  };

  const handleFinalLocation = async (locationData) => {
    try {
      const placeName = await getPlaceName(locationData.latitude, locationData.longitude);
      
      const finalLocation = {
        latitude: locationData.latitude,
        longitude: locationData.longitude,
        accuracy: locationData.accuracy,
        altitude: locationData.altitude,
        heading: locationData.heading,
        speed: locationData.speed,
        placeName,
        timestamp: new Date().toISOString(),
        captureTime: new Date(locationData.timestamp).toISOString(),
        attempts: locationData.attempts
      };
      
      setLocation(finalLocation);
      setLocationLoading(false);
      
      if (locationData.accuracy <= 100) {
        toast.success(`✅ Best GPS location: ${placeName} (±${Math.round(locationData.accuracy)}m)`);
      } else {
        toast.warning(`⚠️ Using best available GPS: ${placeName} (±${Math.round(locationData.accuracy)}m accuracy - may not be precise)`);
      }
      
    } catch (error) {
      console.error('Error processing final location:', error);
      const fallbackLocation = {
        latitude: locationData.latitude,
        longitude: locationData.longitude,
        accuracy: locationData.accuracy,
        altitude: locationData.altitude,
        heading: locationData.heading,
        speed: locationData.speed,
        placeName: `${locationData.latitude.toFixed(6)}, ${locationData.longitude.toFixed(6)}`,
        timestamp: new Date().toISOString(),
        captureTime: new Date(locationData.timestamp).toISOString(),
        attempts: locationData.attempts
      };
      setLocation(fallbackLocation);
      setLocationLoading(false);
      toast.warning(`⚠️ GPS coordinates: ${locationData.latitude.toFixed(6)}, ${locationData.longitude.toFixed(6)} (±${Math.round(locationData.accuracy)}m)`);
    }
  };

  const getPlaceName = async (latitude, longitude) => {
    try {
      // Enhanced geocoding with multiple services and Kerala-specific handling
      
      // First, check if coordinates are in Kerala bounds
      const isInKerala = (lat, lng) => {
        return lat >= 8.2 && lat <= 12.8 && lng >= 74.8 && lng <= 77.4;
      };
      
      const isKeralaLocation = isInKerala(latitude, longitude);
      
      // Try multiple geocoding services for better accuracy
      let bestResult = null;
      
      // Service 1: OpenStreetMap Nominatim with Kerala-specific parameters
      try {
        const nominatimUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=16&addressdetails=1&accept-language=en&countrycodes=in`;
        
        const nominatimResponse = await fetch(nominatimUrl, {
          headers: {
            'User-Agent': 'BuildHub-GeoPhoto/1.0 (Kerala Location Service)'
          }
        });
        
        if (nominatimResponse.ok) {
          const data = await nominatimResponse.json();
          
          if (data && data.address) {
            const address = data.address;
            
            // Kerala-specific address parsing
            if (isKeralaLocation) {
              const keralaAddress = parseKeralaAddress(address, latitude, longitude);
              if (keralaAddress) {
                bestResult = keralaAddress;
              }
            }
            
            // Fallback to general parsing if Kerala-specific failed
            if (!bestResult) {
              bestResult = parseGeneralAddress(address);
            }
          }
        }
      } catch (nominatimError) {
        console.log('Nominatim geocoding failed:', nominatimError);
      }
      
      // Service 2: Try alternative geocoding if first failed or result seems wrong
      if (!bestResult || bestResult.includes('Edathal') || bestResult.length < 10) {
        try {
          // Use a different zoom level for more accurate results
          const altUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=14&addressdetails=1&accept-language=en&countrycodes=in&extratags=1`;
          
          const altResponse = await fetch(altUrl, {
            headers: {
              'User-Agent': 'BuildHub-GeoPhoto-Alt/1.0'
            }
          });
          
          if (altResponse.ok) {
            const altData = await altResponse.json();
            if (altData && altData.address) {
              const altAddress = isKeralaLocation ? 
                parseKeralaAddress(altData.address, latitude, longitude) : 
                parseGeneralAddress(altData.address);
              
              if (altAddress && altAddress !== bestResult) {
                bestResult = altAddress;
              }
            }
          }
        } catch (altError) {
          console.log('Alternative geocoding failed:', altError);
        }
      }
      
      // Service 3: Kerala district-specific validation
      if (isKeralaLocation) {
        const districtInfo = getKeralaDistrictInfo(latitude, longitude);
        if (districtInfo && bestResult) {
          // Validate if the result matches the expected district
          if (!bestResult.toLowerCase().includes(districtInfo.district.toLowerCase())) {
            bestResult = `${bestResult}, ${districtInfo.district} District, Kerala`;
          }
        } else if (districtInfo) {
          bestResult = `${districtInfo.area}, ${districtInfo.district} District, Kerala`;
        }
      }
      
      // Final validation and formatting
      if (bestResult) {
        // Clean up the result
        bestResult = cleanLocationName(bestResult);
        
        // Ensure it's not too long
        if (bestResult.length > 80) {
          bestResult = bestResult.substring(0, 77) + '...';
        }
        
        return bestResult;
      }
      
      // Ultimate fallback with district estimation
      if (isKeralaLocation) {
        const districtInfo = getKeralaDistrictInfo(latitude, longitude);
        if (districtInfo) {
          return `${districtInfo.area}, ${districtInfo.district} District, Kerala`;
        }
      }
      
      return `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
      
    } catch (error) {
      console.error('All geocoding services failed:', error);
      return `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    }
  };

  // Kerala-specific address parsing
  const parseKeralaAddress = (address, lat, lng) => {
    const components = [];
    
    // Road/Street
    if (address.road) {
      components.push(address.road);
    }
    
    // Village/Town/City (prioritize local names)
    const locality = address.village || address.town || address.city || 
                    address.municipality || address.suburb || address.neighbourhood;
    if (locality) {
      components.push(locality);
    }
    
    // Taluk/Block
    if (address.county || address.state_district) {
      components.push(address.county || address.state_district);
    }
    
    // District (ensure it's correct for Kerala)
    let district = address.state_district || address.county;
    if (!district || !district.toLowerCase().includes('district')) {
      // Try to determine district from coordinates
      const districtInfo = getKeralaDistrictInfo(lat, lng);
      if (districtInfo) {
        district = districtInfo.district + ' District';
      }
    }
    
    if (district) {
      components.push(district);
    }
    
    // State
    if (address.state && address.state.toLowerCase().includes('kerala')) {
      components.push('Kerala');
    }
    
    return components.length > 0 ? components.join(', ') : null;
  };

  // General address parsing for non-Kerala locations
  const parseGeneralAddress = (address) => {
    const components = [];
    
    if (address.house_number && address.road) {
      components.push(`${address.house_number} ${address.road}`);
    } else if (address.road) {
      components.push(address.road);
    }
    
    if (address.neighbourhood || address.suburb || address.residential) {
      components.push(address.neighbourhood || address.suburb || address.residential);
    }
    
    if (address.city || address.town || address.village || address.municipality) {
      components.push(address.city || address.town || address.village || address.municipality);
    }
    
    if (address.state) {
      components.push(address.state);
    }
    
    return components.length > 0 ? components.join(', ') : null;
  };

  // Kerala district information based on coordinates
  const getKeralaDistrictInfo = (lat, lng) => {
    // Kerala districts with approximate coordinate bounds
    const keralaDistricts = [
      {
        district: 'Kottayam',
        bounds: { minLat: 9.3, maxLat: 10.0, minLng: 76.2, maxLng: 76.9 }, // Expanded to include Ponkunnam
        areas: ['Kottayam Town', 'Changanassery', 'Pala', 'Ettumanoor', 'Vaikom', 'Ponkunnam', 'Thodupuzha']
      },
      {
        district: 'Ernakulam',
        bounds: { minLat: 9.8, maxLat: 10.3, minLng: 76.0, maxLng: 76.8 },
        areas: ['Kochi', 'Ernakulam', 'Aluva', 'Perumbavoor', 'Muvattupuzha']
      },
      {
        district: 'Thiruvananthapuram',
        bounds: { minLat: 8.2, maxLat: 8.9, minLng: 76.8, maxLng: 77.4 },
        areas: ['Thiruvananthapuram', 'Neyyattinkara', 'Attingal', 'Varkala']
      },
      {
        district: 'Kollam',
        bounds: { minLat: 8.7, maxLat: 9.2, minLng: 76.4, maxLng: 77.0 },
        areas: ['Kollam', 'Karunagappally', 'Punalur', 'Paravur']
      },
      {
        district: 'Pathanamthitta',
        bounds: { minLat: 9.1, maxLat: 9.7, minLng: 76.6, maxLng: 77.2 },
        areas: ['Pathanamthitta', 'Adoor', 'Thiruvalla', 'Mallappally']
      },
      {
        district: 'Alappuzha',
        bounds: { minLat: 9.4, maxLat: 9.9, minLng: 76.2, maxLng: 76.7 },
        areas: ['Alappuzha', 'Cherthala', 'Kayamkulam', 'Mavelikkara']
      },
      {
        district: 'Idukki',
        bounds: { minLat: 9.2, maxLat: 10.3, minLng: 76.7, maxLng: 77.5 },
        areas: ['Thodupuzha', 'Munnar', 'Devikulam', 'Udumbanchola']
      },
      {
        district: 'Thrissur',
        bounds: { minLat: 10.2, maxLat: 10.8, minLng: 75.9, maxLng: 76.8 },
        areas: ['Thrissur', 'Chalakudy', 'Kodungallur', 'Irinjalakuda']
      },
      {
        district: 'Palakkad',
        bounds: { minLat: 10.4, maxLat: 11.2, minLng: 76.2, maxLng: 77.0 },
        areas: ['Palakkad', 'Ottappalam', 'Chittur', 'Mannarkkad']
      },
      {
        district: 'Malappuram',
        bounds: { minLat: 10.9, maxLat: 11.5, minLng: 75.8, maxLng: 76.4 },
        areas: ['Malappuram', 'Manjeri', 'Perinthalmanna', 'Tirur']
      },
      {
        district: 'Kozhikode',
        bounds: { minLat: 11.0, maxLat: 11.6, minLng: 75.5, maxLng: 76.2 },
        areas: ['Kozhikode', 'Vadakara', 'Koyilandy', 'Feroke']
      },
      {
        district: 'Wayanad',
        bounds: { minLat: 11.6, maxLat: 12.0, minLng: 75.8, maxLng: 76.5 },
        areas: ['Kalpetta', 'Mananthavady', 'Sulthan Bathery']
      },
      {
        district: 'Kannur',
        bounds: { minLat: 11.8, maxLat: 12.4, minLng: 75.0, maxLng: 75.8 },
        areas: ['Kannur', 'Thalassery', 'Payyanur', 'Iritty']
      },
      {
        district: 'Kasaragod',
        bounds: { minLat: 12.0, maxLat: 12.8, minLng: 74.8, maxLng: 75.4 },
        areas: ['Kasaragod', 'Kanhangad', 'Nileshwar', 'Uppala']
      }
    ];
    
    // Special handling for Ponkunnam area (which might be near Kottayam-Idukki border)
    const ponkunnamCoords = { lat: 9.6, lng: 76.7 }; // Approximate Ponkunnam coordinates
    const distanceToPonkunnam = Math.sqrt(
      Math.pow(lat - ponkunnamCoords.lat, 2) + Math.pow(lng - ponkunnamCoords.lng, 2)
    );
    
    if (distanceToPonkunnam < 0.1) { // Within ~10km of Ponkunnam
      return {
        district: 'Kottayam',
        area: 'Ponkunnam'
      };
    }
    
    for (const districtData of keralaDistricts) {
      const bounds = districtData.bounds;
      if (lat >= bounds.minLat && lat <= bounds.maxLat && 
          lng >= bounds.minLng && lng <= bounds.maxLng) {
        
        // Find closest area within the district
        let closestArea = districtData.areas[0]; // Default to first area
        
        // Special area detection for specific coordinates
        if (districtData.district === 'Kottayam') {
          if (lng > 76.6) {
            closestArea = 'Ponkunnam'; // Eastern part of Kottayam
          } else if (lat < 9.5) {
            closestArea = 'Changanassery'; // Southern part
          } else if (lat > 9.7) {
            closestArea = 'Vaikom'; // Northern part
          } else {
            closestArea = 'Kottayam Town'; // Central part
          }
        }
        
        return {
          district: districtData.district,
          area: closestArea
        };
      }
    }
    
    return null;
  };

  // Clean up location names
  const cleanLocationName = (locationName) => {
    return locationName
      .replace(/,\s*,/g, ',') // Remove double commas
      .replace(/^,\s*/, '') // Remove leading comma
      .replace(/,\s*$/, '') // Remove trailing comma
      .replace(/\s+/g, ' ') // Replace multiple spaces with single space
      .trim();
  };

  const capturePhoto = () => {
    if (!videoRef.current || !canvasRef.current || !location) {
      if (!location) {
        toast.error('Please wait for location to be captured first');
        return;
      }
      toast.error('Camera not ready');
      return;
    }

    const video = videoRef.current;
    const canvas = canvasRef.current;
    const context = canvas.getContext('2d');

    // Set canvas dimensions to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Draw video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Add enhanced location overlay with coordinates
    addLocationOverlay(context, canvas.width, canvas.height);

    // Convert to blob with high quality
    canvas.toBlob((blob) => {
      if (blob) {
        const photoId = Date.now();
        const enhancedLocation = {
          ...location,
          capturedAt: new Date().toISOString(),
          deviceInfo: {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language
          }
        };
        
        const photo = {
          id: photoId,
          blob,
          url: URL.createObjectURL(blob),
          location: enhancedLocation,
          timestamp: new Date().toISOString(),
          filename: `geo_photo_${photoId}_${location.latitude.toFixed(6)}_${location.longitude.toFixed(6)}.jpg`
        };

        setCapturedPhotos(prev => [...prev, photo]);
        
        // Show success message with coordinates
        toast.success(`📸 Photo captured with GPS location: ${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`);
        
        // Add a visual flash effect
        const flashDiv = document.createElement('div');
        flashDiv.className = 'capture-flash';
        video.parentElement.appendChild(flashDiv);
        setTimeout(() => {
          if (flashDiv.parentElement) {
            flashDiv.parentElement.removeChild(flashDiv);
          }
        }, 300);
      }
    }, 'image/jpeg', 0.95); // High quality JPEG
  };

  const addLocationOverlay = (context, width, height) => {
    // Enhanced overlay with better visibility and more location details
    const overlayHeight = 140;
    
    // Semi-transparent dark overlay at bottom
    context.fillStyle = 'rgba(0, 0, 0, 0.8)';
    context.fillRect(0, height - overlayHeight, width, overlayHeight);
    
    // Add a subtle border at the top of overlay
    context.fillStyle = 'rgba(255, 255, 255, 0.3)';
    context.fillRect(0, height - overlayHeight, width, 2);

    // White text for better contrast
    context.fillStyle = 'white';
    context.textAlign = 'left';
    
    const padding = 20;
    let yPos = height - overlayHeight + 25;
    
    // Location icon and place name (larger font)
    context.font = 'bold 18px Arial';
    context.fillText(`📍 ${location.placeName}`, padding, yPos);
    yPos += 28;
    
    // Coordinates (prominent display)
    context.font = 'bold 16px Arial';
    context.fillStyle = '#FFD700'; // Gold color for coordinates
    context.fillText(`Lat: ${location.latitude.toFixed(6)}°`, padding, yPos);
    context.fillText(`Lng: ${location.longitude.toFixed(6)}°`, padding + 180, yPos);
    yPos += 22;
    
    // Accuracy and timestamp
    context.font = '14px Arial';
    context.fillStyle = 'white';
    const timestamp = new Date(location.timestamp).toLocaleString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
    context.fillText(`🕒 ${timestamp}`, padding, yPos);
    yPos += 20;
    
    // Accuracy information
    context.font = '13px Arial';
    context.fillStyle = '#90EE90'; // Light green for accuracy
    const accuracyText = `📡 GPS Accuracy: ±${Math.round(location.accuracy)}m`;
    context.fillText(accuracyText, padding, yPos);
    
    // Add additional location details if available
    if (location.altitude !== null && location.altitude !== undefined) {
      context.fillStyle = '#87CEEB'; // Sky blue for altitude
      context.fillText(`⛰️ Alt: ${Math.round(location.altitude)}m`, padding + 200, yPos);
    }
    
    // Add a small BuildHub watermark
    context.font = '12px Arial';
    context.fillStyle = 'rgba(255, 255, 255, 0.7)';
    context.textAlign = 'right';
    context.fillText('BuildHub Geo Photo', width - padding, height - 10);
    
    // Reset text alignment
    context.textAlign = 'left';
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
          location: location || { placeName: 'Location not available' },
          timestamp: new Date().toISOString(),
          filename: file.name,
          isUploaded: true
        };
        
        setCapturedPhotos(prev => [...prev, photo]);
      }
    });
    
    // Reset file input
    event.target.value = '';
  };

  const removePhoto = (photoId) => {
    setCapturedPhotos(prev => {
      const updated = prev.filter(photo => photo.id !== photoId);
      // Revoke object URL to free memory
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
      toast.error('Project or contractor information missing');
      return;
    }

    setUploading(true);
    const uploadResults = [];

    try {
      for (let i = 0; i < capturedPhotos.length; i++) {
        const photo = capturedPhotos[i];
        setUploadProgress(prev => ({ ...prev, [photo.id]: 0 }));

        const formData = new FormData();
        formData.append('photo', photo.blob, photo.filename);
        formData.append('project_id', projectId);
        formData.append('contractor_id', contractorId);
        formData.append('location_data', JSON.stringify(photo.location));
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
            setUploadProgress(prev => ({ ...prev, [photo.id]: 100 }));
          } else {
            uploadResults.push({
              success: false,
              photoId: photo.id,
              error: result.message
            });
            setUploadProgress(prev => ({ ...prev, [photo.id]: -1 }));
          }
        } catch (error) {
          console.error('Upload error for photo:', photo.id, error);
          uploadResults.push({
            success: false,
            photoId: photo.id,
            error: 'Network error'
          });
          setUploadProgress(prev => ({ ...prev, [photo.id]: -1 }));
        }
      }

      const successCount = uploadResults.filter(r => r.success).length;
      const failCount = uploadResults.filter(r => !r.success).length;

      if (successCount > 0) {
        toast.success(`${successCount} photo(s) uploaded successfully and sent to homeowner`);
        
        // Call callback with results
        if (onPhotosCaptured) {
          onPhotosCaptured(uploadResults.filter(r => r.success));
        }
        
        // Clear captured photos after successful upload
        setCapturedPhotos([]);
      }

      if (failCount > 0) {
        toast.error(`${failCount} photo(s) failed to upload`);
      }

    } catch (error) {
      console.error('Upload process error:', error);
      toast.error('Failed to upload photos');
    } finally {
      setUploading(false);
      setUploadProgress({});
    }
  };

  return (
    <div className="geo-photo-capture">
      <div className="capture-header">
        <h3>📸 Geo-Located Photo Capture</h3>
        <button className="close-btn" onClick={onClose}>×</button>
      </div>

      {/* Location Status */}
      <div className="location-status">
        {locationLoading ? (
          <div className="location-loading">
            <div className="spinner"></div>
            <span>🛰️ Getting high-accuracy GPS location (may take 30-60 seconds)...</span>
          </div>
        ) : location ? (
          <div className={`location-info ${location.accuracy <= 100 ? 'success' : location.accuracy <= 1000 ? 'warning' : 'error'}`}>
            <div className="location-details">
              <div className="place-name">📍 {location.placeName}</div>
              <div className="coordinates">
                <strong>Lat:</strong> {location.latitude.toFixed(6)}° | <strong>Lng:</strong> {location.longitude.toFixed(6)}°
              </div>
              <div className="location-metadata">
                <span className={`accuracy ${location.accuracy <= 100 ? 'good' : location.accuracy <= 1000 ? 'fair' : 'poor'}`}>
                  📡 ±{Math.round(location.accuracy)}m accuracy
                  {location.accuracy > 1000 && ' (⚠️ POOR - May be inaccurate)'}
                  {location.accuracy <= 100 && ' (✅ GOOD)'}
                  {location.accuracy > 100 && location.accuracy <= 1000 && ' (⚠️ FAIR)'}
                </span>
                {location.altitude && (
                  <span className="altitude">⛰️ {Math.round(location.altitude)}m altitude</span>
                )}
                {location.attempts && (
                  <span className="attempts">🔄 GPS attempts: {location.attempts}</span>
                )}
              </div>
              <div className="capture-time">
                🕒 Captured: {new Date(location.timestamp).toLocaleString('en-IN')}
              </div>
              {location.accuracy > 1000 && (
                <div className="accuracy-warning">
                  ⚠️ <strong>Warning:</strong> GPS accuracy is poor (±{Math.round(location.accuracy/1000)}km). 
                  This location may be incorrect. Try moving to an open area and refresh.
                </div>
              )}
            </div>
            <button className="refresh-location-btn" onClick={getCurrentLocation}>
              🔄 Get Better GPS
            </button>
          </div>
        ) : (
          <div className="location-info error">
            <span>❌ GPS location not available</span>
            <button className="retry-location-btn" onClick={getCurrentLocation}>
              🛰️ Get GPS Location
            </button>
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
              title="Switch Camera"
            >
              🔄
            </button>
            
            <button
              className="capture-btn"
              onClick={capturePhoto}
              disabled={!location || locationLoading}
              title="Capture Photo"
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
            <h4>📷 Captured Photos ({capturedPhotos.length})</h4>
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
                  <img src={photo.url} alt="Captured" />
                  <button
                    className="remove-photo-btn"
                    onClick={() => removePhoto(photo.id)}
                    disabled={uploading}
                  >
                    ×
                  </button>
                </div>
                
                <div className="photo-info">
                  <div className="photo-location">
                    📍 {photo.location.placeName || 'Location unavailable'}
                  </div>
                  <div className="photo-timestamp">
                    🕒 {new Date(photo.timestamp).toLocaleString()}
                  </div>
                  
                  {uploadProgress[photo.id] !== undefined && (
                    <div className="upload-progress">
                      {uploadProgress[photo.id] === -1 ? (
                        <span className="upload-error">❌ Failed</span>
                      ) : uploadProgress[photo.id] === 100 ? (
                        <span className="upload-success">✅ Uploaded</span>
                      ) : (
                        <div className="progress-bar">
                          <div 
                            className="progress-fill"
                            style={{ width: `${uploadProgress[photo.id]}%` }}
                          ></div>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Instructions */}
      <div className="capture-instructions">
        <h4>📋 GPS Location Troubleshooting Guide</h4>
        
        {location && location.accuracy > 1000 && (
          <div style={{ marginBottom: '20px', padding: '15px', background: '#fff3cd', borderRadius: '8px', border: '1px solid #ffc107' }}>
            <h5 style={{ margin: '0 0 10px 0', color: '#856404' }}>⚠️ Poor GPS Accuracy Detected</h5>
            <p style={{ margin: '0', color: '#856404' }}>
              Your current GPS accuracy is ±{Math.round(location.accuracy/1000)}km, which means the location could be 
              {Math.round(location.accuracy/1000)}km away from your actual position. Follow the steps below to improve accuracy.
            </p>
          </div>
        )}
        
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '15px', marginBottom: '20px' }}>
          <div style={{ padding: '15px', background: '#e7f3ff', borderRadius: '8px', border: '1px solid #b8daff' }}>
            <h5 style={{ margin: '0 0 10px 0', color: '#004085' }}>🛰️ For Best GPS Accuracy:</h5>
            <ul style={{ margin: '0', paddingLeft: '20px', color: '#004085' }}>
              <li>Go outside to an open area</li>
              <li>Ensure clear view of the sky</li>
              <li>Stay away from tall buildings</li>
              <li>Wait 30-60 seconds for GPS satellites</li>
              <li>Keep your device still during capture</li>
            </ul>
          </div>
          
          <div style={{ padding: '15px', background: '#f8d7da', borderRadius: '8px', border: '1px solid #f5c6cb' }}>
            <h5 style={{ margin: '0 0 10px 0', color: '#721c24' }}>❌ Avoid These Locations:</h5>
            <ul style={{ margin: '0', paddingLeft: '20px', color: '#721c24' }}>
              <li>Inside buildings or rooms</li>
              <li>Under bridges or overhangs</li>
              <li>Near tall buildings or trees</li>
              <li>In basements or underground</li>
              <li>During heavy rain or storms</li>
            </ul>
          </div>
        </div>
        
        <h5>📱 Device Settings Check:</h5>
        <ul>
          <li>📍 <strong>Enable GPS:</strong> Ensure GPS/Location Services are ON in device settings</li>
          <li>🔋 <strong>High Accuracy Mode:</strong> Set location mode to "High Accuracy" or "GPS + Networks"</li>
          <li>🌐 <strong>Browser Permissions:</strong> Allow location access for this website</li>
          <li>📶 <strong>Network:</strong> Good mobile/WiFi signal helps initial GPS lock</li>
        </ul>
        
        <h5>🎯 Accuracy Guidelines:</h5>
        <ul>
          <li>✅ <strong>Excellent (±5-20m):</strong> Perfect for construction photos</li>
          <li>✅ <strong>Good (±20-100m):</strong> Acceptable for most purposes</li>
          <li>⚠️ <strong>Fair (±100-1000m):</strong> May be inaccurate, try to improve</li>
          <li>❌ <strong>Poor (±1km+):</strong> Likely wrong location, must improve</li>
        </ul>
        
        <div style={{ marginTop: '15px', padding: '12px', background: '#d1ecf1', borderRadius: '6px', fontSize: '0.85rem' }}>
          <strong>💡 For Ponkunnam, Kottayam Users:</strong>
          <p style={{ margin: '8px 0 0 0' }}>
            If you're in Ponkunnam area and seeing locations like "Edathala, Aluva" (50km away), 
            your device is using cell tower location instead of GPS. Move to an open area and wait 
            for true GPS satellites to lock onto your actual location.
          </p>
        </div>
      </div>
    </div>
  );
};

export default GeoPhotoCapture;