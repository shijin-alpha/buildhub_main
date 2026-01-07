import React, { useState } from 'react';
import '../styles/TechnicalDetailsModal.css';

const TechnicalDetailsModal = ({ isOpen, onClose, onSubmit, planData, loading, planId }) => {
  const [technicalDetails, setTechnicalDetails] = useState({
    // Construction Details
    foundation_type: 'RCC',
    structure_type: 'RCC Frame',
    wall_material: 'Brick',
    roofing_type: 'RCC Slab',
    flooring_type: 'Ceramic Tiles',
    
    // Specifications
    wall_thickness: '9',
    ceiling_height: '10',
    door_height: '7',
    window_height: '4',
    
    // Electrical & Plumbing
    electrical_load: '5',
    water_connection: 'Municipal',
    sewage_connection: 'Municipal',
    
    // Estimates
    construction_cost: '',
    construction_duration: '8-12',
    unlock_price: '8000', // Default unlock price
    
    // Additional Details
    special_features: '',
    construction_notes: '',
    compliance_certificates: 'Building Permit, NOC',
    
    // Materials & Finishes
    exterior_finish: 'Paint',
    interior_finish: 'Paint',
    kitchen_type: 'Modular',
    bathroom_fittings: 'Standard',
    
    // Safety & Standards
    earthquake_resistance: 'Zone III Compliant',
    fire_safety: 'Standard',
    ventilation: 'Natural + Exhaust Fans',
    
    // Enhanced Fields
    // Site Details
    site_area: '',
    built_up_area: '',
    carpet_area: '',
    setback_front: '',
    setback_rear: '',
    setback_left: '',
    setback_right: '',
    
    // Structural Details
    beam_size: '9x12',
    column_size: '9x12',
    slab_thickness: '5',
    footing_depth: '4 feet',
    
    // MEP (Mechanical, Electrical, Plumbing)
    electrical_points: '',
    plumbing_fixtures: '',
    hvac_system: 'Split AC',
    solar_provision: 'No',
    
    // Finishes & Materials
    main_door_material: 'Teak Wood',
    window_material: 'UPVC',
    staircase_material: 'RCC with Granite',
    compound_wall: 'Yes',
    
    // Approvals & Compliance
    building_plan_approval: 'Required',
    environmental_clearance: 'Not Required',
    fire_noc: 'Required',
    
    // Design Files
    layout_image: null,
    elevation_images: [],
    section_drawings: [],
    renders_3d: []
  });

  // File upload states
  const [uploadingFiles, setUploadingFiles] = useState(false);
  const [filePreview, setFilePreview] = useState({
    layout_image: null,
    elevation_images: [],
    section_drawings: [],
    renders_3d: []
  });

  const handleInputChange = (field, value) => {
    setTechnicalDetails(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // File upload handlers
  const handleFileUpload = async (field, files) => {
    if (!files || files.length === 0) return;
    
    // Store files locally without uploading immediately
    // Files will be uploaded when the form is submitted and we have a planId
    const localFiles = [];
    
    for (let file of files) {
      // Create preview URL for immediate display
      const previewUrl = URL.createObjectURL(file);
      
      localFiles.push({
        file: file, // Keep the file object for later upload
        name: file.name,
        size: file.size,
        type: file.type,
        preview: previewUrl,
        uploaded: false, // Mark as not uploaded yet
        pending_upload: true // Mark as pending upload
      });
    }
    
    if (field === 'layout_image') {
      // Single file for layout image
      const file = localFiles[0];
      setTechnicalDetails(prev => ({
        ...prev,
        [field]: file
      }));
      setFilePreview(prev => ({
        ...prev,
        [field]: file.preview
      }));
    } else {
      // Multiple files for other fields
      setTechnicalDetails(prev => ({
        ...prev,
        [field]: [...(prev[field] || []), ...localFiles]
      }));
      setFilePreview(prev => ({
        ...prev,
        [field]: [...(prev[field] || []), ...localFiles.map(f => f.preview)]
      }));
    }
  };

  const removeFile = (field, index = null) => {
    if (field === 'layout_image') {
      // Single file removal
      if (technicalDetails[field]?.preview) {
        URL.revokeObjectURL(technicalDetails[field].preview);
      }
      setTechnicalDetails(prev => ({
        ...prev,
        [field]: null
      }));
      setFilePreview(prev => ({
        ...prev,
        [field]: null
      }));
    } else {
      // Multiple files removal
      const files = technicalDetails[field] || [];
      if (index !== null && files[index]?.preview) {
        URL.revokeObjectURL(files[index].preview);
      }
      
      setTechnicalDetails(prev => ({
        ...prev,
        [field]: files.filter((_, i) => i !== index)
      }));
      setFilePreview(prev => ({
        ...prev,
        [field]: (prev[field] || []).filter((_, i) => i !== index)
      }));
    }
  };

  // Function to upload files after plan is created
  const uploadFilesWithPlanId = async (actualPlanId) => {
    const filesToUpload = [];
    
    // Collect all files that need to be uploaded
    if (technicalDetails.layout_image && technicalDetails.layout_image.pending_upload) {
      filesToUpload.push({
        file: technicalDetails.layout_image.file,
        field: 'layout_image',
        originalData: technicalDetails.layout_image
      });
    }
    
    ['elevation_images', 'section_drawings', 'renders_3d'].forEach(field => {
      if (technicalDetails[field] && Array.isArray(technicalDetails[field])) {
        technicalDetails[field].forEach((fileData, index) => {
          if (fileData.pending_upload) {
            filesToUpload.push({
              file: fileData.file,
              field: field,
              originalData: fileData,
              index: index
            });
          }
        });
      }
    });
    
    if (filesToUpload.length === 0) {
      return technicalDetails; // No files to upload, return original technical details
    }
    
    setUploadingFiles(true);
    
    // Create a copy of technical details to update
    let updatedTechnicalDetails = { ...technicalDetails };
    
    try {
      for (let fileToUpload of filesToUpload) {
        const formData = new FormData();
        formData.append('file', fileToUpload.file);
        formData.append('plan_id', actualPlanId);
        formData.append('file_type', fileToUpload.field);
        
        try {
          const response = await fetch('/buildhub/backend/api/architect/upload_house_plan_files.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
          });
          
          const result = await response.json();
          
          if (result.success && result.files && result.files.length > 0) {
            // File uploaded successfully - update the technical details
            const uploadedFile = result.files[0];
            
            if (fileToUpload.field === 'layout_image') {
              updatedTechnicalDetails.layout_image = {
                ...updatedTechnicalDetails.layout_image,
                stored: uploadedFile.stored,
                uploaded: true,
                pending_upload: false,
                upload_time: uploadedFile.upload_time,
                file: null // Remove file object after upload
              };
              
              // Also update the component state
              setTechnicalDetails(prev => ({
                ...prev,
                layout_image: updatedTechnicalDetails.layout_image
              }));
            } else {
              // Handle array fields
              if (!updatedTechnicalDetails[fileToUpload.field]) {
                updatedTechnicalDetails[fileToUpload.field] = [];
              }
              
              if (updatedTechnicalDetails[fileToUpload.field][fileToUpload.index]) {
                updatedTechnicalDetails[fileToUpload.field][fileToUpload.index] = {
                  ...updatedTechnicalDetails[fileToUpload.field][fileToUpload.index],
                  stored: uploadedFile.stored,
                  uploaded: true,
                  pending_upload: false,
                  upload_time: uploadedFile.upload_time,
                  file: null // Remove file object after upload
                };
              }
              
              // Also update the component state
              setTechnicalDetails(prev => {
                const newArray = [...(prev[fileToUpload.field] || [])];
                if (newArray[fileToUpload.index]) {
                  newArray[fileToUpload.index] = updatedTechnicalDetails[fileToUpload.field][fileToUpload.index];
                }
                return {
                  ...prev,
                  [fileToUpload.field]: newArray
                };
              });
            }
          } else {
            console.error('File upload failed:', result.message);
            // Mark as failed but keep the file for retry
            if (fileToUpload.field === 'layout_image') {
              updatedTechnicalDetails.layout_image = {
                ...updatedTechnicalDetails.layout_image,
                uploaded: false,
                pending_upload: false,
                error: result.message || 'Upload failed'
              };
              
              setTechnicalDetails(prev => ({
                ...prev,
                layout_image: updatedTechnicalDetails.layout_image
              }));
            }
          }
        } catch (uploadError) {
          console.error('Upload error:', uploadError);
        }
      }
    } catch (error) {
      console.error('Error uploading files:', error);
    } finally {
      setUploadingFiles(false);
    }
    
    // Return the updated technical details
    return updatedTechnicalDetails;
  };

  const handleSubmit = async () => {
    // Validate required fields
    if (!technicalDetails.construction_cost) {
      alert('Please enter estimated construction cost');
      return;
    }
    
    // Call the parent's onSubmit with technical details and upload function
    onSubmit(technicalDetails, uploadFilesWithPlanId);
  };

  const calculateEstimatedCost = () => {
    if (planData && planData.rooms) {
      const totalArea = planData.rooms.reduce((sum, room) => {
        const actualWidth = room.actual_width || room.layout_width * (planData.scale_ratio || 1.2);
        const actualHeight = room.actual_height || room.layout_height * (planData.scale_ratio || 1.2);
        return sum + (actualWidth * actualHeight);
      }, 0);
      
      // Estimate ₹1500-2500 per sq ft for construction
      const lowEstimate = Math.round(totalArea * 1500);
      const highEstimate = Math.round(totalArea * 2500);
      
      return `₹${lowEstimate.toLocaleString()} - ₹${highEstimate.toLocaleString()}`;
    }
    return 'Calculate based on area';
  };

  if (!isOpen) return null;

  return (
    <div className="technical-details-modal-overlay">
      <div className="technical-details-modal">
        <div className="modal-header">
          <h2>📋 Technical Details & Specifications</h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>
        
        <div className="modal-content">
          <div className="details-sections">
            
            {/* Site Details */}
            <div className="detail-section">
              <h3>📐 Site Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Site Area (sq ft):</label>
                  <input
                    type="number"
                    placeholder="Enter site area"
                    value={technicalDetails.site_area}
                    onChange={(e) => handleInputChange('site_area', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Built-up Area (sq ft):</label>
                  <input
                    type="number"
                    placeholder="Enter built-up area"
                    value={technicalDetails.built_up_area}
                    onChange={(e) => handleInputChange('built_up_area', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Carpet Area (sq ft):</label>
                  <input
                    type="number"
                    placeholder="Enter carpet area"
                    value={technicalDetails.carpet_area}
                    onChange={(e) => handleInputChange('carpet_area', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Front Setback (feet):</label>
                  <input
                    type="number"
                    placeholder="Enter front setback"
                    value={technicalDetails.setback_front}
                    onChange={(e) => handleInputChange('setback_front', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Rear Setback (feet):</label>
                  <input
                    type="number"
                    placeholder="Enter rear setback"
                    value={technicalDetails.setback_rear}
                    onChange={(e) => handleInputChange('setback_rear', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Left Setback (feet):</label>
                  <input
                    type="number"
                    placeholder="Enter left setback"
                    value={technicalDetails.setback_left}
                    onChange={(e) => handleInputChange('setback_left', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Right Setback (feet):</label>
                  <input
                    type="number"
                    placeholder="Enter right setback"
                    value={technicalDetails.setback_right}
                    onChange={(e) => handleInputChange('setback_right', e.target.value)}
                  />
                </div>
              </div>
            </div>

            {/* Construction Details */}
            <div className="detail-section">
              <h3>🏗️ Construction Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Foundation Type:</label>
                  <select 
                    value={technicalDetails.foundation_type}
                    onChange={(e) => handleInputChange('foundation_type', e.target.value)}
                  >
                    <option value="RCC">RCC Foundation</option>
                    <option value="Stone">Stone Foundation</option>
                    <option value="Pile">Pile Foundation</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Structure Type:</label>
                  <select 
                    value={technicalDetails.structure_type}
                    onChange={(e) => handleInputChange('structure_type', e.target.value)}
                  >
                    <option value="RCC Frame">RCC Frame Structure</option>
                    <option value="Load Bearing">Load Bearing Wall</option>
                    <option value="Steel Frame">Steel Frame</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Wall Material:</label>
                  <select 
                    value={technicalDetails.wall_material}
                    onChange={(e) => handleInputChange('wall_material', e.target.value)}
                  >
                    <option value="Brick">Red Brick</option>
                    <option value="AAC Block">AAC Block</option>
                    <option value="Concrete Block">Concrete Block</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Roofing Type:</label>
                  <select 
                    value={technicalDetails.roofing_type}
                    onChange={(e) => handleInputChange('roofing_type', e.target.value)}
                  >
                    <option value="RCC Slab">RCC Slab</option>
                    <option value="Tile Roof">Tile Roof</option>
                    <option value="Sheet Roof">Metal Sheet</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Specifications */}
            <div className="detail-section">
              <h3>📏 Specifications</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Wall Thickness (inches):</label>
                  <select 
                    value={technicalDetails.wall_thickness}
                    onChange={(e) => handleInputChange('wall_thickness', e.target.value)}
                  >
                    <option value="4.5">4.5 inches</option>
                    <option value="9">9 inches</option>
                    <option value="13.5">13.5 inches</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Ceiling Height (feet):</label>
                  <select 
                    value={technicalDetails.ceiling_height}
                    onChange={(e) => handleInputChange('ceiling_height', e.target.value)}
                  >
                    <option value="9">9 feet</option>
                    <option value="10">10 feet</option>
                    <option value="11">11 feet</option>
                    <option value="12">12 feet</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Door Height (feet):</label>
                  <select 
                    value={technicalDetails.door_height}
                    onChange={(e) => handleInputChange('door_height', e.target.value)}
                  >
                    <option value="6.5">6.5 feet</option>
                    <option value="7">7 feet</option>
                    <option value="7.5">7.5 feet</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Window Height (feet):</label>
                  <select 
                    value={technicalDetails.window_height}
                    onChange={(e) => handleInputChange('window_height', e.target.value)}
                  >
                    <option value="3">3 feet</option>
                    <option value="4">4 feet</option>
                    <option value="5">5 feet</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Enhanced Structural Details */}
            <div className="detail-section">
              <h3>🏗️ Structural Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Beam Size:</label>
                  <select 
                    value={technicalDetails.beam_size}
                    onChange={(e) => handleInputChange('beam_size', e.target.value)}
                  >
                    <option value="9x12">9" x 12"</option>
                    <option value="9x15">9" x 15"</option>
                    <option value="12x15">12" x 15"</option>
                    <option value="12x18">12" x 18"</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Column Size:</label>
                  <select 
                    value={technicalDetails.column_size}
                    onChange={(e) => handleInputChange('column_size', e.target.value)}
                  >
                    <option value="9x12">9" x 12"</option>
                    <option value="12x12">12" x 12"</option>
                    <option value="12x15">12" x 15"</option>
                    <option value="15x15">15" x 15"</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Slab Thickness:</label>
                  <select 
                    value={technicalDetails.slab_thickness}
                    onChange={(e) => handleInputChange('slab_thickness', e.target.value)}
                  >
                    <option value="4">4 inches</option>
                    <option value="5">5 inches</option>
                    <option value="6">6 inches</option>
                    <option value="7">7 inches</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Footing Depth:</label>
                  <select 
                    value={technicalDetails.footing_depth}
                    onChange={(e) => handleInputChange('footing_depth', e.target.value)}
                  >
                    <option value="3 feet">3 feet</option>
                    <option value="4 feet">4 feet</option>
                    <option value="5 feet">5 feet</option>
                    <option value="6 feet">6 feet</option>
                  </select>
                </div>
              </div>
            </div>

            {/* MEP (Mechanical, Electrical, Plumbing) */}
            <div className="detail-section">
              <h3>⚡ MEP (Mechanical, Electrical, Plumbing)</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Electrical Points:</label>
                  <input
                    type="number"
                    placeholder="Total electrical points"
                    value={technicalDetails.electrical_points}
                    onChange={(e) => handleInputChange('electrical_points', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>Plumbing Fixtures:</label>
                  <input
                    type="number"
                    placeholder="Total plumbing fixtures"
                    value={technicalDetails.plumbing_fixtures}
                    onChange={(e) => handleInputChange('plumbing_fixtures', e.target.value)}
                  />
                </div>
                
                <div className="form-group">
                  <label>HVAC System:</label>
                  <select 
                    value={technicalDetails.hvac_system}
                    onChange={(e) => handleInputChange('hvac_system', e.target.value)}
                  >
                    <option value="Split AC">Split AC</option>
                    <option value="Central AC">Central AC</option>
                    <option value="Ceiling Fans">Ceiling Fans Only</option>
                    <option value="Ducted AC">Ducted AC</option>
                    <option value="VRF System">VRF System</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Solar Provision:</label>
                  <select 
                    value={technicalDetails.solar_provision}
                    onChange={(e) => handleInputChange('solar_provision', e.target.value)}
                  >
                    <option value="No">No</option>
                    <option value="Yes - Rooftop">Yes - Rooftop Solar</option>
                    <option value="Yes - Ground Mount">Yes - Ground Mount</option>
                    <option value="Provision Only">Provision Only</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Utilities */}
            <div className="detail-section">
              <h3>⚡ Electrical & Plumbing</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Electrical Load (KW):</label>
                  <select 
                    value={technicalDetails.electrical_load}
                    onChange={(e) => handleInputChange('electrical_load', e.target.value)}
                  >
                    <option value="3">3 KW</option>
                    <option value="5">5 KW</option>
                    <option value="7">7 KW</option>
                    <option value="10">10 KW</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Water Connection:</label>
                  <select 
                    value={technicalDetails.water_connection}
                    onChange={(e) => handleInputChange('water_connection', e.target.value)}
                  >
                    <option value="Municipal">Municipal Supply</option>
                    <option value="Borewell">Borewell</option>
                    <option value="Both">Both</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Sewage Connection:</label>
                  <select 
                    value={technicalDetails.sewage_connection}
                    onChange={(e) => handleInputChange('sewage_connection', e.target.value)}
                  >
                    <option value="Municipal">Municipal Drainage</option>
                    <option value="Septic Tank">Septic Tank</option>
                    <option value="STP">Sewage Treatment Plant</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Cost & Timeline */}
            <div className="detail-section">
              <h3>💰 Cost & Timeline</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Estimated Construction Cost:</label>
                  <div className="cost-input-group">
                    <input
                      type="text"
                      placeholder="Enter amount (e.g., 25,00,000)"
                      value={technicalDetails.construction_cost}
                      onChange={(e) => handleInputChange('construction_cost', e.target.value)}
                    />
                    <small>Suggested: {calculateEstimatedCost()}</small>
                  </div>
                </div>
                
                <div className="form-group">
                  <label>Construction Duration (months):</label>
                  <select 
                    value={technicalDetails.construction_duration}
                    onChange={(e) => handleInputChange('construction_duration', e.target.value)}
                  >
                    <option value="6-8">6-8 months</option>
                    <option value="8-12">8-12 months</option>
                    <option value="12-18">12-18 months</option>
                    <option value="18-24">18-24 months</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Technical Details Unlock Price (₹):</label>
                  <div className="price-input-group">
                    <input
                      type="number"
                      placeholder="8000"
                      value={technicalDetails.unlock_price || '8000'}
                      onChange={(e) => handleInputChange('unlock_price', e.target.value)}
                      min="0"
                      step="100"
                    />
                    <small>Amount homeowner pays to unlock technical details (Default: ₹8000)</small>
                  </div>
                </div>
              </div>
            </div>

            {/* Enhanced Materials & Finishes */}
            <div className="detail-section">
              <h3>🎨 Enhanced Materials & Finishes</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Main Door Material:</label>
                  <select 
                    value={technicalDetails.main_door_material}
                    onChange={(e) => handleInputChange('main_door_material', e.target.value)}
                  >
                    <option value="Teak Wood">Teak Wood</option>
                    <option value="Engineered Wood">Engineered Wood</option>
                    <option value="Steel">Steel Door</option>
                    <option value="Fiber">Fiber Door</option>
                    <option value="Glass">Glass Door</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Window Material:</label>
                  <select 
                    value={technicalDetails.window_material}
                    onChange={(e) => handleInputChange('window_material', e.target.value)}
                  >
                    <option value="UPVC">UPVC Windows</option>
                    <option value="Aluminum">Aluminum Windows</option>
                    <option value="Wood">Wooden Windows</option>
                    <option value="Steel">Steel Windows</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Staircase Material:</label>
                  <select 
                    value={technicalDetails.staircase_material}
                    onChange={(e) => handleInputChange('staircase_material', e.target.value)}
                  >
                    <option value="RCC with Granite">RCC with Granite</option>
                    <option value="RCC with Marble">RCC with Marble</option>
                    <option value="Steel Structure">Steel Structure</option>
                    <option value="Wooden Staircase">Wooden Staircase</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Compound Wall:</label>
                  <select 
                    value={technicalDetails.compound_wall}
                    onChange={(e) => handleInputChange('compound_wall', e.target.value)}
                  >
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                    <option value="Partial">Partial</option>
                  </select>
                </div>

                <div className="form-group">
                  <label>Exterior Finish:</label>
                  <select 
                    value={technicalDetails.exterior_finish}
                    onChange={(e) => handleInputChange('exterior_finish', e.target.value)}
                  >
                    <option value="Paint">Paint</option>
                    <option value="Texture">Texture Paint</option>
                    <option value="Stone Cladding">Stone Cladding</option>
                    <option value="Tiles">Exterior Tiles</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Kitchen Type:</label>
                  <select 
                    value={technicalDetails.kitchen_type}
                    onChange={(e) => handleInputChange('kitchen_type', e.target.value)}
                  >
                    <option value="Modular">Modular Kitchen</option>
                    <option value="Semi-Modular">Semi-Modular</option>
                    <option value="Traditional">Traditional</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Bathroom Fittings:</label>
                  <select 
                    value={technicalDetails.bathroom_fittings}
                    onChange={(e) => handleInputChange('bathroom_fittings', e.target.value)}
                  >
                    <option value="Standard">Standard</option>
                    <option value="Premium">Premium</option>
                    <option value="Luxury">Luxury</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Approvals & Compliance */}
            <div className="detail-section">
              <h3>📋 Approvals & Compliance</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>Building Plan Approval:</label>
                  <select 
                    value={technicalDetails.building_plan_approval}
                    onChange={(e) => handleInputChange('building_plan_approval', e.target.value)}
                  >
                    <option value="Required">Required</option>
                    <option value="Obtained">Already Obtained</option>
                    <option value="Not Required">Not Required</option>
                    <option value="In Process">In Process</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Environmental Clearance:</label>
                  <select 
                    value={technicalDetails.environmental_clearance}
                    onChange={(e) => handleInputChange('environmental_clearance', e.target.value)}
                  >
                    <option value="Not Required">Not Required</option>
                    <option value="Required">Required</option>
                    <option value="Obtained">Already Obtained</option>
                    <option value="In Process">In Process</option>
                  </select>
                </div>
                
                <div className="form-group">
                  <label>Fire NOC:</label>
                  <select 
                    value={technicalDetails.fire_noc}
                    onChange={(e) => handleInputChange('fire_noc', e.target.value)}
                  >
                    <option value="Required">Required</option>
                    <option value="Not Required">Not Required</option>
                    <option value="Obtained">Already Obtained</option>
                    <option value="In Process">In Process</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Design Files Upload Section */}
            <div className="detail-section">
              <h3>📁 Design Files</h3>
              
              {/* Layout Image Upload */}
              <div className="form-group">
                <label>Layout Image for Homeowner:</label>
                <div className="file-upload-area">
                  <input
                    type="file"
                    id="layout-image-upload"
                    accept="image/*"
                    onChange={(e) => handleFileUpload('layout_image', e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="layout-image-upload" className="file-upload-button">
                    📷 Choose Layout Image
                  </label>
                  <small className="file-help-text">
                    Upload the main layout image that will be shown to the homeowner
                  </small>
                  
                  {/* Layout Image Preview */}
                  {technicalDetails.layout_image && (
                    <div className="file-preview-item">
                      <div className="file-preview-content">
                        <img 
                          src={filePreview.layout_image} 
                          alt="Layout Preview" 
                          className="file-preview-image"
                        />
                        <div className="file-info">
                          <span className="file-name">{technicalDetails.layout_image.name}</span>
                          <span className="file-size">
                            {(technicalDetails.layout_image.size / 1024 / 1024).toFixed(2)} MB
                          </span>
                          <span className={`upload-status ${technicalDetails.layout_image.uploaded ? 'uploaded' : technicalDetails.layout_image.pending_upload ? 'pending' : 'not-uploaded'}`}>
                            {technicalDetails.layout_image.uploaded ? '✅ Uploaded' : 
                             technicalDetails.layout_image.pending_upload ? '⏳ Ready to upload' : 
                             '❌ Not uploaded'}
                          </span>
                          {technicalDetails.layout_image.error && (
                            <span className="upload-error">❌ {technicalDetails.layout_image.error}</span>
                          )}
                        </div>
                        <button 
                          type="button"
                          className="file-remove-btn"
                          onClick={() => removeFile('layout_image')}
                        >
                          ×
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Elevation Images Upload */}
              <div className="form-group">
                <label>Elevation Images (Optional):</label>
                <div className="file-upload-area">
                  <input
                    type="file"
                    id="elevation-images-upload"
                    accept="image/*"
                    multiple
                    onChange={(e) => handleFileUpload('elevation_images', e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="elevation-images-upload" className="file-upload-button">
                    🏠 Choose Elevation Images
                  </label>
                  <small className="file-help-text">
                    Upload front, side, and rear elevation views (multiple files allowed)
                  </small>
                  
                  {/* Elevation Images Preview */}
                  {technicalDetails.elevation_images && technicalDetails.elevation_images.length > 0 && (
                    <div className="file-preview-grid">
                      {technicalDetails.elevation_images.map((file, index) => (
                        <div key={index} className="file-preview-item">
                          <div className="file-preview-content">
                            <img 
                              src={file.preview} 
                              alt={`Elevation ${index + 1}`} 
                              className="file-preview-image"
                            />
                            <div className="file-info">
                              <span className="file-name">{file.name}</span>
                              <span className="file-size">
                                {(file.size / 1024 / 1024).toFixed(2)} MB
                              </span>
                            </div>
                            <button 
                              type="button"
                              className="file-remove-btn"
                              onClick={() => removeFile('elevation_images', index)}
                            >
                              ×
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* Section Drawings Upload */}
              <div className="form-group">
                <label>Section Drawings (Optional):</label>
                <div className="file-upload-area">
                  <input
                    type="file"
                    id="section-drawings-upload"
                    accept="image/*,.pdf"
                    multiple
                    onChange={(e) => handleFileUpload('section_drawings', e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="section-drawings-upload" className="file-upload-button">
                    📐 Choose Section Drawings
                  </label>
                  <small className="file-help-text">
                    Upload cross-section drawings and detailed views (Images or PDF)
                  </small>
                  
                  {/* Section Drawings Preview */}
                  {technicalDetails.section_drawings && technicalDetails.section_drawings.length > 0 && (
                    <div className="file-preview-list">
                      {technicalDetails.section_drawings.map((file, index) => (
                        <div key={index} className="file-preview-item">
                          <div className="file-preview-content">
                            <div className="file-icon">
                              {file.type.includes('image') ? '🖼️' : '📄'}
                            </div>
                            <div className="file-info">
                              <span className="file-name">{file.name}</span>
                              <span className="file-size">
                                {(file.size / 1024 / 1024).toFixed(2)} MB
                              </span>
                            </div>
                            <button 
                              type="button"
                              className="file-remove-btn"
                              onClick={() => removeFile('section_drawings', index)}
                            >
                              ×
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* 3D Renders Upload */}
              <div className="form-group">
                <label>3D Renders (Optional):</label>
                <div className="file-upload-area">
                  <input
                    type="file"
                    id="3d-renders-upload"
                    accept="image/*"
                    multiple
                    onChange={(e) => handleFileUpload('renders_3d', e.target.files)}
                    style={{ display: 'none' }}
                  />
                  <label htmlFor="3d-renders-upload" className="file-upload-button">
                    🎨 Choose 3D Renders
                  </label>
                  <small className="file-help-text">
                    Upload 3D rendered images of the design
                  </small>
                  
                  {/* 3D Renders Preview */}
                  {technicalDetails.renders_3d && technicalDetails.renders_3d.length > 0 && (
                    <div className="file-preview-grid">
                      {technicalDetails.renders_3d.map((file, index) => (
                        <div key={index} className="file-preview-item">
                          <div className="file-preview-content">
                            <img 
                              src={file.preview} 
                              alt={`3D Render ${index + 1}`} 
                              className="file-preview-image"
                            />
                            <div className="file-info">
                              <span className="file-name">{file.name}</span>
                              <span className="file-size">
                                {(file.size / 1024 / 1024).toFixed(2)} MB
                              </span>
                            </div>
                            <button 
                              type="button"
                              className="file-remove-btn"
                              onClick={() => removeFile('renders_3d', index)}
                            >
                              ×
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>

              {/* Upload Status */}
              {uploadingFiles && (
                <div className="upload-status">
                  <div className="upload-progress">
                    <div className="upload-spinner"></div>
                    <span>Uploading files...</span>
                  </div>
                </div>
              )}
            </div>

            {/* Additional Details */}
            <div className="detail-section">
              <h3>📝 Additional Details</h3>
              <div className="form-group">
                <label>Special Features:</label>
                <textarea
                  placeholder="List any special features, smart home integration, solar panels, etc."
                  value={technicalDetails.special_features}
                  onChange={(e) => handleInputChange('special_features', e.target.value)}
                  rows="3"
                />
              </div>
              
              <div className="form-group">
                <label>Construction Notes:</label>
                <textarea
                  placeholder="Any specific construction notes or requirements"
                  value={technicalDetails.construction_notes}
                  onChange={(e) => handleInputChange('construction_notes', e.target.value)}
                  rows="3"
                />
              </div>
            </div>
          </div>
        </div>
        
        <div className="modal-footer">
          <button className="cancel-btn" onClick={onClose} disabled={loading}>
            Cancel
          </button>
          <button className="submit-btn" onClick={handleSubmit} disabled={loading}>
            {loading ? 'Submitting...' : 'Submit to Homeowner'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default TechnicalDetailsModal;