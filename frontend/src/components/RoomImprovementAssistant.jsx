import React, { useState, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/RoomImprovementAssistant.css';

const RoomImprovementAssistant = ({ show, onClose }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  // Debug logging
  React.useEffect(() => {
    console.log('🏠 RoomImprovementAssistant show state changed:', show);
  }, [show]);
  
  const [formData, setFormData] = useState({
    room_type: '',
    improvement_notes: '',
    selected_file: null
  });
  
  const [analyzing, setAnalyzing] = useState(false);
  const [analysisResult, setAnalysisResult] = useState(null);
  const [previewImage, setPreviewImage] = useState(null);
  const [imageGenerationStatus, setImageGenerationStatus] = useState(null); // For async image generation
  const [pollingJobId, setPollingJobId] = useState(null); // Job ID for polling

  const roomTypes = [
    { value: 'bedroom', label: 'Bedroom', icon: '🛏️' },
    { value: 'living_room', label: 'Living Room', icon: '🛋️' },
    { value: 'kitchen', label: 'Kitchen', icon: '🍳' },
    { value: 'dining_room', label: 'Dining Room', icon: '🍽️' },
    { value: 'other', label: 'Other', icon: '🏠' }
  ];

  // Handle body scroll prevention when modal is open
  React.useEffect(() => {
    if (show) {
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
    } else {
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
    }

    return () => {
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
    };
  }, [show]);

  // Handle escape key
  React.useEffect(() => {
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && show && !analyzing) {
        handleClose();
      }
    };

    if (show) {
      document.addEventListener('keydown', handleEscapeKey);
    }

    return () => {
      document.removeEventListener('keydown', handleEscapeKey);
    };
  }, [show, analyzing]);

  const handleClose = () => {
    if (!analyzing) {
      setFormData({
        room_type: '',
        improvement_notes: '',
        selected_file: null
      });
      setAnalysisResult(null);
      setPreviewImage(null);
      onClose();
    }
  };

  const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
      toast.error('Please select a valid image file (JPG or PNG)');
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      return;
    }

    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Image file size must be less than 5MB');
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      return;
    }

    // Check if it's JPG or PNG specifically
    if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
      toast.error('Only JPG and PNG image formats are supported');
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
      return;
    }

    setFormData(prev => ({ ...prev, selected_file: file }));

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      setPreviewImage(e.target.result);
    };
    reader.onerror = () => {
      toast.error('Failed to read the selected file');
      setFormData(prev => ({ ...prev, selected_file: null }));
    };
    reader.readAsDataURL(file);
  };

  const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
  };

  const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    
    const files = Array.from(event.dataTransfer.files);
    if (files.length > 0) {
      const file = files[0];
      if (file.type.startsWith('image/')) {
        handleFileSelect({ target: { files: [file] } });
      } else {
        toast.error('Please drop a valid image file');
      }
    }
  };

  const removeFile = () => {
    setFormData(prev => ({ ...prev, selected_file: null }));
    setPreviewImage(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const analyzeRoom = async () => {
    // Validation
    if (!formData.room_type) {
      toast.error('Please select a room type');
      return;
    }

    if (!formData.selected_file) {
      toast.error('Please upload a room image');
      return;
    }

    try {
      setAnalyzing(true);
      setImageGenerationStatus(null);
      setPollingJobId(null);
      toast.info('🔍 Analyzing room image and generating improvement concepts...');

      const formDataToSend = new FormData();
      formDataToSend.append('room_type', formData.room_type);
      formDataToSend.append('improvement_notes', formData.improvement_notes);
      formDataToSend.append('room_image', formData.selected_file);

      console.log('Sending analysis request:', {
        room_type: formData.room_type,
        improvement_notes: formData.improvement_notes,
        file_name: formData.selected_file.name,
        file_size: formData.selected_file.size,
        file_type: formData.selected_file.type
      });

      const response = await fetch('/buildhub/backend/api/homeowner/analyze_room_improvement.php', {
        method: 'POST',
        credentials: 'include',
        body: formDataToSend
      });

      console.log('Response status:', response.status);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        setAnalysisResult(data.analysis);
        toast.success('✨ Room improvement concept generated successfully!');
        
        // Start asynchronous image generation
        startAsyncImageGeneration(data.analysis);
      } else {
        console.error('API error:', data);
        toast.error(data.message || 'Failed to analyze room. Please try again.');
      }
    } catch (error) {
      console.error('Room analysis error:', error);
      if (error.name === 'TypeError' && error.message.includes('fetch')) {
        toast.error('Network error. Please check your connection and try again.');
      } else if (error.message.includes('HTTP error')) {
        toast.error(`Server error (${error.message}). Please try again later.`);
      } else {
        toast.error('An unexpected error occurred. Please try again.');
      }
    } finally {
      setAnalyzing(false);
    }
  };

  const startAsyncImageGeneration = async (analysisData) => {
    try {
      setImageGenerationStatus({
        status: 'starting',
        message: 'Starting real AI image generation with Stable Diffusion...'
      });

      const requestData = {
        analysis_data: analysisData,
        room_type: formData.room_type,
        improvement_notes: formData.improvement_notes
      };

      console.log('Starting async image generation:', requestData);

      const response = await fetch('/buildhub/backend/api/homeowner/generate_conceptual_image.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(requestData)
      });

      const data = await response.json();
      console.log('Async image generation response:', data);

      if (data.success && data.async_generation && data.async_generation.job_id) {
        const jobId = data.async_generation.job_id;
        setPollingJobId(jobId);
        setImageGenerationStatus({
          status: 'pending',
          message: 'Real AI image generation started. Generating conceptual visualization...',
          job_id: jobId,
          estimated_completion_time: data.async_generation.estimated_completion_time
        });

        toast.info('🎨 Real AI image generation started in background...');
        
        // Start polling for status
        startStatusPolling(jobId);
      } else {
        console.error('Failed to start async image generation:', data);
        setImageGenerationStatus({
          status: 'failed',
          message: data.message || 'Failed to start image generation',
          error: data.async_generation?.error || 'Unknown error'
        });
        toast.error('Failed to start AI image generation');
      }
    } catch (error) {
      console.error('Error starting async image generation:', error);
      setImageGenerationStatus({
        status: 'failed',
        message: 'Error starting image generation',
        error: error.message
      });
      toast.error('Error starting AI image generation');
    }
  };

  const startStatusPolling = (jobId) => {
    const pollInterval = 3000; // 3 seconds
    const maxPollingTime = 120000; // 2 minutes
    const startTime = Date.now();

    const poll = async () => {
      try {
        const response = await fetch('/buildhub/backend/api/homeowner/check_image_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({ job_id: jobId })
        });

        const data = await response.json();
        console.log('🔍 [DEBUG] Status poll response - Full data:', data);
        console.log('🔍 [DEBUG] Status poll response - data.success:', data.success);
        console.log('🔍 [DEBUG] Status poll response - data.status:', data.status);
        console.log('🔍 [DEBUG] Status poll response - data.conceptual_visualization:', data.conceptual_visualization);

        if (data.success) {
          if (data.status === 'completed') {
            // Image generation completed
            setImageGenerationStatus({
              status: 'completed',
              message: 'Real AI image generated successfully!',
              conceptual_visualization: data.conceptual_visualization
            });
            
            console.log('🔍 [DEBUG] Async polling completed - Full response data:');
            console.log('🔍 [DEBUG] - data.success:', data.success);
            console.log('🔍 [DEBUG] - data.conceptual_visualization:', data.conceptual_visualization);
            console.log('🔍 [DEBUG] - data.conceptual_visualization.success:', data.conceptual_visualization?.success);
            console.log('🔍 [DEBUG] - data.conceptual_visualization.image_url:', data.conceptual_visualization?.image_url);
            console.log('🔍 [DEBUG] - data.conceptual_visualization.image_path:', data.conceptual_visualization?.image_path);
            
            // Update analysis result with the generated image
            setAnalysisResult(prevResult => {
              const updatedResult = {
                ...prevResult,
                ai_enhancements: {
                  ...prevResult.ai_enhancements,
                  conceptual_visualization: data.conceptual_visualization
                }
              };
              
              console.log('🔍 [DEBUG] Updated analysis result:');
              console.log('🔍 [DEBUG] - updatedResult.ai_enhancements.conceptual_visualization:', updatedResult.ai_enhancements.conceptual_visualization);
              console.log('🔍 [DEBUG] - updatedResult.ai_enhancements.conceptual_visualization.success:', updatedResult.ai_enhancements.conceptual_visualization?.success);
              console.log('🔍 [DEBUG] - updatedResult.ai_enhancements.conceptual_visualization.image_url:', updatedResult.ai_enhancements.conceptual_visualization?.image_url);
              
              // Log the image URL that will be used - always Apache
              if (data.conceptual_visualization?.image_url) {
                const imageUrl = data.conceptual_visualization.image_url;
                const apacheUrl = `http://localhost${imageUrl}`;
                console.log('🔍 [DEBUG] Final URL construction:');
                console.log('🔍 [DEBUG] - Image URL from backend:', imageUrl);
                console.log('🔍 [DEBUG] - Final Apache URL:', apacheUrl);
                console.log('🔍 [DEBUG] - Expected file: C:/xampp/htdocs' + imageUrl);
              }
              
              return updatedResult;
            });

            toast.success('🎨 Real AI conceptual image generated successfully!');
            return; // Stop polling
            
          } else if (data.status === 'failed') {
            // Image generation failed
            setImageGenerationStatus({
              status: 'failed',
              message: 'Real AI image generation failed',
              error: data.conceptual_visualization?.error || 'Generation failed'
            });
            toast.error('AI image generation failed');
            return; // Stop polling
            
          } else if (data.status === 'processing') {
            // Still processing
            setImageGenerationStatus({
              status: 'processing',
              message: data.progress?.progress_message || 'Generating real AI image with Stable Diffusion...',
              estimated_remaining_seconds: data.progress?.estimated_remaining_seconds
            });
            
          } else { // pending
            setImageGenerationStatus({
              status: 'pending',
              message: data.progress?.progress_message || 'Image generation queued...'
            });
          }

          // Continue polling if not completed/failed and within time limit
          if ((Date.now() - startTime) < maxPollingTime && 
              data.polling_instructions?.continue_polling !== false) {
            setTimeout(poll, pollInterval);
          } else if ((Date.now() - startTime) >= maxPollingTime) {
            // Timeout
            setImageGenerationStatus({
              status: 'timeout',
              message: 'Image generation is taking longer than expected',
              note: 'The image may still be generating in the background'
            });
            toast.warning('Image generation is taking longer than expected');
          }
        } else {
          console.error('Status poll error:', data);
          setImageGenerationStatus({
            status: 'error',
            message: 'Error checking image generation status',
            error: data.message
          });
        }
      } catch (error) {
        console.error('Status polling error:', error);
        setImageGenerationStatus({
          status: 'error',
          message: 'Error checking image generation status',
          error: error.message
        });
      }
    };

    // Start polling
    setTimeout(poll, pollInterval);
  };

  const resetForm = () => {
    setFormData({
      room_type: '',
      improvement_notes: '',
      selected_file: null
    });
    setAnalysisResult(null);
    setPreviewImage(null);
    setImageGenerationStatus(null);
    setPollingJobId(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  if (!show) {
    console.log('🏠 RoomImprovementAssistant: show is false, not rendering modal');
    return null;
  }

  console.log('🏠 RoomImprovementAssistant: show is true, rendering modal');

  return (
    <div 
      className="room-improvement-modal-overlay" 
      style={{ 
        zIndex: 1000000,
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        background: 'rgba(0, 0, 0, 0.75)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '1rem'
      }}
    >
      <div className="room-improvement-modal">
        <div className="room-improvement-header">
          <div className="header-content">
            <div className="header-icon">🏠</div>
            <div>
              <h2>Post-Construction Room Improvement Assistant</h2>
              <p>Upload your completed room photo to receive AI-assisted improvement and renovation concepts</p>
            </div>
          </div>
          <button 
            className="close-button" 
            onClick={handleClose}
            disabled={analyzing}
            aria-label="Close"
          >
            ×
          </button>
        </div>

        <div className="room-improvement-content">
          {!analysisResult ? (
            // Upload Form
            <div className="upload-form">
              <div className="form-section">
                <label className="form-label">
                  <span className="label-text">Room Type *</span>
                  <span className="label-description">Select the type of room you want to improve</span>
                </label>
                <div className="room-type-grid">
                  {roomTypes.map((room) => (
                    <button
                      key={room.value}
                      type="button"
                      className={`room-type-option ${formData.room_type === room.value ? 'selected' : ''}`}
                      onClick={() => setFormData(prev => ({ ...prev, room_type: room.value }))}
                      disabled={analyzing}
                    >
                      <span className="room-icon">{room.icon}</span>
                      <span className="room-label">{room.label}</span>
                    </button>
                  ))}
                </div>
              </div>

              <div className="form-section">
                <label className="form-label">
                  <span className="label-text">Room Image *</span>
                  <span className="label-description">Upload a clear photo of your completed room (JPG/PNG, max 5MB)</span>
                </label>
                
                {!previewImage ? (
                  <div 
                    className="file-upload-area"
                    onDragOver={handleDragOver}
                    onDrop={handleDrop}
                    onClick={() => fileInputRef.current?.click()}
                  >
                    <div className="upload-icon">📸</div>
                    <div className="upload-text">
                      <p><strong>Click to upload</strong> or drag and drop</p>
                      <p>JPG or PNG (max 5MB)</p>
                    </div>
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept="image/jpeg,image/jpg,image/png"
                      onChange={handleFileSelect}
                      style={{ display: 'none' }}
                      disabled={analyzing}
                    />
                  </div>
                ) : (
                  <div className="image-preview">
                    <img src={previewImage} alt="Room preview" />
                    <button 
                      className="remove-image-btn"
                      onClick={removeFile}
                      disabled={analyzing}
                      aria-label="Remove image"
                    >
                      ×
                    </button>
                  </div>
                )}
              </div>

              <div className="form-section">
                <label className="form-label">
                  <span className="label-text">What would you like to improve? (Optional)</span>
                  <span className="label-description">Describe specific aspects you'd like to enhance (lighting, style, comfort, etc.)</span>
                </label>
                <textarea
                  name="improvement_notes"
                  value={formData.improvement_notes}
                  onChange={handleInputChange}
                  placeholder="e.g., The room feels too dark, I want to make it more cozy, need better storage solutions..."
                  className="improvement-notes"
                  rows="3"
                  disabled={analyzing}
                />
              </div>

              <div className="form-actions">
                <button 
                  className="cancel-btn"
                  onClick={handleClose}
                  disabled={analyzing}
                >
                  Cancel
                </button>
                <button 
                  className="analyze-btn"
                  onClick={analyzeRoom}
                  disabled={analyzing || !formData.room_type || !formData.selected_file}
                >
                  {analyzing ? (
                    <>
                      <span className="spinner"></span>
                      Analyzing Room...
                    </>
                  ) : (
                    <>
                      <span className="analyze-icon">🔍</span>
                      Analyze Room & Generate Concept
                    </>
                  )}
                </button>
              </div>
            </div>
          ) : (
            // Analysis Results
            <div className="analysis-results">
              <div className="concept-card">
                <div className="concept-header">
                  <h3 className="concept-name">{analysisResult.concept_name}</h3>
                  <div className="concept-type">
                    <span className="type-badge">AI-Assisted Concept</span>
                  </div>
                </div>

                <div className="concept-sections">
                  {/* NEW: Asynchronous Image Generation Status */}
                  {imageGenerationStatus && (
                    <div className="concept-section">
                      <h4>🎨 Real AI Image Generation Status</h4>
                      <div className="async-image-status">
                        {imageGenerationStatus.status === 'starting' && (
                          <div className="status-starting">
                            <div className="status-icon">🚀</div>
                            <div className="status-content">
                              <h5>Starting Real AI Image Generation</h5>
                              <p>{imageGenerationStatus.message}</p>
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'pending' && (
                          <div className="status-pending">
                            <div className="status-icon">⏳</div>
                            <div className="status-content">
                              <h5>Image Generation Queued</h5>
                              <p>{imageGenerationStatus.message}</p>
                              {imageGenerationStatus.estimated_completion_time && (
                                <small>Estimated completion: {imageGenerationStatus.estimated_completion_time}</small>
                              )}
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'processing' && (
                          <div className="status-processing">
                            <div className="status-icon">
                              <div className="spinner"></div>
                            </div>
                            <div className="status-content">
                              <h5>Generating Real AI Image</h5>
                              <p>{imageGenerationStatus.message}</p>
                              {imageGenerationStatus.estimated_remaining_seconds && (
                                <div className="progress-info">
                                  <small>Estimated remaining: ~{imageGenerationStatus.estimated_remaining_seconds} seconds</small>
                                  <div className="progress-note">
                                    <em>Stable Diffusion is creating your conceptual visualization...</em>
                                  </div>
                                </div>
                              )}
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'completed' && (
                          <div className="status-completed">
                            <div className="status-icon">✅</div>
                            <div className="status-content">
                              <h5>Real AI Image Generated Successfully!</h5>
                              <p>{imageGenerationStatus.message}</p>
                              <div className="completion-note">
                                <em>Your conceptual visualization is now available below.</em>
                              </div>
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'failed' && (
                          <div className="status-failed">
                            <div className="status-icon">❌</div>
                            <div className="status-content">
                              <h5>Image Generation Failed</h5>
                              <p>{imageGenerationStatus.message}</p>
                              <div className="error-details">
                                <small>Error: {imageGenerationStatus.error}</small>
                              </div>
                              <div className="fallback-note">
                                <em>Your room analysis and improvement suggestions are still available.</em>
                              </div>
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'timeout' && (
                          <div className="status-timeout">
                            <div className="status-icon">⏰</div>
                            <div className="status-content">
                              <h5>Generation Taking Longer Than Expected</h5>
                              <p>{imageGenerationStatus.message}</p>
                              <div className="timeout-note">
                                <em>{imageGenerationStatus.note}</em>
                              </div>
                            </div>
                          </div>
                        )}
                        
                        {imageGenerationStatus.status === 'error' && (
                          <div className="status-error">
                            <div className="status-icon">⚠️</div>
                            <div className="status-content">
                              <h5>Status Check Error</h5>
                              <p>{imageGenerationStatus.message}</p>
                              <div className="error-details">
                                <small>Error: {imageGenerationStatus.error}</small>
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  )}

                  <div className="concept-section">
                    <h4>📋 Current Room Analysis</h4>
                    <div className="analysis-content">
                      <p>{analysisResult.room_condition_summary}</p>
                      {analysisResult.visual_observations && (
                        <div className="visual-observations">
                          <h5>Visual Intelligence Observations:</h5>
                          <ul>
                            {analysisResult.visual_observations.map((observation, index) => (
                              <li key={index}>{observation}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                      {analysisResult.visual_intelligence && analysisResult.visual_intelligence.extracted_features && (
                        <div className="visual-features">
                          <h5>Image Analysis Results:</h5>
                          <div className="analysis-method-note">
                            <span className="method-badge">
                              {analysisResult.visual_intelligence.analysis_method === 'advanced_gd_processing' ? 
                                '🔬 Advanced Visual Processing' : 
                                '📊 Basic Visual Analysis'}
                            </span>
                          </div>
                          <div className="feature-grid">
                            <div className="feature-item">
                              <span className="feature-label">Brightness:</span>
                              <span className="feature-value">{analysisResult.visual_intelligence.extracted_features.brightness}/255</span>
                            </div>
                            <div className="feature-item">
                              <span className="feature-label">Contrast:</span>
                              <span className="feature-value">{analysisResult.visual_intelligence.extracted_features.contrast}%</span>
                            </div>
                            <div className="feature-item">
                              <span className="feature-label">Color Tone:</span>
                              <span className="feature-value">{analysisResult.visual_intelligence.extracted_features.color_temperature.category}</span>
                            </div>
                            <div className="feature-item">
                              <span className="feature-label">Saturation:</span>
                              <span className="feature-value">{analysisResult.visual_intelligence.extracted_features.saturation_level}%</span>
                            </div>
                          </div>
                          {analysisResult.visual_intelligence.analysis_method !== 'advanced_gd_processing' && (
                            <div className="analysis-note">
                              <p><em>Note: Using heuristic-based analysis. Results are based on file characteristics and intelligent estimation.</em></p>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="concept-section">
                    <h4>💡 Improvement Suggestions</h4>
                    <div className="suggestions-grid">
                      {analysisResult.improvement_suggestions.lighting && (
                        <div className="suggestion-item">
                          <h5>💡 Lighting Enhancement</h5>
                          <p>{analysisResult.improvement_suggestions.lighting}</p>
                        </div>
                      )}
                      {analysisResult.improvement_suggestions.color_ambience && (
                        <div className="suggestion-item">
                          <h5>🎨 Color & Ambience</h5>
                          <p>{analysisResult.improvement_suggestions.color_ambience}</p>
                        </div>
                      )}
                      {analysisResult.improvement_suggestions.furniture_layout && (
                        <div className="suggestion-item">
                          <h5>🪑 Furniture & Layout</h5>
                          <p>{analysisResult.improvement_suggestions.furniture_layout}</p>
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="concept-section">
                    <h4>✨ Style Recommendation</h4>
                    <div className="style-recommendation">
                      <div className="style-name">{analysisResult.style_recommendation.style}</div>
                      <p>{analysisResult.style_recommendation.description}</p>
                      {analysisResult.style_recommendation.confidence && (
                        <div className="style-confidence">
                          <span className="confidence-label">Visual Analysis Confidence:</span>
                          <span className="confidence-value">{analysisResult.style_recommendation.confidence}%</span>
                        </div>
                      )}
                      {analysisResult.style_recommendation.key_elements && (
                        <div className="key-elements">
                          <h5>Key Elements:</h5>
                          <div className="elements-tags">
                            {analysisResult.style_recommendation.key_elements.map((element, index) => (
                              <span key={index} className="element-tag">{element}</span>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>

                  {analysisResult.visual_reference && (
                    <div className="concept-section">
                      <h4>🖼️ Visual Reference</h4>
                      <div className="visual-reference">
                        <div className="reference-note">
                          <strong>Note:</strong> This is a conceptual and inspirational reference only. 
                          Actual implementation may vary based on your specific room conditions and preferences.
                        </div>
                        <p>{analysisResult.visual_reference}</p>
                        
                        {/* Show conceptual image in Visual Reference section */}
                        {(() => {
                          // Debug conditional rendering logic
                          const hasAiEnhancements = !!analysisResult.ai_enhancements;
                          const hasConceptualVisualization = !!analysisResult.ai_enhancements?.conceptual_visualization;
                          const isSuccess = analysisResult.ai_enhancements?.conceptual_visualization?.success;
                          const hasImageUrl = !!analysisResult.ai_enhancements?.conceptual_visualization?.image_url;
                          const imageUrl = analysisResult.ai_enhancements?.conceptual_visualization?.image_url;
                          
                          console.log('🔍 [DEBUG] Visual Reference conditional rendering check:');
                          console.log('🔍 [DEBUG] - hasAiEnhancements:', hasAiEnhancements);
                          console.log('🔍 [DEBUG] - hasConceptualVisualization:', hasConceptualVisualization);
                          console.log('🔍 [DEBUG] - isSuccess:', isSuccess);
                          console.log('🔍 [DEBUG] - hasImageUrl:', hasImageUrl);
                          console.log('🔍 [DEBUG] - imageUrl value:', imageUrl);
                          console.log('🔍 [DEBUG] - Full conceptual_visualization object:', analysisResult.ai_enhancements?.conceptual_visualization);
                          
                          const shouldRender = hasAiEnhancements && hasConceptualVisualization && isSuccess && hasImageUrl;
                          console.log('🔍 [DEBUG] - Should render image:', shouldRender);
                          
                          if (!shouldRender) {
                            console.log('🔍 [DEBUG] - Image NOT rendered due to conditional check failure');
                            return null;
                          }
                          
                          return (
                            <div className="visual-reference-image">
                              <h5>🎨 Real AI-Generated Conceptual Visualization</h5>
                              <div className="generated-image-container">
                                {(() => {
                                  // Always construct Apache URL explicitly - never use relative paths
                                  const apacheUrl = `http://localhost${imageUrl}`;
                                  console.log('🔍 [DEBUG] Visual Reference - Final image rendering:');
                                  console.log('🔍 [DEBUG] - Original image_url from backend:', imageUrl);
                                  console.log('🔍 [DEBUG] - Final Apache URL for <img src>:', apacheUrl);
                                  console.log('🔍 [DEBUG] - About to render <img> element with src:', apacheUrl);
                                  
                                  return (
                                    <img 
                                      src={apacheUrl}
                                      alt="Real AI-generated conceptual visualization using Stable Diffusion"
                                      className="generated-image"
                                      onLoad={() => {
                                        console.log('✅ [DEBUG] Visual Reference - Image loaded successfully from Apache:', apacheUrl);
                                      }}
                                      onError={(e) => {
                                        console.error('❌ [DEBUG] Visual Reference - Failed to load image from Apache:', e.target.src);
                                        console.error('❌ [DEBUG] Visual Reference - Expected file location: C:/xampp/htdocs' + imageUrl);
                                        console.error('❌ [DEBUG] Visual Reference - Error event:', e);
                                        e.target.style.display = 'none';
                                        e.target.nextSibling.style.display = 'block';
                                      }}
                                    />
                                  );
                                })()}
                                <div className="image-error-message" style={{display: 'none', color: '#dc3545', padding: '10px', textAlign: 'center'}}>
                                  ❌ Real AI image generated but not accessible via Apache<br/>
                                  <small>Image file exists but Apache cannot serve it</small>
                                  <div style={{marginTop: '5px', fontSize: '11px', fontFamily: 'monospace'}}>
                                    File: C:/xampp/htdocs{imageUrl}<br/>
                                    URL: http://localhost{imageUrl}
                                  </div>
                                </div>
                              </div>
                              <div className="image-disclaimer">
                                <small><em>{analysisResult.ai_enhancements.conceptual_visualization.disclaimer}</em></small>
                                <div className="ai-generation-info">
                                  <span className="generation-badge">Generated by Stable Diffusion AI</span>
                                  {analysisResult.ai_enhancements.conceptual_visualization.generation_metadata?.model_id && (
                                    <span className="model-info">Model: {analysisResult.ai_enhancements.conceptual_visualization.generation_metadata.model_id}</span>
                                  )}
                                </div>
                              </div>
                            </div>
                          );
                        })()}
                        
                        {/* Show fallback message if no image */}
                        {analysisResult.ai_enhancements?.conceptual_visualization && 
                         !analysisResult.ai_enhancements.conceptual_visualization.success && (
                          <div className="visual-reference-fallback">
                            <p><strong>🎨 Real AI image generation attempted but unavailable for display</strong></p>
                            <p>Stable Diffusion AI attempted to create a visualization based on your room analysis.</p>
                            <p><strong>Design Concept Details:</strong></p>
                            <p><em>{analysisResult.ai_enhancements.design_description || 'AI-generated design concept available in analysis results.'}</em></p>
                            <div className="fallback-info">
                              <small>Error: {analysisResult.ai_enhancements.conceptual_visualization.error || 'Image generation service temporarily unavailable'}</small>
                              <div style={{marginTop: '5px', fontSize: '11px', fontFamily: 'monospace'}}>
                                Expected Apache path: C:/xampp/htdocs/buildhub/uploads/conceptual_images/
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  )}

                  {/* NEW: Conceptual Visualization Section */}
                  {analysisResult.ai_enhancements?.conceptual_visualization && (
                    <div className="concept-section">
                      <h4>🎨 Conceptual Visualization</h4>
                      <div className="conceptual-visualization">
                        {analysisResult.ai_enhancements.conceptual_visualization.success ? (
                          <div className="visualization-content">
                            <div className="visualization-disclaimer">
                              <div className="disclaimer-badge">Conceptual Preview</div>
                              <p>This AI-generated image is an inspirational visualization based on your room analysis. 
                              It represents design concepts and mood rather than an exact reconstruction of your space.</p>
                            </div>
                            
                            {analysisResult.ai_enhancements.conceptual_visualization.image_url && (
                              <div className="generated-image-container">
                                {(() => {
                                  // Always construct Apache URL explicitly - never use relative paths
                                  const imageUrl = analysisResult.ai_enhancements.conceptual_visualization.image_url;
                                  const apacheUrl = `http://localhost${imageUrl}`;
                                  console.log('🖼️ [Conceptual Visualization] Loading image from Apache:', apacheUrl);
                                  console.log('🖼️ [Conceptual Visualization] Original image_url from backend:', imageUrl);
                                  
                                  return (
                                    <img 
                                      src={apacheUrl}
                                      alt="Real AI conceptual room improvement visualization using Stable Diffusion"
                                      className="generated-image"
                                      onLoad={() => console.log('✅ [Conceptual Visualization] Real AI image loaded from Apache:', apacheUrl)}
                                      onError={(e) => {
                                        console.error('❌ [Conceptual Visualization] Failed to load real AI image from Apache:', e.target.src);
                                        console.error('❌ [Conceptual Visualization] Expected file location: C:/xampp/htdocs' + imageUrl);
                                        e.target.style.display = 'none';
                                        e.target.nextSibling.style.display = 'block';
                                      }}
                                    />
                                  );
                                })()}
                                <div className="image-error" style={{display: 'none'}}>
                                  <p>🎨 Real AI image generated but not accessible via Apache</p>
                                  <p>Image file exists but Apache cannot serve it</p>
                                  <small>File: C:/xampp/htdocs{analysisResult.ai_enhancements.conceptual_visualization.image_url}</small>
                                  <div style={{marginTop: '5px', fontSize: '11px', fontFamily: 'monospace'}}>
                                    Apache URL: http://localhost{analysisResult.ai_enhancements.conceptual_visualization.image_url}
                                  </div>
                                </div>
                              </div>
                            )}
                            
                            {analysisResult.ai_enhancements.conceptual_visualization.design_description && (
                              <div className="design-description">
                                <h5>Design Concept Description:</h5>
                                <div className="description-content">
                                  {analysisResult.ai_enhancements.conceptual_visualization.design_description.room_characteristics && (
                                    <div className="description-section">
                                      <h6>Room Characteristics:</h6>
                                      <ul>
                                        <li>Style: {analysisResult.ai_enhancements.conceptual_visualization.design_description.room_characteristics.current_style}</li>
                                        <li>Lighting: {analysisResult.ai_enhancements.conceptual_visualization.design_description.room_characteristics.lighting_condition}</li>
                                        <li>Color Palette: {analysisResult.ai_enhancements.conceptual_visualization.design_description.room_characteristics.color_palette}</li>
                                      </ul>
                                    </div>
                                  )}
                                  
                                  {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions && (
                                    <div className="description-section">
                                      <h6>Improvement Directions:</h6>
                                      <ul>
                                        {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.lighting_mood && (
                                          <li>Lighting Mood: {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.lighting_mood}</li>
                                        )}
                                        {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.color_direction && (
                                          <li>Color Direction: {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.color_direction}</li>
                                        )}
                                        {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.style_enhancement && (
                                          <li>Style Enhancement: {analysisResult.ai_enhancements.conceptual_visualization.design_description.improvement_directions.style_enhancement}</li>
                                        )}
                                      </ul>
                                    </div>
                                  )}
                                </div>
                              </div>
                            )}
                            
                            {analysisResult.ai_enhancements.conceptual_visualization.text_prompt && (
                              <div className="generation-details">
                                <h5>AI Generation Prompt:</h5>
                                <p className="generation-prompt">"{analysisResult.ai_enhancements.conceptual_visualization.text_prompt}"</p>
                              </div>
                            )}
                            
                            <div className="visualization-footer">
                              <div className="generation-info">
                                <span className="info-item">
                                  <strong>Generated:</strong> {analysisResult.ai_enhancements.conceptual_visualization.generation_metadata?.generation_timestamp}
                                </span>
                                <span className="info-item">
                                  <strong>Model:</strong> {analysisResult.ai_enhancements.conceptual_visualization.generation_metadata?.model_id || 'Stable Diffusion'}
                                </span>
                              </div>
                              <div className="professional-note">
                                <p><em>💡 Use this visualization as design inspiration. Consult with interior design professionals for implementation guidance.</em></p>
                              </div>
                            </div>
                          </div>
                        ) : (
                          <div className="visualization-unavailable">
                            <div className="unavailable-icon">🎨</div>
                            <h5>Conceptual Visualization Unavailable</h5>
                            <p>{analysisResult.ai_enhancements.conceptual_visualization.error || 
                               analysisResult.ai_enhancements.conceptual_visualization.note || 
                               'Conceptual image generation is temporarily unavailable.'}</p>
                            <p className="fallback-note">Your room analysis and improvement suggestions are still available above.</p>
                          </div>
                        )}
                      </div>
                    </div>
                  )}

                  {/* NEW: Enhanced Collaborative AI Pipeline Results */}
                  {analysisResult.ai_enhancements && (
                    <div className="concept-section">
                      <h4>🤖 Collaborative AI Pipeline Results</h4>
                      <div className="ai-pipeline-results">
                        
                        {/* Pipeline Status */}
                        <div className="pipeline-status">
                          <div className="pipeline-header">
                            <span className="pipeline-badge">
                              {analysisResult.ai_enhancements.ai_metadata?.pipeline_type === 'collaborative_ai_hybrid' ? 
                                '🔬 Collaborative AI Pipeline' : '🤖 AI Enhancement'}
                            </span>
                            <span className="stages-completed">
                              {analysisResult.ai_enhancements.ai_metadata?.stages_completed || 2}/4 stages completed
                            </span>
                          </div>
                          <div className="pipeline-stages">
                            <div className="stage-item completed">
                              <span className="stage-icon">👁️</span>
                              <span className="stage-name">Vision Analysis</span>
                            </div>
                            <div className="stage-item completed">
                              <span className="stage-icon">🧠</span>
                              <span className="stage-name">Rule-Based Reasoning</span>
                            </div>
                            <div className={`stage-item ${analysisResult.ai_enhancements.design_description ? 'completed' : 'pending'}`}>
                              <span className="stage-icon">✍️</span>
                              <span className="stage-name">Gemini Description</span>
                            </div>
                            <div className={`stage-item ${analysisResult.ai_enhancements.conceptual_visualization?.success ? 'completed' : 'pending'}`}>
                              <span className="stage-icon">🎨</span>
                              <span className="stage-name">Diffusion Visualization</span>
                            </div>
                          </div>
                        </div>

                        {/* Gemini-Generated Design Description */}
                        {analysisResult.ai_enhancements.design_description && (
                          <div className="ai-section gemini-section">
                            <h5>✍️ AI-Generated Design Description</h5>
                            <div className="gemini-description">
                              <div className="description-content">
                                <p>{analysisResult.ai_enhancements.design_description}</p>
                              </div>
                              <div className="ai-attribution">
                                <span className="attribution-badge">Generated by Google Gemini AI</span>
                                <span className="gemini-status">
                                  {analysisResult.ai_enhancements.ai_metadata?.gemini_api_available ? 
                                    '✅ Gemini Available' : '❌ Gemini Unavailable'}
                                </span>
                              </div>
                            </div>
                          </div>
                        )}

                        {/* Enhanced Conceptual Visualization */}
                        {analysisResult.ai_enhancements.conceptual_visualization && (
                          <div className="ai-section visualization-section">
                            <h5>🎨 Collaborative Conceptual Visualization</h5>
                            <div className="conceptual-visualization">
                              {analysisResult.ai_enhancements.conceptual_visualization.success ? (
                                <div className="visualization-content">
                                  <div className="visualization-disclaimer">
                                    <div className="disclaimer-badge">Conceptual Visualization / Inspirational Preview</div>
                                    <p>This image combines AI vision analysis, rule-based reasoning, Gemini description generation, 
                                    and diffusion-based visualization to create an inspirational concept.</p>
                                  </div>
                                  
                                  {analysisResult.ai_enhancements.conceptual_visualization.image_url && (
                                    <div className="generated-image-container">
                                      {(() => {
                                        // Always construct Apache URL explicitly - never use relative paths
                                        const imageUrl = analysisResult.ai_enhancements.conceptual_visualization.image_url;
                                        const apacheUrl = `http://localhost${imageUrl}`;
                                        console.log('🖼️ [Collaborative AI] Loading image from Apache:', apacheUrl);
                                        console.log('🖼️ [Collaborative AI] Original image_url from backend:', imageUrl);
                                        
                                        return (
                                          <img 
                                            src={apacheUrl}
                                            alt="Real AI collaborative conceptual visualization using Stable Diffusion"
                                            className="generated-image"
                                            onLoad={() => console.log('✅ [Collaborative AI] Real AI image loaded from Apache:', apacheUrl)}
                                            onError={(e) => {
                                              console.error('❌ [Collaborative AI] Failed to load real AI image from Apache:', e.target.src);
                                              console.error('❌ [Collaborative AI] Expected file location: C:/xampp/htdocs' + imageUrl);
                                              e.target.style.display = 'none';
                                              e.target.nextSibling.style.display = 'block';
                                            }}
                                          />
                                        );
                                      })()}
                                      <div className="image-error" style={{display: 'none'}}>
                                        <div className="error-content">
                                          <span className="error-icon">🎨</span>
                                          <p>Real AI visualization generated but not accessible via Apache</p>
                                          <small>Image file exists but Apache cannot serve it</small>
                                          <div style={{marginTop: '5px', fontSize: '11px', fontFamily: 'monospace'}}>
                                            File: C:/xampp/htdocs{analysisResult.ai_enhancements.conceptual_visualization.image_url}<br/>
                                            Apache URL: http://localhost{analysisResult.ai_enhancements.conceptual_visualization.image_url}
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                  )}
                                  
                                  <div className="pipeline-details">
                                    <h6>Collaborative Pipeline Details:</h6>
                                    <div className="pipeline-info">
                                      <div className="info-item">
                                        <span className="info-label">Vision Analysis:</span>
                                        <span className="info-value">
                                          {analysisResult.ai_enhancements.detected_objects?.total_objects || 0} objects detected
                                        </span>
                                      </div>
                                      <div className="info-item">
                                        <span className="info-label">Spatial Reasoning:</span>
                                        <span className="info-value">
                                          {analysisResult.ai_enhancements.spatial_guidance?.placement_recommendations?.length || 0} recommendations
                                        </span>
                                      </div>
                                      <div className="info-item">
                                        <span className="info-label">Description Generation:</span>
                                        <span className="info-value">
                                          {analysisResult.ai_enhancements.design_description ? 'Gemini AI' : 'Rule-based fallback'}
                                        </span>
                                      </div>
                                      <div className="info-item">
                                        <span className="info-label">Image Generation:</span>
                                        <span className="info-value">
                                          {analysisResult.ai_enhancements.ai_metadata?.diffusion_device || 'Stable Diffusion'}
                                        </span>
                                      </div>
                                    </div>
                                  </div>
                                  
                                  <div className="visualization-footer">
                                    <div className="generation-metadata">
                                      {analysisResult.ai_enhancements.conceptual_visualization.generation_metadata && (
                                        <small>
                                          Generated: {new Date(analysisResult.ai_enhancements.conceptual_visualization.generation_metadata.generation_time).toLocaleString()}
                                        </small>
                                      )}
                                    </div>
                                    <div className="professional-note">
                                      <p><em>💡 This collaborative AI visualization combines multiple AI technologies for inspirational design concepts.</em></p>
                                    </div>
                                  </div>
                                </div>
                              ) : (
                                <div className="visualization-unavailable">
                                  <div className="unavailable-content">
                                    <span className="unavailable-icon">⚠️</span>
                                    <h6>Conceptual Visualization Unavailable</h6>
                                    <p>{analysisResult.ai_enhancements.conceptual_visualization.fallback_message || 
                                       'Conceptual image generation temporarily unavailable'}</p>
                                    <small>Vision analysis and improvement suggestions remain available</small>
                                  </div>
                                </div>
                              )}
                            </div>
                          </div>
                        )}

                        {/* Enhanced Object Detection */}
                        {analysisResult.ai_enhancements.detected_objects && (
                          <div className="ai-section detection-section">
                            <h5>🔍 AI Vision Analysis</h5>
                            <div className="detection-results">
                              <div className="detection-summary">
                                <span className="summary-stat">
                                  <strong>{analysisResult.ai_enhancements.detected_objects.total_objects || 0}</strong> objects detected
                                </span>
                                {analysisResult.ai_enhancements.detected_objects.detection_confidence && (
                                  <span className="summary-stat">
                                    <strong>{Math.round(analysisResult.ai_enhancements.detected_objects.detection_confidence * 100)}%</strong> avg confidence
                                  </span>
                                )}
                              </div>
                              
                              {analysisResult.ai_enhancements.detected_objects.major_items?.length > 0 && (
                                <div className="detected-objects">
                                  <h6>Major Items Detected:</h6>
                                  <div className="objects-grid">
                                    {analysisResult.ai_enhancements.detected_objects.major_items.slice(0, 8).map((item, index) => (
                                      <div key={index} className="object-tag">
                                        {item}
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
                              
                              {analysisResult.ai_enhancements.detected_objects.furniture_categories?.length > 0 && (
                                <div className="furniture-categories">
                                  <h6>Furniture Categories:</h6>
                                  <div className="category-tags">
                                    {analysisResult.ai_enhancements.detected_objects.furniture_categories.map((category, index) => (
                                      <span key={index} className="category-tag">{category}</span>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </div>
                          </div>
                        )}

                        {/* Enhanced Spatial Guidance */}
                        {analysisResult.ai_enhancements.spatial_guidance && (
                          <div className="ai-section spatial-section">
                            <h5>📐 AI Spatial Intelligence</h5>
                            <div className="spatial-results">
                              
                              {analysisResult.ai_enhancements.spatial_guidance.placement_recommendations?.length > 0 && (
                                <div className="placement-guidance">
                                  <h6>Placement Recommendations:</h6>
                                  <div className="recommendations-list">
                                    {analysisResult.ai_enhancements.spatial_guidance.placement_recommendations.slice(0, 4).map((rec, index) => (
                                      <div key={index} className="recommendation-item">
                                        <div className="rec-content">
                                          {typeof rec === 'object' ? (
                                            <>
                                              <span className="rec-object">{rec.object || 'Item'}</span>
                                              <span className="rec-suggestion">{rec.suggestion || rec.description}</span>
                                              {rec.reasoning && <small className="rec-reasoning">{rec.reasoning}</small>}
                                            </>
                                          ) : (
                                            <span className="rec-suggestion">{rec}</span>
                                          )}
                                        </div>
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
                              
                              {analysisResult.ai_enhancements.spatial_guidance.layout_improvements?.length > 0 && (
                                <div className="layout-improvements">
                                  <h6>Layout Improvements:</h6>
                                  <ul className="improvements-list">
                                    {analysisResult.ai_enhancements.spatial_guidance.layout_improvements.slice(0, 3).map((improvement, index) => (
                                      <li key={index}>{improvement}</li>
                                    ))}
                                  </ul>
                                </div>
                              )}
                              
                              {analysisResult.ai_enhancements.spatial_guidance.safety_considerations?.length > 0 && (
                                <div className="safety-considerations">
                                  <h6>⚠️ Safety Considerations:</h6>
                                  <ul className="safety-list">
                                    {analysisResult.ai_enhancements.spatial_guidance.safety_considerations.slice(0, 2).map((safety, index) => (
                                      <li key={index}>{safety}</li>
                                    ))}
                                  </ul>
                                </div>
                              )}
                            </div>
                          </div>
                        )}
                        
                        {/* AI System Status */}
                        <div className="ai-system-status">
                          <div className="status-grid">
                            <div className="status-item">
                              <span className="status-label">Pipeline Type:</span>
                              <span className="status-value">
                                {analysisResult.ai_enhancements.ai_metadata?.pipeline_type || 'Standard AI'}
                              </span>
                            </div>
                            <div className="status-item">
                              <span className="status-label">Gemini API:</span>
                              <span className={`status-value ${analysisResult.ai_enhancements.ai_metadata?.gemini_api_available ? 'available' : 'unavailable'}`}>
                                {analysisResult.ai_enhancements.ai_metadata?.gemini_api_available ? '✅ Available' : '❌ Unavailable'}
                              </span>
                            </div>
                            <div className="status-item">
                              <span className="status-label">Processing:</span>
                              <span className="status-value">
                                {analysisResult.ai_enhancements.ai_metadata?.diffusion_device || 'CPU/GPU'}
                              </span>
                            </div>
                            <div className="status-item">
                              <span className="status-label">Analysis Time:</span>
                              <span className="status-value">
                                {analysisResult.ai_enhancements.ai_metadata?.analysis_timestamp ? 
                                  new Date(analysisResult.ai_enhancements.ai_metadata.analysis_timestamp).toLocaleTimeString() : 
                                  'N/A'}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                <div className="concept-footer">
                    <div className="system-info">
                      <div className="analysis-type">
                        <span className="system-badge">
                          {analysisResult.analysis_metadata?.system_type === 'hybrid_ai_visual_rules' ? 
                            '🔬 Hybrid AI: Advanced Visual Analysis + Rules' : 
                            analysisResult.analysis_metadata?.system_type === 'hybrid_ai_basic_rules' ?
                            '📊 Hybrid AI: Basic Visual Analysis + Rules' :
                            '📋 Rule-Based Analysis'}
                        </span>
                      </div>
                      {analysisResult.visual_intelligence && analysisResult.visual_intelligence.feature_influence && (
                        <div className="feature-influence">
                          <h5>How Visual Analysis Influenced Recommendations:</h5>
                          <div className="influence-items">
                            {Object.entries(analysisResult.visual_intelligence.feature_influence).map(([key, influence]) => (
                              <div key={key} className="influence-item">
                                <span className="influence-type">{key.replace('_influence', '').replace('_', ' ')}</span>
                                <span className={`influence-impact impact-${influence.impact}`}>{influence.impact} impact</span>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                    
                    <div className="disclaimer">
                      <p><strong>Important:</strong> This analysis combines visual image analysis with rule-based AI reasoning to provide 
                      intelligent suggestions for decision support. Results are advisory and inspirational. 
                      Consult with interior design professionals for detailed implementation.</p>
                    </div>
                    
                    <div className="concept-actions">
                      <button 
                        className="new-analysis-btn"
                        onClick={resetForm}
                      >
                        Analyze Another Room
                      </button>
                      <button 
                        className="close-results-btn"
                        onClick={handleClose}
                      >
                        Close
                      </button>
                    </div>
                  </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default RoomImprovementAssistant;