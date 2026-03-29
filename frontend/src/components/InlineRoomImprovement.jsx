import React, { useState, useRef, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/InlineRoomImprovement.css';

const InlineRoomImprovement = () => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [formData, setFormData] = useState({
    room_type: '',
    improvement_notes: '',
    selected_file: null
  });
  
  const [analyzing, setAnalyzing] = useState(false);
  const [analysisResult, setAnalysisResult] = useState(null);
  const [currentAnalysisId, setCurrentAnalysisId] = useState(null);
  const [previewImage, setPreviewImage] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [generatingImage, setGeneratingImage] = useState(false);
  const [imageJobId, setImageJobId] = useState(null);
  const [imageGenerationStatus, setImageGenerationStatus] = useState(null);
  
  // New state for history management
  const [showHistory, setShowHistory] = useState(false);
  const [analysisHistory, setAnalysisHistory] = useState([]);
  const [loadingHistory, setLoadingHistory] = useState(false);

  // Load persisted analysis on component mount
  useEffect(() => {
    loadPersistedAnalysis();
    loadAnalysisHistory();
  }, []);

  // Save analysis to localStorage whenever it changes
  useEffect(() => {
    if (analysisResult && currentAnalysisId) {
      const persistData = {
        analysisResult,
        currentAnalysisId,
        formData: {
          room_type: formData.room_type,
          improvement_notes: formData.improvement_notes
        },
        timestamp: Date.now()
      };
      localStorage.setItem('bh_room_improvement_current', JSON.stringify(persistData));
    }
  }, [analysisResult, currentAnalysisId, formData.room_type, formData.improvement_notes]);

  const loadPersistedAnalysis = () => {
    try {
      const saved = localStorage.getItem('bh_room_improvement_current');
      if (saved) {
        const data = JSON.parse(saved);
        // Only load if it's less than 24 hours old
        if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
          setAnalysisResult(data.analysisResult);
          setCurrentAnalysisId(data.currentAnalysisId);
          setFormData(prev => ({
            ...prev,
            room_type: data.formData.room_type,
            improvement_notes: data.formData.improvement_notes
          }));
        } else {
          // Remove expired data
          localStorage.removeItem('bh_room_improvement_current');
        }
      }
    } catch (error) {
      console.error('Error loading persisted analysis:', error);
      localStorage.removeItem('bh_room_improvement_current');
    }
  };

  const loadAnalysisHistory = async () => {
    try {
      setLoadingHistory(true);
      const response = await fetch('/buildhub/backend/api/homeowner/get_room_improvement_history.php?limit=10', {
        method: 'GET',
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setAnalysisHistory(data.data.analyses);
        }
      }
    } catch (error) {
      console.error('Error loading analysis history:', error);
    } finally {
      setLoadingHistory(false);
    }
  };

  const loadPreviousAnalysis = async (analysisId) => {
    try {
      toast.info('Loading previous analysis...');
      const response = await fetch(`/buildhub/backend/api/homeowner/get_room_improvement_analysis.php?id=${analysisId}`, {
        method: 'GET',
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setAnalysisResult(data.data.analysis_result);
          setCurrentAnalysisId(data.data.id);
          setFormData(prev => ({
            ...prev,
            room_type: data.data.room_type,
            improvement_notes: data.data.improvement_notes || ''
          }));
          setShowForm(false);
          setShowHistory(false);
          toast.success('Previous analysis loaded successfully!');
        } else {
          toast.error('Failed to load analysis: ' + data.message);
        }
      } else {
        toast.error('Failed to load analysis');
      }
    } catch (error) {
      console.error('Error loading previous analysis:', error);
      toast.error('Error loading previous analysis');
    }
  };

  const getRoomTypeIcon = (roomType) => {
    const icons = {
      'bedroom': '🛏️',
      'living_room': '🛋️',
      'kitchen': '🍳',
      'dining_room': '🍽️',
      'other': '🏠'
    };
    return icons[roomType] || '🏠';
  };

  const formatRoomType = (roomType) => {
    const labels = {
      'bedroom': 'Bedroom',
      'living_room': 'Living Room',
      'kitchen': 'Kitchen',
      'dining_room': 'Dining Room',
      'other': 'Other'
    };
    return labels[roomType] || 'Room';
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const roomTypes = [
    { value: 'bedroom', label: 'Bedroom', icon: '🛏️' },
    { value: 'living_room', label: 'Living Room', icon: '🛋️' },
    { value: 'kitchen', label: 'Kitchen', icon: '🍳' },
    { value: 'dining_room', label: 'Dining Room', icon: '🍽️' },
    { value: 'other', label: 'Other', icon: '🏠' }
  ];

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
        setCurrentAnalysisId(data.analysis_id);
        toast.success('✨ Room improvement concept generated successfully!');
        
        // Refresh history to include the new analysis
        loadAnalysisHistory();
        
        // Check if async image generation was started
        if (data.analysis.ai_enhancements?.async_image_generation?.job_id) {
          const jobId = data.analysis.ai_enhancements.async_image_generation.job_id;
          setImageJobId(jobId);
          setGeneratingImage(true);
          setImageGenerationStatus('pending');
          toast.info('🎨 Starting real AI image generation...');
          
          // Start polling for image status
          pollImageStatus(jobId);
        }
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

  const downloadImage = async (imageUrl, filename) => {
    try {
      // Show loading state
      toast.info('📥 Preparing image download...');
      
      // Fetch the image
      const response = await fetch(imageUrl);
      if (!response.ok) {
        throw new Error('Failed to fetch image');
      }
      
      // Convert to blob
      const blob = await response.blob();
      
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = filename || 'ai-generated-room-concept.png';
      
      // Trigger download
      document.body.appendChild(link);
      link.click();
      
      // Cleanup
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      toast.success('✅ Image downloaded successfully!');
      
    } catch (error) {
      console.error('Download error:', error);
      toast.error('❌ Failed to download image: ' + error.message);
    }
  };

  const pollImageStatus = async (jobId) => {
    const maxAttempts = 60; // Poll for up to 5 minutes (60 * 5 seconds)
    let attempts = 0;
    
    const poll = async () => {
      try {
        attempts++;
        
        const response = await fetch(`/buildhub/backend/api/homeowner/check_image_status.php?job_id=${jobId}`, {
          method: 'GET',
          credentials: 'include'
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const statusData = await response.json();
        
        if (statusData.success) {
          setImageGenerationStatus(statusData.status);
          
          if (statusData.status === 'completed') {
            // Image generation completed successfully
            setGeneratingImage(false);
            toast.success('🎨 Real AI image generated successfully!');
            
            // Update the analysis result with the generated image
            setAnalysisResult(prev => ({
              ...prev,
              ai_enhancements: {
                ...prev.ai_enhancements,
                conceptual_visualization: {
                  success: true,
                  image_url: statusData.image_url,
                  image_path: statusData.image_path,
                  disclaimer: statusData.disclaimer,
                  generation_metadata: statusData.generation_metadata
                }
              }
            }));
            
            return; // Stop polling
            
          } else if (statusData.status === 'failed') {
            // Image generation failed
            setGeneratingImage(false);
            setImageGenerationStatus('failed');
            toast.error('❌ Real AI image generation failed: ' + (statusData.error_message || 'Unknown error'));
            return; // Stop polling
            
          } else if (statusData.status === 'processing') {
            // Still processing
            const remaining = statusData.estimated_remaining_seconds || 30;
            toast.info(`🎨 Generating real AI image... (~${remaining}s remaining)`);
            
          } else {
            // Still pending
            toast.info('🎨 Real AI image generation queued...');
          }
          
          // Continue polling if not completed or failed
          if (attempts < maxAttempts) {
            setTimeout(poll, 5000); // Poll every 5 seconds
          } else {
            // Max attempts reached
            setGeneratingImage(false);
            setImageGenerationStatus('timeout');
            toast.error('⏰ Image generation timed out. The image may still be processing in the background.');
          }
          
        } else {
          throw new Error(statusData.message || 'Failed to check image status');
        }
        
      } catch (error) {
        console.error('Image status polling error:', error);
        
        if (attempts < maxAttempts) {
          // Retry after a delay
          setTimeout(poll, 5000);
        } else {
          setGeneratingImage(false);
          setImageGenerationStatus('error');
          toast.error('❌ Failed to check image generation status');
        }
      }
    };
    
    // Start polling
    poll();
  };

  const generateConceptualImage = async () => {
    if (!analysisResult || generatingImage) return;

    try {
      setGeneratingImage(true);
      toast.info('🎨 Generating conceptual visualization...');

      const response = await fetch('/buildhub/backend/api/homeowner/generate_conceptual_image.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          analysis_data: analysisResult,
          room_type: formData.room_type,
          improvement_notes: formData.improvement_notes
        })
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('Image generation response:', data);

      if (data.success) {
        // Update the analysis result with the new conceptual visualization
        setAnalysisResult(prev => ({
          ...prev,
          ai_enhancements: {
            ...prev.ai_enhancements,
            conceptual_visualization: data.conceptual_visualization
          }
        }));
        toast.success('✨ Conceptual visualization generated successfully!');
      } else {
        toast.error(data.message || 'Failed to generate conceptual image. Please try again.');
      }
    } catch (error) {
      console.error('Image generation error:', error);
      toast.error('Failed to generate conceptual image: ' + error.message);
    } finally {
      setGeneratingImage(false);
    }
  };

  const resetForm = () => {
    setFormData({
      room_type: '',
      improvement_notes: '',
      selected_file: null
    });
    setAnalysisResult(null);
    setCurrentAnalysisId(null);
    setPreviewImage(null);
    setShowForm(false);
    setShowHistory(false);
    setGeneratingImage(false);
    setImageJobId(null);
    setImageGenerationStatus(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
    // Clear persisted data
    localStorage.removeItem('bh_room_improvement_current');
  };

  return (
    <div className="inline-room-improvement">
      {!showForm && !analysisResult && !showHistory ? (
        // Initial intro section
        <div className="room-improvement-intro">
          <div className="intro-card">
            <div className="intro-content">
              <h3>🎨 Transform Your Completed Spaces</h3>
              <p>Your construction is complete, but the journey doesn't end there! Use our AI-powered Room Improvement Assistant to discover new possibilities for enhancing your living spaces.</p>
              
              <div className="feature-highlights">
                <div className="feature-item">
                  <span className="feature-icon">📸</span>
                  <div>
                    <strong>Upload Room Photos</strong>
                    <p>Share clear images of your completed rooms</p>
                  </div>
                </div>
                <div className="feature-item">
                  <span className="feature-icon">🔍</span>
                  <div>
                    <strong>AI Analysis</strong>
                    <p>Get intelligent insights about lighting, colors, and layout</p>
                  </div>
                </div>
                <div className="feature-item">
                  <span className="feature-icon">💡</span>
                  <div>
                    <strong>Improvement Concepts</strong>
                    <p>Receive personalized renovation and enhancement ideas</p>
                  </div>
                </div>
              </div>
              
              <div className="intro-actions">
                <button 
                  className="btn btn-primary btn-large"
                  onClick={() => setShowForm(true)}
                >
                  <span className="btn-icon">🚀</span>
                  Start Room Analysis
                </button>
                
                {analysisHistory.length > 0 && (
                  <button 
                    className="btn btn-secondary btn-large"
                    onClick={() => setShowHistory(true)}
                  >
                    <span className="btn-icon">📋</span>
                    View Previous Analyses ({analysisHistory.length})
                  </button>
                )}
              </div>
            </div>
          </div>
          
          <div className="disclaimer-card">
            <div className="disclaimer-header">
              <span className="disclaimer-icon">ℹ️</span>
              <strong>Important Information</strong>
            </div>
            <div className="disclaimer-content">
              <ul>
                <li>This feature provides <strong>AI-assisted suggestions</strong> for decision support and inspiration</li>
                <li>Results are <strong>advisory and conceptual</strong> - not final designs or construction plans</li>
                <li>For detailed implementation, consult with interior design professionals</li>
                <li>The system analyzes visual aspects and generates improvement concepts, not exact redesigns</li>
              </ul>
            </div>
          </div>
        </div>
      ) : showHistory ? (
        // History view
        <div className="analysis-history-section">
          <div className="history-header">
            <h3>📋 Previous Room Analyses</h3>
            <p>View and reload your previous room improvement analyses</p>
          </div>
          
          <div className="history-actions">
            <button 
              className="btn btn-secondary"
              onClick={() => setShowHistory(false)}
            >
              ← Back to Home
            </button>
            <button 
              className="btn btn-primary"
              onClick={() => {
                setShowHistory(false);
                setShowForm(true);
              }}
            >
              + New Analysis
            </button>
          </div>
          
          {loadingHistory ? (
            <div className="loading-history">
              <div className="spinner"></div>
              <p>Loading your analysis history...</p>
            </div>
          ) : analysisHistory.length === 0 ? (
            <div className="no-history">
              <div className="no-history-icon">📝</div>
              <h4>No Previous Analyses</h4>
              <p>You haven't created any room improvement analyses yet.</p>
              <button 
                className="btn btn-primary"
                onClick={() => {
                  setShowHistory(false);
                  setShowForm(true);
                }}
              >
                Create Your First Analysis
              </button>
            </div>
          ) : (
            <div className="history-grid">
              {analysisHistory.map((analysis) => (
                <div key={analysis.id} className="history-card">
                  <div className="history-card-header">
                    <div className="room-type-badge">
                      {getRoomTypeIcon(analysis.room_type)} {formatRoomType(analysis.room_type)}
                    </div>
                    <div className="analysis-date">
                      {formatDate(analysis.created_at)}
                    </div>
                  </div>
                  
                  {analysis.image_url && (
                    <div className="history-image">
                      <img 
                        src={analysis.image_url} 
                        alt="Room analysis" 
                        onError={(e) => {
                          e.target.style.display = 'none';
                        }}
                      />
                    </div>
                  )}
                  
                  <div className="history-content">
                    <h5>{analysis.analysis_result?.concept_name || 'Room Analysis'}</h5>
                    {analysis.improvement_notes && (
                      <p className="improvement-notes">"{analysis.improvement_notes}"</p>
                    )}
                    
                    <div className="analysis-preview">
                      {analysis.analysis_result?.style_recommendation?.style && (
                        <div className="style-preview">
                          <strong>Style:</strong> {analysis.analysis_result.style_recommendation.style}
                        </div>
                      )}
                    </div>
                  </div>
                  
                  <div className="history-actions">
                    <button 
                      className="btn btn-primary btn-small"
                      onClick={() => loadPreviousAnalysis(analysis.id)}
                    >
                      View Analysis
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      ) : showForm && !analysisResult ? (
        // Upload Form
        <div className="upload-form-section">
          <div className="form-header">
            <h3>🏠 Room Improvement Analysis</h3>
            <p>Upload your room photo and get AI-powered improvement suggestions</p>
          </div>
          
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
                onClick={() => setShowForm(false)}
                disabled={analyzing}
              >
                Back
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
        </div>
      ) : (
        // Analysis Results
        <div className="analysis-results-section">
          <div className="results-header">
            <h3>✨ Room Improvement Analysis {currentAnalysisId ? '(Saved)' : 'Complete'}</h3>
            <p>Here are your personalized improvement suggestions</p>
            {currentAnalysisId && (
              <div className="saved-analysis-info">
                <span className="saved-badge">💾 Saved Analysis</span>
                <span className="analysis-id">ID: {currentAnalysisId}</span>
              </div>
            )}
          </div>
          
          <div className="results-actions">
            <button 
              className="btn btn-secondary"
              onClick={() => {
                setAnalysisResult(null);
                setCurrentAnalysisId(null);
                setShowForm(false);
                setShowHistory(false);
                localStorage.removeItem('bh_room_improvement_current');
              }}
            >
              ← Back to Home
            </button>
            <button 
              className="btn btn-outline"
              onClick={() => setShowHistory(true)}
            >
              📋 View All Analyses
            </button>
          </div>
          
          <div className="concept-card">
            <div className="concept-header">
              <h4 className="concept-name">{analysisResult.concept_name}</h4>
              <div className="concept-type">
                <span className="type-badge">AI-Assisted Concept</span>
              </div>
            </div>

            <div className="concept-sections">
              <div className="concept-section">
                <h5>📋 Current Room Analysis</h5>
                <div className="analysis-content">
                  <p>{analysisResult.room_condition_summary}</p>
                  {analysisResult.visual_observations && (
                    <div className="visual-observations">
                      <h6>Visual Observations:</h6>
                      <ul>
                        {analysisResult.visual_observations.map((observation, index) => (
                          <li key={index}>{observation}</li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              </div>

              <div className="concept-section">
                <h5>💡 Improvement Suggestions</h5>
                <div className="suggestions-grid">
                  {analysisResult.improvement_suggestions.lighting && (
                    <div className="suggestion-item">
                      <h6>💡 Lighting Enhancement</h6>
                      <p>{analysisResult.improvement_suggestions.lighting}</p>
                    </div>
                  )}
                  {analysisResult.improvement_suggestions.color_ambience && (
                    <div className="suggestion-item">
                      <h6>🎨 Color & Ambience</h6>
                      <p>{analysisResult.improvement_suggestions.color_ambience}</p>
                    </div>
                  )}
                  {analysisResult.improvement_suggestions.furniture_layout && (
                    <div className="suggestion-item">
                      <h6>🪑 Furniture & Layout</h6>
                      <p>{analysisResult.improvement_suggestions.furniture_layout}</p>
                    </div>
                  )}
                </div>
              </div>

              <div className="concept-section">
                <h5>✨ Style Recommendation</h5>
                <div className="style-recommendation">
                  <div className="style-name">{analysisResult.style_recommendation.style}</div>
                  <p>{analysisResult.style_recommendation.description}</p>
                  {analysisResult.style_recommendation.key_elements && (
                    <div className="key-elements">
                      <h6>Key Elements:</h6>
                      <div className="elements-tags">
                        {analysisResult.style_recommendation.key_elements.map((element, index) => (
                          <span key={index} className="element-tag">{element}</span>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div className="concept-section">
                <h5>🖼️ Visual Reference</h5>
                <div className="visual-reference">
                  <div className="reference-note">
                    <strong>Note:</strong> This is a conceptual and inspirational reference only. 
                    Actual implementation may vary based on your specific room conditions and preferences.
                  </div>
                  
                  {/* Conceptual Image Generation - Async Support */}
                  {generatingImage ? (
                    <div className="async-generation-status">
                      <div className="generation-progress">
                        <div className="progress-icon">🎨</div>
                        <h6>Generating Real AI Image...</h6>
                        <div className="progress-details">
                          {imageGenerationStatus === 'pending' && (
                            <p>⏳ Image generation queued...</p>
                          )}
                          {imageGenerationStatus === 'processing' && (
                            <p>🎨 Creating real AI interior design image...</p>
                          )}
                          <div className="progress-bar">
                            <div className="progress-fill"></div>
                          </div>
                          <p className="progress-note">
                            <strong>Note:</strong> Real AI image generation takes 1-5 minutes depending on system performance.
                            The page will automatically update when complete.
                          </p>
                        </div>
                      </div>
                    </div>
                  ) : analysisResult.ai_enhancements?.conceptual_visualization ? (
                    <div className="conceptual-visualization">
                      {analysisResult.ai_enhancements.conceptual_visualization.success ? (
                        <div className="generated-image-container">
                          {analysisResult.ai_enhancements.conceptual_visualization.image_url ? (
                            <div className="conceptual-image-wrapper">
                              <img 
                                src={analysisResult.ai_enhancements.conceptual_visualization.image_url}
                                alt="AI-generated room improvement concept"
                                className="conceptual-image"
                                onError={(e) => {
                                  e.target.style.display = 'none';
                                  e.target.nextElementSibling.style.display = 'block';
                                }}
                              />
                              <div className="image-fallback" style={{ display: 'none' }}>
                                <div className="fallback-icon">🎨</div>
                                <p>Real AI image generated but not available for display</p>
                                <p>The AI successfully created a visualization based on your room analysis.</p>
                              </div>
                              
                              {/* Download Button */}
                              <div className="image-actions">
                                <button 
                                  className="download-btn"
                                  onClick={() => {
                                    const imageUrl = analysisResult.ai_enhancements.conceptual_visualization.image_url;
                                    const roomType = formData.room_type.replace('_', '-');
                                    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                                    const filename = `ai-room-concept-${roomType}-${timestamp}.png`;
                                    downloadImage(imageUrl, filename);
                                  }}
                                  title="Download AI-generated room concept image"
                                >
                                  <span className="download-icon">📥</span>
                                  Download Image
                                </button>
                              </div>
                            </div>
                          ) : (
                            <div className="image-placeholder">
                              <div className="placeholder-icon">🎨</div>
                              <p>Real AI conceptual visualization generated</p>
                              <p>Image processing completed successfully</p>
                            </div>
                          )}
                          
                          <div className="generation-details">
                            <h6>✅ Real AI Image Generated:</h6>
                            <div className="generation-info">
                              <p><strong>Model:</strong> Stable Diffusion v1.5</p>
                              <p><strong>Quality:</strong> 512×512 photorealistic</p>
                              <p><strong>Type:</strong> Real AI-generated interior design</p>
                            </div>
                          </div>
                        </div>
                      ) : (
                        <div className="generation-error">
                          <div className="error-icon">⚠️</div>
                          <h6>Conceptual Visualization Unavailable</h6>
                          <p>{analysisResult.ai_enhancements.conceptual_visualization.error || 
                             analysisResult.ai_enhancements.conceptual_visualization.note || 
                             'Conceptual image generation is temporarily unavailable.'}</p>
                          <p className="error-note">Your room analysis and improvement suggestions are still available above.</p>
                        </div>
                      )}
                    </div>
                  ) : analysisResult.ai_enhancements?.async_image_generation?.job_id ? (
                    <div className="async-generation-info">
                      <div className="info-icon">ℹ️</div>
                      <h6>Real AI Image Generation Available</h6>
                      <p>A real AI image was requested but generation is still in progress.</p>
                      <button 
                        className="check-status-btn"
                        onClick={() => {
                          setGeneratingImage(true);
                          pollImageStatus(analysisResult.ai_enhancements.async_image_generation.job_id);
                        }}
                      >
                        🔄 Check Generation Status
                      </button>
                    </div>
                  ) : (
                    <div className="default-visual-reference">
                      <p>{analysisResult.visual_reference || 'A space perfectly tailored to your needs through intelligent visual analysis.'}</p>
                      
                      {/* Manual Image Generation Option */}
                      <div className="manual-generation-section">
                        <div className="generation-prompt">
                          <p>Want to see a visual concept of your improved room?</p>
                          <button 
                            className="generate-image-btn"
                            onClick={() => generateConceptualImage()}
                            disabled={analyzing || generatingImage}
                          >
                            {generatingImage ? (
                              <>
                                <span className="spinner"></span>
                                Generating Image...
                              </>
                            ) : (
                              <>
                                <span className="btn-icon">🎨</span>
                                Generate Visual Concept
                              </>
                            )}
                          </button>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="concept-footer">
              <div className="disclaimer">
                <p><strong>Important:</strong> This analysis provides AI-assisted suggestions for decision support. 
                Results are advisory and inspirational. Consult with interior design professionals for detailed implementation.</p>
              </div>
              
              <div className="concept-actions">
                <button 
                  className="new-analysis-btn"
                  onClick={resetForm}
                >
                  Analyze Another Room
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InlineRoomImprovement;