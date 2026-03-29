import React, { useState, useEffect } from 'react';
import '../styles/TechnicalDetailsModal.css';

const TechnicalDetailsModal = ({ isOpen, onClose, onSubmit, planData, loading, planId, requestInfo }) => {
  const [technicalDetails, setTechnicalDetails] = useState({
    // Construction Details
    foundation_type: 'RCC',
    foundation_type_custom: '',
    structure_type: 'RCC Frame',
    structure_type_custom: '',
    wall_material: 'Brick',
    wall_material_custom: '',
    roofing_type: 'RCC Slab',
    roofing_type_custom: '',
    flooring_type: 'Ceramic Tiles',
    flooring_type_custom: '',
    
    // Specifications
    wall_thickness: '9',
    wall_thickness_custom: '',
    ceiling_height: '10',
    ceiling_height_custom: '',
    door_height: '7',
    door_height_custom: '',
    window_height: '4',
    window_height_custom: '',
    
    // Electrical & Plumbing
    electrical_load: '5',
    electrical_load_custom: '',
    water_connection: 'Municipal',
    water_connection_custom: '',
    sewage_connection: 'Municipal',
    sewage_connection_custom: '',
    
    // Estimates
    construction_cost: '',
    construction_duration: '8-12',
    construction_duration_custom: '',
    unlock_price: '8000', // Default unlock price
    
    // Additional Details
    special_features: '',
    construction_notes: '',
    compliance_certificates: 'Building Permit, NOC',
    
    // Materials & Finishes
    exterior_finish: 'Paint',
    exterior_finish_custom: '',
    interior_finish: 'Paint',
    interior_finish_custom: '',
    kitchen_type: 'Modular',
    kitchen_type_custom: '',
    bathroom_fittings: 'Standard',
    bathroom_fittings_custom: '',
    
    // Safety & Standards
    earthquake_resistance: 'Zone III Compliant',
    earthquake_resistance_custom: '',
    fire_safety: 'Standard',
    fire_safety_custom: '',
    ventilation: 'Natural + Exhaust Fans',
    ventilation_custom: '',
    
    // Enhanced Fields
    // Site Details - Auto-populated from request
    site_area: '',
    site_area_custom: '',
    land_area: '',
    land_area_custom: '',
    built_up_area: '',
    built_up_area_custom: '',
    carpet_area: '',
    carpet_area_custom: '',
    setback_front: '',
    setback_front_custom: '',
    setback_rear: '',
    setback_rear_custom: '',
    setback_left: '',
    setback_left_custom: '',
    setback_right: '',
    setback_right_custom: '',
    
    // Structural Details
    beam_size: '9x12',
    beam_size_custom: '',
    column_size: '9x12',
    column_size_custom: '',
    slab_thickness: '5',
    slab_thickness_custom: '',
    footing_depth: '4 feet',
    footing_depth_custom: '',
    
    // MEP (Mechanical, Electrical, Plumbing)
    electrical_points: '',
    plumbing_fixtures: '',
    hvac_system: 'Split AC',
    hvac_system_custom: '',
    solar_provision: 'No',
    solar_provision_custom: '',
    
    // Finishes & Materials
    main_door_material: 'Teak Wood',
    main_door_material_custom: '',
    window_material: 'UPVC',
    window_material_custom: '',
    staircase_material: 'RCC with Granite',
    staircase_material_custom: '',
    compound_wall: 'Yes',
    compound_wall_custom: '',
    
    // Approvals & Compliance
    building_plan_approval: 'Required',
    building_plan_approval_custom: '',
    environmental_clearance: 'Not Required',
    environmental_clearance_custom: '',
    fire_noc: 'Required',
    fire_noc_custom: '',
    
    // Design Files
    layout_image: null,
    elevation_images: [],
    section_drawings: [],
    renders_3d: []
  });

  // Tooltip/popup state
  const [activeTooltip, setActiveTooltip] = useState(null);
  const [tooltipPosition, setTooltipPosition] = useState({ x: 0, y: 0 });

  // File upload states
  const [uploadingFiles, setUploadingFiles] = useState(false);
  const [filePreview, setFilePreview] = useState({
    layout_image: null,
    elevation_images: [],
    section_drawings: [],
    renders_3d: []
  });

  // Auto-populate fields from request info
  useEffect(() => {
    if (requestInfo && isOpen) {
      console.log('Debug - requestInfo received:', requestInfo); // Debug log
      
      const updates = {};
      
      // Auto-populate site area and land area from layout_requests table
      if (requestInfo.plot_size) {
        // Parse plot_size - it could be in various formats like "2000", "30x40", "2000 sq ft", etc.
        let siteArea = '';
        let landArea = '';
        
        const plotSize = requestInfo.plot_size.toString().toLowerCase();
        
        // Handle different plot size formats
        if (plotSize.includes('x')) {
          // Format like "30x40" or "30x40 feet"
          const dimensions = plotSize.split('x');
          if (dimensions.length === 2) {
            const width = parseFloat(dimensions[0].trim());
            const height = parseFloat(dimensions[1].replace(/[^0-9.]/g, ''));
            if (!isNaN(width) && !isNaN(height)) {
              const area = width * height;
              siteArea = area.toString();
              landArea = area.toString();
            }
          }
        } else {
          // Format like "2000", "2000 sq ft", etc.
          const numericValue = parseFloat(plotSize.replace(/[^0-9.]/g, ''));
          if (!isNaN(numericValue) && numericValue > 0) {
            siteArea = numericValue.toString();
            landArea = numericValue.toString();
          }
        }
        
        if (siteArea) {
          updates.site_area = siteArea;
          updates.land_area = landArea;
        }
      }
      
      // Auto-populate building size if available
      if (requestInfo.building_size) {
        const buildingSize = parseFloat(requestInfo.building_size.toString().replace(/[^0-9.]/g, ''));
        if (!isNaN(buildingSize) && buildingSize > 0) {
          updates.built_up_area = buildingSize.toString();
          // Carpet area is typically 70% of built-up area
          updates.carpet_area = Math.round(buildingSize * 0.7).toString();
        }
      }
      
      // Auto-populate from plan data if available
      if (planData) {
        // If building_size is not available, calculate from plan data
        if (!updates.built_up_area && planData.rooms && planData.rooms.length > 0) {
          const builtUpArea = planData.rooms.reduce((total, room) => {
            const actualWidth = room.actual_width || room.layout_width * (planData.scale_ratio || 1.2);
            const actualHeight = room.actual_height || room.layout_height * (planData.scale_ratio || 1.2);
            return total + (actualWidth * actualHeight);
          }, 0);
          
          if (builtUpArea > 0) {
            updates.built_up_area = Math.round(builtUpArea).toString();
            
            // Carpet area is typically 70% of built-up area
            if (!updates.carpet_area) {
              updates.carpet_area = Math.round(builtUpArea * 0.7).toString();
            }
          }
        }
        
        // If site area is not available from plot_size, try from plan dimensions
        if (!updates.site_area && planData.plot_width && planData.plot_height) {
          const plotArea = planData.plot_width * planData.plot_height;
          updates.site_area = plotArea.toString();
          updates.land_area = plotArea.toString();
        }
        
        // Estimate electrical points based on rooms
        if (planData.rooms && planData.rooms.length > 0) {
          const totalRooms = planData.rooms.length;
          updates.electrical_points = (totalRooms * 8).toString(); // 8 points per room average
          
          // Count bathrooms and kitchens for plumbing fixtures
          const bathroomCount = planData.rooms.filter(room => 
            room.name.toLowerCase().includes('bathroom') || 
            room.name.toLowerCase().includes('toilet') ||
            room.type === 'bathroom'
          ).length;
          
          const kitchenCount = planData.rooms.filter(room => 
            room.name.toLowerCase().includes('kitchen') ||
            room.type === 'kitchen'
          ).length;
          
          // Estimate plumbing fixtures: bathrooms (3 fixtures each) + kitchens (2 fixtures each) + utility areas
          const plumbingFixtures = (bathroomCount * 3) + (kitchenCount * 2) + Math.ceil(totalRooms / 5);
          updates.plumbing_fixtures = plumbingFixtures.toString();
        }
      }
      
      // Auto-populate from parsed requirements if available
      if (requestInfo.requirements_parsed || requestInfo.parsed_requirements) {
        const req = requestInfo.requirements_parsed || requestInfo.parsed_requirements;
        
        // Set default setbacks based on plot size
        const plotSizeNum = parseFloat(updates.site_area || requestInfo.plot_size || 0);
        if (plotSizeNum > 0) {
          if (plotSizeNum < 1000) {
            // Small plots
            updates.setback_front = '3';
            updates.setback_rear = '3';
            updates.setback_left = '3';
            updates.setback_right = '3';
          } else if (plotSizeNum < 2000) {
            // Medium plots
            updates.setback_front = '5';
            updates.setback_rear = '3';
            updates.setback_left = '3';
            updates.setback_right = '3';
          } else if (plotSizeNum < 5000) {
            // Large plots
            updates.setback_front = '10';
            updates.setback_rear = '5';
            updates.setback_left = '5';
            updates.setback_right = '5';
          } else {
            // Very large plots
            updates.setback_front = '15';
            updates.setback_rear = '10';
            updates.setback_left = '10';
            updates.setback_right = '10';
          }
        }
        
        // Auto-populate number of floors if available
        if (req.num_floors || requestInfo.num_floors) {
          const numFloors = parseInt(req.num_floors || requestInfo.num_floors);
          if (!isNaN(numFloors) && numFloors > 0) {
            // Adjust electrical load based on number of floors
            if (updates.electrical_points) {
              const basePoints = parseInt(updates.electrical_points);
              updates.electrical_points = Math.round(basePoints * numFloors * 0.8).toString(); // 80% factor for multi-floor
            }
          }
        }
      }
      
      // Auto-populate construction cost based on homeowner's budget
      if (requestInfo.budget_range && !technicalDetails.construction_cost) {
        const budgetRange = requestInfo.budget_range.toString().trim();
        
        console.log('Debug - Auto-populate budget:', budgetRange); // Debug log
        
        // Parse budget and set construction cost within the range
        if (budgetRange.includes('-')) {
          // Format like "20-30 lakhs" or "₹25-30 lakhs"
          const parts = budgetRange.toLowerCase().split('-');
          if (parts.length === 2) {
            const lowBudget = parseFloat(parts[0].replace(/[^0-9.]/g, ''));
            const highBudget = parseFloat(parts[1].replace(/[^0-9.]/g, ''));
            
            if (!isNaN(lowBudget) && !isNaN(highBudget) && lowBudget > 0 && highBudget > 0) {
              // Convert lakhs to actual amount if needed
              const multiplier = budgetRange.toLowerCase().includes('lakh') ? 100000 : 1;
              const lowAmount = Math.round(lowBudget * multiplier);
              const highAmount = Math.round(highBudget * multiplier);
              
              // Set construction cost to the middle of the range
              const midAmount = Math.round((lowAmount + highAmount) / 2);
              updates.construction_cost = midAmount.toLocaleString();
              
              console.log('Debug - Auto-populated cost:', midAmount.toLocaleString()); // Debug log
            }
          }
        } else {
          // Single budget value like "25 lakhs" or "₹2500000"
          const budgetValue = parseFloat(budgetRange.replace(/[^0-9.]/g, ''));
          if (!isNaN(budgetValue) && budgetValue > 0) {
            const multiplier = budgetRange.toLowerCase().includes('lakh') ? 100000 : 1;
            const amount = Math.round(budgetValue * multiplier);
            updates.construction_cost = amount.toLocaleString();
            
            console.log('Debug - Auto-populated single cost:', amount.toLocaleString()); // Debug log
          }
        }
      }
      
      // Update state with auto-populated values
      if (Object.keys(updates).length > 0) {
        setTechnicalDetails(prev => ({
          ...prev,
          ...updates
        }));
        
        console.log('Auto-populated technical details:', updates); // Debug log
      }
    }
  }, [requestInfo, planData, isOpen]);

  const handleInputChange = (field, value) => {
    setTechnicalDetails(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // Enhanced dropdown component with custom option
  const EnhancedSelect = ({ field, options, value, onChange, customField, placeholder = "Enter custom value" }) => {
    const isCustom = value === 'Custom';
    const customValue = technicalDetails[customField] || '';
    
    return (
      <div className="enhanced-select-container">
        <select 
          value={value}
          onChange={(e) => onChange(field, e.target.value)}
          className="enhanced-select"
        >
          {options.map(option => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
          <option value="Custom">🔧 Custom</option>
        </select>
        
        {isCustom && (
          <div className="custom-input-container">
            <input
              type="text"
              placeholder={placeholder}
              value={customValue}
              onChange={(e) => onChange(customField, e.target.value)}
              className="custom-input"
            />
          </div>
        )}
      </div>
    );
  };

  // Tooltip component
  const showTooltip = (field, event, content) => {
    const rect = event.target.getBoundingClientRect();
    setTooltipPosition({
      x: rect.left + rect.width / 2,
      y: rect.top - 10
    });
    setActiveTooltip({ field, content });
  };

  const hideTooltip = () => {
    setActiveTooltip(null);
  };

  // Field info/help content
  const fieldHelp = {
    site_area: "Total area of the plot/land in square feet. Auto-populated from layout request plot_size field.",
    land_area: "Same as site area - total land available for construction. Auto-populated from layout request.",
    built_up_area: "Total covered area including all floors and structures. Auto-calculated from room areas or building_size field.",
    carpet_area: "Usable floor area excluding walls and common areas. Auto-calculated as 70% of built-up area.",
    setback_front: "Minimum distance from front boundary as per local regulations. Auto-set based on plot size.",
    setback_rear: "Minimum distance from rear boundary. Auto-set based on plot size.",
    setback_left: "Minimum distance from left side boundary. Auto-set based on plot size.",
    setback_right: "Minimum distance from right side boundary. Auto-set based on plot size.",
    foundation_type: "Type of foundation based on soil conditions and load requirements",
    structure_type: "Main structural system for the building (RCC Frame, Load Bearing, etc.)",
    wall_material: "Primary material for wall construction (Brick, AAC Block, etc.)",
    roofing_type: "Type of roof structure and covering (RCC Slab, Tile Roof, etc.)",
    wall_thickness: "Thickness of load-bearing and partition walls in inches",
    ceiling_height: "Height from floor to ceiling in feet",
    beam_size: "Cross-sectional dimensions of structural beams (width x depth in inches)",
    column_size: "Cross-sectional dimensions of structural columns (width x depth in inches)",
    slab_thickness: "Thickness of RCC floor slabs in inches",
    footing_depth: "Depth of foundation footing below ground level",
    electrical_load: "Total electrical load requirement in KW based on house size and appliances",
    electrical_points: "Total number of electrical outlets and switches. Auto-calculated as 8 points per room.",
    plumbing_fixtures: "Total number of taps, outlets, and fixtures. Auto-calculated from bathrooms and kitchens.",
    hvac_system: "Heating, ventilation, and air conditioning system type",
    construction_cost: "Estimated total cost for construction within homeowner's budget range",
    construction_duration: "Expected time to complete construction from start to finish",
    unlock_price: "Amount homeowner pays to access detailed technical drawings and specifications",
    main_door_material: "Material for the main entrance door (Teak Wood, Steel, etc.)",
    window_material: "Material for window frames (UPVC, Aluminum, Wood, etc.)",
    staircase_material: "Material and finish for staircase construction",
    compound_wall: "Whether compound wall is required around the property",
    exterior_finish: "External wall finishing material and treatment",
    kitchen_type: "Type of kitchen design and layout (Modular, Traditional, etc.)",
    bathroom_fittings: "Quality level of bathroom fixtures and fittings",
    building_plan_approval: "Status of building plan approval from local authorities",
    environmental_clearance: "Environmental clearance requirement status",
    fire_noc: "Fire safety No Objection Certificate requirement status"
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
    // First priority: Use homeowner's budget range if available
    if (requestInfo && requestInfo.budget_range) {
      const budgetRange = requestInfo.budget_range.toString().trim();
      
      console.log('Debug - Budget Range:', budgetRange); // Debug log
      
      // Parse different budget formats
      if (budgetRange.includes('-')) {
        // Format like "20-30 lakhs" or "₹25-30 lakhs"
        const parts = budgetRange.toLowerCase().split('-');
        if (parts.length === 2) {
          const lowBudget = parseFloat(parts[0].replace(/[^0-9.]/g, ''));
          const highBudget = parseFloat(parts[1].replace(/[^0-9.]/g, ''));
          
          console.log('Debug - Parsed values:', { lowBudget, highBudget }); // Debug log
          
          if (!isNaN(lowBudget) && !isNaN(highBudget) && lowBudget > 0 && highBudget > 0) {
            // Convert lakhs to actual amount if needed
            const multiplier = budgetRange.toLowerCase().includes('lakh') ? 100000 : 1;
            const lowAmount = Math.round(lowBudget * multiplier);
            const highAmount = Math.round(highBudget * multiplier);
            
            console.log('Debug - Final amounts:', { lowAmount, highAmount, multiplier }); // Debug log
            
            return `₹${lowAmount.toLocaleString()} - ₹${highAmount.toLocaleString()}`;
          }
        }
      } else {
        // Single budget value like "25 lakhs" or "₹2500000"
        const budgetValue = parseFloat(budgetRange.replace(/[^0-9.]/g, ''));
        if (!isNaN(budgetValue) && budgetValue > 0) {
          const multiplier = budgetRange.toLowerCase().includes('lakh') ? 100000 : 1;
          const amount = Math.round(budgetValue * multiplier);
          
          // Create a range around the budget (±10%)
          const lowAmount = Math.round(amount * 0.9);
          const highAmount = Math.round(amount * 1.1);
          
          return `₹${lowAmount.toLocaleString()} - ₹${highAmount.toLocaleString()}`;
        }
      }
      
      // If we can't parse the budget, return it as-is with a note
      return `${budgetRange} (Please verify format)`;
    }
    
    // Fallback: Calculate based on area if no budget available
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
    
    return 'Enter based on homeowner budget';
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
                  <label>
                    Site Area (sq ft):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('site_area', e, fieldHelp.site_area)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="site_area"
                    customField="site_area_custom"
                    value={technicalDetails.site_area}
                    onChange={handleInputChange}
                    placeholder="Enter custom site area"
                    options={[
                      { value: '600', label: '600 sq ft' },
                      { value: '800', label: '800 sq ft' },
                      { value: '1000', label: '1000 sq ft' },
                      { value: '1200', label: '1200 sq ft (30x40)' },
                      { value: '1500', label: '1500 sq ft' },
                      { value: '1800', label: '1800 sq ft' },
                      { value: '2000', label: '2000 sq ft' },
                      { value: '2400', label: '2400 sq ft (40x60)' },
                      { value: '2500', label: '2500 sq ft' },
                      { value: '3000', label: '3000 sq ft' },
                      { value: '4000', label: '4000 sq ft' },
                      { value: '5000', label: '5000 sq ft' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Land Area (sq ft):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('land_area', e, fieldHelp.land_area)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="land_area"
                    customField="land_area_custom"
                    value={technicalDetails.land_area}
                    onChange={handleInputChange}
                    placeholder="Enter custom land area"
                    options={[
                      { value: '600', label: '600 sq ft' },
                      { value: '800', label: '800 sq ft' },
                      { value: '1000', label: '1000 sq ft' },
                      { value: '1200', label: '1200 sq ft (30x40)' },
                      { value: '1500', label: '1500 sq ft' },
                      { value: '1800', label: '1800 sq ft' },
                      { value: '2000', label: '2000 sq ft' },
                      { value: '2400', label: '2400 sq ft (40x60)' },
                      { value: '2500', label: '2500 sq ft' },
                      { value: '3000', label: '3000 sq ft' },
                      { value: '4000', label: '4000 sq ft' },
                      { value: '5000', label: '5000 sq ft' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Built-up Area (sq ft):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('built_up_area', e, fieldHelp.built_up_area)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="built_up_area"
                    customField="built_up_area_custom"
                    value={technicalDetails.built_up_area}
                    onChange={handleInputChange}
                    placeholder="Enter custom built-up area"
                    options={[
                      { value: '500', label: '500 sq ft' },
                      { value: '750', label: '750 sq ft' },
                      { value: '1000', label: '1000 sq ft' },
                      { value: '1250', label: '1250 sq ft' },
                      { value: '1500', label: '1500 sq ft' },
                      { value: '1750', label: '1750 sq ft' },
                      { value: '2000', label: '2000 sq ft' },
                      { value: '2250', label: '2250 sq ft' },
                      { value: '2500', label: '2500 sq ft' },
                      { value: '3000', label: '3000 sq ft' },
                      { value: '3500', label: '3500 sq ft' },
                      { value: '4000', label: '4000 sq ft' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Carpet Area (sq ft):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('carpet_area', e, fieldHelp.carpet_area)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="carpet_area"
                    customField="carpet_area_custom"
                    value={technicalDetails.carpet_area}
                    onChange={handleInputChange}
                    placeholder="Enter custom carpet area"
                    options={[
                      { value: '400', label: '400 sq ft' },
                      { value: '600', label: '600 sq ft' },
                      { value: '800', label: '800 sq ft' },
                      { value: '1000', label: '1000 sq ft' },
                      { value: '1200', label: '1200 sq ft' },
                      { value: '1400', label: '1400 sq ft' },
                      { value: '1600', label: '1600 sq ft' },
                      { value: '1800', label: '1800 sq ft' },
                      { value: '2000', label: '2000 sq ft' },
                      { value: '2500', label: '2500 sq ft' },
                      { value: '3000', label: '3000 sq ft' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Front Setback (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('setback_front', e, fieldHelp.setback_front)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="setback_front"
                    customField="setback_front_custom"
                    value={technicalDetails.setback_front}
                    onChange={handleInputChange}
                    placeholder="Enter custom front setback"
                    options={[
                      { value: '0', label: '0 feet (No setback)' },
                      { value: '3', label: '3 feet' },
                      { value: '5', label: '5 feet' },
                      { value: '6', label: '6 feet' },
                      { value: '8', label: '8 feet' },
                      { value: '10', label: '10 feet' },
                      { value: '12', label: '12 feet' },
                      { value: '15', label: '15 feet' },
                      { value: '20', label: '20 feet' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Rear Setback (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('setback_rear', e, fieldHelp.setback_rear)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="setback_rear"
                    customField="setback_rear_custom"
                    value={technicalDetails.setback_rear}
                    onChange={handleInputChange}
                    placeholder="Enter custom rear setback"
                    options={[
                      { value: '0', label: '0 feet (No setback)' },
                      { value: '3', label: '3 feet' },
                      { value: '4', label: '4 feet' },
                      { value: '5', label: '5 feet' },
                      { value: '6', label: '6 feet' },
                      { value: '8', label: '8 feet' },
                      { value: '10', label: '10 feet' },
                      { value: '12', label: '12 feet' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Left Setback (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('setback_left', e, fieldHelp.setback_left)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="setback_left"
                    customField="setback_left_custom"
                    value={technicalDetails.setback_left}
                    onChange={handleInputChange}
                    placeholder="Enter custom left setback"
                    options={[
                      { value: '0', label: '0 feet (No setback)' },
                      { value: '3', label: '3 feet' },
                      { value: '4', label: '4 feet' },
                      { value: '5', label: '5 feet' },
                      { value: '6', label: '6 feet' },
                      { value: '8', label: '8 feet' },
                      { value: '10', label: '10 feet' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Right Setback (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('setback_right', e, fieldHelp.setback_right)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="setback_right"
                    customField="setback_right_custom"
                    value={technicalDetails.setback_right}
                    onChange={handleInputChange}
                    placeholder="Enter custom right setback"
                    options={[
                      { value: '0', label: '0 feet (No setback)' },
                      { value: '3', label: '3 feet' },
                      { value: '4', label: '4 feet' },
                      { value: '5', label: '5 feet' },
                      { value: '6', label: '6 feet' },
                      { value: '8', label: '8 feet' },
                      { value: '10', label: '10 feet' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Construction Details */}
            <div className="detail-section">
              <h3>🏗️ Construction Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Foundation Type:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('foundation_type', e, fieldHelp.foundation_type)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="foundation_type"
                    customField="foundation_type_custom"
                    value={technicalDetails.foundation_type}
                    onChange={handleInputChange}
                    placeholder="Enter custom foundation type"
                    options={[
                      { value: 'RCC', label: 'RCC Foundation' },
                      { value: 'Stone', label: 'Stone Foundation' },
                      { value: 'Pile', label: 'Pile Foundation' },
                      { value: 'Raft', label: 'Raft Foundation' },
                      { value: 'Strip', label: 'Strip Foundation' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Structure Type:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('structure_type', e, fieldHelp.structure_type)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="structure_type"
                    customField="structure_type_custom"
                    value={technicalDetails.structure_type}
                    onChange={handleInputChange}
                    placeholder="Enter custom structure type"
                    options={[
                      { value: 'RCC Frame', label: 'RCC Frame Structure' },
                      { value: 'Load Bearing', label: 'Load Bearing Wall' },
                      { value: 'Steel Frame', label: 'Steel Frame' },
                      { value: 'Precast', label: 'Precast Concrete' },
                      { value: 'Composite', label: 'Composite Structure' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Wall Material:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('wall_material', e, fieldHelp.wall_material)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="wall_material"
                    customField="wall_material_custom"
                    value={technicalDetails.wall_material}
                    onChange={handleInputChange}
                    placeholder="Enter custom wall material"
                    options={[
                      { value: 'Brick', label: 'Red Brick' },
                      { value: 'AAC Block', label: 'AAC Block' },
                      { value: 'Concrete Block', label: 'Concrete Block' },
                      { value: 'Fly Ash Brick', label: 'Fly Ash Brick' },
                      { value: 'CLC Block', label: 'CLC Block' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Roofing Type:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('roofing_type', e, fieldHelp.roofing_type)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="roofing_type"
                    customField="roofing_type_custom"
                    value={technicalDetails.roofing_type}
                    onChange={handleInputChange}
                    placeholder="Enter custom roofing type"
                    options={[
                      { value: 'RCC Slab', label: 'RCC Slab' },
                      { value: 'Tile Roof', label: 'Tile Roof' },
                      { value: 'Sheet Roof', label: 'Metal Sheet' },
                      { value: 'Terrace', label: 'Flat Terrace' },
                      { value: 'Sloped Roof', label: 'Sloped Roof' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Specifications */}
            <div className="detail-section">
              <h3>📏 Specifications</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Wall Thickness (inches):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('wall_thickness', e, fieldHelp.wall_thickness)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="wall_thickness"
                    customField="wall_thickness_custom"
                    value={technicalDetails.wall_thickness}
                    onChange={handleInputChange}
                    placeholder="Enter custom thickness"
                    options={[
                      { value: '4.5', label: '4.5 inches' },
                      { value: '9', label: '9 inches' },
                      { value: '13.5', label: '13.5 inches' },
                      { value: '6', label: '6 inches' },
                      { value: '8', label: '8 inches' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Ceiling Height (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('ceiling_height', e, fieldHelp.ceiling_height)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="ceiling_height"
                    customField="ceiling_height_custom"
                    value={technicalDetails.ceiling_height}
                    onChange={handleInputChange}
                    placeholder="Enter custom height"
                    options={[
                      { value: '9', label: '9 feet' },
                      { value: '10', label: '10 feet' },
                      { value: '11', label: '11 feet' },
                      { value: '12', label: '12 feet' },
                      { value: '8.5', label: '8.5 feet' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Door Height (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('door_height', e, fieldHelp.door_height)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="door_height"
                    customField="door_height_custom"
                    value={technicalDetails.door_height}
                    onChange={handleInputChange}
                    placeholder="Enter custom height"
                    options={[
                      { value: '6.5', label: '6.5 feet' },
                      { value: '7', label: '7 feet' },
                      { value: '7.5', label: '7.5 feet' },
                      { value: '8', label: '8 feet' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Window Height (feet):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('window_height', e, fieldHelp.window_height)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="window_height"
                    customField="window_height_custom"
                    value={technicalDetails.window_height}
                    onChange={handleInputChange}
                    placeholder="Enter custom height"
                    options={[
                      { value: '3', label: '3 feet' },
                      { value: '4', label: '4 feet' },
                      { value: '5', label: '5 feet' },
                      { value: '3.5', label: '3.5 feet' },
                      { value: '4.5', label: '4.5 feet' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Enhanced Structural Details */}
            <div className="detail-section">
              <h3>🏗️ Structural Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Beam Size:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('beam_size', e, fieldHelp.beam_size)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="beam_size"
                    customField="beam_size_custom"
                    value={technicalDetails.beam_size}
                    onChange={handleInputChange}
                    placeholder="Enter custom beam size"
                    options={[
                      { value: '9x12', label: '9" x 12"' },
                      { value: '9x15', label: '9" x 15"' },
                      { value: '12x15', label: '12" x 15"' },
                      { value: '12x18', label: '12" x 18"' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Column Size:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('column_size', e, fieldHelp.column_size)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="column_size"
                    customField="column_size_custom"
                    value={technicalDetails.column_size}
                    onChange={handleInputChange}
                    placeholder="Enter custom column size"
                    options={[
                      { value: '9x12', label: '9" x 12"' },
                      { value: '12x12', label: '12" x 12"' },
                      { value: '12x15', label: '12" x 15"' },
                      { value: '15x15', label: '15" x 15"' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Slab Thickness:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('slab_thickness', e, fieldHelp.slab_thickness)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="slab_thickness"
                    customField="slab_thickness_custom"
                    value={technicalDetails.slab_thickness}
                    onChange={handleInputChange}
                    placeholder="Enter custom thickness"
                    options={[
                      { value: '4', label: '4 inches' },
                      { value: '5', label: '5 inches' },
                      { value: '6', label: '6 inches' },
                      { value: '7', label: '7 inches' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Footing Depth:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('footing_depth', e, fieldHelp.footing_depth)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="footing_depth"
                    customField="footing_depth_custom"
                    value={technicalDetails.footing_depth}
                    onChange={handleInputChange}
                    placeholder="Enter custom depth"
                    options={[
                      { value: '3 feet', label: '3 feet' },
                      { value: '4 feet', label: '4 feet' },
                      { value: '5 feet', label: '5 feet' },
                      { value: '6 feet', label: '6 feet' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* MEP (Mechanical, Electrical, Plumbing) */}
            <div className="detail-section">
              <h3>⚡ MEP (Mechanical, Electrical, Plumbing)</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Electrical Points:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('electrical_points', e, fieldHelp.electrical_points)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <input
                    type="number"
                    placeholder="Auto-calculated from rooms"
                    value={technicalDetails.electrical_points}
                    onChange={(e) => handleInputChange('electrical_points', e.target.value)}
                    className="auto-populated"
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Plumbing Fixtures:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('plumbing_fixtures', e, fieldHelp.plumbing_fixtures)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <input
                    type="number"
                    placeholder="Auto-calculated from rooms"
                    value={technicalDetails.plumbing_fixtures}
                    onChange={(e) => handleInputChange('plumbing_fixtures', e.target.value)}
                    className="auto-populated"
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    HVAC System:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('hvac_system', e, fieldHelp.hvac_system)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="hvac_system"
                    customField="hvac_system_custom"
                    value={technicalDetails.hvac_system}
                    onChange={handleInputChange}
                    placeholder="Enter custom HVAC system"
                    options={[
                      { value: 'Split AC', label: 'Split AC' },
                      { value: 'Central AC', label: 'Central AC' },
                      { value: 'Ceiling Fans', label: 'Ceiling Fans Only' },
                      { value: 'Ducted AC', label: 'Ducted AC' },
                      { value: 'VRF System', label: 'VRF System' },
                      { value: 'Cassette AC', label: 'Cassette AC' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Solar Provision:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('solar_provision', e, "Solar panel installation provision")}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="solar_provision"
                    customField="solar_provision_custom"
                    value={technicalDetails.solar_provision}
                    onChange={handleInputChange}
                    placeholder="Enter custom solar provision"
                    options={[
                      { value: 'No', label: 'No' },
                      { value: 'Yes - Rooftop', label: 'Yes - Rooftop Solar' },
                      { value: 'Yes - Ground Mount', label: 'Yes - Ground Mount' },
                      { value: 'Provision Only', label: 'Provision Only' },
                      { value: 'Hybrid System', label: 'Hybrid System' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Utilities */}
            <div className="detail-section">
              <h3>⚡ Electrical & Plumbing</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Electrical Load (KW):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('electrical_load', e, fieldHelp.electrical_load)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="electrical_load"
                    customField="electrical_load_custom"
                    value={technicalDetails.electrical_load}
                    onChange={handleInputChange}
                    placeholder="Enter custom load in KW"
                    options={[
                      { value: '3', label: '3 KW' },
                      { value: '5', label: '5 KW' },
                      { value: '7', label: '7 KW' },
                      { value: '10', label: '10 KW' },
                      { value: '15', label: '15 KW' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Water Connection:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('water_connection', e, "Primary source of water supply")}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="water_connection"
                    customField="water_connection_custom"
                    value={technicalDetails.water_connection}
                    onChange={handleInputChange}
                    placeholder="Enter custom water source"
                    options={[
                      { value: 'Municipal', label: 'Municipal Supply' },
                      { value: 'Borewell', label: 'Borewell' },
                      { value: 'Both', label: 'Both' },
                      { value: 'Tanker', label: 'Water Tanker' },
                      { value: 'Well', label: 'Open Well' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Sewage Connection:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('sewage_connection', e, "Wastewater disposal system")}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="sewage_connection"
                    customField="sewage_connection_custom"
                    value={technicalDetails.sewage_connection}
                    onChange={handleInputChange}
                    placeholder="Enter custom sewage system"
                    options={[
                      { value: 'Municipal', label: 'Municipal Drainage' },
                      { value: 'Septic Tank', label: 'Septic Tank' },
                      { value: 'STP', label: 'Sewage Treatment Plant' },
                      { value: 'Biogas Plant', label: 'Biogas Plant' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Cost & Timeline */}
            <div className="detail-section">
              <h3>💰 Cost & Timeline</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Construction Cost (Within Budget):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('construction_cost', e, fieldHelp.construction_cost)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <div className="cost-input-group">
                    <input
                      type="text"
                      placeholder="Enter amount within homeowner's budget range"
                      value={technicalDetails.construction_cost}
                      onChange={(e) => handleInputChange('construction_cost', e.target.value)}
                    />
                    <small>Budget Range: {calculateEstimatedCost()}</small>
                  </div>
                </div>
                
                <div className="form-group">
                  <label>
                    Construction Duration (months):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('construction_duration', e, fieldHelp.construction_duration)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="construction_duration"
                    customField="construction_duration_custom"
                    value={technicalDetails.construction_duration}
                    onChange={handleInputChange}
                    placeholder="Enter custom duration"
                    options={[
                      { value: '6-8', label: '6-8 months' },
                      { value: '8-12', label: '8-12 months' },
                      { value: '12-18', label: '12-18 months' },
                      { value: '18-24', label: '18-24 months' },
                      { value: '24+', label: '24+ months' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Technical Details Unlock Price (₹):
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('unlock_price', e, fieldHelp.unlock_price)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
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
                  <label>
                    Main Door Material:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('main_door_material', e, fieldHelp.main_door_material)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="main_door_material"
                    customField="main_door_material_custom"
                    value={technicalDetails.main_door_material}
                    onChange={handleInputChange}
                    placeholder="Enter custom door material"
                    options={[
                      { value: 'Teak Wood', label: 'Teak Wood' },
                      { value: 'Engineered Wood', label: 'Engineered Wood' },
                      { value: 'Steel', label: 'Steel Door' },
                      { value: 'Fiber', label: 'Fiber Door' },
                      { value: 'Glass', label: 'Glass Door' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Window Material:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('window_material', e, fieldHelp.window_material)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="window_material"
                    customField="window_material_custom"
                    value={technicalDetails.window_material}
                    onChange={handleInputChange}
                    placeholder="Enter custom window material"
                    options={[
                      { value: 'UPVC', label: 'UPVC Windows' },
                      { value: 'Aluminum', label: 'Aluminum Windows' },
                      { value: 'Wood', label: 'Wooden Windows' },
                      { value: 'Steel', label: 'Steel Windows' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Staircase Material:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('staircase_material', e, fieldHelp.staircase_material)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="staircase_material"
                    customField="staircase_material_custom"
                    value={technicalDetails.staircase_material}
                    onChange={handleInputChange}
                    placeholder="Enter custom staircase material"
                    options={[
                      { value: 'RCC with Granite', label: 'RCC with Granite' },
                      { value: 'RCC with Marble', label: 'RCC with Marble' },
                      { value: 'Steel Structure', label: 'Steel Structure' },
                      { value: 'Wooden Staircase', label: 'Wooden Staircase' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Compound Wall:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('compound_wall', e, fieldHelp.compound_wall)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="compound_wall"
                    customField="compound_wall_custom"
                    value={technicalDetails.compound_wall}
                    onChange={handleInputChange}
                    placeholder="Enter custom compound wall option"
                    options={[
                      { value: 'Yes', label: 'Yes' },
                      { value: 'No', label: 'No' },
                      { value: 'Partial', label: 'Partial' }
                    ]}
                  />
                </div>

                <div className="form-group">
                  <label>
                    Exterior Finish:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('exterior_finish', e, fieldHelp.exterior_finish)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="exterior_finish"
                    customField="exterior_finish_custom"
                    value={technicalDetails.exterior_finish}
                    onChange={handleInputChange}
                    placeholder="Enter custom exterior finish"
                    options={[
                      { value: 'Paint', label: 'Paint' },
                      { value: 'Texture', label: 'Texture Paint' },
                      { value: 'Stone Cladding', label: 'Stone Cladding' },
                      { value: 'Tiles', label: 'Exterior Tiles' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Kitchen Type:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('kitchen_type', e, fieldHelp.kitchen_type)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="kitchen_type"
                    customField="kitchen_type_custom"
                    value={technicalDetails.kitchen_type}
                    onChange={handleInputChange}
                    placeholder="Enter custom kitchen type"
                    options={[
                      { value: 'Modular', label: 'Modular Kitchen' },
                      { value: 'Semi-Modular', label: 'Semi-Modular' },
                      { value: 'Traditional', label: 'Traditional' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Bathroom Fittings:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('bathroom_fittings', e, fieldHelp.bathroom_fittings)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="bathroom_fittings"
                    customField="bathroom_fittings_custom"
                    value={technicalDetails.bathroom_fittings}
                    onChange={handleInputChange}
                    placeholder="Enter custom bathroom fittings"
                    options={[
                      { value: 'Standard', label: 'Standard' },
                      { value: 'Premium', label: 'Premium' },
                      { value: 'Luxury', label: 'Luxury' }
                    ]}
                  />
                </div>
              </div>
            </div>

            {/* Approvals & Compliance */}
            <div className="detail-section">
              <h3>📋 Approvals & Compliance</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label>
                    Building Plan Approval:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('building_plan_approval', e, fieldHelp.building_plan_approval)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="building_plan_approval"
                    customField="building_plan_approval_custom"
                    value={technicalDetails.building_plan_approval}
                    onChange={handleInputChange}
                    placeholder="Enter custom approval status"
                    options={[
                      { value: 'Required', label: 'Required' },
                      { value: 'Obtained', label: 'Already Obtained' },
                      { value: 'Not Required', label: 'Not Required' },
                      { value: 'In Process', label: 'In Process' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Environmental Clearance:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('environmental_clearance', e, fieldHelp.environmental_clearance)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="environmental_clearance"
                    customField="environmental_clearance_custom"
                    value={technicalDetails.environmental_clearance}
                    onChange={handleInputChange}
                    placeholder="Enter custom clearance status"
                    options={[
                      { value: 'Not Required', label: 'Not Required' },
                      { value: 'Required', label: 'Required' },
                      { value: 'Obtained', label: 'Already Obtained' },
                      { value: 'In Process', label: 'In Process' }
                    ]}
                  />
                </div>
                
                <div className="form-group">
                  <label>
                    Fire NOC:
                    <button 
                      type="button"
                      className="help-icon"
                      onMouseEnter={(e) => showTooltip('fire_noc', e, fieldHelp.fire_noc)}
                      onMouseLeave={hideTooltip}
                    >
                      ℹ️
                    </button>
                  </label>
                  <EnhancedSelect
                    field="fire_noc"
                    customField="fire_noc_custom"
                    value={technicalDetails.fire_noc}
                    onChange={handleInputChange}
                    placeholder="Enter custom NOC status"
                    options={[
                      { value: 'Required', label: 'Required' },
                      { value: 'Not Required', label: 'Not Required' },
                      { value: 'Obtained', label: 'Already Obtained' },
                      { value: 'In Process', label: 'In Process' }
                    ]}
                  />
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
        
        {/* Tooltip */}
        {activeTooltip && (
          <div 
            className="field-tooltip"
            style={{
              position: 'fixed',
              left: tooltipPosition.x,
              top: tooltipPosition.y,
              transform: 'translateX(-50%) translateY(-100%)',
              zIndex: 10000
            }}
          >
            <div className="tooltip-content">
              {activeTooltip.content}
            </div>
            <div className="tooltip-arrow"></div>
          </div>
        )}
      </div>
    </div>
  );
};

export default TechnicalDetailsModal;