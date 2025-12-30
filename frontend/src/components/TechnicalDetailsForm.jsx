import React, { useState, useMemo } from 'react';
import '../styles/TechnicalDetailsForm.css';

const TechnicalDetailsForm = ({ data, setData, onNext, onPrev }) => {
  const [activeSection, setActiveSection] = useState('floor_plans');
  const [selectedTemplate, setSelectedTemplate] = useState('');
  const [showQuickFill, setShowQuickFill] = useState(true);

  const [formData, setFormData] = useState({
    floor_plan_layout: data.technical_details?.floor_plan_layout || '',
    room_dimensions: data.technical_details?.room_dimensions || {
      living_room: '',
      master_bedroom: '',
      kitchen: '',
      other_rooms: ''
    },
    door_window_positions: data.technical_details?.door_window_positions || '',
    circulation_paths: data.technical_details?.circulation_paths || '',
    structural_elements: data.technical_details?.structural_elements || '',
    elevations_sections: data.technical_details?.elevations_sections || '',
    construction_notes: data.technical_details?.construction_notes || '',
    foundation_type: data.technical_details?.foundation_type || '',
    structural_materials: data.technical_details?.structural_materials || '',
    load_bearing_elements: data.technical_details?.load_bearing_elements || '',
    facade_treatment: data.technical_details?.facade_treatment || '',
    section_details: data.technical_details?.section_details || '',
    building_height: data.technical_details?.building_height || '',
    material_specifications: data.technical_details?.material_specifications || '',
    construction_methods: data.technical_details?.construction_methods || '',
    special_requirements: data.technical_details?.special_requirements || '',
    electrical_system: data.technical_details?.electrical_system || '',
    plumbing_system: data.technical_details?.plumbing_system || '',
    hvac_system: data.technical_details?.hvac_system || '',
    fire_safety: data.technical_details?.fire_safety || '',
    accessibility_features: data.technical_details?.accessibility_features || '',
    energy_efficiency: data.technical_details?.energy_efficiency || '',
    estimated_cost: data.technical_details?.estimated_cost || '',
    cost_breakdown: data.technical_details?.cost_breakdown || '',
    material_costs: data.technical_details?.material_costs || '',
    labor_costs: data.technical_details?.labor_costs || '',
    view_price: data.technical_details?.view_price || data.view_price || 0
  });

  // Template definitions
  const templates = useMemo(() => [
    {
      id: 'residential_concrete',
      name: 'Residential – Concrete baseline',
      data: {
        structural_elements: 'RCC framed structure with concrete columns, beams, and slabs. Standard residential construction with M20 grade concrete.',
        foundation_type: 'RCC Foundation with strip footing',
        structural_materials: 'M20 grade concrete, Fe415 steel reinforcement',
        electrical_system: 'Standard residential electrical layout with MCB distribution board',
        plumbing_system: 'CPVC pipes for water supply, PVC for drainage',
        hvac_system: 'Natural ventilation with ceiling fans',
        estimated_cost: '₹15,00,000 - ₹20,00,000'
      }
    },
    {
      id: 'steel_office',
      name: 'Office – Steel frame + curtain wall',
      data: {
        structural_elements: 'Steel frame structure with composite steel-concrete floors. Modern office building design.',
        foundation_type: 'Raft foundation with pile foundation',
        structural_materials: 'Structural steel grade Fe500, M25 grade concrete',
        electrical_system: 'Commercial electrical system with UPS backup',
        plumbing_system: 'GI pipes for water supply, cast iron for drainage',
        hvac_system: 'Centralized AC system with VRF technology',
        estimated_cost: '₹25,00,000 - ₹35,00,000'
      }
    },
    {
      id: 'timber_school',
      name: 'School – Timber CLT',
      data: {
        structural_elements: 'Cross-laminated timber (CLT) structure with steel connections',
        foundation_type: 'Strip foundation with timber posts',
        structural_materials: 'CLT panels, structural timber, steel connections',
        electrical_system: 'Educational facility electrical with safety features',
        plumbing_system: 'PEX pipes for water supply, PVC for drainage',
        hvac_system: 'Natural ventilation with mechanical assistance',
        estimated_cost: '₹18,00,000 - ₹25,00,000'
      }
    },
    {
      id: 'residential_contemporary',
      name: 'Residential – Contemporary',
      data: {
        structural_elements: 'Modern RCC structure with cantilevered elements and glass facades',
        foundation_type: 'RCC foundation with basement',
        structural_materials: 'M25 grade concrete, Fe500 steel, glass curtain wall',
        electrical_system: 'Smart home electrical system with automation',
        plumbing_system: 'PEX pipes with smart water management',
        hvac_system: 'VRF air conditioning with heat recovery',
        estimated_cost: '₹30,00,000 - ₹45,00,000'
      }
    },
    {
      id: 'residential_traditional',
      name: 'Residential – Traditional',
      data: {
        structural_elements: 'Traditional masonry construction with RCC roof',
        foundation_type: 'Strip foundation with masonry walls',
        structural_materials: 'Brick masonry, M15 grade concrete, traditional roofing',
        electrical_system: 'Standard residential electrical with traditional aesthetics',
        plumbing_system: 'Traditional plumbing with modern fixtures',
        hvac_system: 'Natural ventilation with traditional cooling methods',
        estimated_cost: '₹12,00,000 - ₹18,00,000'
      }
    },
    {
      id: 'residential_minimalist',
      name: 'Residential – Modern Minimal',
      data: {
        structural_elements: 'Minimalist RCC structure with clean lines and open spaces',
        foundation_type: 'RCC foundation with floating slab',
        structural_materials: 'M20 grade concrete, minimal steel, clean finishes',
        electrical_system: 'Hidden electrical system with minimal visible elements',
        plumbing_system: 'Concealed plumbing with minimalist fixtures',
        hvac_system: 'Underfloor heating with minimal visible systems',
        estimated_cost: '₹20,00,000 - ₹30,00,000'
      }
    },
    {
      id: 'commercial_retail',
      name: 'Commercial – Retail shell',
      data: {
        structural_elements: 'Steel frame with large open spaces for retail flexibility',
        foundation_type: 'Raft foundation for heavy loads',
        structural_materials: 'Structural steel, composite floors, curtain wall system',
        electrical_system: 'High-capacity commercial electrical with backup',
        plumbing_system: 'Commercial plumbing with multiple connections',
        hvac_system: 'Centralized HVAC with zone control',
        estimated_cost: '₹40,00,000 - ₹60,00,000'
      }
    },
    {
      id: 'commercial_mixeduse',
      name: 'Commercial – Mixed-use podium + tower',
      data: {
        structural_elements: 'Mixed-use structure with podium and tower elements',
        foundation_type: 'Deep foundation with basement levels',
        structural_materials: 'High-strength concrete M30+, structural steel',
        electrical_system: 'Multi-zone electrical system for different uses',
        plumbing_system: 'Complex plumbing system for multiple functions',
        hvac_system: 'Zoned HVAC system for different building uses',
        estimated_cost: '₹50,00,000 - ₹80,00,000'
      }
    },
    {
      id: 'residential_villa',
      name: 'Residential – Luxury Villa',
      data: {
        structural_elements: 'Luxury RCC structure with premium finishes and features',
        foundation_type: 'RCC foundation with basement and parking',
        structural_materials: 'High-grade concrete M25+, premium steel, luxury finishes',
        electrical_system: 'Premium electrical system with smart home features',
        plumbing_system: 'Premium plumbing with luxury fixtures',
        hvac_system: 'Premium HVAC with smart climate control',
        estimated_cost: '₹60,00,000 - ₹1,00,00,000'
      }
    },
    {
      id: 'residential_apartment_midrise',
      name: 'Residential – Mid-rise Apartments',
      data: {
        structural_elements: 'Mid-rise RCC structure optimized for apartment living',
        foundation_type: 'RCC foundation with basement parking',
        structural_materials: 'M25 grade concrete, Fe500 steel, apartment finishes',
        electrical_system: 'Apartment electrical system with individual meters',
        plumbing_system: 'Apartment plumbing with individual connections',
        hvac_system: 'Individual AC units with common ventilation',
        estimated_cost: '₹35,00,000 - ₹50,00,000'
      }
    }
  ], []);

  // Quick-fill options for common specifications
  const quickFillOptions = useMemo(() => ({
    floor_plan_layout: [
      'Simple rectangular layout with clear circulation',
      'U-shaped layout with central courtyard',
      'L-shaped layout with optimal space utilization',
      'Open plan layout with flexible spaces',
      'Traditional layout with separate formal and informal areas',
      'Modern minimalist layout with clean lines',
      'Duplex layout with ground floor and upper floor'
    ],
    room_dimensions: [
      { label: 'Standard 2BHK', living_room: '20×15 ft', master_bedroom: '14×12 ft', kitchen: '10×8 ft', other_rooms: 'Bedroom 2: 12×10 ft\nBathroom: 8×6 ft' },
      { label: 'Compact 2BHK', living_room: '18×14 ft', master_bedroom: '12×11 ft', kitchen: '9×7 ft', other_rooms: 'Bedroom 2: 11×10 ft\nBathroom: 7×6 ft' },
      { label: 'Luxury 3BHK', living_room: '24×18 ft', master_bedroom: '16×14 ft', kitchen: '12×10 ft', other_rooms: 'Bedroom 2: 14×12 ft\nBedroom 3: 12×11 ft\nBathroom 1: 10×8 ft\nBathroom 2: 9×6 ft' },
      { label: 'Standard 3BHK', living_room: '22×16 ft', master_bedroom: '14×13 ft', kitchen: '11×9 ft', other_rooms: 'Bedroom 2: 13×11 ft\nBedroom 3: 12×10 ft\nBathroom 1: 9×6 ft\nBathroom 2: 8×6 ft' },
      { label: 'Spacious 4BHK', living_room: '26×20 ft', master_bedroom: '18×15 ft', kitchen: '14×11 ft', other_rooms: 'Bedroom 2: 15×13 ft\nBedroom 3: 14×12 ft\nBedroom 4: 12×11 ft\nBathroom 1: 10×8 ft\nBathroom 2: 9×6 ft' }
    ],
    living_room_options: [
      '20×15 ft', '18×14 ft', '24×18 ft', '22×16 ft', '26×20 ft', '16×12 ft', '28×22 ft'
    ],
    master_bedroom_options: [
      '14×12 ft', '12×11 ft', '16×14 ft', '14×13 ft', '18×15 ft', '15×13 ft', '20×16 ft'
    ],
    kitchen_options: [
      '10×8 ft', '9×7 ft', '12×10 ft', '11×9 ft', '14×11 ft', '10×9 ft', '15×12 ft'
    ],
    door_window_positions: [
      'North-facing main entrance with east-west rooms for optimal sunlight',
      'Southern exposure for main living spaces, northern entrance',
      'Cross-ventilation with opposing windows in all rooms',
      'Windows positioned for privacy while maximizing natural light',
      'Strategic door placement for optimal flow and privacy',
      'Open floor plan with minimal partitions, large windows',
      'Traditional layout with separate entrance for formal and informal areas'
    ],
    circulation_paths: [
      'Central hallway with easy access to all rooms',
      'Minimal circulation with direct access to spaces',
      'Defined pathways separating public and private zones',
      'Open circulation allowing flexible use of space',
      'Traditional layout with clear public-private zoning',
      'Minimal corridors maximizing usable area'
    ],
    structural_elements: [
      'RCC framed structure with concrete columns, beams, and slabs',
      'Steel frame structure with composite steel-concrete floors',
      'Load-bearing masonry with RCC roof',
      'Hybrid structure combining steel and concrete'
    ],
    foundation_type: [
      'RCC strip footing foundation',
      'Raft foundation with pile system',
      'Pile foundation for high loads',
      'RCC foundation with basement',
      'Isolated footing foundation'
    ],
    structural_materials: [
      'M20 grade concrete, Fe415 steel reinforcement',
      'M25 grade concrete, Fe500 steel',
      'Structural steel grade Fe500',
      'M15 grade concrete for foundation',
      'High-strength concrete M30+'
    ],
    electrical_system: [
      'Standard residential electrical layout with MCB distribution board',
      'Commercial electrical system with UPS backup',
      'Smart home electrical system with automation',
      'High-capacity commercial electrical with backup',
      'Premium electrical system with smart home features'
    ],
    plumbing_system: [
      'CPVC pipes for water supply, PVC for drainage',
      'GI pipes for water supply, cast iron for drainage',
      'PEX pipes for water supply, PVC for drainage',
      'Concealed plumbing with minimalist fixtures'
    ],
    hvac_system: [
      'Natural ventilation with ceiling fans',
      'Centralized AC system with VRF technology',
      'VRF air conditioning with heat recovery',
      'Individual AC units with common ventilation',
      'Natural ventilation with mechanical assistance'
    ],
    facade_treatment: [
      'Standard cement plaster finish with paint',
      'Premium facade with glass curtain wall',
      'Traditional brick facade with stone accents',
      'Modern minimalist facade with clean lines',
      'Textured plaster with designer finishes'
    ],
    fire_safety: [
      'Standard fire safety with fire extinguishers and exit signs',
      'Fire sprinkler system with smoke detectors',
      'Advanced fire safety system with sprinklers and alarms',
      'Complete fire safety with automatic sprinkler system',
      'Standard fire safety compliance with smoke detectors'
    ],
    accessibility_features: [
      'Ramps and accessible parking',
      'Wheelchair accessible with elevators',
      'Complete accessibility features with ramps and rails',
      'Universal design with accessible facilities',
      'Basic accessibility with ramps'
    ],
    energy_efficiency: [
      'Standard insulation with energy-efficient windows',
      'Solar panels with energy-efficient design',
      'High-performance insulation and solar features',
      'Green building practices with energy efficiency',
      'Standard energy efficiency with proper ventilation'
    ],
    load_bearing_elements: [
      'RCC columns and beams with standard spacing',
      'Load-bearing walls with RCC slabs',
      'Steel columns with composite floors',
      'RCC columns with precast elements',
      'Hybrid structure with RC frame and masonry walls'
    ],
    elevations_sections: [
      'Standard elevation with cement plaster and paint',
      'Modern elevation with glass and designer materials',
      'Traditional elevation with decorative elements',
      'Minimalist elevation with clean lines',
      'Premium elevation with high-quality finishes'
    ],
    section_details: [
      'Standard wall thickness with cavity insulation',
      'Reinforced concrete sections with proper detailing',
      'Composite sections with steel and concrete',
      'Insulated sections for energy efficiency',
      'Load-bearing wall sections with proper foundations'
    ],
    building_height: [
      'Ground + 1 floor (approx. 10.5m)',
      'Ground + 2 floors (approx. 13.5m)',
      'Ground + 3 floors (approx. 16.5m)',
      'Ground + 4 floors (approx. 19.5m)',
      'Single story (approx. 7.5m ceiling)',
      'Multi-storey high-rise (20m+)'
    ],
    construction_notes: [
      'Standard residential construction with M20 concrete',
      'Premium construction with high-quality materials',
      'Sustainable construction with eco-friendly materials',
      'Fast-track construction with modern methods',
      'Traditional construction with local materials',
      'Industrial construction for commercial use'
    ],
    material_specifications: [
      'M20 grade concrete, Fe415 steel, standard finishes',
      'M25 grade concrete, Fe500 steel, premium finishes',
      'Standard cement, sand, steel, and tiles',
      'High-grade materials with designer finishes',
      'Eco-friendly materials with sustainable options'
    ],
    construction_methods: [
      'Traditional construction methodology',
      'Modern construction with precast elements',
      'Fast-track construction technique',
      'Hybrid construction approach',
      'Sustainable construction practices',
      'Modular construction methodology'
    ],
    special_requirements: [
      'Earthquake-resistant design with proper reinforcement',
      'Waterproofing and damp-proofing systems',
      'Sound insulation and acoustic treatment',
      'Thermal insulation for energy efficiency',
      'Security features and smart home integration',
      'Accessibility compliance with ramps and elevators'
    ],
    cost_breakdown: [
      'Foundation: 15%, Structure: 30%, Finishing: 35%, Services: 20%',
      'Site work: 10%, Structure: 35%, Finishing: 40%, Services: 15%',
      'Foundation: 20%, Frame: 25%, Roofing: 15%, Finishing: 40%',
      'Land preparation: 8%, Structure: 32%, Interior: 45%, Exterior: 15%'
    ],
    material_costs: [
      'Cement: ₹250/bag, Steel: ₹55/kg, Bricks: ₹8/unit',
      'High-grade materials: Cement ₹280/bag, Steel ₹65/kg',
      'Premium materials: Cement ₹320/bag, Steel ₹75/kg',
      'Standard materials: Cement ₹240/bag, Steel ₹52/kg'
    ],
    labor_costs: [
      'Masonry: ₹600/day, Carpenter: ₹700/day, Electrician: ₹550/day',
      'Skilled: ₹800/day, Unskilled: ₹500/day, Specialist: ₹1000/day',
      'Standard labor: ₹600/day, Specialized: ₹900/day',
      'Premium labor: ₹900/day, Supervisor: ₹1200/day'
    ]
  }), []);

  // Function to apply quick-fill value
  const applyQuickFill = (field, value) => {
    handleInputChange(field, value);
  };

  // Function to apply room dimension template
  const applyRoomDimensionTemplate = (template) => {
    handleRoomDimensionChange('living_room', template.living_room);
    handleRoomDimensionChange('master_bedroom', template.master_bedroom);
    handleRoomDimensionChange('kitchen', template.kitchen);
    handleRoomDimensionChange('other_rooms', template.other_rooms);
  };

  // Section definitions
  const sections = useMemo(() => [
    {
      id: 'floor_plans',
      title: 'Floor Plans & Layout',
      icon: '🏠',
      description: 'Complete the fields below to provide technical details for this section'
    },
    {
      id: 'structural',
      title: 'Structural Elements',
      icon: '🏗️',
      description: 'Specify structural components and construction methods'
    },
    {
      id: 'elevations',
      title: 'Key Elevations & Sections',
      icon: '📐',
      description: 'Provide details about building elevations and cross-sections'
    },
    {
      id: 'construction',
      title: 'Construction Notes',
      icon: '📋',
      description: 'Additional construction specifications and considerations'
    },
    {
      id: 'systems',
      title: 'Building Systems',
      icon: '⚡',
      description: 'Electrical, plumbing, HVAC, and other building systems'
    },
    {
      id: 'pricing',
      title: 'Cost Estimation',
      icon: '💰',
      description: 'Pricing information and cost breakdown'
    }
  ], []);

  const handleInputChange = (field, value) => {
    const newFormData = { ...formData, [field]: value };
    setFormData(newFormData);
    
    // Update parent data
    setData({
      ...data,
      technical_details: newFormData
    });
  };

  const handleRoomDimensionChange = (room, value) => {
    const newRoomDimensions = { ...formData.room_dimensions, [room]: value };
    const newFormData = { ...formData, room_dimensions: newRoomDimensions };
    setFormData(newFormData);
    
    // Update parent data
    setData({
      ...data,
      technical_details: newFormData
    });
  };

  const handleTemplateChange = (templateId) => {
    setSelectedTemplate(templateId);
    if (templateId) {
      const template = templates.find(t => t.id === templateId);
      if (template) {
        const newFormData = { ...formData, ...template.data };
        setFormData(newFormData);
        setData({
          ...data,
          technical_details: newFormData
        });
      }
    }
  };

  const validateCurrentSection = () => {
    // Simple validation - just return true for now
    // You can add section-specific validation here
    return true;
  };

  const handleNext = () => {
    const currentIndex = sections.findIndex(s => s.id === activeSection);
    if (currentIndex < sections.length - 1) {
      setActiveSection(sections[currentIndex + 1].id);
    }
  };

  const handlePrev = () => {
    const currentIndex = sections.findIndex(s => s.id === activeSection);
    if (currentIndex > 0) {
      setActiveSection(sections[currentIndex - 1].id);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    // Update the parent data with technical details including view_price
    setData(prevData => ({
      ...prevData,
      technical_details: formData,
      view_price: formData.view_price
    }));
    onNext();
  };

  const renderFloorPlansSection = () => (
    <div className="technical-section">
      <div className="form-group">
        <label>Floor Plan Layout Description</label>
        {showQuickFill && quickFillOptions.floor_plan_layout && (
          <div className="quick-fill-buttons">
            {quickFillOptions.floor_plan_layout.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('floor_plan_layout', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.floor_plan_layout}
          onChange={(e) => handleInputChange('floor_plan_layout', e.target.value)}
          placeholder="Describe the floor plan layout, room arrangements, and spatial organization..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Room Dimensions</label>
        {showQuickFill && quickFillOptions.room_dimensions && (
          <div className="quick-fill-buttons" style={{ marginBottom: '16px' }}>
            {quickFillOptions.room_dimensions.map((template, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyRoomDimensionTemplate(template)}
                style={{ fontSize: '11px', padding: '6px 10px' }}
              >
                📐 {template.label}
              </button>
            ))}
          </div>
        )}
        <div className="dimensions-grid">
          <div className="dimension-item">
            <label>Living Room</label>
            {showQuickFill && quickFillOptions.living_room_options && (
              <div className="quick-fill-buttons">
                {quickFillOptions.living_room_options.map((option, idx) => (
                  <button
                    key={idx}
                    type="button"
                    className="quick-fill-btn"
                    onClick={() => handleRoomDimensionChange('living_room', option)}
                    style={{ fontSize: '11px', padding: '4px 8px' }}
                  >
                    {option}
                  </button>
                ))}
              </div>
            )}
            <input
              type="text"
              value={formData.room_dimensions.living_room}
              onChange={(e) => handleRoomDimensionChange('living_room', e.target.value)}
              placeholder="e.g., 20 × 15 ft"
            />
          </div>
          <div className="dimension-item">
            <label>Master Bedroom</label>
            {showQuickFill && quickFillOptions.master_bedroom_options && (
              <div className="quick-fill-buttons">
                {quickFillOptions.master_bedroom_options.map((option, idx) => (
                  <button
                    key={idx}
                    type="button"
                    className="quick-fill-btn"
                    onClick={() => handleRoomDimensionChange('master_bedroom', option)}
                    style={{ fontSize: '11px', padding: '4px 8px' }}
                  >
                    {option}
                  </button>
                ))}
              </div>
            )}
            <input
              type="text"
              value={formData.room_dimensions.master_bedroom}
              onChange={(e) => handleRoomDimensionChange('master_bedroom', e.target.value)}
              placeholder="e.g., 16 × 12 ft"
            />
          </div>
          <div className="dimension-item">
            <label>Kitchen</label>
            {showQuickFill && quickFillOptions.kitchen_options && (
              <div className="quick-fill-buttons">
                {quickFillOptions.kitchen_options.map((option, idx) => (
                  <button
                    key={idx}
                    type="button"
                    className="quick-fill-btn"
                    onClick={() => handleRoomDimensionChange('kitchen', option)}
                    style={{ fontSize: '11px', padding: '4px 8px' }}
                  >
                    {option}
                  </button>
                ))}
              </div>
            )}
            <input
              type="text"
              value={formData.room_dimensions.kitchen}
              onChange={(e) => handleRoomDimensionChange('kitchen', e.target.value)}
              placeholder="e.g., 12 × 10 ft"
            />
          </div>
          <div className="dimension-item">
            <label>Other Rooms</label>
            <textarea
              value={formData.room_dimensions.other_rooms}
              onChange={(e) => handleRoomDimensionChange('other_rooms', e.target.value)}
              placeholder="Bedroom 2: 14 × 12 ft, Bedroom 3: 12 × 10 ft, Bathroom: 8 × 6 ft..."
              rows="3"
            />
          </div>
        </div>
      </div>

      <div className="form-group">
        <label>Door & Window Positions</label>
        {showQuickFill && quickFillOptions.door_window_positions && (
          <div className="quick-fill-buttons">
            {quickFillOptions.door_window_positions.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('door_window_positions', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.door_window_positions}
          onChange={(e) => handleInputChange('door_window_positions', e.target.value)}
          placeholder="Specify door and window locations, sizes, and orientations..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Main Circulation Paths</label>
        {showQuickFill && quickFillOptions.circulation_paths && (
          <div className="quick-fill-buttons">
            {quickFillOptions.circulation_paths.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('circulation_paths', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.circulation_paths}
          onChange={(e) => handleInputChange('circulation_paths', e.target.value)}
          placeholder="Describe hallways, staircases, and main circulation routes..."
          rows="3"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary" disabled={activeSection === 'floor_plans'}>
          ← Previous
        </button>
        <button type="button" onClick={handleNext} className="btn btn-primary">
          Next: Structural Elements →
        </button>
      </div>
    </div>
  );

  const renderStructuralSection = () => (
    <div className="technical-section">
      <div className="form-group">
        <label>Structural System</label>
        {showQuickFill && quickFillOptions.structural_elements && (
          <div className="quick-fill-buttons">
            {quickFillOptions.structural_elements.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('structural_elements', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.structural_elements}
          onChange={(e) => handleInputChange('structural_elements', e.target.value)}
          placeholder="Describe structural system, materials, load-bearing elements, foundation type..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Foundation Type</label>
        {showQuickFill && quickFillOptions.foundation_type && (
          <div className="quick-fill-buttons">
            {quickFillOptions.foundation_type.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('foundation_type', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <input
          type="text"
          value={formData.foundation_type}
          onChange={(e) => handleInputChange('foundation_type', e.target.value)}
          placeholder="e.g., RCC Foundation, Pile Foundation, Strip Foundation..."
        />
      </div>

      <div className="form-group">
        <label>Structural Materials</label>
        {showQuickFill && quickFillOptions.structural_materials && (
          <div className="quick-fill-buttons">
            {quickFillOptions.structural_materials.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('structural_materials', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.structural_materials}
          onChange={(e) => handleInputChange('structural_materials', e.target.value)}
          placeholder="Specify concrete grade, steel specifications, masonry details..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Load Bearing Elements</label>
        {showQuickFill && quickFillOptions.load_bearing_elements && (
          <div className="quick-fill-buttons">
            {quickFillOptions.load_bearing_elements.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('load_bearing_elements', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.load_bearing_elements}
          onChange={(e) => handleInputChange('load_bearing_elements', e.target.value)}
          placeholder="Describe columns, beams, walls, and other load-bearing components..."
          rows="3"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary">
          ← Previous
        </button>
        <button type="button" onClick={handleNext} className="btn btn-primary">
          Next: Elevations & Sections →
        </button>
      </div>
    </div>
  );

  const renderElevationsSection = () => (
    <div className="technical-section">
      <div className="form-group">
        <label>Elevation Details</label>
        {showQuickFill && quickFillOptions.elevations_sections && (
          <div className="quick-fill-buttons">
            {quickFillOptions.elevations_sections.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('elevations_sections', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.elevations_sections}
          onChange={(e) => handleInputChange('elevations_sections', e.target.value)}
          placeholder="Describe building elevations, facade treatments, section details..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Facade Treatment</label>
        {showQuickFill && quickFillOptions.facade_treatment && (
          <div className="quick-fill-buttons">
            {quickFillOptions.facade_treatment.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('facade_treatment', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.facade_treatment}
          onChange={(e) => handleInputChange('facade_treatment', e.target.value)}
          placeholder="Specify exterior finishes, cladding materials, architectural features..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Section Details</label>
        {showQuickFill && quickFillOptions.section_details && (
          <div className="quick-fill-buttons">
            {quickFillOptions.section_details.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('section_details', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.section_details}
          onChange={(e) => handleInputChange('section_details', e.target.value)}
          placeholder="Describe cross-sections, wall thicknesses, floor-to-floor heights..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Building Height</label>
        {showQuickFill && quickFillOptions.building_height && (
          <div className="quick-fill-buttons">
            {quickFillOptions.building_height.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('building_height', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <input
          type="text"
          value={formData.building_height}
          onChange={(e) => handleInputChange('building_height', e.target.value)}
          placeholder="e.g., 10.5m (Ground + 2 floors)"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary">
          ← Previous
        </button>
        <button type="button" onClick={handleNext} className="btn btn-primary">
          Next: Construction Details →
        </button>
      </div>
    </div>
  );

  const renderConstructionSection = () => (
    <div className="technical-section">
      <div className="form-group">
        <label>Construction Specifications</label>
        {showQuickFill && quickFillOptions.construction_notes && (
          <div className="quick-fill-buttons">
            {quickFillOptions.construction_notes.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('construction_notes', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.construction_notes}
          onChange={(e) => handleInputChange('construction_notes', e.target.value)}
          placeholder="Include material specifications, construction methods, special requirements..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Material Specifications</label>
        {showQuickFill && quickFillOptions.material_specifications && (
          <div className="quick-fill-buttons">
            {quickFillOptions.material_specifications.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('material_specifications', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.material_specifications}
          onChange={(e) => handleInputChange('material_specifications', e.target.value)}
          placeholder="Specify brands, grades, and quality standards for materials..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Construction Methods</label>
        {showQuickFill && quickFillOptions.construction_methods && (
          <div className="quick-fill-buttons">
            {quickFillOptions.construction_methods.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('construction_methods', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.construction_methods}
          onChange={(e) => handleInputChange('construction_methods', e.target.value)}
          placeholder="Describe construction techniques, sequencing, and methodologies..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Special Requirements</label>
        {showQuickFill && quickFillOptions.special_requirements && (
          <div className="quick-fill-buttons">
            {quickFillOptions.special_requirements.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('special_requirements', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.special_requirements}
          onChange={(e) => handleInputChange('special_requirements', e.target.value)}
          placeholder="Any special construction requirements, permits, or considerations..."
          rows="3"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary">
          ← Previous
        </button>
        <button type="button" onClick={handleNext} className="btn btn-primary">
          Next: MEP Systems →
        </button>
      </div>
    </div>
  );

  const renderSystemsSection = () => (
    <div className="technical-section">
      <div className="form-group">
        <label>Electrical System</label>
        {showQuickFill && quickFillOptions.electrical_system && (
          <div className="quick-fill-buttons">
            {quickFillOptions.electrical_system.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('electrical_system', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.electrical_system}
          onChange={(e) => handleInputChange('electrical_system', e.target.value)}
          placeholder="Describe electrical layout, load calculations, panel locations..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Plumbing System</label>
        {showQuickFill && quickFillOptions.plumbing_system && (
          <div className="quick-fill-buttons">
            {quickFillOptions.plumbing_system.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('plumbing_system', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.plumbing_system}
          onChange={(e) => handleInputChange('plumbing_system', e.target.value)}
          placeholder="Specify water supply, drainage, fixture locations..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>HVAC System</label>
        {showQuickFill && quickFillOptions.hvac_system && (
          <div className="quick-fill-buttons">
            {quickFillOptions.hvac_system.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('hvac_system', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.hvac_system}
          onChange={(e) => handleInputChange('hvac_system', e.target.value)}
          placeholder="Describe heating, ventilation, and air conditioning systems..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Fire Safety</label>
        {showQuickFill && quickFillOptions.fire_safety && (
          <div className="quick-fill-buttons">
            {quickFillOptions.fire_safety.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('fire_safety', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.fire_safety}
          onChange={(e) => handleInputChange('fire_safety', e.target.value)}
          placeholder="Specify fire safety measures, exits, sprinkler systems..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Accessibility Features</label>
        {showQuickFill && quickFillOptions.accessibility_features && (
          <div className="quick-fill-buttons">
            {quickFillOptions.accessibility_features.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('accessibility_features', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.accessibility_features}
          onChange={(e) => handleInputChange('accessibility_features', e.target.value)}
          placeholder="Describe accessibility compliance, ramps, elevators..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Energy Efficiency</label>
        {showQuickFill && quickFillOptions.energy_efficiency && (
          <div className="quick-fill-buttons">
            {quickFillOptions.energy_efficiency.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('energy_efficiency', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.energy_efficiency}
          onChange={(e) => handleInputChange('energy_efficiency', e.target.value)}
          placeholder="Specify insulation, solar panels, energy-efficient systems..."
          rows="3"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary">
          ← Previous
        </button>
        <button type="button" onClick={() => { const result = validateCurrentSection(); if (result) handleNext(); }} className="btn btn-primary">
          Next: Pricing & Timeline →
        </button>
      </div>
    </div>
  );

  const renderPricingSection = () => (
    <div className="technical-section">
      <div className="price-section">
        <label>Estimated Total Cost</label>
        <div className="price-input-container">
          <input
            type="text"
            className="price-input"
            value={formData.estimated_cost}
            onChange={(e) => handleInputChange('estimated_cost', e.target.value)}
            placeholder="e.g., ₹25,00,000"
          />
        </div>
        <div className="field-help">
          Enter the total estimated cost for the project including all materials and labor
        </div>
      </div>

      <div className="form-group">
        <label>Price to View Layout (₹)</label>
        <input
          type="number"
          value={formData.view_price || 0}
          onChange={(e) => handleInputChange('view_price', e.target.value)}
          placeholder="e.g., 100"
          min="0"
          step="0.01"
        />
        <div className="field-help" style={{marginTop: '4px'}}>
          Amount homeowners must pay to view this layout (Optional - set 0 for free)
        </div>
      </div>

      <div className="form-group">
        <label>Cost Breakdown</label>
        {showQuickFill && quickFillOptions.cost_breakdown && (
          <div className="quick-fill-buttons">
            {quickFillOptions.cost_breakdown.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('cost_breakdown', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.cost_breakdown}
          onChange={(e) => handleInputChange('cost_breakdown', e.target.value)}
          placeholder="Provide detailed cost breakdown by category..."
          rows="4"
        />
      </div>

      <div className="form-group">
        <label>Material Costs</label>
        {showQuickFill && quickFillOptions.material_costs && (
          <div className="quick-fill-buttons">
            {quickFillOptions.material_costs.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('material_costs', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.material_costs}
          onChange={(e) => handleInputChange('material_costs', e.target.value)}
          placeholder="Breakdown of material costs (concrete, steel, finishes, etc.)..."
          rows="3"
        />
      </div>

      <div className="form-group">
        <label>Labor Costs</label>
        {showQuickFill && quickFillOptions.labor_costs && (
          <div className="quick-fill-buttons">
            {quickFillOptions.labor_costs.map((option, idx) => (
              <button
                key={idx}
                type="button"
                className="quick-fill-btn"
                onClick={() => applyQuickFill('labor_costs', option)}
              >
                {option}
              </button>
            ))}
          </div>
        )}
        <textarea
          value={formData.labor_costs}
          onChange={(e) => handleInputChange('labor_costs', e.target.value)}
          placeholder="Breakdown of labor costs (masonry, carpentry, electrical, etc.)..."
          rows="3"
        />
      </div>

      {/* Section Navigation */}
      <div className="section-navigation">
        <button type="button" onClick={handlePrev} className="btn btn-secondary">
          ← Previous
        </button>
        <button type="button" onClick={handleSubmit} className="btn btn-primary">
          Next: Files & Submit →
        </button>
      </div>
    </div>
  );

  const renderCurrentSection = () => {
    switch (activeSection) {
      case 'floor_plans':
        return renderFloorPlansSection();
      case 'structural':
        return renderStructuralSection();
      case 'elevations':
        return renderElevationsSection();
      case 'construction':
        return renderConstructionSection();
      case 'systems':
        return renderSystemsSection();
      case 'pricing':
        return renderPricingSection();
      default:
        return renderFloorPlansSection();
    }
  };

  const currentSection = sections.find(s => s.id === activeSection);

  return (
    <div className="technical-details-form-new">
      {/* Header */}
      <div className="technical-header-new">
        <div className="header-left">
          <button className="modal-close-new" onClick={onPrev}>
            ×
          </button>
          <div className="header-info">
            <h2>Technical Design Details</h2>
            <div className="progress-indicator">
              <span className="progress-text">Step 2 of 3</span>
              <div className="progress-bar">
                <div className="progress-fill" style={{ width: '66%' }}></div>
              </div>
            </div>
          </div>
        </div>
        <div className="header-right">
          <div className="template-selector">
            <label>Template:</label>
            <select 
              value={selectedTemplate} 
              onChange={(e) => handleTemplateChange(e.target.value)}
            >
              <option value="">Select a template...</option>
              {templates.map(template => (
                <option key={template.id} value={template.id}>
                  {template.name}
                </option>
              ))}
            </select>
          </div>
          <button
            type="button"
            className="quick-fill-toggle"
            onClick={() => setShowQuickFill(!showQuickFill)}
            style={{
              marginLeft: '12px',
              padding: '8px 16px',
              background: 'rgba(255, 255, 255, 0.2)',
              border: '1px solid rgba(255, 255, 255, 0.3)',
              borderRadius: '6px',
              color: 'white',
              cursor: 'pointer',
              fontSize: '13px',
              fontWeight: '500'
            }}
          >
            {showQuickFill ? '⚡ Quick Fill: ON' : '⚡ Quick Fill: OFF'}
          </button>
        </div>
      </div>

      {/* Main Content */}
      <div className="technical-main-new">
        {/* Sidebar */}
        <div className="technical-sidebar-new">
          <div className="section-nav-new">
            {sections.map((section, index) => (
              <button
                key={section.id}
                className={`section-nav-item-new ${activeSection === section.id ? 'active' : ''}`}
                onClick={() => setActiveSection(section.id)}
              >
                <div className="section-number">{index + 1}</div>
                <div className="section-info">
                  <span className="section-icon-new">{section.icon}</span>
                  <span className="section-title-new">{section.title}</span>
                </div>
              </button>
            ))}
          </div>
        </div>

        {/* Content Area */}
        <div className="technical-content-new">
          <div className="content-header">
            <h3>{currentSection?.title}</h3>
            <p>{currentSection?.description}</p>
          </div>
          <div className="content-body">
            {renderCurrentSection()}
          </div>
        </div>
      </div>

    </div>
  );
};

export default TechnicalDetailsForm;