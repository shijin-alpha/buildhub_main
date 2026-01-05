import React, { useState, useEffect, useRef, useCallback } from 'react';
import '../styles/HousePlanDrawer.css';
import NotificationToast from './NotificationToast';
import HousePlanTour from './HousePlanTour';
import HousePlanHelp from './HousePlanHelp';
import { useNotifications } from '../hooks/useNotifications';

const HousePlanDrawer = ({ 
  layoutRequestId = null, 
  requestInfo = null,
  existingPlan = null, 
  onSave, 
  onCancel 
}) => {
  const canvasRef = useRef(null);
  const [canvas, setCanvas] = useState(null);
  const [selectedTool, setSelectedTool] = useState('select');
  const [selectedRoom, setSelectedRoom] = useState(null);
  const [roomTemplates, setRoomTemplates] = useState([]);
  const [loading, setLoading] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [isResizing, setIsResizing] = useState(false);
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [resizeHandle, setResizeHandle] = useState(null);
  const [showMeasurements, setShowMeasurements] = useState(true);
  const [measurementMode, setMeasurementMode] = useState('both'); // 'layout', 'actual', 'both'

  // Undo/Redo state
  const [history, setHistory] = useState([]);
  const [historyIndex, setHistoryIndex] = useState(-1);
  const [isUndoRedo, setIsUndoRedo] = useState(false);

  // Notification system
  const {
    notifications,
    removeNotification,
    showSuccess,
    showError,
    showWarning,
    showInfo
  } = useNotifications();

  // Auto-save functionality state
  const [autoSaveTimer, setAutoSaveTimer] = useState(null);
  const [lastSaved, setLastSaved] = useState(null);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

  // Tour and Help state
  const [showTour, setShowTour] = useState(false);
  const [showHelp, setShowHelp] = useState(false);
  const [isFirstTime, setIsFirstTime] = useState(false);

  // Plan data - Initialize with proper handling of existing plan
  const [planData, setPlanData] = useState(() => {
    if (existingPlan) {
      // Parse plan_data if it's a string
      let parsedPlanData = existingPlan.plan_data;
      if (typeof parsedPlanData === 'string') {
        try {
          parsedPlanData = JSON.parse(parsedPlanData);
        } catch (e) {
          console.error('Error parsing existing plan data:', e);
          parsedPlanData = { rooms: [], scale_ratio: 1.2 };
        }
      }
      
      return {
        plan_name: existingPlan.plan_name || '',
        plot_width: existingPlan.plot_width || 100,
        plot_height: existingPlan.plot_height || 100,
        rooms: parsedPlanData?.rooms || [],
        notes: existingPlan.notes || '',
        scale_ratio: parsedPlanData?.scale_ratio || 1.2
      };
    }
    
    return {
      plan_name: '',
      plot_width: 100,
      plot_height: 100,
      rooms: [],
      notes: '',
      scale_ratio: 1.2
    };
  });

  // Canvas settings
  const GRID_SIZE = 20;
  const PIXELS_PER_FOOT = 20; // Layout scale: 1 foot = 20 pixels
  const RESIZE_HANDLE_SIZE = 8;

  useEffect(() => {
    loadRoomTemplates();
    // Load request information if layoutRequestId is provided
    if (layoutRequestId || requestInfo) {
      loadRequestInfo();
    }
    // Initialize history with empty state
    saveToHistory();
    
    // Check if this is the user's first time
    const hasSeenTour = localStorage.getItem('housePlanTourCompleted');
    if (!hasSeenTour) {
      setIsFirstTime(true);
      setTimeout(() => setShowTour(true), 1000); // Show tour after component loads
    }
  }, [layoutRequestId, requestInfo]);

  // Warn user about unsaved changes when leaving
  useEffect(() => {
    const handleBeforeUnload = (e) => {
      if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return 'You have unsaved changes. Are you sure you want to leave?';
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [hasUnsavedChanges]);

  // Handle existing plan changes (when switching between edit modes)
  useEffect(() => {
    if (existingPlan) {
      // Parse plan_data if it's a string
      let parsedPlanData = existingPlan.plan_data;
      if (typeof parsedPlanData === 'string') {
        try {
          parsedPlanData = JSON.parse(parsedPlanData);
        } catch (e) {
          console.error('Error parsing existing plan data:', e);
          parsedPlanData = { rooms: [], scale_ratio: 1.2 };
        }
      }
      
      setPlanData({
        plan_name: existingPlan.plan_name || '',
        plot_width: existingPlan.plot_width || 100,
        plot_height: existingPlan.plot_height || 100,
        rooms: parsedPlanData?.rooms || [],
        notes: existingPlan.notes || '',
        scale_ratio: parsedPlanData?.scale_ratio || 1.2
      });
      
      // Reset history when loading existing plan
      setHistory([]);
      setHistoryIndex(-1);
      setHasUnsavedChanges(false);
      setLastSaved(existingPlan.updated_at ? new Date(existingPlan.updated_at) : null);
      
      // Save initial state to history after a short delay
      setTimeout(() => {
        saveToHistory();
      }, 100);
    }
  }, [existingPlan]);

  // Save current state to history
  const saveToHistory = useCallback(() => {
    if (isUndoRedo) return; // Don't save during undo/redo operations
    
    const currentState = {
      rooms: JSON.parse(JSON.stringify(planData.rooms)),
      plot_width: planData.plot_width,
      plot_height: planData.plot_height,
      scale_ratio: planData.scale_ratio,
      timestamp: Date.now()
    };

    setHistory(prev => {
      // Remove any history after current index (when user made changes after undo)
      const newHistory = prev.slice(0, historyIndex + 1);
      newHistory.push(currentState);
      
      // Limit history to 50 states
      if (newHistory.length > 50) {
        newHistory.shift();
        return newHistory;
      }
      
      return newHistory;
    });
    
    setHistoryIndex(prev => Math.min(prev + 1, 49));
  }, [planData.rooms, planData.plot_width, planData.plot_height, planData.scale_ratio, historyIndex, isUndoRedo]);

  // Undo function
  const undo = useCallback(() => {
    if (historyIndex > 0) {
      setIsUndoRedo(true);
      const previousState = history[historyIndex - 1];
      setPlanData(prev => ({
        ...prev,
        rooms: JSON.parse(JSON.stringify(previousState.rooms)),
        plot_width: previousState.plot_width,
        plot_height: previousState.plot_height,
        scale_ratio: previousState.scale_ratio
      }));
      setHistoryIndex(prev => prev - 1);
      setSelectedRoom(null);
      setTimeout(() => setIsUndoRedo(false), 100);
      
      showInfo('Action Undone', `Reverted to previous state (${historyIndex}/${history.length})`);
    }
  }, [history, historyIndex, showInfo]);

  // Redo function
  const redo = useCallback(() => {
    if (historyIndex < history.length - 1) {
      setIsUndoRedo(true);
      const nextState = history[historyIndex + 1];
      setPlanData(prev => ({
        ...prev,
        rooms: JSON.parse(JSON.stringify(nextState.rooms)),
        plot_width: nextState.plot_width,
        plot_height: nextState.plot_height,
        scale_ratio: nextState.scale_ratio
      }));
      setHistoryIndex(prev => prev + 1);
      setSelectedRoom(null);
      setTimeout(() => setIsUndoRedo(false), 100);
      
      showInfo('Action Redone', `Restored to next state (${historyIndex + 2}/${history.length})`);
    }
  }, [history, historyIndex, showInfo]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e) => {
      if (e.ctrlKey || e.metaKey) {
        if (e.key === 'z' && !e.shiftKey) {
          e.preventDefault();
          undo();
        } else if ((e.key === 'z' && e.shiftKey) || e.key === 'y') {
          e.preventDefault();
          redo();
        }
      }
      
      // Delete selected room with Delete key
      if (e.key === 'Delete' && selectedRoom !== null) {
        e.preventDefault();
        deleteSelectedRoom();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [undo, redo, selectedRoom]);

  // Save to history when rooms change (but not during undo/redo)
  useEffect(() => {
    if (!isUndoRedo && planData.rooms.length >= 0) {
      const timeoutId = setTimeout(() => {
        saveToHistory();
      }, 500); // Debounce to avoid too many history entries
      
      return () => clearTimeout(timeoutId);
    }
  }, [planData.rooms, saveToHistory, isUndoRedo]);

  // Auto-save when plan data changes
  useEffect(() => {
    if (planData.plan_name && planData.rooms.length > 0 && !isUndoRedo) {
      setHasUnsavedChanges(true);
      
      // Clear existing timer
      if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
      }
      
      // Set new timer for auto-save (30 seconds after last change)
      const timer = setTimeout(() => {
        handleAutoSave();
      }, 30000);
      
      setAutoSaveTimer(timer);
    }
    
    return () => {
      if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
      }
    };
  }, [planData, isUndoRedo]);

  // Define drawing functions first
  const drawGrid = () => {
    canvas.strokeStyle = '#e8e8e8';
    canvas.lineWidth = 0.5;

    const canvasElement = canvasRef.current;
    
    // Vertical lines
    for (let x = 0; x <= canvasElement.width; x += GRID_SIZE) {
      canvas.beginPath();
      canvas.moveTo(x, 0);
      canvas.lineTo(x, canvasElement.height);
      canvas.stroke();
    }

    // Horizontal lines
    for (let y = 0; y <= canvasElement.height; y += GRID_SIZE) {
      canvas.beginPath();
      canvas.moveTo(0, y);
      canvas.lineTo(canvasElement.width, y);
      canvas.stroke();
    }

    // Draw major grid lines every 5 feet
    canvas.strokeStyle = '#d0d0d0';
    canvas.lineWidth = 1;
    
    for (let x = 0; x <= canvasElement.width; x += GRID_SIZE * 5) {
      canvas.beginPath();
      canvas.moveTo(x, 0);
      canvas.lineTo(x, canvasElement.height);
      canvas.stroke();
    }

    for (let y = 0; y <= canvasElement.height; y += GRID_SIZE * 5) {
      canvas.beginPath();
      canvas.moveTo(0, y);
      canvas.lineTo(canvasElement.width, y);
      canvas.stroke();
    }
  };

  const drawPlotBoundary = () => {
    canvas.strokeStyle = '#2c3e50';
    canvas.lineWidth = 3;
    canvas.setLineDash([]);

    const plotPixelWidth = planData.plot_width * PIXELS_PER_FOOT;
    const plotPixelHeight = planData.plot_height * PIXELS_PER_FOOT;

    canvas.strokeRect(20, 20, plotPixelWidth, plotPixelHeight);

    // Add plot dimensions
    canvas.fillStyle = '#2c3e50';
    canvas.font = 'bold 14px Arial';
    canvas.textAlign = 'center';
    
    // Top dimension
    canvas.fillText(`${planData.plot_width}'`, 20 + plotPixelWidth / 2, 15);
    
    // Left dimension
    canvas.save();
    canvas.translate(10, 20 + plotPixelHeight / 2);
    canvas.rotate(-Math.PI / 2);
    canvas.fillText(`${planData.plot_height}'`, 0, 0);
    canvas.restore();
  };

  const adjustColor = (color, amount) => {
    const usePound = color[0] === '#';
    const col = usePound ? color.slice(1) : color;
    const num = parseInt(col, 16);
    let r = (num >> 16) + amount;
    let g = (num >> 8 & 0x00FF) + amount;
    let b = (num & 0x0000FF) + amount;
    r = r > 255 ? 255 : r < 0 ? 0 : r;
    g = g > 255 ? 255 : g < 0 ? 0 : g;
    b = b > 255 ? 255 : b < 0 ? 0 : b;
    return (usePound ? '#' : '') + (r << 16 | g << 8 | b).toString(16).padStart(6, '0');
  };

  // Get color based on room type for better visual identification
  const getRoomColor = (roomType) => {
    const colorMap = {
      // Bedrooms (Green tones)
      'master_bedroom': '#c8e6c9',
      'bedroom': '#dcedc8',
      'bedrooms': '#dcedc8',
      'guest_bedroom': '#e8f5e8',
      'kids_bedroom': '#f1f8e9',
      
      // Bathrooms (Blue tones)
      'master_bathroom': '#b3e5fc',
      'bathroom': '#e1f5fe',
      'bathrooms': '#e1f5fe',
      'attached_bathroom': '#e3f2fd',
      'powder_room': '#f0f8ff',
      
      // Kitchen (Pink/Red tones)
      'kitchen': '#ffcdd2',
      'modular_kitchen': '#f8bbd9',
      'pantry': '#fce4ec',
      
      // Living Areas (Orange tones)
      'living_room': '#ffe0b2',
      'family_room': '#ffcc80',
      'drawing_room': '#fff3e0',
      'tv_lounge': '#ffe0b2',
      
      // Dining Areas (Purple tones)
      'dining_room': '#e1bee7',
      'breakfast_area': '#f3e5f5',
      
      // Utility Areas (Gray tones)
      'utility_room': '#e0e0e0',
      'laundry_room': '#eeeeee',
      'store_room': '#f5f5f5',
      'servant_room': '#e8eaf6',
      
      // Outdoor Areas (Light Green tones)
      'balcony': '#c8e6c9',
      'terrace': '#dcedc8',
      'garden': '#e8f5e8',
      'courtyard': '#f1f8e9',
      
      // Circulation Areas (Yellow tones)
      'corridor': '#fff9c4',
      'hallway': '#fff59d',
      'passage': '#ffecb3',
      'entrance_hall': '#ffe082',
      'foyer': '#ffd54f',
      
      // Structural Elements (Brown tones)
      'staircase': '#d7ccc8',
      'spiral_staircase': '#bcaaa4',
      'elevator_shaft': '#a1887f',
      'column': '#8d6e63',
      'beam_area': '#795548',
      
      // Other Special Rooms (Light Purple tones)
      'study_room': '#e8eaf6',
      'home_office': '#c5cae9',
      'pooja_room': '#d1c4e9',
      'prayer_room': '#b39ddb',
      'home_theater': '#9575cd',
      'gym': '#7e57c2',
      'library': '#673ab7',
      'music_room': '#5e35b1',
      'workshop': '#512da8',
      'safe_room': '#4527a0'
    };
    
    return colorMap[roomType] || '#e3f2fd'; // Default light blue
  };

  const drawRoom = (room, isSelected) => {
    // Validate room properties to prevent non-finite values
    if (!room || typeof room.x !== 'number' || typeof room.y !== 'number' ||
        !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
        room.layout_width <= 0 || room.layout_height <= 0) {
      console.warn('Invalid room data:', room);
      return; // Skip drawing invalid rooms
    }

    const x = room.x + 20;
    const y = room.y + 20;
    const width = room.layout_width * PIXELS_PER_FOOT;
    const height = room.layout_height * PIXELS_PER_FOOT;

    // Additional validation for calculated dimensions
    if (!isFinite(width) || !isFinite(height) || width <= 0 || height <= 0) {
      console.warn('Invalid calculated dimensions:', { width, height, room });
      return;
    }

    // Room background with gradient
    const gradient = canvas.createLinearGradient(x, y, x + width, y + height);
    gradient.addColorStop(0, room.color || '#e3f2fd');
    gradient.addColorStop(1, adjustColor(room.color || '#e3f2fd', -10));
    
    canvas.fillStyle = gradient;
    canvas.fillRect(x, y, width, height);

    // Room border
    canvas.strokeStyle = isSelected ? '#1976d2' : '#666';
    canvas.lineWidth = isSelected ? 3 : 1;
    canvas.setLineDash([]);
    canvas.strokeRect(x, y, width, height);

    // Room label and dimensions
    canvas.fillStyle = '#333';
    canvas.font = 'bold 12px Arial';
    canvas.textAlign = 'center';
    
    const centerX = x + width / 2;
    const centerY = y + height / 2;
    
    // Room name
    canvas.fillText(room.name, centerX, centerY - 15);
    
    // Layout dimensions
    if (measurementMode === 'layout' || measurementMode === 'both') {
      canvas.font = '10px Arial';
      canvas.fillStyle = '#666';
      canvas.fillText(`Layout: ${room.layout_width}' × ${room.layout_height}'`, centerX, centerY);
    }
    
    // Actual construction dimensions
    if (measurementMode === 'actual' || measurementMode === 'both') {
      canvas.font = '10px Arial';
      canvas.fillStyle = '#d32f2f';
      const actualWidth = (room.actual_width || room.layout_width * planData.scale_ratio).toFixed(1);
      const actualHeight = (room.actual_height || room.layout_height * planData.scale_ratio).toFixed(1);
      canvas.fillText(`Actual: ${actualWidth}' × ${actualHeight}'`, centerX, centerY + 15);
    }

    // Area calculation
    canvas.font = '9px Arial';
    canvas.fillStyle = '#888';
    const layoutArea = (room.layout_width * room.layout_height).toFixed(1);
    const actualArea = ((room.actual_width || room.layout_width * planData.scale_ratio) * 
                      (room.actual_height || room.layout_height * planData.scale_ratio)).toFixed(1);
    
    if (measurementMode === 'both') {
      canvas.fillText(`L: ${layoutArea} sq ft | A: ${actualArea} sq ft`, centerX, centerY + 30);
    } else if (measurementMode === 'actual') {
      canvas.fillText(`${actualArea} sq ft`, centerX, centerY + 30);
    } else {
      canvas.fillText(`${layoutArea} sq ft`, centerX, centerY + 30);
    }

    // Draw resize handles for selected room
    if (isSelected) {
      drawResizeHandles(x, y, width, height);
    }
  };

  const drawResizeHandles = (x, y, width, height) => {
    canvas.fillStyle = '#1976d2';
    canvas.strokeStyle = '#fff';
    canvas.lineWidth = 2;

    const handles = [
      { x: x + width - RESIZE_HANDLE_SIZE/2, y: y + height/2 - RESIZE_HANDLE_SIZE/2, cursor: 'e-resize', type: 'right' },
      { x: x + width/2 - RESIZE_HANDLE_SIZE/2, y: y + height - RESIZE_HANDLE_SIZE/2, cursor: 's-resize', type: 'bottom' },
      { x: x + width - RESIZE_HANDLE_SIZE/2, y: y + height - RESIZE_HANDLE_SIZE/2, cursor: 'se-resize', type: 'corner' }
    ];

    handles.forEach(handle => {
      canvas.fillRect(handle.x, handle.y, RESIZE_HANDLE_SIZE, RESIZE_HANDLE_SIZE);
      canvas.strokeRect(handle.x, handle.y, RESIZE_HANDLE_SIZE, RESIZE_HANDLE_SIZE);
    });
  };

  const drawMeasurements = () => {
    if (!showMeasurements) return;

    canvas.strokeStyle = '#ff6b6b';
    canvas.lineWidth = 1;
    canvas.setLineDash([2, 2]);
    canvas.font = '10px Arial';
    canvas.fillStyle = '#ff6b6b';

    planData.rooms.forEach(room => {
      const x = room.x + 20;
      const y = room.y + 20;
      const width = room.layout_width * PIXELS_PER_FOOT;
      const height = room.layout_height * PIXELS_PER_FOOT;

      // Draw dimension lines
      // Top dimension line
      canvas.beginPath();
      canvas.moveTo(x, y - 15);
      canvas.lineTo(x + width, y - 15);
      canvas.stroke();

      // Left dimension line
      canvas.beginPath();
      canvas.moveTo(x - 15, y);
      canvas.lineTo(x - 15, y + height);
      canvas.stroke();

      // Dimension text
      canvas.textAlign = 'center';
      const actualWidth = (room.actual_width || room.layout_width * planData.scale_ratio).toFixed(1);
      const actualHeight = (room.actual_height || room.layout_height * planData.scale_ratio).toFixed(1);
      
      canvas.fillText(`${actualWidth}'`, x + width/2, y - 20);
      
      canvas.save();
      canvas.translate(x - 20, y + height/2);
      canvas.rotate(-Math.PI / 2);
      canvas.fillText(`${actualHeight}'`, 0, 0);
      canvas.restore();
    });
  };

  const drawCanvas = useCallback(() => {
    if (!canvas || !canvasRef.current) return;

    const canvasElement = canvasRef.current;
    canvas.clearRect(0, 0, canvasElement.width, canvasElement.height);

    // Draw grid
    drawGrid();
    
    // Draw plot boundary
    drawPlotBoundary();
    
    // Draw rooms
    planData.rooms.forEach((room, index) => {
      drawRoom(room, index === selectedRoom);
    });

    // Draw measurements if enabled
    if (showMeasurements) {
      drawMeasurements();
    }
  }, [canvas, planData, selectedRoom, showMeasurements, measurementMode]);

  useEffect(() => {
    if (canvasRef.current) {
      const canvasElement = canvasRef.current;
      const container = canvasElement.parentElement;
      
      // Set canvas size dynamically based on container and plot size
      const containerWidth = container.clientWidth - 32; // Account for padding
      const containerHeight = container.clientHeight - 32;
      
      // Calculate minimum canvas size based on plot dimensions
      const plotPixelWidth = (planData.plot_width * 20) + 100; // 20px per foot + margins
      const plotPixelHeight = (planData.plot_height * 20) + 100;
      
      // Ensure canvas is at least as large as the plot, but allow scrolling for large plots
      const minCanvasWidth = Math.max(plotPixelWidth, 800);
      const minCanvasHeight = Math.max(plotPixelHeight, 600);
      
      // Use larger of container size or minimum required size
      const canvasWidth = Math.max(containerWidth, minCanvasWidth);
      const canvasHeight = Math.max(containerHeight, minCanvasHeight);
      
      // Set canvas dimensions
      canvasElement.width = canvasWidth;
      canvasElement.height = canvasHeight;
      
      const ctx = canvasElement.getContext('2d');
      setCanvas(ctx);
      drawCanvas();
    }
  }, [planData, selectedRoom, showMeasurements, measurementMode, drawCanvas]);

  // Handle window resize to update canvas size
  useEffect(() => {
    const handleResize = () => {
      if (canvasRef.current) {
        const canvasElement = canvasRef.current;
        const container = canvasElement.parentElement;
        
        const containerWidth = container.clientWidth - 32;
        const containerHeight = container.clientHeight - 32;
        
        // Calculate minimum canvas size based on plot dimensions
        const plotPixelWidth = (planData.plot_width * 20) + 100;
        const plotPixelHeight = (planData.plot_height * 20) + 100;
        
        const minCanvasWidth = Math.max(plotPixelWidth, 800);
        const minCanvasHeight = Math.max(plotPixelHeight, 600);
        
        const canvasWidth = Math.max(containerWidth, minCanvasWidth);
        const canvasHeight = Math.max(containerHeight, minCanvasHeight);
        
        canvasElement.width = canvasWidth;
        canvasElement.height = canvasHeight;
        
        drawCanvas();
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [drawCanvas, planData.plot_width, planData.plot_height]);

  useEffect(() => {
    const handleMouseMove = (e) => {
      if (isDragging && selectedRoom !== null) {
        handleRoomDrag(e);
      } else if (isResizing && selectedRoom !== null) {
        handleRoomResize(e);
      }
    };

    const handleMouseUp = () => {
      setIsDragging(false);
      setIsResizing(false);
      setResizeHandle(null);
    };

    if (isDragging || isResizing) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, isResizing, selectedRoom, dragStart, resizeHandle]);

  const loadRoomTemplates = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/get_room_templates.php');
      const result = await response.json();
      if (result.success) {
        setRoomTemplates(result.grouped);
      }
    } catch (error) {
      console.error('Error loading room templates:', error);
    }
  };

  const loadRequestInfo = async () => {
    try {
      let assignment = requestInfo;
      
      // If requestInfo is not passed, fetch it
      if (!assignment && layoutRequestId) {
        const response = await fetch(`/buildhub/backend/api/architect/get_assigned_requests.php`);
        const result = await response.json();
        
        if (result.success) {
          assignment = result.assignments?.find(a => 
            a.layout_request_id === layoutRequestId || 
            a.layout_request?.id === layoutRequestId
          );
        }
      }
      
      if (assignment && assignment.layout_request) {
        const request = assignment.layout_request;
        
        // Parse requirements to get room specifications
        let requirements = null;
        if (request.requirements) {
          try {
            requirements = typeof request.requirements === 'string' 
              ? JSON.parse(request.requirements) 
              : request.requirements;
          } catch (e) {
            console.error('Error parsing requirements:', e);
          }
        }
        
        // Update plan data with request information
        setPlanData(prev => ({
          ...prev,
          plan_name: `${assignment.homeowner?.name || 'Client'} House Plan`,
          plot_width: parseFloat(request.plot_size) || 100,
          plot_height: parseFloat(request.plot_size) || 100, // Assuming square plot if only one dimension
          scale_ratio: prev.scale_ratio || 1.2 // Ensure scale_ratio is available
        }));
        
        // Pre-populate rooms based on requirements
        if (requirements) {
          const prePopulatedRooms = [];
          let roomId = 1;
          let currentY = 50; // Starting Y position
          const currentScaleRatio = 1.2; // Use default scale ratio during initialization
          
          // Handle floor_rooms if available
          if (requirements.floor_rooms) {
            Object.entries(requirements.floor_rooms).forEach(([floorKey, floorRooms]) => {
              const floorNumber = parseInt(floorKey.replace('floor', ''));
              let currentX = 50; // Starting X position for each floor
              
              Object.entries(floorRooms).forEach(([roomType, count]) => {
                const roomCount = typeof count === 'number' ? count : 1;
                
                for (let i = 0; i < roomCount; i++) {
                  const roomName = roomCount > 1 ? `${roomType.replace(/_/g, ' ')} ${i + 1}` : roomType.replace(/_/g, ' ');
                  
                  prePopulatedRooms.push({
                    id: roomId++,
                    name: roomName,
                    type: roomType,
                    x: currentX,
                    y: currentY + (floorNumber - 1) * 200, // Offset floors vertically
                    layout_width: 10, // Updated default layout width in feet
                    layout_height: 10, // Updated default layout height in feet
                    actual_width: 10 * currentScaleRatio,
                    actual_height: 10 * currentScaleRatio,
                    color: getRoomColor(roomType),
                    floor: floorNumber
                  });
                  
                  currentX += 140; // Space rooms horizontally
                  if (currentX > 400) { // Wrap to next row
                    currentX = 50;
                    currentY += 120;
                  }
                }
              });
            });
          } 
          // Handle simple rooms list if floor_rooms not available
          else if (requirements.rooms) {
            const roomsList = typeof requirements.rooms === 'string' 
              ? requirements.rooms.split(',').map(r => r.trim())
              : requirements.rooms;
            
            let currentX = 50;
            
            roomsList.forEach((roomType) => {
              prePopulatedRooms.push({
                id: roomId++,
                name: roomType.replace(/_/g, ' '),
                type: roomType,
                x: currentX,
                y: currentY,
                layout_width: 10, // Updated default layout width in feet
                layout_height: 10, // Updated default layout height in feet
                actual_width: 10 * currentScaleRatio,
                actual_height: 10 * currentScaleRatio,
                color: getRoomColor(roomType),
                floor: 1
              });
              
              currentX += 140;
              if (currentX > 400) {
                currentX = 50;
                currentY += 120;
              }
            });
          }
          
          // Update plan data with pre-populated rooms
          if (prePopulatedRooms.length > 0) {
            setPlanData(prev => ({
              ...prev,
              rooms: prePopulatedRooms
            }));
            
            showInfo('Rooms Pre-loaded', `Added ${prePopulatedRooms.length} rooms based on client requirements`);
          }
        }
      }
    } catch (error) {
      console.error('Error loading request info:', error);
    }
  };

  const snapToGrid = (value) => {
    return Math.round(value / GRID_SIZE) * GRID_SIZE;
  };

  const getCanvasCoordinates = (event) => {
    const rect = canvasRef.current.getBoundingClientRect();
    return {
      x: event.clientX - rect.left,
      y: event.clientY - rect.top
    };
  };

  const getResizeHandle = (x, y, roomX, roomY, roomWidth, roomHeight) => {
    const handles = [
      { x: roomX + roomWidth - RESIZE_HANDLE_SIZE/2, y: roomY + roomHeight/2 - RESIZE_HANDLE_SIZE/2, type: 'right' },
      { x: roomX + roomWidth/2 - RESIZE_HANDLE_SIZE/2, y: roomY + roomHeight - RESIZE_HANDLE_SIZE/2, type: 'bottom' },
      { x: roomX + roomWidth - RESIZE_HANDLE_SIZE/2, y: roomY + roomHeight - RESIZE_HANDLE_SIZE/2, type: 'corner' }
    ];

    for (let handle of handles) {
      if (x >= handle.x && x <= handle.x + RESIZE_HANDLE_SIZE &&
          y >= handle.y && y <= handle.y + RESIZE_HANDLE_SIZE) {
        return handle.type;
      }
    }
    return null;
  };

  const handleCanvasMouseDown = (event) => {
    const coords = getCanvasCoordinates(event);
    
    if (selectedTool === 'select') {
      // Find clicked room
      const clickedRoomIndex = planData.rooms.findIndex(room => {
        // Validate room properties before calculations
        if (!room || !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
            room.layout_width <= 0 || room.layout_height <= 0) {
          return false;
        }
        
        const roomX = room.x + 20;
        const roomY = room.y + 20;
        const roomWidth = room.layout_width * PIXELS_PER_FOOT;
        const roomHeight = room.layout_height * PIXELS_PER_FOOT;
        
        return coords.x >= roomX && coords.x <= roomX + roomWidth &&
               coords.y >= roomY && coords.y <= roomY + roomHeight;
      });
      
      if (clickedRoomIndex >= 0) {
        setSelectedRoom(clickedRoomIndex);
        const room = planData.rooms[clickedRoomIndex];
        
        // Validate room properties before calculations
        if (room && isFinite(room.layout_width) && isFinite(room.layout_height) &&
            room.layout_width > 0 && room.layout_height > 0) {
          const roomX = room.x + 20;
          const roomY = room.y + 20;
          const roomWidth = room.layout_width * PIXELS_PER_FOOT;
          const roomHeight = room.layout_height * PIXELS_PER_FOOT;

        // Check if clicking on resize handle
        const handle = getResizeHandle(coords.x, coords.y, roomX, roomY, roomWidth, roomHeight);
        
        if (handle) {
          setIsResizing(true);
          setResizeHandle(handle);
          setDragStart({ x: coords.x, y: coords.y });
        } else {
          // Start dragging
          setIsDragging(true);
          setDragStart({ 
            x: coords.x - room.x, 
            y: coords.y - room.y 
          });
        }
        } // Close validation block
      } else {
        setSelectedRoom(null);
      }
    }
  };

  const handleRoomDrag = (event) => {
    if (!isDragging || selectedRoom === null) return;

    const coords = getCanvasCoordinates(event);
    const newX = snapToGrid(coords.x - dragStart.x - 20);
    const newY = snapToGrid(coords.y - dragStart.y - 20);

    // Ensure room stays within plot boundaries
    const room = planData.rooms[selectedRoom];
    
    // Validate room properties
    if (!room || !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
        room.layout_width <= 0 || room.layout_height <= 0) {
      return;
    }
    
    const roomWidth = room.layout_width * PIXELS_PER_FOOT;
    const roomHeight = room.layout_height * PIXELS_PER_FOOT;
    const plotWidth = planData.plot_width * PIXELS_PER_FOOT;
    const plotHeight = planData.plot_height * PIXELS_PER_FOOT;

    const clampedX = Math.max(0, Math.min(newX, plotWidth - roomWidth));
    const clampedY = Math.max(0, Math.min(newY, plotHeight - roomHeight));

    updateSelectedRoom({ x: clampedX, y: clampedY });
  };

  const handleRoomResize = (event) => {
    if (!isResizing || selectedRoom === null) return;

    const coords = getCanvasCoordinates(event);
    const room = planData.rooms[selectedRoom];
    const roomX = room.x + 20;
    const roomY = room.y + 20;

    let newLayoutWidth = room.layout_width;
    let newLayoutHeight = room.layout_height;

    if (resizeHandle === 'right' || resizeHandle === 'corner') {
      const newWidth = (coords.x - roomX) / PIXELS_PER_FOOT;
      newLayoutWidth = Math.max(4, Math.min(30, Math.round(newWidth * 2) / 2)); // Snap to 0.5 feet
    }

    if (resizeHandle === 'bottom' || resizeHandle === 'corner') {
      const newHeight = (coords.y - roomY) / PIXELS_PER_FOOT;
      newLayoutHeight = Math.max(4, Math.min(30, Math.round(newHeight * 2) / 2)); // Snap to 0.5 feet
    }

    // Auto-calculate actual dimensions based on scale ratio
    const actualWidth = newLayoutWidth * planData.scale_ratio;
    const actualHeight = newLayoutHeight * planData.scale_ratio;

    updateSelectedRoom({ 
      layout_width: newLayoutWidth, 
      layout_height: newLayoutHeight,
      actual_width: actualWidth,
      actual_height: actualHeight
    });
  };

  const handleCanvasClick = (event) => {
    // This is now handled by handleCanvasMouseDown for better interaction
  };

  const addRoom = (template) => {
    const newRoom = {
      id: Date.now(),
      name: template.name,
      category: template.category,
      x: 50,
      y: 50,
      layout_width: template.default_width,
      layout_height: template.default_height,
      actual_width: template.default_width * planData.scale_ratio,
      actual_height: template.default_height * planData.scale_ratio,
      color: template.color,
      icon: template.icon,
      // Construction specifications
      wall_thickness: 0.5, // feet
      ceiling_height: 9, // feet
      floor_type: 'ceramic', // ceramic, marble, wood, etc.
      wall_material: 'brick', // brick, concrete, wood, etc.
      notes: ''
    };

    setPlanData(prev => ({
      ...prev,
      rooms: [...prev.rooms, newRoom]
    }));

    setSelectedRoom(planData.rooms.length);
    setHasUnsavedChanges(true);
    showSuccess('Room Added', `${template.name} has been added to your plan`);
  };

  // Quick room addition for common circulation and structural elements
  const addQuickRoom = (type, name, width, height, color, icon) => {
    const newRoom = {
      id: Date.now(),
      name: name,
      category: type.includes('stair') || type.includes('column') || type.includes('beam') ? 'structural' : 'circulation',
      type: type,
      x: 50,
      y: 50,
      layout_width: width,
      layout_height: height,
      actual_width: width * planData.scale_ratio,
      actual_height: height * planData.scale_ratio,
      color: color,
      icon: icon,
      // Construction specifications
      wall_thickness: type.includes('stair') ? 1.0 : 0.5, // Thicker walls for stairs
      ceiling_height: type.includes('stair') ? 10 : 9, // Higher ceiling for stairs
      floor_type: type.includes('stair') ? 'concrete' : 'ceramic',
      wall_material: type.includes('stair') ? 'concrete' : 'brick',
      notes: ''
    };

    setPlanData(prev => ({
      ...prev,
      rooms: [...prev.rooms, newRoom]
    }));

    setSelectedRoom(planData.rooms.length);
    setHasUnsavedChanges(true);
    showSuccess('Element Added', `${name} has been added to your plan`);
  };

  const updateSelectedRoom = (updates) => {
    if (selectedRoom === null) return;

    setPlanData(prev => ({
      ...prev,
      rooms: prev.rooms.map((room, index) => 
        index === selectedRoom ? { ...room, ...updates } : room
      )
    }));
    
    setHasUnsavedChanges(true);
  };

  const deleteSelectedRoom = () => {
    if (selectedRoom === null) return;

    const roomName = planData.rooms[selectedRoom]?.name || `Room ${selectedRoom + 1}`;
    
    setPlanData(prev => ({
      ...prev,
      rooms: prev.rooms.filter((_, index) => index !== selectedRoom)
    }));

    setSelectedRoom(null);
    setHasUnsavedChanges(true);
    showWarning('Room Deleted', `"${roomName}" has been removed from your plan`);
    // History will be saved automatically by the useEffect
  };

  const calculateTotalArea = () => {
    return planData.rooms.reduce((total, room) => {
      const area = measurementMode === 'actual' 
        ? (room.actual_width || room.layout_width * planData.scale_ratio) * 
          (room.actual_height || room.layout_height * planData.scale_ratio)
        : room.layout_width * room.layout_height;
      return total + area;
    }, 0);
  };

  const calculateConstructionArea = () => {
    return planData.rooms.reduce((total, room) => {
      const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
      const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
      return total + (actualWidth * actualHeight);
    }, 0);
  };

  // Auto-save function
  const handleAutoSave = async () => {
    if (!planData.plan_name.trim() || planData.rooms.length === 0) {
      return;
    }

    try {
      const payload = {
        plan_name: planData.plan_name,
        layout_request_id: layoutRequestId,
        plot_width: planData.plot_width,
        plot_height: planData.plot_height,
        plan_data: {
          rooms: planData.rooms,
          scale_ratio: planData.scale_ratio,
          total_layout_area: calculateTotalArea(),
          total_construction_area: calculateConstructionArea()
        },
        notes: planData.notes
      };

      const url = existingPlan 
        ? '/buildhub/backend/api/architect/update_house_plan.php'
        : '/buildhub/backend/api/architect/create_house_plan.php';

      if (existingPlan) {
        payload.plan_id = existingPlan.id;
      }

      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      
      if (result.success) {
        setLastSaved(new Date());
        setHasUnsavedChanges(false);
        showInfo('Auto-saved', 'Your changes have been automatically saved');
      }
    } catch (error) {
      console.error('Auto-save failed:', error);
    }
  };

  const sendInboxMessage = async (recipientId, messageType, title, message, metadata = null, priority = 'normal') => {
    try {
      const user = JSON.parse(sessionStorage.getItem('user') || '{}');
      const response = await fetch('/buildhub/backend/api/architect/send_inbox_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          recipient_id: recipientId,
          sender_id: user.id,
          message_type: messageType,
          title: title,
          message: message,
          metadata: metadata,
          priority: priority
        })
      });

      const result = await response.json();
      return result;
    } catch (error) {
      console.error('Error sending inbox message:', error);
      return { success: false, message: 'Failed to send inbox message' };
    }
  };

  const handleTourComplete = () => {
    localStorage.setItem('housePlanTourCompleted', 'true');
    setIsFirstTime(false);
    showSuccess('Welcome!', 'You\'re now ready to create professional house plans.');
  };

  const startTour = () => {
    setShowTour(true);
  };

  const openHelp = () => {
    setShowHelp(true);
  };

  const handleSubmitToHomeowner = async () => {
    if (!planData.plan_name.trim()) {
      showError('Validation Error', 'Please enter a plan name before submitting');
      return;
    }

    if (planData.rooms.length === 0) {
      showWarning('Empty Plan', 'Your plan has no rooms. Add some rooms before submitting.');
      return;
    }

    // First save the plan
    setLoading(true);
    showInfo('Submitting Plan', 'Saving plan and notifying homeowner...');
    
    try {
      const payload = {
        plan_name: planData.plan_name,
        layout_request_id: layoutRequestId,
        plot_width: planData.plot_width,
        plot_height: planData.plot_height,
        plan_data: {
          rooms: planData.rooms,
          scale_ratio: planData.scale_ratio,
          total_layout_area: calculateTotalArea(),
          total_construction_area: calculateConstructionArea()
        },
        notes: planData.notes
      };

      const url = existingPlan 
        ? '/buildhub/backend/api/architect/update_house_plan.php'
        : '/buildhub/backend/api/architect/create_house_plan.php';

      if (existingPlan) {
        payload.plan_id = existingPlan.id;
      }

      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      
      if (result.success) {
        // Now send inbox message to homeowner
        const user = JSON.parse(sessionStorage.getItem('user') || '{}');
        const homeownerId = layoutRequestId; // Assuming layout request ID corresponds to homeowner ID
        
        const inboxResult = await sendInboxMessage(
          homeownerId,
          'plan_submitted',
          'New House Plan Ready for Review',
          `Your architect has completed a new house plan: "${planData.plan_name}". The plan includes ${planData.rooms.length} rooms covering ${calculateConstructionArea().toFixed(0)} sq ft. Please review and provide your feedback.`,
          {
            plan_id: result.plan_id || existingPlan?.id,
            plan_name: planData.plan_name,
            total_rooms: planData.rooms.length,
            total_area: calculateConstructionArea().toFixed(0),
            architect_id: user.id,
            architect_name: `${user.first_name || ''} ${user.last_name || ''}`.trim()
          },
          'high'
        );

        if (inboxResult.success) {
          showSuccess(
            'Plan Submitted Successfully!', 
            `Your house plan "${planData.plan_name}" has been submitted to the homeowner for review.`
          );
        } else {
          showWarning(
            'Plan Saved, Notification Failed', 
            `Plan was saved but we couldn't notify the homeowner. Please contact them directly.`
          );
        }
        
        // Clear history after successful save
        setHistory([]);
        setHistoryIndex(-1);
        saveToHistory();
        
        if (onSave) {
          setTimeout(() => onSave(result), 1500); // Delay to show success message
        }
      } else {
        showError(
          'Submit Failed', 
          result.message || 'Unable to submit your house plan. Please check your connection and try again.'
        );
      }
    } catch (error) {
      console.error('Error submitting plan:', error);
      showError(
        'Network Error', 
        'Failed to connect to the server. Please check your internet connection and try again.'
      );
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    if (!planData.plan_name.trim()) {
      showError('Validation Error', 'Please enter a plan name before saving');
      return;
    }

    if (planData.rooms.length === 0) {
      showWarning('Empty Plan', 'Your plan has no rooms. Add some rooms before saving.');
      return;
    }

    setLoading(true);
    showInfo('Saving Plan', 'Please wait while we save your house plan...');
    
    try {
      const payload = {
        plan_name: planData.plan_name,
        layout_request_id: layoutRequestId,
        plot_width: planData.plot_width,
        plot_height: planData.plot_height,
        plan_data: {
          rooms: planData.rooms,
          scale_ratio: planData.scale_ratio,
          total_layout_area: calculateTotalArea(),
          total_construction_area: calculateConstructionArea()
        },
        notes: planData.notes,
        status: 'draft'
      };

      const url = existingPlan 
        ? '/buildhub/backend/api/architect/update_house_plan.php'
        : '/buildhub/backend/api/architect/create_house_plan.php';

      if (existingPlan) {
        payload.plan_id = existingPlan.id;
      }

      console.log('Saving plan with payload:', payload); // Debug log

      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await response.json();
      console.log('Save result:', result); // Debug log
      
      if (result.success) {
        // Update local state to reflect saved data
        setLastSaved(new Date());
        setHasUnsavedChanges(false);
        
        // Clear auto-save timer
        if (autoSaveTimer) {
          clearTimeout(autoSaveTimer);
          setAutoSaveTimer(null);
        }
        
        // Show success toast notification
        showSuccess(
          'Plan Saved Successfully!', 
          `Your house plan "${planData.plan_name}" has been saved with ${planData.rooms.length} rooms covering ${calculateConstructionArea().toFixed(0)} sq ft.`
        );
        
        // Clear history after successful save and reinitialize
        setHistory([]);
        setHistoryIndex(-1);
        setTimeout(() => {
          saveToHistory();
        }, 100);
        
        if (onSave) {
          setTimeout(() => onSave(result), 1500); // Delay to show success message
        }
      } else {
        showError(
          'Save Failed', 
          result.message || 'Unable to save your house plan. Please check your connection and try again.'
        );
      }
    } catch (error) {
      console.error('Error saving plan:', error);
      showError(
        'Network Error', 
        'Failed to connect to the server. Please check your internet connection and try again.'
      );
    } finally {
      setLoading(false);
    }
  };

  const currentRoom = selectedRoom !== null ? planData.rooms[selectedRoom] : null;

  return (
    <div className="house-plan-drawer">
      <NotificationToast 
        notifications={notifications} 
        onRemove={removeNotification} 
      />
      
      <HousePlanTour 
        isOpen={showTour}
        onClose={() => setShowTour(false)}
        onComplete={handleTourComplete}
      />
      
      <HousePlanHelp 
        isOpen={showHelp}
        onClose={() => setShowHelp(false)}
      />
      
      <div className="drawer-header">
        <div className="header-title">
          <h2>House Plan Designer</h2>
          {hasUnsavedChanges && (
            <span className="unsaved-indicator" title="You have unsaved changes">
              ● Unsaved Changes
            </span>
          )}
          {lastSaved && (
            <span className="last-saved" title={`Last saved: ${lastSaved.toLocaleTimeString()}`}>
              Last saved: {lastSaved.toLocaleTimeString()}
            </span>
          )}
        </div>
        
        <div className="header-actions">
          <div className="help-actions">
            <button 
              onClick={startTour}
              className="action-btn tour-btn"
              title="Take a guided tour"
            >
              🎯 Tour
            </button>
            <button 
              onClick={openHelp}
              className="action-btn help-btn"
              title="Open user manual"
            >
              📖 Help
            </button>
          </div>
          
          <div className="edit-actions">
            <button 
              onClick={undo} 
              disabled={historyIndex <= 0}
              className="action-btn undo-btn"
              title="Undo (Ctrl+Z)"
            >
              ↶ Undo
            </button>
            <button 
              onClick={redo} 
              disabled={historyIndex >= history.length - 1}
              className="action-btn redo-btn"
              title="Redo (Ctrl+Y)"
            >
              ↷ Redo
            </button>
            <button 
              onClick={deleteSelectedRoom} 
              disabled={selectedRoom === null}
              className="action-btn delete-btn"
              title="Delete Selected Room (Delete)"
            >
              🗑️ Delete
            </button>
          </div>
          
          <div className="drawer-actions">
            <button onClick={handleSave} disabled={loading} className="save-btn">
              {loading ? 'Saving...' : 'Save Plan'}
            </button>
            <button onClick={handleSubmitToHomeowner} disabled={loading} className="submit-btn">
              {loading ? 'Submitting...' : 'Submit to Homeowner'}
            </button>
            <button onClick={onCancel} className="cancel-btn">Cancel</button>
          </div>
        </div>
      </div>

      <div className="drawer-content">
        <div className="tools-panel">
          <div className="plan-details-section">
            <h4>Plan Details</h4>
            <div className="plan-form">
              <div className="form-group">
                <label>Plan Name:</label>
                <input
                  type="text"
                  placeholder="Enter plan name"
                  value={planData.plan_name}
                  onChange={(e) => {
                    setPlanData(prev => ({ ...prev, plan_name: e.target.value }));
                    setHasUnsavedChanges(true);
                  }}
                  className="plan-detail-input"
                />
              </div>
              
              <div className="form-group">
                <label>Plot Dimensions:</label>
                <div className="dimension-inputs">
                  <div className="dimension-input">
                    <label>Width:</label>
                    <input
                      type="number"
                      value={planData.plot_width}
                      onChange={(e) => {
                        setPlanData(prev => ({ ...prev, plot_width: parseFloat(e.target.value) || 0 }));
                        setHasUnsavedChanges(true);
                      }}
                      min="10"
                      max="100"
                      className="dimension-field"
                    />
                    <span>ft</span>
                  </div>
                  <div className="dimension-input">
                    <label>Height:</label>
                    <input
                      type="number"
                      value={planData.plot_height}
                      onChange={(e) => {
                        setPlanData(prev => ({ ...prev, plot_height: parseFloat(e.target.value) || 0 }));
                        setHasUnsavedChanges(true);
                      }}
                      min="10"
                      max="100"
                      className="dimension-field"
                    />
                    <span>ft</span>
                  </div>
                </div>
              </div>
              
              <div className="form-group">
                <label>Notes:</label>
                <textarea
                  placeholder="Add notes about this plan..."
                  value={planData.notes}
                  onChange={(e) => {
                    setPlanData(prev => ({ ...prev, notes: e.target.value }));
                    setHasUnsavedChanges(true);
                  }}
                  className="plan-notes-textarea"
                  rows="3"
                />
              </div>
            </div>
          </div>

          <div className="plan-stats">
            <h4>Plan Statistics</h4>
            <div className="stats">
              <div>Plot Area: {(planData.plot_width * planData.plot_height).toFixed(0)} sq ft</div>
              <div>Layout Area: {calculateTotalArea().toFixed(0)} sq ft</div>
              <div>Construction Area: {calculateConstructionArea().toFixed(0)} sq ft</div>
              <div>Coverage: {((calculateConstructionArea() / (planData.plot_width * planData.plot_height)) * 100).toFixed(1)}%</div>
              <div>Total Rooms: {planData.rooms.length}</div>
            </div>
          </div>
          <div className="tool-section">
            <h4>Tools</h4>
            <div className="tool-buttons">
              <button 
                className={selectedTool === 'select' ? 'active' : ''}
                onClick={() => setSelectedTool('select')}
                title="Select and move rooms"
              >
                🔍 Select
              </button>
            </div>
          </div>

          <div className="measurement-controls">
            <h4>Measurements</h4>
            <div className="measurement-options">
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={showMeasurements}
                  onChange={(e) => setShowMeasurements(e.target.checked)}
                />
                Show Dimensions
              </label>
              <div className="measurement-mode">
                <label>
                  <input
                    type="radio"
                    name="measurementMode"
                    value="layout"
                    checked={measurementMode === 'layout'}
                    onChange={(e) => setMeasurementMode(e.target.value)}
                  />
                  Layout Only
                </label>
                <label>
                  <input
                    type="radio"
                    name="measurementMode"
                    value="actual"
                    checked={measurementMode === 'actual'}
                    onChange={(e) => setMeasurementMode(e.target.value)}
                  />
                  Construction Only
                </label>
                <label>
                  <input
                    type="radio"
                    name="measurementMode"
                    value="both"
                    checked={measurementMode === 'both'}
                    onChange={(e) => setMeasurementMode(e.target.value)}
                  />
                  Both
                </label>
              </div>
            </div>
            <div className="scale-control">
              <label>
                Scale Ratio (Layout → Construction):
                <input
                  type="number"
                  value={planData.scale_ratio}
                  onChange={(e) => {
                    setPlanData(prev => ({ ...prev, scale_ratio: parseFloat(e.target.value) || 1 }));
                    setHasUnsavedChanges(true);
                  }}
                  min="0.5"
                  max="3"
                  step="0.1"
                  className="scale-input"
                />
              </label>
              <small>1.0 = Same size, 1.2 = 20% larger construction</small>
            </div>
          </div>

          <div className="room-templates">
            <h4>Room Templates</h4>
            
            {/* Quick Access for Common Elements */}
            <div className="quick-access">
              <h5>🚀 Quick Access</h5>
              <div className="quick-access-grid">
                <button
                  className="quick-btn circulation"
                  onClick={() => addQuickRoom('corridor', 'Corridor', 20, 4, '#fff9c4', '🚶')}
                  title="Add Corridor (20' × 4')"
                >
                  🚶 Corridor
                </button>
                <button
                  className="quick-btn circulation"
                  onClick={() => addQuickRoom('hallway', 'Hallway', 15, 6, '#fff59d', '🚶‍♂️')}
                  title="Add Hallway (15' × 6')"
                >
                  🚶‍♂️ Hallway
                </button>
                <button
                  className="quick-btn structural"
                  onClick={() => addQuickRoom('staircase', 'Staircase', 8, 12, '#d7ccc8', '🪜')}
                  title="Add Staircase (8' × 12')"
                >
                  🪜 Staircase
                </button>
                <button
                  className="quick-btn structural"
                  onClick={() => addQuickRoom('spiral_staircase', 'Spiral Staircase', 6, 6, '#bcaaa4', '🌀')}
                  title="Add Spiral Staircase (6' × 6')"
                >
                  🌀 Spiral Stair
                </button>
                <button
                  className="quick-btn circulation"
                  onClick={() => addQuickRoom('entrance_hall', 'Entrance Hall', 10, 8, '#ffe082', '🚪')}
                  title="Add Entrance Hall (10' × 8')"
                >
                  🚪 Entrance
                </button>
                <button
                  className="quick-btn circulation"
                  onClick={() => addQuickRoom('passage', 'Passage', 12, 3, '#ffecb3', '➡️')}
                  title="Add Passage (12' × 3')"
                >
                  ➡️ Passage
                </button>
              </div>
            </div>
            
            {Object.entries(roomTemplates).map(([category, templates]) => (
              <div key={category} className="template-category">
                <h5>{category.charAt(0).toUpperCase() + category.slice(1)}</h5>
                <div className="template-grid">
                  {templates.map(template => (
                    <button
                      key={template.id}
                      className="template-btn"
                      onClick={() => addRoom(template)}
                      style={{ backgroundColor: template.color }}
                      title={`Add ${template.name} (${template.default_width}' × ${template.default_height}')`}
                    >
                      {template.icon && <span className="template-icon">{template.icon}</span>}
                      {template.name}
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* Color Legend */}
          <div className="color-legend">
            <h5>🎨 Color Legend</h5>
            <div className="legend-grid">
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#c8e6c9' }}></div>
                <span className="legend-label">Bedrooms</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#b3e5fc' }}></div>
                <span className="legend-label">Bathrooms</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#ffcdd2' }}></div>
                <span className="legend-label">Kitchen</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#ffe0b2' }}></div>
                <span className="legend-label">Living Areas</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#e1bee7' }}></div>
                <span className="legend-label">Dining Areas</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#fff9c4' }}></div>
                <span className="legend-label">Walkways</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#d7ccc8' }}></div>
                <span className="legend-label">Stairs/Structure</span>
              </div>
              <div className="legend-item">
                <div className="legend-color" style={{ backgroundColor: '#e0e0e0' }}></div>
                <span className="legend-label">Utility</span>
              </div>
            </div>
          </div>

          {currentRoom && (
            <div className="room-properties">
              <h4>Room Properties</h4>
              <div className="property-group">
                <label>
                  Name:
                  <input
                    type="text"
                    value={currentRoom.name}
                    onChange={(e) => {
                      updateSelectedRoom({ name: e.target.value });
                    }}
                  />
                </label>
                
                <div className="dimensions-section">
                  <h5>Layout Dimensions</h5>
                  <div className="dimension-inputs">
                    <label>
                      Width:
                      <input
                        type="number"
                        value={currentRoom.layout_width}
                        onChange={(e) => {
                          const newWidth = parseFloat(e.target.value) || 0;
                          updateSelectedRoom({ 
                            layout_width: newWidth,
                            actual_width: newWidth * planData.scale_ratio
                          });
                        }}
                        min="4"
                        max="30"
                        step="0.5"
                      /> ft
                    </label>
                    <label>
                      Height:
                      <input
                        type="number"
                        value={currentRoom.layout_height}
                        onChange={(e) => {
                          const newHeight = parseFloat(e.target.value) || 0;
                          updateSelectedRoom({ 
                            layout_height: newHeight,
                            actual_height: newHeight * planData.scale_ratio
                          });
                        }}
                        min="4"
                        max="30"
                        step="0.5"
                      /> ft
                    </label>
                  </div>
                  <div className="dimension-display">
                    Layout Area: {(currentRoom.layout_width * currentRoom.layout_height).toFixed(1)} sq ft
                  </div>
                </div>

                <div className="dimensions-section">
                  <h5>Construction Dimensions</h5>
                  <div className="dimension-inputs">
                    <label>
                      Actual Width:
                      <input
                        type="number"
                        value={(currentRoom.actual_width || currentRoom.layout_width * planData.scale_ratio).toFixed(1)}
                        onChange={(e) => updateSelectedRoom({ actual_width: parseFloat(e.target.value) || 0 })}
                        min="4"
                        max="40"
                        step="0.1"
                      /> ft
                    </label>
                    <label>
                      Actual Height:
                      <input
                        type="number"
                        value={(currentRoom.actual_height || currentRoom.layout_height * planData.scale_ratio).toFixed(1)}
                        onChange={(e) => updateSelectedRoom({ actual_height: parseFloat(e.target.value) || 0 })}
                        min="4"
                        max="40"
                        step="0.1"
                      /> ft
                    </label>
                  </div>
                  <div className="dimension-display">
                    Construction Area: {((currentRoom.actual_width || currentRoom.layout_width * planData.scale_ratio) * 
                    (currentRoom.actual_height || currentRoom.layout_height * planData.scale_ratio)).toFixed(1)} sq ft
                  </div>
                </div>

                <div className="construction-specs">
                  <h5>Construction Specifications</h5>
                  <label>
                    Ceiling Height:
                    <input
                      type="number"
                      value={currentRoom.ceiling_height || 9}
                      onChange={(e) => updateSelectedRoom({ ceiling_height: parseFloat(e.target.value) || 9 })}
                      min="8"
                      max="15"
                      step="0.5"
                    /> ft
                  </label>
                  <label>
                    Wall Thickness:
                    <input
                      type="number"
                      value={currentRoom.wall_thickness || 0.5}
                      onChange={(e) => updateSelectedRoom({ wall_thickness: parseFloat(e.target.value) || 0.5 })}
                      min="0.25"
                      max="1"
                      step="0.25"
                    /> ft
                  </label>
                  <label>
                    Floor Type:
                    <select
                      value={currentRoom.floor_type || 'ceramic'}
                      onChange={(e) => updateSelectedRoom({ floor_type: e.target.value })}
                    >
                      <option value="ceramic">Ceramic Tiles</option>
                      <option value="marble">Marble</option>
                      <option value="granite">Granite</option>
                      <option value="wood">Wooden</option>
                      <option value="concrete">Concrete</option>
                      <option value="vinyl">Vinyl</option>
                    </select>
                  </label>
                  <label>
                    Wall Material:
                    <select
                      value={currentRoom.wall_material || 'brick'}
                      onChange={(e) => updateSelectedRoom({ wall_material: e.target.value })}
                    >
                      <option value="brick">Brick</option>
                      <option value="concrete">Concrete Block</option>
                      <option value="wood">Wood Frame</option>
                      <option value="steel">Steel Frame</option>
                      <option value="stone">Stone</option>
                    </select>
                  </label>
                </div>

                <button onClick={deleteSelectedRoom} className="delete-room-btn">
                  🗑️ Delete Room
                </button>
              </div>
            </div>
          )}

          <div className="editor-status">
            <h4>Editor Status</h4>
            <div className="status-info">
              <div className="status-item">
                <span className="status-label">Selected:</span>
                <span className="status-value">
                  {selectedRoom !== null ? planData.rooms[selectedRoom]?.name || `Room ${selectedRoom + 1}` : 'None'}
                </span>
              </div>
              <div className="status-item">
                <span className="status-label">History:</span>
                <span className="status-value">
                  {historyIndex + 1} / {history.length}
                </span>
              </div>
              <div className="status-item">
                <span className="status-label">Can Undo:</span>
                <span className={`status-value ${historyIndex > 0 ? 'enabled' : 'disabled'}`}>
                  {historyIndex > 0 ? 'Yes' : 'No'}
                </span>
              </div>
              <div className="status-item">
                <span className="status-label">Can Redo:</span>
                <span className={`status-value ${historyIndex < history.length - 1 ? 'enabled' : 'disabled'}`}>
                  {historyIndex < history.length - 1 ? 'Yes' : 'No'}
                </span>
              </div>
            </div>
          </div>

          <div className="interaction-help">
            <h4>How to Use</h4>
            <ul>
              <li>🖱️ Click rooms to select them</li>
              <li>🔄 Drag rooms to move them</li>
              <li>📏 Drag blue handles to resize</li>
              <li>⌨️ Ctrl+Z to undo, Ctrl+Y to redo</li>
              <li>🗑️ Delete key to remove selected room</li>
              <li>📐 Layout = Visual, Construction = Actual</li>
              <li>⚖️ Adjust scale ratio for realistic sizing</li>
            </ul>
          </div>
        </div>

        <div className="canvas-container">
          <div className="canvas-toolbar">
            <div className="canvas-info">
              <span>Plot: {planData.plot_width}' × {planData.plot_height}'</span>
              <span>Rooms: {planData.rooms.length}</span>
              <span>Scale: 1:{planData.scale_ratio}</span>
            </div>
            <div className="canvas-controls">
              <button onClick={() => setShowMeasurements(!showMeasurements)}>
                {showMeasurements ? '📏 Hide' : '📏 Show'} Dimensions
              </button>
              <button onClick={() => drawCanvas()}>🔄 Refresh</button>
            </div>
          </div>
          <div className="canvas-wrapper">
            <canvas
              ref={canvasRef}
              className="plan-canvas"
              onMouseDown={handleCanvasMouseDown}
              style={{ 
                cursor: isDragging ? 'grabbing' : isResizing ? 'nw-resize' : 'default'
              }}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default HousePlanDrawer;