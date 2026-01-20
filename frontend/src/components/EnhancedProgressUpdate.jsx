import React, { useState, useEffect, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import { 
  validateLabourData, 
  validateProgressUpdate, 
  generateProductivityInsights,
  calculateOptimalWage,
  standardHourlyRates 
} from '../utils/progressValidation.js';
import GeoPhotoCapture from './GeoPhotoCapture.jsx';
import '../styles/EnhancedProgress.css';

const EnhancedProgressUpdate = ({ contractorId, onUpdateSubmitted }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [activeSection, setActiveSection] = useState('daily'); // 'daily', 'weekly', 'monthly'
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [location, setLocation] = useState({ latitude: null, longitude: null });
  const [gettingLocation, setGettingLocation] = useState(false);

  // Validation states
  const [validationErrors, setValidationErrors] = useState({});
  const [fieldTouched, setFieldTouched] = useState({});
  const [isFormValid, setIsFormValid] = useState(false);

  // Daily Update Form State
  const [dailyForm, setDailyForm] = useState({
    update_date: new Date().toISOString().split('T')[0],
    construction_stage: '',
    work_done_today: '',
    incremental_completion_percentage: '',
    working_hours: '8',
    weather_condition: '',
    site_issues: '',
    labour_data: []
  });

  // Weekly Summary Form State
  const [weeklyForm, setWeeklyForm] = useState({
    week_start_date: '',
    week_end_date: '',
    stages_worked: [],
    delays_and_reasons: '',
    weekly_remarks: ''
  });

  // Monthly Report Form State
  const [monthlyForm, setMonthlyForm] = useState({
    report_month: new Date().getMonth() + 1,
    report_year: new Date().getFullYear(),
    planned_progress_percentage: '',
    milestones_achieved: [],
    delay_explanation: '',
    contractor_remarks: ''
  });

  const [photos, setPhotos] = useState([]);
  const [validationResults, setValidationResults] = useState({ errors: [], warnings: [] });
  const [productivityInsights, setProductivityInsights] = useState([]);
  const [showValidation, setShowValidation] = useState(false);
  const [showGeoPhotoCapture, setShowGeoPhotoCapture] = useState(false);
  const [geoPhotos, setGeoPhotos] = useState([]);

  // Worker selection states
  const [phaseWorkers, setPhaseWorkers] = useState(null);
  const [loadingWorkers, setLoadingWorkers] = useState(false);
  const [availableWorkerTypes, setAvailableWorkerTypes] = useState([]);

  const stages = [
    'Foundation', 'Structure', 'Brickwork', 'Roofing', 
    'Electrical', 'Plumbing', 'Finishing', 'Other'
  ];

  const weatherConditions = [
    'Sunny', 'Cloudy', 'Rainy', 'Stormy', 'Foggy', 'Hot', 'Cold', 'Windy'
  ];

  // Static fallback worker types (used when phase-specific loading fails)
  const fallbackWorkerTypes = [
    'Mason', 'Helper', 'Electrician', 'Plumber', 'Carpenter', 'Painter', 
    'Supervisor', 'Welder', 'Crane Operator', 'Excavator Operator', 
    'Steel Fixer', 'Tile Worker', 'Plasterer', 'Roofer', 'Security Guard',
    'Site Engineer', 'Quality Inspector', 'Safety Officer', 'Other'
  ];

  const safetyComplianceOptions = [
    'excellent', 'good', 'average', 'poor', 'needs_improvement'
  ];

  // Comprehensive validation rules
  const validationRules = {
    // Project selection
    selectedProject: {
      required: true,
      message: 'Please select a project'
    },
    
    // Daily form validations
    update_date: {
      required: true,
      validate: (value) => {
        const date = new Date(value);
        const today = new Date();
        const maxPastDays = 7; // Allow up to 7 days in the past
        const maxFutureDays = 1; // Allow up to 1 day in the future
        
        const diffTime = today - date;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > maxPastDays) {
          return `Date cannot be more than ${maxPastDays} days in the past`;
        }
        if (diffDays < -maxFutureDays) {
          return `Date cannot be more than ${maxFutureDays} day in the future`;
        }
        return null;
      }
    },
    
    construction_stage: {
      required: true,
      message: 'Please select a construction stage'
    },
    
    work_done_today: {
      required: true,
      minLength: 10,
      maxLength: 1000,
      validate: (value) => {
        if (value.length < 10) return 'Work description must be at least 10 characters';
        if (value.length > 1000) return 'Work description cannot exceed 1000 characters';
        if (!/[a-zA-Z]/.test(value)) return 'Work description must contain letters';
        return null;
      }
    },
    
    incremental_completion_percentage: {
      required: true,
      validate: (value) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Completion percentage must be a valid number';
        if (num < 0) return 'Completion percentage cannot be negative';
        if (num > 100) return 'Completion percentage cannot exceed 100%';
        if (num > 20) return 'Daily incremental progress should typically not exceed 20%';
        return null;
      }
    },
    
    working_hours: {
      required: true,
      validate: (value) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Working hours must be a valid number';
        if (num < 0) return 'Working hours cannot be negative';
        if (num > 16) return 'Working hours cannot exceed 16 hours per day';
        if (num < 4 && num > 0) return 'Working hours should be at least 4 hours if work was done';
        return null;
      }
    },
    
    weather_condition: {
      required: true,
      message: 'Please select weather condition'
    },
    
    site_issues: {
      validate: (value) => {
        if (value && value.length > 1000) return 'Site issues description cannot exceed 1000 characters';
        return null;
      }
    },
    
    // Labour validation rules
    labour_worker_type: {
      required: true,
      message: 'Please select worker type'
    },
    
    labour_worker_count: {
      required: true,
      validate: (value) => {
        const num = parseInt(value);
        if (isNaN(num)) return 'Worker count must be a valid number';
        if (num < 0) return 'Worker count cannot be negative';
        if (num > 100) return 'Worker count seems too high (max 100)';
        return null;
      }
    },
    
    labour_hours_worked: {
      required: true,
      validate: (value) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Hours worked must be a valid number';
        if (num < 0) return 'Hours worked cannot be negative';
        if (num > 12) return 'Regular hours cannot exceed 12 hours';
        return null;
      }
    },
    
    labour_overtime_hours: {
      validate: (value) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Overtime hours must be a valid number';
        if (num < 0) return 'Overtime hours cannot be negative';
        if (num > 8) return 'Overtime hours cannot exceed 8 hours';
        return null;
      }
    },
    
    labour_absent_count: {
      validate: (value) => {
        const num = parseInt(value);
        if (isNaN(num)) return 'Absent count must be a valid number';
        if (num < 0) return 'Absent count cannot be negative';
        if (num > 50) return 'Absent count seems too high';
        return null;
      }
    },
    

    
    labour_productivity_rating: {
      validate: (value) => {
        const num = parseInt(value);
        if (isNaN(num)) return 'Productivity rating must be a valid number';
        if (num < 1 || num > 5) return 'Productivity rating must be between 1 and 5';
        return null;
      }
    },
    
    labour_remarks: {
      validate: (value) => {
        if (value && value.length > 500) return 'Remarks cannot exceed 500 characters';
        return null;
      }
    },
    
    // Weekly form validations
    week_start_date: {
      required: true,
      validate: (value, formData) => {
        const startDate = new Date(value);
        const endDate = new Date(formData.week_end_date);
        
        if (endDate && startDate >= endDate) {
          return 'Start date must be before end date';
        }
        
        const dayOfWeek = startDate.getDay();
        if (dayOfWeek !== 1) {
          return 'Week should typically start on Monday';
        }
        
        return null;
      }
    },
    
    week_end_date: {
      required: true,
      validate: (value, formData) => {
        const startDate = new Date(formData.week_start_date);
        const endDate = new Date(value);
        
        if (startDate && endDate <= startDate) {
          return 'End date must be after start date';
        }
        
        const diffTime = endDate - startDate;
        const diffDays = diffTime / (1000 * 60 * 60 * 24);
        
        if (diffDays > 7) {
          return 'Week duration cannot exceed 7 days';
        }
        
        return null;
      }
    },
    
    stages_worked: {
      required: true,
      validate: (value) => {
        if (!Array.isArray(value) || value.length === 0) {
          return 'Please select at least one construction stage';
        }
        return null;
      }
    },
    
    weekly_remarks: {
      required: true,
      minLength: 20,
      validate: (value) => {
        if (value.length < 20) return 'Weekly remarks must be at least 20 characters';
        if (value.length > 2000) return 'Weekly remarks cannot exceed 2000 characters';
        return null;
      }
    },
    
    delays_and_reasons: {
      validate: (value) => {
        if (value && value.length > 1000) return 'Delays description cannot exceed 1000 characters';
        return null;
      }
    },
    
    // Monthly form validations
    report_month: {
      required: true,
      validate: (value) => {
        const num = parseInt(value);
        if (isNaN(num) || num < 1 || num > 12) {
          return 'Please select a valid month';
        }
        return null;
      }
    },
    
    report_year: {
      required: true,
      validate: (value) => {
        const num = parseInt(value);
        const currentYear = new Date().getFullYear();
        if (isNaN(num)) return 'Year must be a valid number';
        if (num < 2020) return 'Year cannot be before 2020';
        if (num > currentYear + 1) return `Year cannot be after ${currentYear + 1}`;
        return null;
      }
    },
    
    planned_progress_percentage: {
      required: true,
      validate: (value) => {
        const num = parseFloat(value);
        if (isNaN(num)) return 'Planned progress must be a valid number';
        if (num < 0) return 'Planned progress cannot be negative';
        if (num > 100) return 'Planned progress cannot exceed 100%';
        return null;
      }
    },
    
    contractor_remarks: {
      required: true,
      minLength: 50,
      validate: (value) => {
        if (value.length < 50) return 'Contractor remarks must be at least 50 characters';
        if (value.length > 3000) return 'Contractor remarks cannot exceed 3000 characters';
        return null;
      }
    },
    
    delay_explanation: {
      validate: (value) => {
        if (value && value.length > 2000) return 'Delay explanation cannot exceed 2000 characters';
        return null;
      }
    }
  };

  // Validation function
  const validateField = (fieldName, value, formData = {}) => {
    const rule = validationRules[fieldName];
    if (!rule) return null;

    // Required field validation
    if (rule.required && (!value || (typeof value === 'string' && value.trim() === ''))) {
      return rule.message || `${fieldName} is required`;
    }

    // Custom validation function
    if (rule.validate) {
      return rule.validate(value, formData);
    }

    return null;
  };

  // Validate entire form
  const validateForm = (formType) => {
    const errors = {};
    let formData = {};
    
    if (formType === 'daily') {
      formData = dailyForm;
      
      // Validate project selection
      const projectError = validateField('selectedProject', selectedProject);
      if (projectError) errors.selectedProject = projectError;
      
      // Validate daily form fields
      Object.keys(dailyForm).forEach(field => {
        const error = validateField(field, dailyForm[field], dailyForm);
        if (error) errors[field] = error;
      });
      
      // Validate labour data
      dailyForm.labour_data.forEach((labour, index) => {
        Object.keys(labour).forEach(field => {
          const error = validateField(`labour_${field}`, labour[field]);
          if (error) {
            if (!errors.labour_data) errors.labour_data = {};
            if (!errors.labour_data[index]) errors.labour_data[index] = {};
            errors.labour_data[index][field] = error;
          }
        });
        
        // Cross-field validation for labour
        if (labour.worker_count > 0 && labour.hours_worked === 0) {
          if (!errors.labour_data) errors.labour_data = {};
          if (!errors.labour_data[index]) errors.labour_data[index] = {};
          errors.labour_data[index].hours_worked = 'Hours worked required when worker count > 0';
        }
      });
      
      // Photo validation
      if (dailyForm.incremental_completion_percentage >= 10 && photos.length === 0) {
        errors.progress_photos = 'Photos are mandatory for completion claims of 10% or more';
      }
      
    } else if (formType === 'weekly') {
      formData = weeklyForm;
      
      const projectError = validateField('selectedProject', selectedProject);
      if (projectError) errors.selectedProject = projectError;
      
      Object.keys(weeklyForm).forEach(field => {
        const error = validateField(field, weeklyForm[field], weeklyForm);
        if (error) errors[field] = error;
      });
      
    } else if (formType === 'monthly') {
      formData = monthlyForm;
      
      const projectError = validateField('selectedProject', selectedProject);
      if (projectError) errors.selectedProject = projectError;
      
      Object.keys(monthlyForm).forEach(field => {
        const error = validateField(field, monthlyForm[field], monthlyForm);
        if (error) errors[field] = error;
      });
    }
    
    return errors;
  };

  // Real-time validation
  const handleFieldValidation = (fieldName, value, formData = {}) => {
    const error = validateField(fieldName, value, formData);
    
    setValidationErrors(prev => ({
      ...prev,
      [fieldName]: error
    }));
    
    setFieldTouched(prev => ({
      ...prev,
      [fieldName]: true
    }));
    
    return error;
  };

  // Mark field as touched
  const markFieldTouched = (fieldName) => {
    setFieldTouched(prev => ({
      ...prev,
      [fieldName]: true
    }));
  };

  // Get field error class
  const getFieldErrorClass = (fieldName) => {
    if (!fieldTouched[fieldName]) return '';
    return validationErrors[fieldName] ? 'error' : 'success';
  };

  // Display field error
  const renderFieldError = (fieldName) => {
    if (!fieldTouched[fieldName] || !validationErrors[fieldName]) return null;
    
    return (
      <div className="field-error">
        <span className="error-icon">⚠️</span>
        <span className="error-message">{validationErrors[fieldName]}</span>
      </div>
    );
  };

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  // Load assigned projects
  useEffect(() => {
    loadAssignedProjects();
  }, [contractorId]);

  // Get current location
  useEffect(() => {
    getCurrentLocation();
  }, []);

  // Initialize available worker types
  useEffect(() => {
    // Start with empty array - will be populated when construction stage is selected
    setAvailableWorkerTypes([]);
  }, []);

  // Set default week dates when switching to weekly section
  useEffect(() => {
    if (activeSection === 'weekly' && !weeklyForm.week_start_date) {
      const today = new Date();
      const monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);
      
      setWeeklyForm(prev => ({
        ...prev,
        week_start_date: monday.toISOString().split('T')[0],
        week_end_date: sunday.toISOString().split('T')[0]
      }));
    }
  }, [activeSection]);

  // Load phase-specific workers when construction stage changes
  useEffect(() => {
    if (dailyForm.construction_stage) {
      loadPhaseWorkers(dailyForm.construction_stage);
      
      // Clear existing labour entries when stage changes since worker types might be different
      if (dailyForm.labour_data.length > 0) {
        setDailyForm(prev => ({
          ...prev,
          labour_data: []
        }));
        toast.info(`Cleared labour entries due to construction stage change. Please add workers for ${dailyForm.construction_stage} phase.`);
      }
    } else {
      setPhaseWorkers(null);
      setAvailableWorkerTypes([]); // Clear worker types when no stage selected
    }
  }, [dailyForm.construction_stage]);

  const loadPhaseWorkers = async (phaseName) => {
    try {
      setLoadingWorkers(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_phase_workers.php?phase=${encodeURIComponent(phaseName)}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setPhaseWorkers(data.data);
        
        // Extract available worker types for this phase
        const phaseWorkerTypes = Object.values(data.data.available_workers).map(workerGroup => 
          workerGroup.requirement.type_name
        );
        
        // Add 'Other' as fallback option
        if (!phaseWorkerTypes.includes('Other')) {
          phaseWorkerTypes.push('Other');
        }
        
        console.log(`Phase ${phaseName} worker types:`, phaseWorkerTypes);
        setAvailableWorkerTypes(phaseWorkerTypes);
        
        toast.success(`Loaded ${phaseWorkerTypes.length} worker types for ${phaseName} phase`);
      } else {
        console.warn('Failed to load phase workers:', data.message);
        setPhaseWorkers(null);
        setAvailableWorkerTypes(['Mason', 'Helper', 'Other']); // Minimal fallback
        toast.error(`Failed to load phase workers: ${data.message}`);
      }
    } catch (error) {
      console.error('Error loading phase workers:', error);
      setPhaseWorkers(null);
      setAvailableWorkerTypes(['Mason', 'Helper', 'Other']); // Minimal fallback
      toast.error('Error loading phase workers');
    } finally {
      setLoadingWorkers(false);
    }
  };

  const loadAssignedProjects = async () => {
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setProjects(data.data.projects || []);
      } else {
        toast.error('Failed to load projects: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      toast.error('Error loading projects');
    } finally {
      setLoadingProjects(false);
    }
  };

  const getCurrentLocation = () => {
    if (navigator.geolocation) {
      setGettingLocation(true);
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLocation({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          });
          setGettingLocation(false);
        },
        (error) => {
          console.warn('Geolocation error:', error);
          setGettingLocation(false);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    }
  };

  const handleDailyInputChange = (e) => {
    const { name, value } = e.target;
    
    // Update form data
    setDailyForm(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Real-time validation
    handleFieldValidation(name, value, { ...dailyForm, [name]: value });
  };

  const handleWeeklyInputChange = (e) => {
    const { name, value } = e.target;
    
    // Update form data
    setWeeklyForm(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Real-time validation
    handleFieldValidation(name, value, { ...weeklyForm, [name]: value });
  };

  const handleMonthlyInputChange = (e) => {
    const { name, value } = e.target;
    
    // Update form data
    setMonthlyForm(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Real-time validation
    handleFieldValidation(name, value, { ...monthlyForm, [name]: value });
  };

  const handleLabourChange = (index, field, value) => {
    const newLabourData = [...dailyForm.labour_data];
    if (!newLabourData[index]) {
      newLabourData[index] = { 
        worker_type: '', 
        worker_count: 0, 
        hours_worked: 8, 
        overtime_hours: 0, 
        absent_count: 0, 
        productivity_rating: 5,
        safety_compliance: 'good',
        remarks: '' 
      };
    }
    newLabourData[index][field] = value;
    
    setDailyForm(prev => ({ ...prev, labour_data: newLabourData }));
    
    // Real-time validation for labour fields
    const fieldName = `labour_${field}`;
    const error = validateField(fieldName, value);
    
    setValidationErrors(prev => ({
      ...prev,
      [`labour_data_${index}_${field}`]: error
    }));
    
    setFieldTouched(prev => ({
      ...prev,
      [`labour_data_${index}_${field}`]: true
    }));
  };

  const addLabourEntry = () => {
    setDailyForm(prev => ({
      ...prev,
      labour_data: [...prev.labour_data, { 
        worker_type: 'Helper', 
        worker_count: 1, 
        hours_worked: 8, 
        overtime_hours: 0, 
        absent_count: 0,
        productivity_rating: 5,
        safety_compliance: 'good',
        remarks: '' 
      }]
    }));
  };

  const removeLabourEntry = (index) => {
    setDailyForm(prev => ({
      ...prev,
      labour_data: prev.labour_data.filter((_, i) => i !== index)
    }));
  };

  const handleStageToggle = (stage) => {
    const newStages = weeklyForm.stages_worked.includes(stage)
      ? weeklyForm.stages_worked.filter(s => s !== stage)
      : [...weeklyForm.stages_worked, stage];
      
    setWeeklyForm(prev => ({
      ...prev,
      stages_worked: newStages
    }));
    
    // Validate stages selection
    handleFieldValidation('stages_worked', newStages);
  };

  const handleMilestoneToggle = (milestone) => {
    const newMilestones = monthlyForm.milestones_achieved.includes(milestone)
      ? monthlyForm.milestones_achieved.filter(m => m !== milestone)
      : [...monthlyForm.milestones_achieved, milestone];
      
    setMonthlyForm(prev => ({
      ...prev,
      milestones_achieved: newMilestones
    }));
  };

  const handlePhotoChange = (e) => {
    const files = Array.from(e.target.files);
    const maxFiles = 10;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    
    // Validation
    const errors = [];
    
    if (files.length > maxFiles) {
      errors.push(`Maximum ${maxFiles} photos allowed`);
    }

    const validFiles = [];
    files.forEach((file, index) => {
      // File type validation
      if (!allowedTypes.includes(file.type.toLowerCase())) {
        errors.push(`${file.name}: Invalid file type. Only JPG, PNG, WebP, and GIF allowed`);
        return;
      }
      
      // File size validation
      if (file.size > maxSize) {
        errors.push(`${file.name}: File too large. Maximum 5MB allowed`);
        return;
      }
      
      // File name validation
      if (file.name.length > 100) {
        errors.push(`${file.name}: Filename too long (max 100 characters)`);
        return;
      }
      
      // Check for duplicate names
      const duplicateIndex = validFiles.findIndex(f => f.name === file.name);
      if (duplicateIndex !== -1) {
        errors.push(`${file.name}: Duplicate filename`);
        return;
      }
      
      validFiles.push(file);
    });

    // Show errors if any
    if (errors.length > 0) {
      toast.error(`Photo validation errors:\n${errors.slice(0, 3).join('\n')}${errors.length > 3 ? '\n...and more' : ''}`);
      
      // Set validation error
      setValidationErrors(prev => ({
        ...prev,
        progress_photos: errors[0]
      }));
      setFieldTouched(prev => ({
        ...prev,
        progress_photos: true
      }));
      
      return;
    }

    // Clear photo validation errors
    setValidationErrors(prev => {
      const newErrors = { ...prev };
      delete newErrors.progress_photos;
      return newErrors;
    });

    setPhotos(validFiles);
    
    // Validate photo requirement based on completion percentage
    const completionPercent = parseFloat(dailyForm.incremental_completion_percentage) || 0;
    if (completionPercent >= 10 && validFiles.length === 0) {
      setValidationErrors(prev => ({
        ...prev,
        progress_photos: 'Photos are mandatory for completion claims of 10% or more'
      }));
    }
  };

  const removePhoto = (index) => {
    setPhotos(prev => prev.filter((_, i) => i !== index));
  };

  // Geo photo handlers
  const handleGeoPhotosCaptured = (capturedPhotos) => {
    const newGeoPhotos = capturedPhotos.map(result => ({
      id: result.data.id,
      filename: result.data.filename,
      url: result.data.url, // Use the blob URL for immediate display
      blob: result.data.blob, // Store the blob for form submission
      location: result.data.location,
      timestamp: result.data.timestamp,
      isAttached: true // Mark as attached to form
    }));
    
    setGeoPhotos(prev => [...prev, ...newGeoPhotos]);
    setShowGeoPhotoCapture(false); // Auto-close camera
    toast.success(`${capturedPhotos.length} photo(s) attached to progress form`);
  };

  const removeGeoPhoto = (index) => {
    setGeoPhotos(prev => prev.filter((_, i) => i !== index));
  };

  const validateDailyForm = () => {
    const errors = validateForm('daily');
    setValidationErrors(errors);
    
    // Mark all fields as touched to show errors
    const touchedFields = {};
    Object.keys(dailyForm).forEach(field => {
      touchedFields[field] = true;
    });
    touchedFields.selectedProject = true;
    
    // Mark labour fields as touched
    dailyForm.labour_data.forEach((labour, index) => {
      Object.keys(labour).forEach(field => {
        touchedFields[`labour_data_${index}_${field}`] = true;
      });
    });
    
    setFieldTouched(touchedFields);
    
    const hasErrors = Object.keys(errors).length > 0;
    if (hasErrors) {
      // Show specific error messages
      const errorMessages = [];
      if (errors.selectedProject) errorMessages.push(errors.selectedProject);
      if (errors.update_date) errorMessages.push(`Date: ${errors.update_date}`);
      if (errors.construction_stage) errorMessages.push(errors.construction_stage);
      if (errors.work_done_today) errorMessages.push(`Work Description: ${errors.work_done_today}`);
      if (errors.incremental_completion_percentage) errorMessages.push(`Completion %: ${errors.incremental_completion_percentage}`);
      if (errors.working_hours) errorMessages.push(`Working Hours: ${errors.working_hours}`);
      if (errors.weather_condition) errorMessages.push(errors.weather_condition);
      if (errors.progress_photos) errorMessages.push(errors.progress_photos);
      
      if (errors.labour_data) {
        errorMessages.push('Please fix labour tracking errors');
      }
      
      toast.error(`Please fix the following errors:\n${errorMessages.slice(0, 3).join('\n')}${errorMessages.length > 3 ? '\n...and more' : ''}`);
    }
    
    return !hasErrors;
  };

  const validateWeeklyForm = () => {
    const errors = validateForm('weekly');
    setValidationErrors(errors);
    
    const touchedFields = {};
    Object.keys(weeklyForm).forEach(field => {
      touchedFields[field] = true;
    });
    touchedFields.selectedProject = true;
    setFieldTouched(touchedFields);
    
    const hasErrors = Object.keys(errors).length > 0;
    if (hasErrors) {
      const errorMessages = Object.values(errors).slice(0, 3);
      toast.error(`Please fix the following errors:\n${errorMessages.join('\n')}`);
    }
    
    return !hasErrors;
  };

  const validateMonthlyForm = () => {
    const errors = validateForm('monthly');
    setValidationErrors(errors);
    
    const touchedFields = {};
    Object.keys(monthlyForm).forEach(field => {
      touchedFields[field] = true;
    });
    touchedFields.selectedProject = true;
    setFieldTouched(touchedFields);
    
    const hasErrors = Object.keys(errors).length > 0;
    if (hasErrors) {
      const errorMessages = Object.values(errors).slice(0, 3);
      toast.error(`Please fix the following errors:\n${errorMessages.join('\n')}`);
    }
    
    return !hasErrors;
  };

  const submitDailyUpdate = async (e) => {
    e.preventDefault();
    
    if (!validateDailyForm()) return;

    setLoading(true);
    
    try {
      const submitData = new FormData();
      submitData.append('project_id', selectedProject);
      submitData.append('contractor_id', contractorId);
      submitData.append('update_date', dailyForm.update_date);
      submitData.append('construction_stage', dailyForm.construction_stage);
      submitData.append('work_done_today', dailyForm.work_done_today);
      submitData.append('incremental_completion_percentage', dailyForm.incremental_completion_percentage);
      submitData.append('working_hours', dailyForm.working_hours);
      submitData.append('weather_condition', dailyForm.weather_condition);
      submitData.append('site_issues', dailyForm.site_issues);
      submitData.append('labour_data', JSON.stringify(dailyForm.labour_data));
      
      if (location.latitude && location.longitude) {
        submitData.append('latitude', location.latitude);
        submitData.append('longitude', location.longitude);
      }

      // Append regular photos
      photos.forEach((photo, index) => {
        submitData.append('progress_photos[]', photo);
      });

      // Append attached geo photos
      geoPhotos.forEach((geoPhoto, index) => {
        if (geoPhoto.blob) {
          submitData.append('geo_photos[]', geoPhoto.blob, geoPhoto.filename);
          submitData.append(`geo_photo_location_${index}`, JSON.stringify(geoPhoto.location || {}));
        }
      });

      const response = await fetch(
        'http://localhost/buildhub/backend/api/contractor/submit_daily_progress.php',
        {
          method: 'POST',
          credentials: 'include',
          body: submitData
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success('Daily progress update submitted successfully!');
        
        // Reset form
        setDailyForm({
          update_date: new Date().toISOString().split('T')[0],
          construction_stage: '',
          work_done_today: '',
          incremental_completion_percentage: '',
          working_hours: '8',
          weather_condition: '',
          site_issues: '',
          labour_data: []
        });
        setPhotos([]);
        setGeoPhotos([]); // Clear attached geo photos
        
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        
        if (onUpdateSubmitted) {
          onUpdateSubmitted(data.data);
        }
      } else {
        toast.error('Failed to submit update: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting daily update:', error);
      toast.error('Error submitting daily update');
    } finally {
      setLoading(false);
    }
  };

  const submitWeeklySummary = async (e) => {
    e.preventDefault();
    
    if (!validateWeeklyForm()) return;

    setLoading(true);
    
    try {
      const response = await fetch(
        'http://localhost/buildhub/backend/api/contractor/submit_weekly_summary.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            project_id: selectedProject,
            contractor_id: contractorId,
            ...weeklyForm
          })
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success('Weekly summary submitted successfully!');
        
        // Reset form
        setWeeklyForm({
          week_start_date: '',
          week_end_date: '',
          stages_worked: [],
          delays_and_reasons: '',
          weekly_remarks: ''
        });
        
        if (onUpdateSubmitted) {
          onUpdateSubmitted(data.data);
        }
      } else {
        toast.error('Failed to submit summary: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting weekly summary:', error);
      toast.error('Error submitting weekly summary');
    } finally {
      setLoading(false);
    }
  };

  const submitMonthlyReport = async (e) => {
    e.preventDefault();
    
    if (!validateMonthlyForm()) return;

    setLoading(true);
    
    try {
      const response = await fetch(
        'http://localhost/buildhub/backend/api/contractor/submit_monthly_report.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            project_id: selectedProject,
            contractor_id: contractorId,
            ...monthlyForm
          })
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success('Monthly report submitted successfully!');
        
        // Reset form
        setMonthlyForm({
          report_month: new Date().getMonth() + 1,
          report_year: new Date().getFullYear(),
          planned_progress_percentage: '',
          milestones_achieved: [],
          delay_explanation: '',
          contractor_remarks: ''
        });
        
        if (onUpdateSubmitted) {
          onUpdateSubmitted(data.data);
        }
      } else {
        toast.error('Failed to submit report: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting monthly report:', error);
      toast.error('Error submitting monthly report');
    } finally {
      setLoading(false);
    }
  };

  const getSelectedProjectInfo = () => {
    const project = projects.find(p => (p.id || p.project_id) == selectedProject);
    return project || null;
  };

  return (
    <div className="enhanced-progress-update">
      <div className="progress-update-header">
        <h3>Construction Progress Monitoring System</h3>
        <p>Comprehensive progress tracking with daily updates, weekly summaries, and monthly reports</p>
      </div>

      {/* Project Selection */}
      <div className="form-group project-selection">
        <label htmlFor="project">Select Project *</label>
        {loadingProjects ? (
          <div className="loading-spinner">Loading projects...</div>
        ) : (
          <div className="project-selection-container">
            <select
              id="project"
              value={selectedProject}
              onChange={(e) => {
                setSelectedProject(e.target.value);
                handleFieldValidation('selectedProject', e.target.value);
              }}
              onBlur={() => markFieldTouched('selectedProject')}
              required
              className={`project-select ${getFieldErrorClass('selectedProject')}`}
            >
              <option value="">Choose a project...</option>
              {projects.map(project => {
                const projectId = project.id || project.project_id;
                const projectName = project.project_name || project.project_display_name || 'Unnamed Project';
                return (
                  <option key={projectId} value={projectId}>
                    {projectName}
                  </option>
                );
              })}
            </select>
            {renderFieldError('selectedProject')}
          </div>
        )}
      </div>

      {/* Enhanced Project Info Display */}
      {selectedProject && (
        <div className="selected-project-info-card">
          {(() => {
            const projectInfo = getSelectedProjectInfo();
            if (!projectInfo) return null;
            
            // Handle both API response formats and extract from structured_data if available
            const structured = projectInfo.structured_data || {};
            
            const homeownerName = projectInfo.homeowner_name || structured.client_name || projectInfo.project_summary?.homeowner_name || 'Unknown';
            const statusDisplay = projectInfo.status || projectInfo.project_summary?.status_display || 'N/A';
            const contractorName = projectInfo.project_summary?.contractor_name || 'N/A';
            const homeownerEmail = projectInfo.homeowner_email || 'N/A';
            const homeownerPhone = projectInfo.homeowner_phone || structured.client_contact || 'N/A';
            const contractorEmail = projectInfo.contractor_email || 'N/A';
            const plotSize = projectInfo.plot_size || structured.plot_size || 'N/A';
            const builtUpArea = projectInfo.built_up_area || structured.built_up_area || 'N/A';
            const floors = projectInfo.floors || structured.floors || 'N/A';
            const location = projectInfo.location || structured.project_address || 'N/A';
            
            return (
              <div className="selected-project-details">
                <div className="project-info-header">
                  <h4>Selected Project Details</h4>
                  <div className="project-status-badge">
                    {statusDisplay}
                  </div>
                </div>
                
                <div className="project-participants">
                  <div className="participant">
                    <strong>Homeowner:</strong> {homeownerName}
                    <br />
                    <small>📧 {homeownerEmail} | 📱 {homeownerPhone}</small>
                  </div>
                  <div className="participant">
                    <strong>Contractor:</strong> {contractorName}
                    <br />
                    <small>📧 {contractorEmail}</small>
                  </div>
                </div>
                
                <div className="project-info-grid">
                  <div className="info-card">
                    <strong>Plot Details</strong>
                    <div>Plot Size: {plotSize}</div>
                    {builtUpArea !== 'N/A' && <div>Built-up Area: {builtUpArea}</div>}
                    {floors !== 'N/A' && <div>Floors: {floors}</div>}
                    <small>Style: {projectInfo.preferred_style || 'Not specified'}</small>
                  </div>
                  
                  <div className="info-card">
                    <strong>Location</strong>
                    <div>{location}</div>
                    {projectInfo.project_address && (
                      <small>📍 GPS: {projectInfo.project_address}</small>
                    )}
                  </div>
                  
                  <div className="info-card">
                    <strong>Budget & Cost</strong>
                    <div>Budget: {projectInfo.budget_range || 'N/A'}</div>
                    <div>Estimate: {projectInfo.estimate_cost ? `₹${projectInfo.estimate_cost.toLocaleString('en-IN')}` : (projectInfo.project_summary?.total_cost_formatted || 'N/A')}</div>
                  </div>
                  
                  <div className="info-card">
                    <strong>Timeline</strong>
                    <div>Requested: {projectInfo.requested_timeline || 'Not specified'}</div>
                    <div>Estimate: {projectInfo.timeline || projectInfo.project_summary?.timeline || 'N/A'}</div>
                  </div>
                  
                  <div className="info-card">
                    <strong>Progress Status</strong>
                    <div>{(projectInfo.completion_percentage || projectInfo.latest_progress || 0)}% complete</div>
                    <div>Completed Stages: {projectInfo.completed_stages || 0}</div>
                  </div>
                  
                  <div className="info-card">
                    <strong>Update History</strong>
                    <div>Daily: {projectInfo.daily_updates_count || 0} updates</div>
                    <div>Weekly: {projectInfo.weekly_summaries_count || 0} summaries</div>
                    <div>Monthly: {projectInfo.monthly_reports_count || 0} reports</div>
                    <small>Last: {projectInfo.updated_at || projectInfo.project_summary?.last_activity || 'N/A'}</small>
                  </div>
                </div>
                
                <div className="project-overall-progress">
                  <div className="progress-header">
                    <strong>Overall Progress</strong>
                    <span>{projectInfo.completion_percentage || projectInfo.latest_progress || 0}%</span>
                  </div>
                  <div className="progress-bar-large">
                    <div 
                      className="progress-fill-large"
                      style={{ width: `${projectInfo.latest_progress}%` }}
                    ></div>
                  </div>
                </div>
                
                {projectInfo.requirements && (
                  <div className="project-requirements-full">
                    <strong>Project Requirements:</strong>
                    <p>{projectInfo.requirements}</p>
                  </div>
                )}
                
                <div className="project-dates">
                  <small>
                    <strong>Request Date:</strong> {projectInfo.request_date_formatted || 'N/A'} | 
                    <strong> Estimate Date:</strong> {projectInfo.estimate_date_formatted || 'N/A'} | 
                    <strong> Acknowledged:</strong> {projectInfo.acknowledged_date_formatted || 'N/A'}
                  </small>
                </div>
              </div>
            );
          })()}
        </div>
      )}

      {/* Section Navigation */}
      <div className="section-navigation">
        <button 
          className={`section-btn ${activeSection === 'daily' ? 'active' : ''}`}
          onClick={() => setActiveSection('daily')}
        >
          Daily Progress Update
        </button>
        <button 
          className={`section-btn ${activeSection === 'weekly' ? 'active' : ''}`}
          onClick={() => setActiveSection('weekly')}
        >
          Weekly Progress Summary
        </button>
        <button 
          className={`section-btn ${activeSection === 'monthly' ? 'active' : ''}`}
          onClick={() => setActiveSection('monthly')}
        >
          Monthly Progress Report
        </button>
      </div>

      {/* Daily Progress Update Section */}
      {activeSection === 'daily' && (
        <form onSubmit={submitDailyUpdate} className="progress-section daily-section">
          <h4>📅 Daily Progress Update</h4>
          
          <div className="form-row">
            <div className={`form-group ${getFieldErrorClass('update_date')}`}>
              <label htmlFor="update_date">Date *</label>
              <input
                type="date"
                id="update_date"
                name="update_date"
                value={dailyForm.update_date}
                onChange={handleDailyInputChange}
                onBlur={() => markFieldTouched('update_date')}
                required
                className={getFieldErrorClass('update_date')}
              />
              {renderFieldError('update_date')}
            </div>
            
            <div className={`form-group ${getFieldErrorClass('construction_stage')}`}>
              <label htmlFor="construction_stage">Construction Stage *</label>
              <select
                id="construction_stage"
                name="construction_stage"
                value={dailyForm.construction_stage}
                onChange={handleDailyInputChange}
                onBlur={() => markFieldTouched('construction_stage')}
                required
                className={getFieldErrorClass('construction_stage')}
              >
                <option value="">Select stage...</option>
                {stages.map(stage => (
                  <option key={stage} value={stage}>{stage}</option>
                ))}
              </select>
              {renderFieldError('construction_stage')}
            </div>
          </div>

          <div className={`form-group ${getFieldErrorClass('work_done_today')}`}>
            <label htmlFor="work_done_today">Work Done Today *</label>
            <textarea
              id="work_done_today"
              name="work_done_today"
              value={dailyForm.work_done_today}
              onChange={handleDailyInputChange}
              onBlur={() => markFieldTouched('work_done_today')}
              placeholder="Describe the work completed today in detail... (minimum 10 characters)"
              rows="4"
              required
              className={getFieldErrorClass('work_done_today')}
              maxLength="1000"
            />
            <div className="field-info">
              {dailyForm.work_done_today.length}/1000 characters
              {dailyForm.work_done_today.length < 10 && dailyForm.work_done_today.length > 0 && (
                <span className="char-warning"> (minimum 10 required)</span>
              )}
            </div>
            {renderFieldError('work_done_today')}
          </div>

          <div className="form-row">
            <div className={`form-group ${getFieldErrorClass('incremental_completion_percentage')}`}>
              <label htmlFor="incremental_completion_percentage">Incremental Completion % *</label>
              <div className="percentage-input">
                <input
                  type="number"
                  id="incremental_completion_percentage"
                  name="incremental_completion_percentage"
                  value={dailyForm.incremental_completion_percentage}
                  onChange={handleDailyInputChange}
                  onBlur={() => markFieldTouched('incremental_completion_percentage')}
                  min="0"
                  max="100"
                  step="0.1"
                  required
                  className={getFieldErrorClass('incremental_completion_percentage')}
                  placeholder="0.0"
                />
                <span className="percentage-symbol">%</span>
              </div>
              <div className="field-info">Daily progress should typically be 0.5% - 5%</div>
              {renderFieldError('incremental_completion_percentage')}
            </div>
            
            <div className={`form-group ${getFieldErrorClass('working_hours')}`}>
              <label htmlFor="working_hours">Working Hours *</label>
              <input
                type="number"
                id="working_hours"
                name="working_hours"
                value={dailyForm.working_hours}
                onChange={handleDailyInputChange}
                onBlur={() => markFieldTouched('working_hours')}
                min="0"
                max="16"
                step="0.5"
                className={getFieldErrorClass('working_hours')}
                placeholder="8.0"
              />
              <div className="field-info">Standard: 8 hours, Maximum: 16 hours</div>
              {renderFieldError('working_hours')}
            </div>
          </div>

          <div className="form-row">
            <div className={`form-group ${getFieldErrorClass('weather_condition')}`}>
              <label htmlFor="weather_condition">Weather Condition *</label>
              <select
                id="weather_condition"
                name="weather_condition"
                value={dailyForm.weather_condition}
                onChange={handleDailyInputChange}
                onBlur={() => markFieldTouched('weather_condition')}
                required
                className={getFieldErrorClass('weather_condition')}
              >
                <option value="">Select weather...</option>
                {weatherConditions.map(weather => (
                  <option key={weather} value={weather}>{weather}</option>
                ))}
              </select>
              {renderFieldError('weather_condition')}
            </div>
          </div>

          <div className={`form-group ${getFieldErrorClass('site_issues')}`}>
            <label htmlFor="site_issues">Site Issues (if any)</label>
            <textarea
              id="site_issues"
              name="site_issues"
              value={dailyForm.site_issues}
              onChange={handleDailyInputChange}
              onBlur={() => markFieldTouched('site_issues')}
              placeholder="Describe any issues encountered at the site..."
              rows="3"
              className={getFieldErrorClass('site_issues')}
              maxLength="1000"
            />
            <div className="field-info">{dailyForm.site_issues.length}/1000 characters</div>
            {renderFieldError('site_issues')}
          </div>

          {/* Labour Tracking Section */}
          <div className="labour-tracking-section">
            <div className="section-header">
              <h5>👷 Labour Tracking & Management</h5>
              <button type="button" onClick={addLabourEntry} className="add-labour-btn">
                + Add Worker Type
              </button>
            </div>
            
            {dailyForm.labour_data.length === 0 && (
              <div className="no-labour-message">
                <p>No labour entries added yet. Click "Add Worker Type" to start tracking workers.</p>
              </div>
            )}
            
            {dailyForm.labour_data.map((labour, index) => (
              <div key={index} className="labour-entry">
                <div className="labour-entry-header">
                  <h6>Worker Entry #{index + 1}</h6>
                  <button 
                    type="button" 
                    onClick={() => removeLabourEntry(index)}
                    className="remove-labour-btn"
                    title="Remove entry"
                  >
                    ×
                  </button>
                </div>
                
                {/* Primary Labour Information */}
                <div className="labour-row primary-info">
                  <div className={`form-group worker-type-group ${validationErrors[`labour_data_${index}_worker_type`] ? 'error' : ''}`}>
                    <label>Worker Type *</label>
                    <select
                      value={labour.worker_type}
                      onChange={(e) => handleLabourChange(index, 'worker_type', e.target.value)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_worker_type`]: true }))}
                      required
                      disabled={loadingWorkers || !dailyForm.construction_stage}
                    >
                      <option value="">
                        {!dailyForm.construction_stage 
                          ? 'Select construction stage first'
                          : loadingWorkers 
                            ? 'Loading workers...' 
                            : 'Select Type'
                        }
                      </option>
                      {availableWorkerTypes.map(type => (
                        <option key={type} value={type}>{type}</option>
                      ))}
                    </select>
                    {!dailyForm.construction_stage && (
                      <div className="field-info">⚠️ Please select a construction stage first to see relevant worker types</div>
                    )}
                    {loadingWorkers && dailyForm.construction_stage && (
                      <div className="field-info">🔄 Loading phase-specific workers...</div>
                    )}
                    {phaseWorkers && !loadingWorkers && dailyForm.construction_stage && (
                      <div className="field-info">
                        ✅ Showing {availableWorkerTypes.length} worker types for {dailyForm.construction_stage} phase
                      </div>
                    )}
                    {fieldTouched[`labour_data_${index}_worker_type`] && validationErrors[`labour_data_${index}_worker_type`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_worker_type`]}</span>
                      </div>
                    )}
                  </div>
                  
                  <div className={`form-group worker-count-group ${validationErrors[`labour_data_${index}_worker_count`] ? 'error' : ''}`}>
                    <label>Worker Count *</label>
                    <input
                      type="number"
                      value={labour.worker_count}
                      onChange={(e) => handleLabourChange(index, 'worker_count', parseInt(e.target.value) || 0)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_worker_count`]: true }))}
                      min="0"
                      max="100"
                      placeholder="0"
                      required
                    />
                    <div className="field-info">Maximum: 100 workers</div>
                    {fieldTouched[`labour_data_${index}_worker_count`] && validationErrors[`labour_data_${index}_worker_count`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_worker_count`]}</span>
                      </div>
                    )}
                  </div>
                  
                  <div className={`form-group hours-worked-group ${validationErrors[`labour_data_${index}_hours_worked`] ? 'error' : ''}`}>
                    <label>Regular Hours *</label>
                    <input
                      type="number"
                      value={labour.hours_worked}
                      onChange={(e) => handleLabourChange(index, 'hours_worked', parseFloat(e.target.value) || 0)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_hours_worked`]: true }))}
                      min="0"
                      max="12"
                      step="0.5"
                      placeholder="8.0"
                      required
                    />
                    <div className="field-info">Standard: 8 hours, Max: 12 hours</div>
                    {fieldTouched[`labour_data_${index}_hours_worked`] && validationErrors[`labour_data_${index}_hours_worked`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_hours_worked`]}</span>
                      </div>
                    )}
                  </div>
                  
                  <div className={`form-group overtime-group ${validationErrors[`labour_data_${index}_overtime_hours`] ? 'error' : ''}`}>
                    <label>Overtime Hours</label>
                    <input
                      type="number"
                      value={labour.overtime_hours}
                      onChange={(e) => handleLabourChange(index, 'overtime_hours', parseFloat(e.target.value) || 0)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_overtime_hours`]: true }))}
                      min="0"
                      max="8"
                      step="0.5"
                      placeholder="0.0"
                    />
                    <div className="field-info">Maximum: 8 hours (1.5x rate)</div>
                    {fieldTouched[`labour_data_${index}_overtime_hours`] && validationErrors[`labour_data_${index}_overtime_hours`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_overtime_hours`]}</span>
                      </div>
                    )}
                  </div>
                  
                  <div className={`form-group absent-group ${validationErrors[`labour_data_${index}_absent_count`] ? 'error' : ''}`}>
                    <label>Absent Count</label>
                    <input
                      type="number"
                      value={labour.absent_count}
                      onChange={(e) => handleLabourChange(index, 'absent_count', parseInt(e.target.value) || 0)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_absent_count`]: true }))}
                      min="0"
                      max="50"
                      placeholder="0"
                    />
                    <div className="field-info">Workers who didn't show up</div>
                    {fieldTouched[`labour_data_${index}_absent_count`] && validationErrors[`labour_data_${index}_absent_count`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_absent_count`]}</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Secondary Labour Information */}
                <div className="labour-row secondary-info">
                  <div className={`form-group productivity-group ${validationErrors[`labour_data_${index}_productivity_rating`] ? 'error' : ''}`}>
                    <label>Productivity Rating</label>
                    <select
                      value={labour.productivity_rating}
                      onChange={(e) => handleLabourChange(index, 'productivity_rating', parseInt(e.target.value))}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_productivity_rating`]: true }))}
                    >
                      <option value={1}>1 - Poor ⭐</option>
                      <option value={2}>2 - Below Average ⭐⭐</option>
                      <option value={3}>3 - Average ⭐⭐⭐</option>
                      <option value={4}>4 - Good ⭐⭐⭐⭐</option>
                      <option value={5}>5 - Excellent ⭐⭐⭐⭐⭐</option>
                    </select>
                    <div className="field-info">Rate work quality and efficiency</div>
                    {fieldTouched[`labour_data_${index}_productivity_rating`] && validationErrors[`labour_data_${index}_productivity_rating`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_productivity_rating`]}</span>
                      </div>
                    )}
                  </div>
                  
                  <div className="form-group safety-group">
                    <label>Safety Compliance</label>
                    <select
                      value={labour.safety_compliance}
                      onChange={(e) => handleLabourChange(index, 'safety_compliance', e.target.value)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_safety_compliance`]: true }))}
                    >
                      {safetyComplianceOptions.map(option => (
                        <option key={option} value={option}>
                          {option.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </option>
                      ))}
                    </select>
                    <div className="field-info">Safety protocol adherence</div>
                  </div>
                </div>
                
                {/* Remarks Section */}
                <div className="labour-row remarks-section">
                  <div className={`form-group remarks-group ${validationErrors[`labour_data_${index}_remarks`] ? 'error' : ''}`}>
                    <label>Remarks & Notes</label>
                    <textarea
                      value={labour.remarks}
                      onChange={(e) => handleLabourChange(index, 'remarks', e.target.value)}
                      onBlur={() => setFieldTouched(prev => ({ ...prev, [`labour_data_${index}_remarks`]: true }))}
                      placeholder="Any specific notes about this worker type, performance, issues, or achievements..."
                      rows="2"
                      maxLength="500"
                    />
                    <div className="field-info">{labour.remarks.length}/500 characters</div>
                    {fieldTouched[`labour_data_${index}_remarks`] && validationErrors[`labour_data_${index}_remarks`] && (
                      <div className="field-error">
                        <span className="error-message">{validationErrors[`labour_data_${index}_remarks`]}</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Labour Summary */}
                <div className="labour-summary">
                  <div className="summary-item">
                    <span className="label">Total Workers:</span>
                    <span className="value">{labour.worker_count || 0}</span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Total Hours:</span>
                    <span className="value">
                      {((parseFloat(labour.hours_worked) || 0) + (parseFloat(labour.overtime_hours) || 0)) * (parseInt(labour.worker_count) || 0)} hrs
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Efficiency:</span>
                    <span className="value">{labour.productivity_rating}/5 ⭐</span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Safety:</span>
                    <span className="value">{labour.safety_compliance.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                  </div>
                </div>
                
                {/* Add Worker Button after each entry */}
                <div className="add-worker-after-entry">
                  <button 
                    type="button" 
                    onClick={addLabourEntry}
                    className="add-labour-btn-inline"
                    title="Add another worker type"
                  >
                    + Add Another Worker Type
                  </button>
                </div>
              </div>
            ))}

            {/* Labour Summary Total */}
            {dailyForm.labour_data.length > 0 && (
              <div className="labour-total-summary">
                <h6>📊 Daily Labour Summary</h6>
                <div className="total-summary-grid">
                  <div className="total-item">
                    <span className="label">Total Workers:</span>
                    <span className="value">
                      {dailyForm.labour_data.reduce((sum, labour) => sum + (parseInt(labour.worker_count) || 0), 0)}
                    </span>
                  </div>
                  <div className="total-item">
                    <span className="label">Total Regular Hours:</span>
                    <span className="value">
                      {dailyForm.labour_data.reduce((sum, labour) => 
                        sum + ((parseFloat(labour.hours_worked) || 0) * (parseInt(labour.worker_count) || 0)), 0
                      )} hrs
                    </span>
                  </div>
                  <div className="total-item">
                    <span className="label">Total Overtime Hours:</span>
                    <span className="value">
                      {dailyForm.labour_data.reduce((sum, labour) => 
                        sum + ((parseFloat(labour.overtime_hours) || 0) * (parseInt(labour.worker_count) || 0)), 0
                      )} hrs
                    </span>
                  </div>
                  <div className="total-item">
                    <span className="label">Average Productivity:</span>
                    <span className="value">
                      {dailyForm.labour_data.length > 0 
                        ? (dailyForm.labour_data.reduce((sum, labour) => sum + (parseInt(labour.productivity_rating) || 0), 0) / dailyForm.labour_data.length).toFixed(1)
                        : 0}/5 ⭐
                    </span>
                  </div>
                  <div className="total-item">
                    <span className="label">Total Absent:</span>
                    <span className="value">
                      {dailyForm.labour_data.reduce((sum, labour) => sum + (parseInt(labour.absent_count) || 0), 0)}
                    </span>
                  </div>
                  <div className="total-item">
                    <span className="label">Worker Types:</span>
                    <span className="value">
                      {dailyForm.labour_data.length} types
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>



          {/* Enhanced Photo Upload Section */}
          <div className="photo-upload-section">
            <div className="photo-section-header">
              <h5>📸 Progress Photos</h5>
              <div className="photo-upload-options">
                <button
                  type="button"
                  className="geo-photo-btn"
                  onClick={() => setShowGeoPhotoCapture(true)}
                >
                  📍 Capture Geo Photos
                </button>
                <button
                  type="button"
                  className="regular-upload-btn"
                  onClick={() => fileInputRef.current?.click()}
                >
                  📁 Upload Files
                </button>
              </div>
            </div>

            <div className={`form-group ${getFieldErrorClass('progress_photos')}`}>
              <input
                type="file"
                id="progress_photos"
                ref={fileInputRef}
                onChange={handlePhotoChange}
                accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                multiple
                className="file-input-hidden"
                style={{ display: 'none' }}
              />
              
              <div className="photo-requirements">
                <div className="requirements-grid">
                  <div className="requirement-item">
                    <span className="icon">📷</span>
                    <span>Up to 10 photos</span>
                  </div>
                  <div className="requirement-item">
                    <span className="icon">📏</span>
                    <span>Max 5MB each</span>
                  </div>
                  <div className="requirement-item">
                    <span className="icon">🌟</span>
                    <span>Clear, well-lit images</span>
                  </div>
                  <div className="requirement-item">
                    <span className="icon">📍</span>
                    <span>Geo-location recommended</span>
                  </div>
                </div>
                
                {dailyForm.incremental_completion_percentage >= 10 && (
                  <div className="mandatory-notice">
                    <span className="icon">⚠️</span>
                    <strong>Photos are mandatory for completion claims of 10% or more</strong>
                  </div>
                )}
              </div>
              {renderFieldError('progress_photos')}
            </div>

            {/* Combined Photo Preview */}
            {(photos.length > 0 || geoPhotos.length > 0) && (
              <div className="photo-preview">
                <h5>
                  Progress Photos ({photos.length + geoPhotos.length}/10)
                  {geoPhotos.length > 0 && (
                    <span className="geo-badge">📍 {geoPhotos.length} geo-located</span>
                  )}
                </h5>
                
                <div className="photo-grid">
                  {/* Regular uploaded photos */}
                  {photos.map((photo, index) => (
                    <div key={`regular-${index}`} className="photo-item">
                      <img
                        src={URL.createObjectURL(photo)}
                        alt={`Preview ${index + 1}`}
                        className="photo-thumbnail"
                      />
                      <button
                        type="button"
                        onClick={() => removePhoto(index)}
                        className="remove-photo-btn"
                        title="Remove photo"
                      >
                        ×
                      </button>
                      <div className="photo-info">
                        <div className="photo-name">{photo.name}</div>
                        <div className="photo-type">📁 Uploaded</div>
                      </div>
                    </div>
                  ))}
                  
                  {/* Geo-located photos */}
                  {geoPhotos.map((geoPhoto, index) => (
                    <div key={`geo-${index}`} className="photo-item geo-photo">
                      <img
                        src={geoPhoto.url}
                        alt={`Geo Photo ${index + 1}`}
                        className="photo-thumbnail"
                      />
                      <button
                        type="button"
                        onClick={() => removeGeoPhoto(index)}
                        className="remove-photo-btn"
                        title="Remove geo photo"
                      >
                        ×
                      </button>
                      <div className="photo-info">
                        <div className="photo-name">{geoPhoto.filename}</div>
                        <div className="photo-location">📍 {geoPhoto.location?.placeName || 'Located'}</div>
                      </div>
                      <div className="geo-indicator">📍</div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Location Status */}
          <div className="location-status">
            {gettingLocation ? (
              <div className="location-info">📍 Getting your location...</div>
            ) : location.latitude ? (
              <div className="location-info success">📍 Location captured for verification</div>
            ) : (
              <div className="location-info warning">📍 Location not available (optional)</div>
            )}
          </div>

          <div className="form-actions">
            <button
              type="submit"
              disabled={loading || loadingProjects}
              className="submit-btn daily-btn"
            >
              {loading ? 'Submitting...' : 'Submit Daily Update'}
            </button>
          </div>
        </form>
      )}

      {/* Weekly Progress Summary Section */}
      {activeSection === 'weekly' && (
        <form onSubmit={submitWeeklySummary} className="progress-section weekly-section">
          <h4>📊 Weekly Progress Summary</h4>
          
          <div className="form-row">
            <div className={`form-group ${getFieldErrorClass('week_start_date')}`}>
              <label htmlFor="week_start_date">Week Start Date *</label>
              <input
                type="date"
                id="week_start_date"
                name="week_start_date"
                value={weeklyForm.week_start_date}
                onChange={handleWeeklyInputChange}
                onBlur={() => markFieldTouched('week_start_date')}
                required
                className={getFieldErrorClass('week_start_date')}
              />
              <div className="field-info">Should typically start on Monday</div>
              {renderFieldError('week_start_date')}
            </div>
            
            <div className={`form-group ${getFieldErrorClass('week_end_date')}`}>
              <label htmlFor="week_end_date">Week End Date *</label>
              <input
                type="date"
                id="week_end_date"
                name="week_end_date"
                value={weeklyForm.week_end_date}
                onChange={handleWeeklyInputChange}
                onBlur={() => markFieldTouched('week_end_date')}
                required
                className={getFieldErrorClass('week_end_date')}
              />
              <div className="field-info">Should be within 7 days of start date</div>
              {renderFieldError('week_end_date')}
            </div>
          </div>

          <div className={`form-group ${getFieldErrorClass('stages_worked')}`}>
            <label>Stages Worked This Week *</label>
            <div className="checkbox-grid">
              {stages.map(stage => (
                <label key={stage} className="checkbox-item">
                  <input
                    type="checkbox"
                    checked={weeklyForm.stages_worked.includes(stage)}
                    onChange={() => handleStageToggle(stage)}
                  />
                  <span>{stage}</span>
                </label>
              ))}
            </div>
            <div className="field-info">
              Selected: {weeklyForm.stages_worked.length} stage{weeklyForm.stages_worked.length !== 1 ? 's' : ''}
              {weeklyForm.stages_worked.length === 0 && <span className="char-warning"> (at least 1 required)</span>}
            </div>
            {renderFieldError('stages_worked')}
          </div>

          <div className={`form-group ${getFieldErrorClass('delays_and_reasons')}`}>
            <label htmlFor="delays_and_reasons">Delays & Reasons</label>
            <textarea
              id="delays_and_reasons"
              name="delays_and_reasons"
              value={weeklyForm.delays_and_reasons}
              onChange={handleWeeklyInputChange}
              onBlur={() => markFieldTouched('delays_and_reasons')}
              placeholder="Describe any delays encountered this week and their reasons..."
              rows="4"
              className={getFieldErrorClass('delays_and_reasons')}
              maxLength="1000"
            />
            <div className="field-info">{weeklyForm.delays_and_reasons.length}/1000 characters</div>
            {renderFieldError('delays_and_reasons')}
          </div>

          <div className={`form-group ${getFieldErrorClass('weekly_remarks')}`}>
            <label htmlFor="weekly_remarks">Weekly Remarks *</label>
            <textarea
              id="weekly_remarks"
              name="weekly_remarks"
              value={weeklyForm.weekly_remarks}
              onChange={handleWeeklyInputChange}
              onBlur={() => markFieldTouched('weekly_remarks')}
              placeholder="Provide a comprehensive summary of the week's progress, achievements, and observations... (minimum 20 characters)"
              rows="5"
              required
              className={getFieldErrorClass('weekly_remarks')}
              maxLength="2000"
            />
            <div className="field-info">
              {weeklyForm.weekly_remarks.length}/2000 characters
              {weeklyForm.weekly_remarks.length < 20 && weeklyForm.weekly_remarks.length > 0 && (
                <span className="char-warning"> (minimum 20 required)</span>
              )}
            </div>
            {renderFieldError('weekly_remarks')}
          </div>

          <div className="form-actions">
            <button
              type="submit"
              disabled={loading || loadingProjects}
              className="submit-btn weekly-btn"
            >
              {loading ? 'Submitting...' : 'Submit Weekly Summary'}
            </button>
          </div>
        </form>
      )}

      {/* Monthly Progress Report Section */}
      {activeSection === 'monthly' && (
        <form onSubmit={submitMonthlyReport} className="progress-section monthly-section">
          <h4>📈 Monthly Progress Report</h4>
          
          <div className="form-row">
            <div className={`form-group ${getFieldErrorClass('report_month')}`}>
              <label htmlFor="report_month">Report Month *</label>
              <select
                id="report_month"
                name="report_month"
                value={monthlyForm.report_month}
                onChange={handleMonthlyInputChange}
                onBlur={() => markFieldTouched('report_month')}
                required
                className={getFieldErrorClass('report_month')}
              >
                {months.map((month, index) => (
                  <option key={index + 1} value={index + 1}>{month}</option>
                ))}
              </select>
              {renderFieldError('report_month')}
            </div>
            
            <div className={`form-group ${getFieldErrorClass('report_year')}`}>
              <label htmlFor="report_year">Report Year *</label>
              <input
                type="number"
                id="report_year"
                name="report_year"
                value={monthlyForm.report_year}
                onChange={handleMonthlyInputChange}
                onBlur={() => markFieldTouched('report_year')}
                min="2020"
                max="2050"
                required
                className={getFieldErrorClass('report_year')}
                placeholder="2024"
              />
              <div className="field-info">Range: 2020 - {new Date().getFullYear() + 1}</div>
              {renderFieldError('report_year')}
            </div>
          </div>

          <div className={`form-group ${getFieldErrorClass('planned_progress_percentage')}`}>
            <label htmlFor="planned_progress_percentage">Planned Progress % *</label>
            <div className="percentage-input">
              <input
                type="number"
                id="planned_progress_percentage"
                name="planned_progress_percentage"
                value={monthlyForm.planned_progress_percentage}
                onChange={handleMonthlyInputChange}
                onBlur={() => markFieldTouched('planned_progress_percentage')}
                min="0"
                max="100"
                step="0.1"
                required
                className={getFieldErrorClass('planned_progress_percentage')}
                placeholder="0.0"
              />
              <span className="percentage-symbol">%</span>
            </div>
            <div className="field-info">Expected progress percentage for this month</div>
            {renderFieldError('planned_progress_percentage')}
          </div>

          <div className="form-group">
            <label>Milestones Achieved This Month</label>
            <div className="checkbox-grid">
              {[
                'Foundation Excavation Complete',
                'Foundation Concrete Pour',
                'Ground Floor Structure',
                'First Floor Structure',
                'Roofing Complete',
                'Electrical Rough-in',
                'Plumbing Rough-in',
                'Final Finishing'
              ].map(milestone => (
                <label key={milestone} className="checkbox-item">
                  <input
                    type="checkbox"
                    checked={monthlyForm.milestones_achieved.includes(milestone)}
                    onChange={() => handleMilestoneToggle(milestone)}
                  />
                  <span>{milestone}</span>
                </label>
              ))}
            </div>
            <div className="field-info">
              Selected: {monthlyForm.milestones_achieved.length} milestone{monthlyForm.milestones_achieved.length !== 1 ? 's' : ''}
            </div>
          </div>

          <div className={`form-group ${getFieldErrorClass('delay_explanation')}`}>
            <label htmlFor="delay_explanation">Delay Explanation</label>
            <textarea
              id="delay_explanation"
              name="delay_explanation"
              value={monthlyForm.delay_explanation}
              onChange={handleMonthlyInputChange}
              onBlur={() => markFieldTouched('delay_explanation')}
              placeholder="Explain any delays that occurred this month and their impact on the project timeline..."
              rows="4"
              className={getFieldErrorClass('delay_explanation')}
              maxLength="2000"
            />
            <div className="field-info">{monthlyForm.delay_explanation.length}/2000 characters</div>
            {renderFieldError('delay_explanation')}
          </div>

          <div className={`form-group ${getFieldErrorClass('contractor_remarks')}`}>
            <label htmlFor="contractor_remarks">Contractor Remarks *</label>
            <textarea
              id="contractor_remarks"
              name="contractor_remarks"
              value={monthlyForm.contractor_remarks}
              onChange={handleMonthlyInputChange}
              onBlur={() => markFieldTouched('contractor_remarks')}
              placeholder="Provide detailed remarks about the month's progress, challenges faced, quality of work, and plans for next month... (minimum 50 characters)"
              rows="6"
              required
              className={getFieldErrorClass('contractor_remarks')}
              maxLength="3000"
            />
            <div className="field-info">
              {monthlyForm.contractor_remarks.length}/3000 characters
              {monthlyForm.contractor_remarks.length < 50 && monthlyForm.contractor_remarks.length > 0 && (
                <span className="char-warning"> (minimum 50 required)</span>
              )}
            </div>
            {renderFieldError('contractor_remarks')}
          </div>

          <div className="form-actions">
            <button
              type="submit"
              disabled={loading || loadingProjects}
              className="submit-btn monthly-btn"
            >
              {loading ? 'Submitting...' : 'Submit Monthly Report'}
            </button>
          </div>
        </form>
      )}

      {/* Geo Photo Capture Modal */}
      {showGeoPhotoCapture && (
        <div className="modal-overlay">
          <div className="modal-container">
            <GeoPhotoCapture
              projectId={selectedProject}
              contractorId={contractorId}
              onPhotosCaptured={handleGeoPhotosCaptured}
              onClose={() => setShowGeoPhotoCapture(false)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default EnhancedProgressUpdate;