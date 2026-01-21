import React, { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from './ToastProvider.jsx';
import Stepper from './wizard/Stepper';
import WizardLayout from './wizard/WizardLayout';
import SearchableDropdown from './SearchableDropdown';
import TourGuide from './TourGuide';
import ArchitectSelection from './ArchitectSelection';
import { stateDistricts, keralaPanchayatsMunicipalities } from '../data/locationData';
// Import removed: TimelinePrediction
// Import removed: designRulesEngine


// Helper function to convert budget range to numeric value
const convertBudgetRangeToNumeric = (budgetRange) => {
  const ranges = {
    '5-10 Lakhs': 750000,      // 7.5 lakhs (middle of range)
    '10-20 Lakhs': 1500000,    // 15 lakhs
    '20-30 Lakhs': 2500000,    // 25 lakhs
    '30-50 Lakhs': 4000000,    // 40 lakhs
    '50-75 Lakhs': 6250000,    // 62.5 lakhs
    '75 Lakhs - 1 Crore': 8750000,  // 87.5 lakhs
    '1-2 Crores': 15000000,    // 1.5 crores
    '2-5 Crores': 35000000,    // 3.5 crores
    '5+ Crores': 75000000      // 7.5 crores (assume 5-10 crores)
  };
  return ranges[budgetRange] || 0;
};

// Helper function to get all locations (districts, panchayats, municipalities) from all states
const getAllLocations = () => {
  const allPlaces = [];
  
  // Add all districts from all states
  Object.values(stateDistricts).forEach(districts => {
    if (Array.isArray(districts)) {
      allPlaces.push(...districts);
    }
  });
  
  // Add all Kerala panchayats and municipalities
  if (keralaPanchayatsMunicipalities) {
    Object.values(keralaPanchayatsMunicipalities).forEach(districtData => {
      if (districtData.Municipalities && Array.isArray(districtData.Municipalities)) {
        allPlaces.push(...districtData.Municipalities);
      }
      if (districtData.Panchayats && Array.isArray(districtData.Panchayats)) {
        allPlaces.push(...districtData.Panchayats);
      }
    });
  }
  
  // Remove duplicates and sort
  return [...new Set(allPlaces)].sort();
};

const allLocationOptions = getAllLocations();

export default function HomeownerRequestWizard() {
  const navigate = useNavigate();
  const toast = useToast();
  const steps = ['Preliminary', 'Site', 'Family', 'Preferences', 'Review', 'Architect', 'Submit'];
  const [step, setStep] = useState(0);
  const [data, setData] = useState({
    plot_size: '', building_size: '', plot_shape: '', topography: '', development_laws: '',
    family_needs: [], rooms: [], budget_range: '', aesthetic: '', // Changed to arrays for multi-select
    requirements: '', location: '', timeline: '', num_floors: '',
    selected_layout_id: null, layout_type: 'custom',
    selected_architect_ids: [],
    custom_budget: '', // Added for custom budget input
    plot_unit: 'cents', // Plot unit (cents, acres, sqft)
    floor_rooms: {}, // New: floor-wise room planning { floor1: { bedrooms: 2, bathrooms: 1, ... }, floor2: {...} }
    expandedFloors: { 1: true }, // Track which floors are expanded
    room_images: {}, // New: floor-specific images { floor1: { bedrooms: [images], kitchen: [images] }, floor2: { ... } }
    expandedRoomImages: {}, // Track which room image sections are expanded { floor1: { bedrooms: true }, floor2: { ... } }
    // New sections
    orientation: '', // Site orientation preferences
    site_considerations: '', // Additional site considerations
    material_preferences: [], // Material preferences array
    budget_allocation: '', // Budget allocation preferences
    reference_images: [], // Uploaded reference images
    site_images: [], // Site photos and scans
    room_requirements: {} // Room requirements with counts { bedrooms: 2, bathrooms: 1, ... }
  });

  const [selectedArchitects, setSelectedArchitects] = useState([]); // Store architect objects with names

  const [loading, setLoading] = useState(false);
  const [showTourGuide, setShowTourGuide] = useState(false);
  const [tourStep, setTourStep] = useState(0);
  const prev = () => setStep(s => Math.max(s - 1, 0));

  // Validation & helper state
  const [fieldErrors, setFieldErrors] = useState({});
  const [fieldWarnings, setFieldWarnings] = useState({});

  // Derived heuristics for hints
  const plotCategory = useMemo(() => {
    const size = parseFloat(data.plot_size) || 0;
    if (!size) return null;
    if (size < 1200) return 'small';
    if (size < 2400) return 'medium';
    if (size < 4000) return 'large';
    if (size < 6000) return 'xlarge';
    return 'estate';
  }, [data.plot_size]);

  const numericBudget = useMemo(() => {
    const base = data.budget_range === 'Custom'
      ? parseFloat(data.custom_budget) || 0
      : convertBudgetRangeToNumeric(data.budget_range);
    return Number.isFinite(base) ? base : 0;
  }, [data.budget_range, data.custom_budget]);

  const estimatedCost = useMemo(() => {
    if (numericBudget > 0) return numericBudget;
    const buildSize = parseFloat(data.building_size) || 0;
    const fallbackCostPerSqFt = 2000;
    return buildSize > 0 ? buildSize * fallbackCostPerSqFt : 0;
  }, [numericBudget, data.building_size]);

  const budgetCategory = useMemo(() => {
    if (!numericBudget) return null;
    if (numericBudget < 2000000) return 'economy';
    if (numericBudget < 5000000) return 'standard';
    if (numericBudget < 10000000) return 'premium';
    return 'luxury';
  }, [numericBudget]);

  // Step-wise validation to gate navigation
  const handleNext = () => {
    const errors = {};
    const warnings = {};

    switch (step) {
      case 0: { // Preliminary
        const plotSize = parseFloat(data.plot_size);
        const buildingSize = parseFloat(data.building_size);
        if (!plotSize || Number.isNaN(plotSize) || plotSize <= 0) errors.plot_size = 'Enter a valid plot size';
        if (!buildingSize || Number.isNaN(buildingSize) || buildingSize <= 0) errors.building_size = 'Enter a valid building size';
        if (!data.budget_range) errors.budget_range = 'Select a budget range';
        if (data.budget_range === 'Custom') {
          const cb = parseFloat(data.custom_budget);
          if (!cb || Number.isNaN(cb) || cb <= 0) errors.custom_budget = 'Enter a valid custom budget';
        }
        break;
      }
      case 1: { // Site
        if (!data.plot_shape) errors.plot_shape = 'Select plot shape';
        if (!data.topography) errors.topography = 'Select topography';
        if (!data.development_laws) errors.development_laws = 'Select development laws';
        const floors = parseInt(data.num_floors, 10);
        if (!floors || Number.isNaN(floors) || floors <= 0) errors.num_floors = 'Enter number of floors';
        break;
      }
      case 2: { // Family
        if (!Array.isArray(data.rooms) || data.rooms.length === 0) errors.rooms = 'Select at least one room type';
        break;
      }
      case 3: { // Preferences
        if (!data.aesthetic) errors.aesthetic = 'Choose a house style';
        break;
      }
      case 5: { // Architect selection
        if (!Array.isArray(data.selected_architect_ids) || data.selected_architect_ids.length === 0) {
          errors.selected_architect_ids = 'Select at least one architect';
        }
        break;
      }
      default:
        break;
    }

    setFieldErrors(errors);
    setFieldWarnings(warnings);

    if (Object.keys(errors).length > 0) {
      toast.error('Please fix the highlighted fields before continuing');
      return;
    }

    setStep(s => Math.min(s + 1, steps.length - 1));
  };

  // Lightweight timeline heuristic to avoid undefined timelineData usages
  const timelineData = useMemo(() => {
    const size = parseFloat(data.building_size) || 0;
    const rooms = Array.isArray(data.rooms) ? data.rooms.length : 0;
    if (!size && !rooms) return null;

    const baseMonths = Math.min(18, Math.max(3, Math.round(size / 500) + Math.floor(rooms / 2)));
    const estimatedCompletion = (() => {
      const d = new Date();
      d.setMonth(d.getMonth() + baseMonths);
      return d;
    })();

    const phases = [
      { name: 'Planning', duration: 1 },
      { name: 'Design', duration: 1 },
      { name: 'Construction', duration: Math.max(1, baseMonths - 2) }
    ];

    return { months: baseMonths, estimatedCompletion, phases };
  }, [data.building_size, data.rooms]);

  // Tour guide functions
  const handleTourNext = () => {
    if (tourStep < steps.length - 1) {
      setTourStep(tourStep + 1);
    } else {
      setShowTourGuide(false);
    }
  };

  const handleTourPrev = () => {
    if (tourStep > 0) {
      setTourStep(tourStep - 1);
    }
  };

  const handleTourSkip = () => {
    setShowTourGuide(false);
    // Mark tour as completed in localStorage
    localStorage.setItem('buildhub_tour_completed', 'true');
  };

  const handleTourClose = () => {
    setShowTourGuide(false);
    // Mark tour as completed in localStorage
    localStorage.setItem('buildhub_tour_completed', 'true');
  };

  // Auto-assign rooms to floors based on logical constraints
  const autoAssignRoomsToFloors = (selectedRooms) => {
    const numFloors = parseInt(data.num_floors) || 1;
    const floorAssignments = {};

    // Initialize floor assignments
    for (let i = 1; i <= numFloors; i++) {
      floorAssignments[`floor${i}`] = {};
    }

    // Define room-floor logic
    const roomFloorLogic = {
      // Ground floor only rooms
      garage: [1],
      master_bedroom: [1], // Master bedroom only on ground floor
      kitchen: [1], // Kitchen only on ground floor
      dining_room: [1], // Dining room only on ground floor
      living_room: [1], // Living room only on ground floor
      store_room: [1], // Usually ground floor

      // Can be on both floors
      bedrooms: [1, 2], // Bedrooms can be on both floors
      bathrooms: [1, 2], // Bathrooms can be on both floors
      attached_bathroom: [1, 2], // Attached bathroom can be on both floors

      // Upper floors only (not ground floor)
      balcony: numFloors > 1 ? [2] : [1], // Balcony not on ground floor
      terrace: [numFloors], // Terrace always on top floor

      // Other rooms
      study_room: numFloors > 1 ? [2] : [1],
      prayer_room: numFloors > 1 ? [2] : [1],
      guest_room: numFloors > 1 ? [2] : [1]
    };

    // Assign rooms to floors
    selectedRooms.forEach(roomType => {
      const allowedFloors = roomFloorLogic[roomType] || [1];
      const assignedFloor = allowedFloors[0]; // Use first allowed floor
      const floorKey = `floor${assignedFloor}`;

      if (!floorAssignments[floorKey][roomType]) {
        floorAssignments[floorKey][roomType] = 1; // Default count of 1
      }
    });

    return floorAssignments;
  };



  // Check if this is the first time user and show tour
  useEffect(() => {
    const tourCompleted = localStorage.getItem('buildhub_tour_completed');
    if (!tourCompleted) {
      // Show tour after a short delay to let the page load
      const timer = setTimeout(() => {
        setShowTourGuide(true);
        setTourStep(0);
      }, 1000);
      return () => clearTimeout(timer);
    }
  }, []);

  // Add keyboard shortcut for help (Ctrl + H)
  useEffect(() => {
    const handleKeyPress = (e) => {
      if (e.ctrlKey && e.key === 'h') {
        e.preventDefault();
        setShowTourGuide(true);
        setTourStep(step);
      }
    };

    window.addEventListener('keydown', handleKeyPress);
    return () => window.removeEventListener('keydown', handleKeyPress);
  }, [step]);

  // Room types definition - moved to component level for accessibility
  const roomTypes = [
    { key: 'master_bedroom', label: 'Master Bedroom', icon: '👑', short: 'MB', max: 1, floorRestriction: 'each' }, // Only 1 per floor
    { key: 'bedrooms', label: 'Bedrooms', icon: '🛏️', short: 'BR', max: 8 },
    { key: 'attached_bathrooms', label: 'Attached Bathrooms', icon: '🚿', short: 'AB', max: 8 },
    { key: 'common_bathrooms', label: 'Common Bathrooms', icon: '🚽', short: 'CB', max: 6 },
    { key: 'living_room', label: 'Living Room', icon: '🛋️', short: 'LR', max: 3 },
    { key: 'dining_room', label: 'Dining Room', icon: '🍽️', short: 'DR', max: 2 },
    { key: 'kitchen', label: 'Kitchen', icon: '🍳', short: 'K', max: 2, floorRestriction: 'ground' }, // Only on ground floor
    { key: 'study_area', label: 'Study Area', icon: '📚', short: 'SA', max: 3 },
    { key: 'prayer_area', label: 'Prayer Area', icon: '🕉️', short: 'PA', max: 2 },
    { key: 'guest_room', label: 'Guest Room', icon: '🏠', short: 'GR', max: 3 },
    { key: 'store_room', label: 'Store Room', icon: '📦', short: 'STR', max: 4, floorRestriction: 'ground' }, // Only on ground floor
    { key: 'balcony', label: 'Balcony', icon: '🌅', short: 'B', max: 5, excludeFromGroundFloor: true },
    { key: 'terrace', label: 'Terrace', icon: '🏞️', short: 'T', max: 2, excludeFromGroundFloor: true },
    { key: 'garage', label: 'Garage', icon: '🚗', short: 'G', max: 3, floorRestriction: 'ground' }, // Only on ground floor
    { key: 'utility_area', label: 'Utility Area', icon: '🔧', short: 'UA', max: 2 }
  ];

  // Architect directory state (reusing existing backend endpoints/logic)
  const [architects, setArchitects] = useState([]);
  const [archLoading, setArchLoading] = useState(false);
  const [archError, setArchError] = useState('');
  const [archSearch, setArchSearch] = useState('');
  const [archSpec, setArchSpec] = useState('');
  const [archMinExp, setArchMinExp] = useState('');
  const [sortKey, setSortKey] = useState('best');
  // Expanded details and reviews for selected architect
  const [expandedArchitectId, setExpandedArchitectId] = useState(null);
  const [reviewsCache, setReviewsCache] = useState({}); // { [architectId]: { reviews, avg_rating, review_count } }
  const [reviewsLoading, setReviewsLoading] = useState(false);
  // Active floor tab for family planning
  const [activeFloorTab, setActiveFloorTab] = useState(1);

  // Prefill from URL query params when applicable (from library or deep link)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const selected_layout_id = params.get('selected_layout_id');
    const layout_type = params.get('layout_type');
    const plot_size = params.get('plot_size');
    const action = params.get('action');
    setData(prev => ({
      ...prev,
      ...(selected_layout_id ? { selected_layout_id } : {}),
      ...(layout_type ? { layout_type } : {}),
      ...(plot_size ? { plot_size } : {})
    }));

    // If deep-linked with action=send_to_contractors and a library layout is present,
    // auto-trigger the fast-path submission to contractors.
    if (action === 'send_to_contractors' && layout_type === 'library' && selected_layout_id) {
      (async () => {
        setLoading(true);
        try {
          const res = await fetch('/buildhub/backend/api/homeowner/submit_request.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
              plot_size: plot_size || '',
              budget_range: '',
              requirements: '',
              location: '',
              timeline: '',
              selected_layout_id,
              layout_type: 'library',
              // packed fields (empty for quick send)
              plot_shape: '', topography: '', development_laws: '',
              family_needs: '', rooms: '', aesthetic: '',
              // activate for contractors
              activate_for_contractors: true
            })
          });
          const j = await res.json();
          if (j.success) {
            window.dispatchEvent && window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Sent to contractors' } }));
            window.history.back();
          } else {
            console.error('Failed to send to contractors:', j.message);
            alert(j.message || 'Failed to send to contractors');
          }
        } catch (_) {
          console.error('Network error');
          alert('Network error');
        }
        finally { setLoading(false); }
      })();
    }
  }, []);

  const sortedArchitects = useMemo(() => {
    const list = [...architects];
    if (sortKey === 'best') {
      list.sort((a, b) => {
        const ar = (a.avg_rating ?? -1);
        const br = (b.avg_rating ?? -1);
        if (br !== ar) return br - ar;
        const ac = (a.review_count ?? 0);
        const bc = (b.review_count ?? 0);
        return bc - ac;
      });
    } else if (sortKey === 'experience') {
      list.sort((a, b) => (b.experience_years ?? -1) - (a.experience_years ?? -1));
    } else if (sortKey === 'recent') {
      list.sort((a, b) => b.id - a.id);
    }
    return list;
  }, [architects, sortKey]);

  const getInitials = (first, last) => {
    const f = (first || '').trim().charAt(0) || '';
    const l = (last || '').trim().charAt(0) || '';
    const init = (f + l).toUpperCase();
    return init || 'A';
  };

  const toggleArchitectDetails = async (architectId) => {
    if (expandedArchitectId === architectId) {
      setExpandedArchitectId(null);
      return;
    }
    setExpandedArchitectId(architectId);
    if (!reviewsCache[architectId]) {
      setReviewsLoading(true);
      try {
        const res = await fetch(`/buildhub/backend/api/reviews/get_reviews.php?architect_id=${architectId}`);
        const json = await res.json();
        if (json.success) {
          setReviewsCache(prev => ({ ...prev, [architectId]: json }));
        }
      } catch (_) {
        // ignore
      } finally {
        setReviewsLoading(false);
      }
    }
  };

  const renderStars = (n) => {
    const rating = Math.max(0, Math.min(5, Number(n) || 0));
    const filled = '★'.repeat(rating);
    const empty = '☆'.repeat(5 - rating);
    return filled + empty;
  };

  const fetchArchitects = async (params = {}) => {
    setArchLoading(true);
    setArchError('');
    try {
      const queryParams = new URLSearchParams();

      if (params.search && params.search.trim()) {
        queryParams.append('search', params.search.trim());
      }
      if (params.specialization && params.specialization.trim()) {
        queryParams.append('specialization', params.specialization.trim());
      }
      if (params.min_experience && params.min_experience > 0) {
        queryParams.append('min_experience', params.min_experience);
      }

      const queryString = queryParams.toString();
      const url = `/buildhub/backend/api/homeowner/get_architects.php${queryString ? `?${queryString}` : ''}`;

      console.log('Fetching architects from:', url); // Debug log

      const res = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include'
      });

      console.log('Response status:', res.status); // Debug log

      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      const json = await res.json();
      console.log('Architects response:', json); // Debug log

      if (json.success) {
        setArchitects(json.architects || []);
      } else {
        setArchError(json.message || 'Failed to load architects');
      }
    } catch (e) {
      console.error('Error fetching architects:', e); // Debug log
      setArchError('Error loading architects: ' + e.message);
    }
    finally {
      setArchLoading(false);
    }
  };

  // Auto-load architects when entering the Architect step
  useEffect(() => {
    if (step === 5) fetchArchitects({ search: archSearch, specialization: archSpec, min_experience: archMinExp });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [step]);

  // Debounced search effect
  useEffect(() => {
    if (step === 5) {
      const timeoutId = setTimeout(() => {
        fetchArchitects({ search: archSearch, specialization: archSpec, min_experience: archMinExp });
      }, 500); // 500ms delay

      return () => clearTimeout(timeoutId);
    }
  }, [archSearch, archSpec, archMinExp, step]);

  async function submit() {
    setLoading(true);
    try {
      // Validate that at least one architect is selected
      if (!Array.isArray(data.selected_architect_ids) || data.selected_architect_ids.length === 0) {
        toast.error('Please select at least one architect before submitting your request');
        return;
      }

      // 1) Create the layout request
      const submitData = {
        plot_size: data.plot_size,
        building_size: data.building_size,
        budget_range: data.budget_range === 'Custom' ? data.custom_budget : data.budget_range,
        requirements: data.requirements,
        location: data.location,
        timeline: data.timeline,
        selected_layout_id: data.selected_layout_id,
        layout_type: data.layout_type,
        // packed structured fields used by backend
        plot_shape: data.plot_shape,
        topography: data.topography,
        development_laws: data.development_laws,
        family_needs: Array.isArray(data.family_needs) ? data.family_needs.join(',') : data.family_needs,
        rooms: Array.isArray(data.rooms) ? data.rooms.join(',') : data.rooms,
        aesthetic: data.aesthetic,
        num_floors: data.num_floors,
        preferred_style: data.aesthetic, // Use aesthetic as preferred_style
        floor_rooms: data.floor_rooms || {}, // Floor-wise room distribution
        reference_images: data.reference_images || [],
        site_images: data.site_images || [], // Site images
        room_images: data.room_images || {}, // Room-specific images
      };

      console.log('Submitting data:', submitData); // Debug log
      console.log('🏗️ Floor rooms data being submitted:', data.floor_rooms);

      // Validate required fields
      if (!submitData.plot_size || !submitData.building_size) {
        toast.error('Please fill in plot size and building size');
        return;
      }

      // Validate numeric fields
      if (isNaN(parseFloat(submitData.plot_size)) || isNaN(parseFloat(submitData.building_size))) {
        toast.error('Plot size and building size must be valid numbers');
        return;
      }

      console.log('🚀 Submitting request to:', '/buildhub/backend/api/homeowner/submit_request.php');
      const res = await fetch('/buildhub/backend/api/homeowner/submit_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(submitData),
        credentials: 'include'
      });

      console.log('📡 Response status:', res.status);
      console.log('📡 Response ok:', res.ok);

      if (!res.ok) {
        const errorText = await res.text();
        console.error('❌ HTTP error response:', errorText);
        throw new Error(`HTTP error! status: ${res.status} - ${errorText}`);
      }

      const json = await res.json();
      console.log('📊 Submit response:', json);

      if (!json.success) {
        console.error('❌ Submit failed:', json.message);
        toast.error(json.message || 'Failed to submit');
        return;
      }

      const requestId = json.request_id;

      // 2) Send architect assignment(s) - validated earlier that architects are selected
      console.log('📤 Assigning architects:', data.selected_architect_ids);
      try {
        console.log('🚀 Submitting architect assignment to:', '/buildhub/backend/api/homeowner/assign_architect.php');
        const ares = await fetch('/buildhub/backend/api/homeowner/assign_architect.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            layout_request_id: requestId,
            architect_ids: data.selected_architect_ids
          }),
          credentials: 'include'
        });

        console.log('📡 Architect assignment response status:', ares.status);
        console.log('📡 Architect assignment response ok:', ares.ok);

        if (!ares.ok) {
          const errorText = await ares.text();
          console.error('❌ Architect assignment HTTP error:', errorText);
          throw new Error(`HTTP error! status: ${ares.status} - ${errorText}`);
        }

        const aj = await ares.json();
        console.log('📊 Architect assignment response:', aj);

        if (aj.success) {
          console.log('✅ Architects assigned successfully');
        } else {
          console.warn('⚠️ Architect assignment warning:', aj.message);
          // Don't fail the entire submission for architect assignment issues
        }
      } catch (e) {
        console.warn('⚠️ Architect assignment failed:', e.message);
        // Don't fail the entire submission for architect assignment issues
      }

      // Show success message
      toast.success('Request submitted successfully! Your custom design request has been created and assigned to the selected architects.');

      // Redirect to homeowner dashboard using React Router
      navigate('/homeowner-dashboard');
    } catch (e) {
      console.error('Submit error:', e);
      toast.error('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  const header = useMemo(() => ({ title: 'File your Request', subtitle: 'Approx 5–7 minutes' }), []);

  // Helpers
  const toggleArchitect = (id) => {
    setData(prev => {
      const list = new Set(prev.selected_architect_ids || []);
      if (list.has(id)) list.delete(id); else list.add(id);
      return { ...prev, selected_architect_ids: Array.from(list) };
    });
  };



  const renderFieldErrors = () => {
    if (Object.keys(fieldErrors).length === 0) return null;

    return (
      <div className="validation-errors" style={{
        backgroundColor: '#f8d7da',
        border: '1px solid #f5c6cb',
        borderRadius: '5px',
        padding: '15px',
        margin: '15px 0',
        color: '#721c24'
      }}>
        <strong>❌ Errors:</strong>
        <ul style={{ margin: '10px 0 0 20px' }}>
          {Object.entries(fieldErrors).map(([field, error]) => (
            <li key={field}>{error}</li>
          ))}
        </ul>
      </div>
    );
  };

  const renderFieldWarnings = () => {
    if (Object.keys(fieldWarnings).length === 0) return null;

    return (
      <div className="validation-warnings" style={{
        backgroundColor: '#fff3cd',
        border: '1px solid #ffeaa7',
        borderRadius: '5px',
        padding: '15px',
        margin: '15px 0',
        color: '#856404'
      }}>
        <h4 style={{ marginTop: 0, marginBottom: '10px' }}>Suggestions</h4>
        <ul style={{ marginBottom: 0, paddingLeft: '20px' }}>
          {Object.entries(fieldWarnings).map(([field, warning]) => (
            <li key={field}>{warning}</li>
          ))}
        </ul>
      </div>
    );
  };




  return (
    <WizardLayout
      title={header.title}
      subtitle={header.subtitle}
      stepper={<Stepper steps={steps} current={step} />}
      onBack={step > 0 ? prev : () => window.history.back()}
      onClose={() => window.history.back()}
    >
      {/* Step content */}
      {step === 0 && (
        <div className="section">
          <div className="section-header">Preliminary</div>




          <div className="section-body" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '20px' }}>
            <div className="field" style={{ gridColumn: '1 / -1' }}>
              <label style={{ fontSize: '18px', fontWeight: '600', color: '#1f2937', marginBottom: '8px', display: 'block' }}>
                📏 Plot Size
              </label>
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
                Enter your plot size to get accurate budget and timeline estimates
              </div>
              <div style={{ display: 'flex', gap: '8px', alignItems: 'center', backgroundColor: '#f9fafb', padding: '20px', borderRadius: '12px', border: '2px solid #e5e7eb' }}>
                <input
                  type="number"
                  value={data.plot_size}
                  onChange={e => {
                    const newValue = e.target.value;
                    setData(prev => ({ ...prev, plot_size: newValue }));
                  }}
                  min={1}
                  placeholder="Enter plot size (e.g., 10 for 10 cents)"
                  style={{
                    flex: 1,
                    padding: '18px 24px',
                    fontSize: '20px',
                    fontWeight: '600',
                    border: '2px solid #d1d5db',
                    borderRadius: '8px',
                    backgroundColor: 'white',
                    boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
                  }}
                />
                <select
                  value={data.plot_unit || 'cents'}
                  onChange={e => setData(prev => ({ ...prev, plot_unit: e.target.value }))}
                  style={{
                    padding: '12px 8px',
                    fontSize: '14px',
                    fontWeight: '500',
                    border: '2px solid #e5e7eb',
                    borderRadius: '8px',
                    minWidth: '80px',
                    maxWidth: '90px',
                    backgroundColor: 'white'
                  }}
                >
                  <option value="cents">Cents</option>
                  <option value="acres">Acres</option>
                  <option value="sqft">Sq Ft</option>
                </select>
              </div>
              <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '8px' }}>
                {data.plot_size && data.plot_unit && (
                  <span>
                    {data.plot_unit === 'cents' && `${data.plot_size} cents = ${(data.plot_size * 435.6).toFixed(0)} sq ft`}
                    {data.plot_unit === 'acres' && `${data.plot_size} acres = ${(data.plot_size * 43560).toFixed(0)} sq ft`}
                    {data.plot_unit === 'sqft' && `${data.plot_size} sq ft`}
                  </span>
                )}
              </div>
            </div>
            <div className="field" style={{ gridColumn: '1 / -1' }}>
              <label style={{ fontSize: '18px', fontWeight: '600', color: '#1f2937', marginBottom: '8px', display: 'block' }}>
                🏠 Building Size
              </label>
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
                Enter your desired building size in square feet
              </div>
              <div style={{ backgroundColor: '#f9fafb', padding: '20px', borderRadius: '12px', border: '2px solid #e5e7eb' }}>
                <input
                  type="number"
                  value={data.building_size}
                  onChange={e => {
                    const newValue = e.target.value;
                    setData(prev => ({ ...prev, building_size: newValue }));
                  }}
                  min={100}
                  placeholder="Enter building size in square feet (e.g., 2000)"
                  style={{
                    width: '100%',
                    padding: '16px 20px',
                    fontSize: '18px',
                    fontWeight: '500',
                    border: '2px solid #d1d5db',
                    borderRadius: '8px',
                    backgroundColor: 'white',
                    boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
                  }}
                />
              </div>
            </div>
            <div className="field" style={{ gridColumn: '1 / -1' }}>
              <label style={{ fontSize: '18px', fontWeight: '600', color: '#1f2937', marginBottom: '8px', display: 'block' }}>
                💰 Budget Range
              </label>
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
                Select your budget range for the project
              </div>
              <select
                value={data.budget_range}
                onChange={e => setData({ ...data, budget_range: e.target.value })}
                style={{
                  width: '100%',
                  padding: '16px 20px',
                  fontSize: '18px',
                  fontWeight: '500',
                  border: '2px solid #e5e7eb',
                  borderRadius: '8px',
                  backgroundColor: 'white',
                  boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
                }}
              >
                <option value="">Select budget range</option>
                <option value="5-10 Lakhs">₹5-10 Lakhs</option>
                <option value="10-20 Lakhs">₹10-20 Lakhs</option>
                <option value="20-30 Lakhs">₹20-30 Lakhs</option>
                <option value="30-50 Lakhs">₹30-50 Lakhs</option>
                <option value="50-75 Lakhs">₹50-75 Lakhs</option>
                <option value="75 Lakhs - 1 Crore">₹75 Lakhs - 1 Crore</option>
                <option value="1-2 Crores">₹1-2 Crores</option>
                <option value="2-5 Crores">₹2-5 Crores</option>
                <option value="5+ Crores">₹5+ Crores</option>
                <option value="Custom">Custom Amount</option>
              </select>

              {data.budget_range === 'Custom' && (
                <div style={{ marginTop: '16px' }}>
                  <input
                    type="number"
                    value={data.custom_budget}
                    onChange={e => setData({ ...data, custom_budget: e.target.value })}
                    placeholder="Enter custom budget amount in rupees"
                    style={{
                      width: '100%',
                      padding: '16px 20px',
                      fontSize: '18px',
                      fontWeight: '500',
                      border: '2px solid #e5e7eb',
                      borderRadius: '8px',
                      backgroundColor: 'white',
                      boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
                    }}
                  />
                </div>
              )}
            </div>
            <div className="field">
              <label style={{ fontSize: '18px', fontWeight: '600', color: '#1f2937', marginBottom: '8px', display: 'block' }}>
                📍 Location
              </label>
              <div style={{ fontSize: '14px', color: '#6b7280', marginBottom: '12px' }}>
                Select your district/location
              </div>
              <SearchableDropdown
                options={allLocationOptions}
                value={data.location}
                onChange={(value) => setData({ ...data, location: value })}
                placeholder="Select or type your district"
                style={{ width: '100%', padding: '16px 20px', fontSize: '16px', border: '2px solid #e5e7eb', borderRadius: '8px', backgroundColor: 'white', boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)' }}
              />
            </div>
            <div className="field">
              <label>Timeline</label>
              <select
                value={data.timeline}
                onChange={e => setData({ ...data, timeline: e.target.value })}
                style={{ width: '100%', padding: '12px 16px', fontSize: '16px', border: '2px solid #e5e7eb', borderRadius: '8px' }}
              >
                <option value="">Select timeline</option>
                <option value="1-3 months">1-3 months</option>
                <option value="3-6 months">3-6 months</option>
                <option value="6-12 months">6-12 months</option>
                <option value="1-2 years">1-2 years</option>
                <option value="2+ years">2+ years</option>
                <option value="Flexible">Flexible</option>
              </select>
            </div>

          </div>

          <div className="wizard-footer">
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 1 && (
        <div className="section">
          <div className="section-header">Site Details</div>
          <div className="section-body" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '20px' }}>
            <div className="field">
              <label>Plot Shape</label>
              <select
                value={data.plot_shape}
                onChange={e => setData({ ...data, plot_shape: e.target.value })}
                style={{ width: '100%', padding: '12px 16px', fontSize: '16px', border: '2px solid #e5e7eb', borderRadius: '8px' }}
              >
                <option value="">Select plot shape</option>
                <option value="Rectangular">Rectangular</option>
                <option value="Square">Square</option>
                <option value="L-shaped">L-shaped</option>
                <option value="Irregular">Irregular</option>
                <option value="Corner">Corner</option>
              </select>
            </div>
            <div className="field">
              <label>Topography</label>
              <select
                value={data.topography}
                onChange={e => setData({ ...data, topography: e.target.value })}
                style={{ width: '100%', padding: '12px 16px', fontSize: '16px', border: '2px solid #e5e7eb', borderRadius: '8px' }}
              >
                <option value="">Select topography</option>
                <option value="Flat">Flat</option>
                <option value="Sloping">Sloping</option>
                <option value="Hilly">Hilly</option>
                <option value="Waterfront">Waterfront</option>
              </select>
            </div>
            <div className="field">
              <label>Number of Floors</label>
              <select
                value={data.num_floors}
                onChange={e => {
                  const newNumFloors = e.target.value;
                  // Reassign rooms to floors when number of floors changes
                  const currentRooms = Array.isArray(data.rooms) ? data.rooms : [];
                  const newFloorAssignments = autoAssignRoomsToFloors(currentRooms);

                  setData({
                    ...data,
                    num_floors: newNumFloors,
                    floor_rooms: newFloorAssignments
                  });
                }}
                style={{
                  width: '100%',
                  padding: '12px 16px',
                  fontSize: '16px',
                  border: '2px solid #e5e7eb',
                  borderRadius: '8px'
                }}
              >
                <option value="">Select number of floors</option>
                <option value="1">1 Floor (Ground Floor Only)</option>
                <option value="2">2 Floors (G+1)</option>
                <option value="3">3 Floors (G+2)</option>
                <option value="4">4 Floors (G+3)</option>
                <option value="5">5 Floors (G+4)</option>
                <option value="6+">6+ Floors</option>
              </select>


            </div>
            <div className="field">
              <label>Development Laws</label>
              <select
                value={data.development_laws}
                onChange={e => setData({ ...data, development_laws: e.target.value })}
                style={{ width: '100%', padding: '12px 16px', fontSize: '16px', border: '2px solid #e5e7eb', borderRadius: '8px' }}
              >
                <option value="">Select development laws</option>
                <option value="Standard">Standard</option>
                <option value="Restricted">Restricted</option>
                <option value="Heritage">Heritage</option>
                <option value="Coastal">Coastal</option>
                <option value="Hill Station">Hill Station</option>
              </select>
            </div>
          </div>



          {/* Validation Summary for Site Details Page */}
          {(fieldErrors.num_floors || fieldWarnings.num_floors) && (
            <div style={{
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              padding: '20px',
              marginBottom: '20px'
            }}>
              <h3 style={{ margin: '0 0 16px 0', color: '#1e293b', fontSize: '18px' }}>
                🏗️ Site Details Validation
              </h3>

              {fieldErrors.num_floors && (
                <div style={{ marginBottom: '8px', padding: '8px', backgroundColor: '#fef2f2', border: '1px solid #fecaca', borderRadius: '4px', color: '#dc2626' }}>
                  ❌ Number of Floors: {fieldErrors.num_floors}
                </div>
              )}
              {fieldWarnings.num_floors && (
                <div style={{ marginBottom: '8px', padding: '8px', backgroundColor: '#fffbeb', border: '1px solid #fed7aa', borderRadius: '4px', color: '#d97706' }}>
                  ⚠️ Number of Floors: {fieldWarnings.num_floors}
                </div>
              )}
            </div>
          )}

          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 2 && (
        <div className="section">
          <div className="section-header">Family Needs</div>
          <div className="section-body">
            <div className="field">
              <label style={{
                fontSize: '20px',
                fontWeight: '700',
                color: '#1f2937',
                marginBottom: '24px',
                display: 'block',
                textAlign: 'center'
              }}>
                🏠 Select Room Requirements
              </label>
              <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                gap: '16px',
                padding: '20px',
                backgroundColor: '#f8fafc',
                borderRadius: '16px',
                border: '2px solid #e2e8f0',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
              }}>
                {[
                  { key: 'master_bedroom', label: 'Master Bedroom', icon: '👑', category: 'Essential' },
                  { key: 'bedrooms', label: 'Bedrooms', icon: '🛏️', category: 'Essential' },
                  { key: 'bathrooms', label: 'Bathrooms', icon: '🚿', category: 'Essential' },
                  { key: 'attached_bathroom', label: 'Attached Bathroom', icon: '🚿', category: 'Luxury' },
                  { key: 'kitchen', label: 'Kitchen', icon: '🍳', category: 'Essential' },
                  { key: 'living_room', label: 'Living Room', icon: '🛋️', category: 'Essential' },
                  { key: 'dining_room', label: 'Dining Room', icon: '🍽️', category: 'Essential' },
                  { key: 'study_room', label: 'Study Room', icon: '📚', category: 'Optional' },
                  { key: 'prayer_room', label: 'Prayer Room', icon: '🕉️', category: 'Optional' },
                  { key: 'guest_room', label: 'Guest Room', icon: '🏠', category: 'Optional' },
                  { key: 'store_room', label: 'Store Room', icon: '📦', category: 'Utility' },
                  { key: 'balcony', label: 'Balcony', icon: '🌅', category: 'Luxury' },
                  { key: 'terrace', label: 'Terrace', icon: '🏞️', category: 'Luxury' },
                  { key: 'garage', label: 'Garage', icon: '🚗', category: 'Utility' }
                ].map(option => {
                  const isSelected = Array.isArray(data.rooms) && data.rooms.includes(option.key);

                  return (
                    <button
                      key={option.key}
                      type="button"
                      onClick={() => {
                        const currentRooms = Array.isArray(data.rooms) ? data.rooms : [];
                        const newRooms = currentRooms.includes(option.key)
                          ? currentRooms.filter(item => item !== option.key)
                          : [...currentRooms, option.key];

                        // Auto-assign rooms to floors based on logical constraints
                        const newFloorAssignments = autoAssignRoomsToFloors(newRooms);

                        setData({
                          ...data,
                          rooms: newRooms,
                          floor_rooms: newFloorAssignments
                        });
                      }}
                      style={{
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '20px 16px',
                        backgroundColor: isSelected ? '#dbeafe' : 'white',
                        border: isSelected ? '3px solid #3b82f6' : '2px solid #e5e7eb',
                        borderRadius: '12px',
                        cursor: 'pointer',
                        transition: 'all 0.3s ease',
                        position: 'relative',
                        minHeight: '120px',
                        boxShadow: isSelected ? '0 8px 25px -5px rgba(59, 130, 246, 0.3)' : '0 2px 4px rgba(0, 0, 0, 0.1)',
                        transform: isSelected ? 'translateY(-2px)' : 'translateY(0)',
                        fontSize: '16px',
                        fontWeight: '600'
                      }}
                      onMouseEnter={(e) => {
                        if (!isSelected) {
                          e.target.style.backgroundColor = '#f1f5f9';
                          e.target.style.borderColor = '#94a3b8';
                          e.target.style.transform = 'translateY(-1px)';
                        }
                      }}
                      onMouseLeave={(e) => {
                        if (!isSelected) {
                          e.target.style.backgroundColor = 'white';
                          e.target.style.borderColor = '#e5e7eb';
                          e.target.style.transform = 'translateY(0)';
                        }
                      }}
                    >
                      {/* Category Badge */}
                      <div style={{
                        position: 'absolute',
                        top: '8px',
                        right: '8px',
                        fontSize: '10px',
                        fontWeight: '700',
                        padding: '2px 6px',
                        borderRadius: '4px',
                        backgroundColor: option.category === 'Essential' ? '#dcfce7' :
                          option.category === 'Luxury' ? '#fef3c7' :
                            option.category === 'Utility' ? '#e0e7ff' : '#f3f4f6',
                        color: option.category === 'Essential' ? '#166534' :
                          option.category === 'Luxury' ? '#92400e' :
                            option.category === 'Utility' ? '#3730a3' : '#6b7280',
                        border: '1px solid',
                        borderColor: option.category === 'Essential' ? '#bbf7d0' :
                          option.category === 'Luxury' ? '#fde68a' :
                            option.category === 'Utility' ? '#c7d2fe' : '#d1d5db'
                      }}>
                        {option.category}
                      </div>

                      {/* Icon */}
                      <div style={{
                        fontSize: '32px',
                        marginBottom: '8px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                      }}>
                        {option.icon}
                      </div>

                      {/* Label */}
                      <div style={{
                        fontSize: '14px',
                        fontWeight: '600',
                        color: '#374151',
                        textAlign: 'center',
                        lineHeight: '1.3',
                        marginBottom: '4px'
                      }}>
                        {option.label}
                      </div>

                      {/* Selection Indicator */}
                      {isSelected && (
                        <div style={{
                          position: 'absolute',
                          top: '8px',
                          left: '8px',
                          width: '24px',
                          height: '24px',
                          backgroundColor: '#3b82f6',
                          borderRadius: '50%',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          color: 'white',
                          fontSize: '14px',
                          fontWeight: '700',
                          boxShadow: '0 2px 4px rgba(59, 130, 246, 0.3)'
                        }}>
                          ✓
                        </div>
                      )}
                    </button>
                  );
                })}
              </div>

              {/* Selection Summary */}
              {Array.isArray(data.rooms) && data.rooms.length > 0 && (
                <div style={{
                  marginTop: '20px',
                  padding: '16px',
                  backgroundColor: '#f0f9ff',
                  border: '2px solid #0ea5e9',
                  borderRadius: '12px',
                  textAlign: 'center'
                }}>
                  <div style={{
                    fontSize: '16px',
                    fontWeight: '600',
                    color: '#0c4a6e',
                    marginBottom: '8px'
                  }}>
                    ✅ {data.rooms.length} Room{data.rooms.length !== 1 ? 's' : ''} Selected
                  </div>
                  <div style={{
                    fontSize: '14px',
                    color: '#0369a1',
                    display: 'flex',
                    flexWrap: 'wrap',
                    gap: '8px',
                    justifyContent: 'center'
                  }}>
                    {data.rooms.map(room => (
                      <span key={room} style={{
                        backgroundColor: '#bae6fd',
                        padding: '4px 8px',
                        borderRadius: '6px',
                        fontSize: '12px',
                        fontWeight: '500'
                      }}>
                        {room.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                      </span>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Floor-wise Room Planning - Show when floors are selected */}
            {data.num_floors && parseInt(data.num_floors) >= 1 && (
              <div style={{
                marginTop: '32px',
                padding: '24px',
                backgroundColor: '#ffffff',
                border: '2px solid #e2e8f0',
                borderRadius: '16px',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
              }}>
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  marginBottom: '24px',
                  paddingBottom: '16px',
                  borderBottom: '2px solid #f1f5f9'
                }}>
                  <h4 style={{
                    margin: 0,
                    fontSize: '20px',
                    fontWeight: '700',
                    color: '#1f2937',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px'
                  }}>
                    🏗️ Floor-wise Room Planning
                  </h4>
                  <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                    <span style={{
                      fontSize: '14px',
                      color: '#059669',
                      backgroundColor: '#f0fdf4',
                      padding: '8px 12px',
                      borderRadius: '8px',
                      fontWeight: '600',
                      border: '1px solid #bbf7d0'
                    }}>
                      📊 {data.num_floors} Floor{data.num_floors !== '1' ? 's' : ''} Total
                    </span>
                    {(() => {
                      const totalImages = Array.from({ length: parseInt(data.num_floors) || 0 }, (_, index) => {
                        const floorKey = `floor${index + 1}`;
                        const floorImages = data.room_images?.[floorKey] || {};
                        return Object.values(floorImages).reduce((sum, roomImages) => sum + (roomImages?.length || 0), 0);
                      }).reduce((sum, count) => sum + count, 0);

                      return totalImages > 0 ? (
                        <span style={{
                          fontSize: '12px',
                          color: '#059669',
                          backgroundColor: '#f0fdf4',
                          padding: '4px 8px',
                          borderRadius: '4px',
                          border: '1px solid #bbf7d0'
                        }}>
                          📷 {totalImages} Images
                        </span>
                      ) : null;
                    })()}
                  </div>
                </div>

                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(350px, 1fr))',
                  gap: '20px'
                }}>
                  {Array.from({ length: parseInt(data.num_floors) || 0 }, (_, index) => {
                    const floorNumber = index + 1;
                    const floorKey = `floor${floorNumber}`;
                    const isExpanded = data.expandedFloors?.[floorNumber] || (floorNumber === 1);

                    return (
                      <div key={floorKey} style={{
                        border: '2px solid #e5e7eb',
                        borderRadius: '12px',
                        padding: '20px',
                        backgroundColor: isExpanded ? '#f8fafc' : '#ffffff',
                        boxShadow: isExpanded ? '0 4px 6px -1px rgba(0, 0, 0, 0.1)' : '0 1px 3px rgba(0, 0, 0, 0.1)',
                        transition: 'all 0.3s ease'
                      }}>
                        <div
                          style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            cursor: 'pointer',
                            marginBottom: isExpanded ? '20px' : '0',
                            padding: '12px',
                            backgroundColor: floorNumber === 1 ? '#dbeafe' : '#f0fdf4',
                            borderRadius: '8px',
                            border: `2px solid ${floorNumber === 1 ? '#3b82f6' : '#10b981'}`
                          }}
                          onClick={() => setData(prev => ({
                            ...prev,
                            expandedFloors: {
                              ...prev.expandedFloors,
                              [floorNumber]: !isExpanded
                            }
                          }))}
                        >
                          <h5 style={{
                            margin: 0,
                            fontSize: '16px',
                            fontWeight: '700',
                            color: floorNumber === 1 ? '#1e40af' : '#047857',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                          }}>
                            {floorNumber === 1 ? '🏠 Ground Floor' : `🏢 Floor ${floorNumber}`}
                          </h5>
                          <span style={{
                            fontSize: '18px',
                            color: floorNumber === 1 ? '#1e40af' : '#047857',
                            fontWeight: '700'
                          }}>
                            {isExpanded ? '▼' : '▶'}
                          </span>
                        </div>

                        {isExpanded && (
                          <div style={{ display: 'grid', gap: '12px' }}>
                            {/* Room Count Inputs with Individual Image Uploads */}
                            {data.rooms && data.rooms.length > 0 ? (
                              data.rooms.map(roomType => {
                                const roomKey = roomType;
                                const currentCount = data.floor_rooms?.[floorKey]?.[roomKey] || 0;
                                const roomImages = data.room_images?.[floorKey]?.[roomKey] || [];
                                const isRoomExpanded = data.expandedRoomImages?.[floorKey]?.[roomKey] || false;

                                return (
                                  <div key={roomType} style={{
                                    border: '1px solid #e5e7eb',
                                    borderRadius: '6px',
                                    padding: '8px',
                                    backgroundColor: '#ffffff',
                                    marginBottom: '8px'
                                  }}>
                                    {/* Room Header with Count Controls */}
                                    <div style={{
                                      display: 'flex',
                                      justifyContent: 'space-between',
                                      alignItems: 'center',
                                      marginBottom: '8px'
                                    }}>
                                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                        <span style={{ fontSize: '16px' }}>
                                          {[
                                            { key: 'master_bedroom', icon: '👑' },
                                            { key: 'bedrooms', icon: '🛏️' },
                                            { key: 'bathrooms', icon: '🚿' },
                                            { key: 'attached_bathroom', icon: '🚿' },
                                            { key: 'kitchen', icon: '🍳' },
                                            { key: 'living_room', icon: '🛋️' },
                                            { key: 'dining_room', icon: '🍽️' },
                                            { key: 'study_room', icon: '📚' },
                                            { key: 'prayer_room', icon: '🕉️' },
                                            { key: 'guest_room', icon: '🏠' },
                                            { key: 'store_room', icon: '📦' },
                                            { key: 'balcony', icon: '🌅' },
                                            { key: 'terrace', icon: '🏞️' },
                                            { key: 'garage', icon: '🚗' }
                                          ].find(r => r.key === roomType)?.icon || '🏠'}
                                        </span>
                                        <span style={{ fontSize: '12px', fontWeight: '500' }}>
                                          {roomType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                        </span>
                                        {roomImages.length > 0 && (
                                          <span style={{
                                            fontSize: '10px',
                                            color: '#059669',
                                            backgroundColor: '#f0fdf4',
                                            padding: '2px 4px',
                                            borderRadius: '2px'
                                          }}>
                                            📷 {roomImages.length}
                                          </span>
                                        )}
                                      </div>

                                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                        <span style={{ fontSize: '16px' }}>
                                          {[
                                            { key: 'master_bedroom', icon: '👑' },
                                            { key: 'bedrooms', icon: '🛏️' },
                                            { key: 'bathrooms', icon: '🚿' },
                                            { key: 'attached_bathroom', icon: '🚿' },
                                            { key: 'kitchen', icon: '🍳' },
                                            { key: 'living_room', icon: '🛋️' },
                                            { key: 'dining_room', icon: '🍽️' },
                                            { key: 'study_room', icon: '📚' },
                                            { key: 'prayer_room', icon: '🕉️' },
                                            { key: 'guest_room', icon: '🏠' },
                                            { key: 'store_room', icon: '📦' },
                                            { key: 'balcony', icon: '🌅' },
                                            { key: 'terrace', icon: '🏞️' },
                                            { key: 'garage', icon: '🚗' }
                                          ].find(r => r.key === roomType)?.icon || '🏠'}
                                        </span>
                                        <span style={{ fontSize: '12px', fontWeight: '500' }}>
                                          {roomType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                        </span>
                                        {/* Auto-assignment indicator */}
                                        <span style={{
                                          fontSize: '10px',
                                          color: '#059669',
                                          backgroundColor: '#f0fdf4',
                                          padding: '2px 4px',
                                          borderRadius: '2px',
                                          border: '1px solid #bbf7d0'
                                        }}>
                                          Auto
                                        </span>
                                        {roomImages.length > 0 && (
                                          <span style={{
                                            fontSize: '10px',
                                            color: '#059669',
                                            backgroundColor: '#f0fdf4',
                                            padding: '2px 4px',
                                            borderRadius: '2px'
                                          }}>
                                            📷 {roomImages.length}
                                          </span>
                                        )}
                                      </div>

                                      <div style={{ display: 'flex', gap: '4px', alignItems: 'center' }}>
                                        {/* Manual Change Button */}
                                        <button
                                          type="button"
                                          onClick={() => {
                                            // Move room to different floor
                                            const currentFloorRooms = data.floor_rooms?.[floorKey] || {};
                                            const otherFloors = Object.keys(data.floor_rooms || {}).filter(f => f !== floorKey);

                                            if (otherFloors.length > 0) {
                                              const targetFloor = otherFloors[0]; // Move to first available floor
                                              setData(prev => ({
                                                ...prev,
                                                floor_rooms: {
                                                  ...prev.floor_rooms,
                                                  [floorKey]: {
                                                    ...prev.floor_rooms?.[floorKey],
                                                    [roomKey]: 0 // Remove from current floor
                                                  },
                                                  [targetFloor]: {
                                                    ...prev.floor_rooms?.[targetFloor],
                                                    [roomKey]: 1 // Add to target floor
                                                  }
                                                }
                                              }));
                                            }
                                          }}
                                          style={{
                                            fontSize: '10px',
                                            padding: '2px 6px',
                                            backgroundColor: '#fef3c7',
                                            border: '1px solid #f59e0b',
                                            borderRadius: '4px',
                                            color: '#92400e',
                                            cursor: 'pointer'
                                          }}
                                        >
                                          🔄 Move
                                        </button>
                                        <button
                                          type="button"
                                          onClick={() => setData(prev => ({
                                            ...prev,
                                            floor_rooms: {
                                              ...prev.floor_rooms,
                                              [floorKey]: {
                                                ...prev.floor_rooms?.[floorKey],
                                                [roomKey]: Math.max(0, currentCount - 1)
                                              }
                                            }
                                          }))}
                                          style={{
                                            width: '24px',
                                            height: '24px',
                                            border: '1px solid #d1d5db',
                                            borderRadius: '4px',
                                            backgroundColor: '#ffffff',
                                            cursor: 'pointer',
                                            fontSize: '14px',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center'
                                          }}
                                        >
                                          -
                                        </button>
                                        <span style={{
                                          minWidth: '20px',
                                          textAlign: 'center',
                                          fontSize: '12px',
                                          fontWeight: '600'
                                        }}>
                                          {currentCount}
                                        </span>
                                        <button
                                          type="button"
                                          onClick={() => setData(prev => ({
                                            ...prev,
                                            floor_rooms: {
                                              ...prev.floor_rooms,
                                              [floorKey]: {
                                                ...prev.floor_rooms?.[floorKey],
                                                [roomKey]: currentCount + 1
                                              }
                                            }
                                          }))}
                                          style={{
                                            width: '24px',
                                            height: '24px',
                                            border: '1px solid #d1d5db',
                                            borderRadius: '4px',
                                            backgroundColor: '#ffffff',
                                            cursor: 'pointer',
                                            fontSize: '14px',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center'
                                          }}
                                        >
                                          +
                                        </button>
                                      </div>
                                    </div>

                                    {/* Reference Image Field */}
                                    {currentCount > 0 && (
                                      <div style={{
                                        borderTop: '1px solid #f3f4f6',
                                        paddingTop: '8px',
                                        marginTop: '8px'
                                      }}>
                                        <div style={{
                                          display: 'flex',
                                          alignItems: 'center',
                                          gap: '8px',
                                          marginBottom: '8px'
                                        }}>
                                          <span style={{
                                            fontSize: '11px',
                                            fontWeight: '500',
                                            color: '#6b7280',
                                            minWidth: '80px'
                                          }}>
                                            🖼️ Reference:
                                          </span>
                                          <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            onChange={(e) => {
                                              const files = Array.from(e.target.files);
                                              const newImages = [...roomImages, ...files];
                                              setData(prev => ({
                                                ...prev,
                                                room_images: {
                                                  ...prev.room_images,
                                                  [floorKey]: {
                                                    ...prev.room_images?.[floorKey],
                                                    [roomKey]: newImages
                                                  }
                                                }
                                              }));
                                            }}
                                            style={{
                                              fontSize: '10px',
                                              padding: '4px 8px',
                                              border: '1px solid #d1d5db',
                                              borderRadius: '4px',
                                              backgroundColor: '#ffffff',
                                              flex: 1
                                            }}
                                          />
                                        </div>

                                        {/* Show uploaded images */}
                                        {roomImages.length > 0 && (
                                          <div style={{
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: '4px',
                                            marginTop: '8px'
                                          }}>
                                            {roomImages.map((image, index) => (
                                              <div key={index} style={{
                                                position: 'relative',
                                                width: '40px',
                                                height: '40px',
                                                borderRadius: '4px',
                                                overflow: 'hidden',
                                                border: '1px solid #d1d5db'
                                              }}>
                                                <img
                                                  src={typeof image === 'string' ? image : URL.createObjectURL(image)}
                                                  alt={`Reference ${index + 1}`}
                                                  style={{
                                                    width: '100%',
                                                    height: '100%',
                                                    objectFit: 'cover'
                                                  }}
                                                />
                                                <button
                                                  type="button"
                                                  onClick={() => {
                                                    const newImages = roomImages.filter((_, i) => i !== index);
                                                    setData(prev => ({
                                                      ...prev,
                                                      room_images: {
                                                        ...prev.room_images,
                                                        [floorKey]: {
                                                          ...prev.room_images?.[floorKey],
                                                          [roomKey]: newImages
                                                        }
                                                      }
                                                    }));
                                                  }}
                                                  style={{
                                                    position: 'absolute',
                                                    top: '-2px',
                                                    right: '-2px',
                                                    width: '16px',
                                                    height: '16px',
                                                    borderRadius: '50%',
                                                    backgroundColor: '#dc2626',
                                                    color: 'white',
                                                    border: 'none',
                                                    fontSize: '10px',
                                                    cursor: 'pointer',
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center'
                                                  }}
                                                >
                                                  ×
                                                </button>
                                              </div>
                                            ))}
                                          </div>
                                        )}
                                      </div>
                                    )}

                                    {/* Individual Room Image Upload Section */}
                                    {currentCount > 0 && (
                                      <div style={{
                                        borderTop: '1px solid #f3f4f6',
                                        paddingTop: '8px'
                                      }}>
                                        <div style={{
                                          display: 'flex',
                                          justifyContent: 'space-between',
                                          alignItems: 'center',
                                          marginBottom: '8px'
                                        }}>
                                          <span style={{
                                            fontSize: '11px',
                                            fontWeight: '500',
                                            color: '#6b7280'
                                          }}>
                                            📷 Reference Images for {roomType.replace('_', ' ')}
                                          </span>
                                          <button
                                            type="button"
                                            onClick={() => setData(prev => ({
                                              ...prev,
                                              expandedRoomImages: {
                                                ...prev.expandedRoomImages,
                                                [floorKey]: {
                                                  ...prev.expandedRoomImages?.[floorKey],
                                                  [roomKey]: !isRoomExpanded
                                                }
                                              }
                                            }))}
                                            style={{
                                              fontSize: '12px',
                                              color: '#6b7280',
                                              background: 'none',
                                              border: 'none',
                                              cursor: 'pointer'
                                            }}
                                          >
                                            {isRoomExpanded ? '▼ Hide' : '▶ Show'}
                                          </button>
                                        </div>

                                        {isRoomExpanded && (
                                          <div>
                                            <input
                                              type="file"
                                              multiple
                                              accept="image/*"
                                              onChange={e => {
                                                const files = Array.from(e.target.files);
                                                setData(prev => ({
                                                  ...prev,
                                                  room_images: {
                                                    ...prev.room_images,
                                                    [floorKey]: {
                                                      ...prev.room_images?.[floorKey],
                                                      [roomKey]: [...(prev.room_images?.[floorKey]?.[roomKey] || []), ...files]
                                                    }
                                                  }
                                                }));
                                              }}
                                              style={{ display: 'none' }}
                                              id={`${floorKey}-${roomKey}-images`}
                                            />

                                            <label
                                              htmlFor={`${floorKey}-${roomKey}-images`}
                                              style={{
                                                display: 'inline-block',
                                                padding: '4px 8px',
                                                backgroundColor: '#3b82f6',
                                                color: 'white',
                                                borderRadius: '4px',
                                                cursor: 'pointer',
                                                fontSize: '10px',
                                                fontWeight: '500',
                                                marginBottom: '8px'
                                              }}
                                            >
                                              Add Images
                                            </label>

                                            {roomImages.length > 0 && (
                                              <div style={{
                                                display: 'grid',
                                                gridTemplateColumns: 'repeat(auto-fill, minmax(50px, 1fr))',
                                                gap: '4px'
                                              }}>
                                                {roomImages.map((file, imgIndex) => (
                                                  <div key={imgIndex} style={{
                                                    position: 'relative',
                                                    border: '1px solid #d1d5db',
                                                    borderRadius: '2px',
                                                    overflow: 'hidden'
                                                  }}>
                                                    <img
                                                      src={URL.createObjectURL(file)}
                                                      alt={`${roomType} ${imgIndex + 1}`}
                                                      style={{
                                                        width: '100%',
                                                        height: '35px',
                                                        objectFit: 'cover'
                                                      }}
                                                    />
                                                    <button
                                                      type="button"
                                                      onClick={() => setData(prev => ({
                                                        ...prev,
                                                        room_images: {
                                                          ...prev.room_images,
                                                          [floorKey]: {
                                                            ...prev.room_images?.[floorKey],
                                                            [roomKey]: prev.room_images[floorKey][roomKey].filter((_, i) => i !== imgIndex)
                                                          }
                                                        }
                                                      }))}
                                                      style={{
                                                        position: 'absolute',
                                                        top: '1px',
                                                        right: '1px',
                                                        background: 'rgba(0,0,0,0.7)',
                                                        color: 'white',
                                                        border: 'none',
                                                        borderRadius: '50%',
                                                        width: '14px',
                                                        height: '14px',
                                                        fontSize: '8px',
                                                        cursor: 'pointer'
                                                      }}
                                                    >
                                                      ×
                                                    </button>
                                                  </div>
                                                ))}
                                              </div>
                                            )}
                                          </div>
                                        )}
                                      </div>
                                    )}
                                  </div>
                                );
                              })
                            ) : (
                              <div style={{
                                textAlign: 'center',
                                color: '#6b7280',
                                fontSize: '12px',
                                padding: '20px'
                              }}>
                                Select room types above to plan floor-wise
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>


              </div>
            )}
          </div>



          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="section">
          <div className="section-header">Preferences</div>
          <div className="section-body">

            {/* Option 1: Manual Selection */}
            <div style={{
              backgroundColor: '#ffffff',
              border: '2px solid #e5e7eb',
              borderRadius: '16px',
              padding: '24px',
              marginBottom: '24px',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
            }}>
              <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                marginBottom: '20px',
                paddingBottom: '16px',
                borderBottom: '2px solid #f1f5f9'
              }}>
                <span style={{ fontSize: '24px' }}>🎨</span>
                <h3 style={{
                  margin: 0,
                  fontSize: '20px',
                  fontWeight: '700',
                  color: '#1f2937'
                }}>
                  Option 1: Manual Style Selection
                </h3>
                <span style={{
                  fontSize: '12px',
                  backgroundColor: '#dbeafe',
                  color: '#1e40af',
                  padding: '4px 8px',
                  borderRadius: '6px',
                  fontWeight: '600'
                }}>
                  Your Choice
                </span>
              </div>

              <div className="field">
                <label style={{
                  fontSize: '16px',
                  fontWeight: '600',
                  color: '#374151',
                  marginBottom: '12px',
                  display: 'block'
                }}>
                  Select Your Preferred House Style
                </label>
                <select
                  value={data.aesthetic || ''}
                  onChange={e => {
                    const newValue = e.target.value;
                    console.log('🎨 User manually selected aesthetic:', newValue);
                    setData(prev => ({ ...prev, aesthetic: newValue }));
                    // Clear any suggestions when user manually selects
                  }}
                  style={{
                    width: '100%',
                    padding: '16px 20px',
                    fontSize: '18px',
                    fontWeight: '500',
                    border: fieldErrors.aesthetic ? '3px solid #dc2626' : '2px solid #e5e7eb',
                    borderRadius: '12px',
                    backgroundColor: '#ffffff',
                    boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
                    cursor: 'pointer',
                    transition: 'all 0.2s ease'
                  }}
                  onFocus={(e) => {
                    e.target.style.borderColor = '#3b82f6';
                    e.target.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                  }}
                  onBlur={(e) => {
                    e.target.style.borderColor = '#e5e7eb';
                    e.target.style.boxShadow = '0 2px 4px rgba(0, 0, 0, 0.1)';
                  }}
                >
                  <option value="">Choose your preferred house style...</option>
                  <option value="Modern">🏢 Modern</option>
                  <option value="Traditional">🏛️ Traditional</option>
                  <option value="Contemporary">🏗️ Contemporary</option>
                  <option value="Minimalist">📐 Minimalist</option>
                  <option value="Classical">🏛️ Classical</option>
                  <option value="Mediterranean">🌊 Mediterranean</option>
                  <option value="Scandinavian">❄️ Scandinavian</option>
                  <option value="Industrial">🏭 Industrial</option>
                  <option value="Colonial">🏛️ Colonial</option>
                  <option value="Victorian">🏰 Victorian</option>
                  <option value="Art Deco">🎭 Art Deco</option>
                  <option value="Rustic">🌲 Rustic</option>
                  <option value="Farmhouse">🚜 Farmhouse</option>
                  <option value="Craftsman">🔨 Craftsman</option>
                  <option value="Tudor">🏰 Tudor</option>
                  <option value="Ranch">🤠 Ranch</option>
                  <option value="Cape Cod">🏖️ Cape Cod</option>
                  <option value="Spanish">🌞 Spanish</option>
                  <option value="French Country">🇫🇷 French Country</option>
                  <option value="Mid-Century Modern">🕰️ Mid-Century Modern</option>
                  <option value="Prairie">🌾 Prairie</option>
                  <option value="Gothic">🏰 Gothic</option>
                  <option value="Neoclassical">🏛️ Neoclassical</option>
                  <option value="Baroque">🎨 Baroque</option>
                  <option value="Rococo">🌸 Rococo</option>
                  <option value="Bauhaus">📐 Bauhaus</option>
                  <option value="Brutalist">🏗️ Brutalist</option>
                  <option value="Postmodern">🎭 Postmodern</option>
                  <option value="Deconstructivist">🔧 Deconstructivist</option>
                  <option value="High-Tech">🤖 High-Tech</option>
                  <option value="Sustainable/Green">🌱 Sustainable/Green</option>
                  <option value="Smart Home">🏠 Smart Home</option>
                  <option value="Luxury">💎 Luxury</option>
                  <option value="Affordable">💰 Affordable</option>
                  <option value="Custom">🎨 Custom</option>
                </select>

                {/* Selection Status */}
                {data.aesthetic && (
                  <div style={{
                    marginTop: '16px',
                    padding: '12px 16px',
                    backgroundColor: '#f0f9ff',
                    border: '2px solid #0ea5e9',
                    borderRadius: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px'
                  }}>
                    <span style={{ fontSize: '16px' }}>✅</span>
                    <span style={{ fontSize: '14px', fontWeight: '600', color: '#0c4a6e' }}>
                      Selected: <strong>{data.aesthetic}</strong>
                    </span>
                  </div>
                )}

                {fieldErrors.aesthetic && (
                  <div style={{
                    color: '#dc2626',
                    fontSize: '14px',
                    marginTop: '8px',
                    padding: '8px 12px',
                    backgroundColor: '#fef2f2',
                    border: '1px solid #fecaca',
                    borderRadius: '6px'
                  }}>
                    ⚠️ {fieldErrors.aesthetic}
                  </div>
                )}
              </div>
            </div>


          </div>





          {/* Validation Summary for Preferences Page */}
          {(fieldErrors.aesthetic || fieldWarnings.aesthetic) && (
            <div style={{
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '12px',
              padding: '20px',
              marginBottom: '20px'
            }}>
              <h3 style={{ margin: '0 0 16px 0', color: '#1e293b', fontSize: '18px' }}>
                🎨 Preferences Validation
              </h3>

              {fieldErrors.aesthetic && (
                <div style={{ marginBottom: '8px', padding: '8px', backgroundColor: '#fef2f2', border: '1px solid #fecaca', borderRadius: '4px', color: '#dc2626' }}>
                  ❌ House Style: {fieldErrors.aesthetic}
                </div>
              )}
              {fieldWarnings.aesthetic && (
                <div style={{ marginBottom: '8px', padding: '8px', backgroundColor: '#fffbeb', border: '1px solid #fed7aa', borderRadius: '4px', color: '#d97706' }}>
                  ⚠️ House Style: {fieldWarnings.aesthetic}
                </div>
              )}
            </div>
          )}

          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 4 && (
        <div className="section">
          <div className="section-header">Review</div>

          {/* Comprehensive Rules Summary */}


          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 5 && (
        <div className="section">
          <div className="section-header">Select Architect</div>
          <div className="section-body">
            <ArchitectSelection
              selectedArchitectIds={data.selected_architect_ids}
              onSelectionChange={(selectedIds, selectedArchitectObjects) => {
                setData(prev => ({ ...prev, selected_architect_ids: selectedIds }));
                setSelectedArchitects(selectedArchitectObjects || []);
              }}
              layoutRequestId={null}
              stylePreferences={data.aesthetic ? { aesthetic: data.aesthetic } : {}}
            />
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={handleNext}>Next</button>
          </div>
        </div>
      )}

      {step === 6 && (
        <div className="section">
          <div className="section-header">Submit</div>
          <div className="section-body">
            <div className="submit-message">
              <h3>Ready to Submit?</h3>
              <p>Please review all your information before submitting your request.</p>
            </div>

            {/* Project Summary */}
            <div style={{
              backgroundColor: '#f8fafc',
              border: '1px solid #e2e8f0',
              borderRadius: '8px',
              padding: '20px',
              marginBottom: '20px'
            }}>
              <h4 style={{ margin: '0 0 16px 0', color: '#374151', fontSize: '18px' }}>
                📋 Project Summary
              </h4>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '16px' }}>
                <div style={{ backgroundColor: 'white', padding: '16px', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                  <h5 style={{ margin: '0 0 12px 0', color: '#374151', fontSize: '16px' }}>🏠 Project Details</h5>
                  <div style={{ fontSize: '14px', color: '#6b7280', lineHeight: '1.6' }}>
                    <div><strong>Plot Size:</strong> {data.plot_size} {data.plot_unit || 'sq ft'}</div>
                    <div><strong>Building Size:</strong> {data.building_size} sq ft</div>
                    <div><strong>Floors:</strong> {data.num_floors}</div>
                    <div><strong>Budget:</strong> {data.budget_range}</div>
                    <div><strong>Style:</strong> {data.aesthetic}</div>
                    <div><strong>Timeline:</strong> {data.timeline}</div>
                    {estimatedCost && (
                      <div><strong>Estimated Cost:</strong> ₹{Math.round(estimatedCost).toLocaleString()}</div>
                    )}
                    {plotCategory && (
                      <div><strong>Plot Category:</strong> {plotCategory}</div>
                    )}
                    {timelineData && (
                      <div><strong>Predicted Duration:</strong> {timelineData.months} months</div>
                    )}
                  </div>
                </div>

                <div style={{ backgroundColor: 'white', padding: '16px', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                  <h5 style={{ margin: '0 0 12px 0', color: '#374151', fontSize: '16px' }}>
                    👥 Selected Architects ({selectedArchitects.length})
                  </h5>
                  <div style={{ fontSize: '14px', color: '#6b7280', lineHeight: '1.6' }}>
                    {selectedArchitects.length > 0 ? (
                      <div>
                        {selectedArchitects.map((architect, index) => (
                          <div key={architect.id} style={{
                            padding: '8px 12px',
                            backgroundColor: '#f0f9ff',
                            border: '1px solid #bae6fd',
                            borderRadius: '6px',
                            marginBottom: '8px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                          }}>
                            <div style={{
                              width: '32px',
                              height: '32px',
                              backgroundColor: '#3b82f6',
                              borderRadius: '50%',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: 'white',
                              fontSize: '12px',
                              fontWeight: '600'
                            }}>
                              {architect.first_name?.charAt(0) || 'A'}{architect.last_name?.charAt(0) || ''}
                            </div>
                            <div>
                              <div style={{ fontWeight: '600', color: '#1e40af' }}>
                                {architect.first_name} {architect.last_name}
                              </div>
                              <div style={{ fontSize: '12px', color: '#6b7280' }}>
                                {architect.email}
                              </div>
                              {architect.specialization && (
                                <div style={{ fontSize: '12px', color: '#059669' }}>
                                  {architect.specialization}
                                </div>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div style={{ color: '#9ca3af', fontStyle: 'italic' }}>
                        No architects selected
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={submit}>Submit Request</button>
          </div>
        </div>
      )}
    </WizardLayout>
  );
}
