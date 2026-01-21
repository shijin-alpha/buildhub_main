import React, { useState, useEffect, useRef, useCallback } from 'react';
import '../styles/HousePlanDrawer.css';
import NotificationToast from './NotificationToast';
import HousePlanTour from './HousePlanTour';
import HousePlanHelp from './HousePlanHelp';
import TechnicalDetailsModal from './TechnicalDetailsModal';
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
  const [periodicSaveTimer, setPeriodicSaveTimer] = useState(null);
  const [lastSaved, setLastSaved] = useState(null);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);
  const [autoSaveInProgress, setAutoSaveInProgress] = useState(false);

  // Mouse interaction state
  const [mouseDownTime, setMouseDownTime] = useState(null);
  const [mouseDownPos, setMouseDownPos] = useState(null);
  const [isDragCandidate, setIsDragCandidate] = useState(false);
  const [isRotating, setIsRotating] = useState(false);

  // Tour and Help state
  const [showTour, setShowTour] = useState(false);
  const [showHelp, setShowHelp] = useState(false);
  const [isFirstTime, setIsFirstTime] = useState(false);
  const [showKeyboardShortcuts, setShowKeyboardShortcuts] = useState(false);

  // Requirements display state
  const [showRequirements, setShowRequirements] = useState(true);
  const [requirementsData, setRequirementsData] = useState(null);
  const [highlightedRoomType, setHighlightedRoomType] = useState(null);

  // Technical details modal state
  const [showTechnicalModal, setShowTechnicalModal] = useState(false);
  const [submissionLoading, setSubmissionLoading] = useState(false);

  // Download functionality state
  const [downloadLoading, setDownloadLoading] = useState(false);

  // Plan ID tracking for auto-save
  const [currentPlanId, setCurrentPlanId] = useState(existingPlan?.id || null);

  // Floor management state
  const [currentFloor, setCurrentFloor] = useState(1);
  const [totalFloors, setTotalFloors] = useState(1);
  const [floorNames, setFloorNames] = useState({ 1: 'Ground Floor' });
  const [floorOffsets, setFloorOffsets] = useState({ 1: { x: 0, y: 0 } }); // Custom positioning for each floor
  const [floorSectionCollapsed, setFloorSectionCollapsed] = useState(false);
  const [positioningSectionCollapsed, setPositioningSectionCollapsed] = useState(true); // Start collapsed to save space

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
  const ROTATION_HANDLE_SIZE = 16; // Larger rotation handle for better usability

  // Helper functions that need to be available early
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
        
        // Set requirements data for display
        setRequirementsData({
          ...request,
          homeowner_name: assignment.homeowner?.name || assignment.homeowner?.first_name + ' ' + assignment.homeowner?.last_name || 'Client',
          parsed_requirements: requirements
        });
        
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
                  
                  const floorOffset = floorOffsets[floorNumber] || { x: 0, y: (floorNumber - 1) * 300 }; // Default to 300px spacing if not set
                  
                  prePopulatedRooms.push({
                    id: roomId++,
                    name: roomName,
                    type: roomType,
                    x: currentX + floorOffset.x,
                    y: currentY + floorOffset.y, // Use custom floor offset
                    layout_width: 10, // Updated default layout width in feet
                    layout_height: 10, // Updated default layout height in feet
                    actual_width: 10 * currentScaleRatio,
                    actual_height: 10 * currentScaleRatio,
                    rotation: 0, // Add rotation property
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
                rotation: 0, // Add rotation property
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

  useEffect(() => {
    loadRoomTemplates();
    // Load request information if layoutRequestId is provided
    if (layoutRequestId || requestInfo) {
      loadRequestInfo();
    }
    
    // Check if this is the user's first time
    const hasSeenTour = localStorage.getItem('housePlanTourCompleted');
    if (!hasSeenTour) {
      setIsFirstTime(true);
      setTimeout(() => setShowTour(true), 1000); // Show tour after component loads
    }
  }, [layoutRequestId, requestInfo]);

  // Initialize history after planData is set
  useEffect(() => {
    // Initialize history with current state after a short delay to ensure planData is ready
    const timer = setTimeout(() => {
      saveToHistory();
    }, 100);
    
    return () => clearTimeout(timer);
  }, []); // Only run once on mount

  // Warn user about unsaved changes when leaving and auto-save
  useEffect(() => {
    const handleBeforeUnload = async (e) => {
      if (hasUnsavedChanges) {
        // Try to save immediately before leaving
        if (!autoSaveInProgress) {
          try {
            await handleAutoSave();
          } catch (error) {
            console.error('Failed to auto-save before leaving:', error);
          }
        }
        
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Your work has been auto-saved as a draft.';
        return 'You have unsaved changes. Your work has been auto-saved as a draft.';
      }
    };

    const handleVisibilityChange = () => {
      // Auto-save when tab becomes hidden (user switches tabs)
      if (document.hidden && hasUnsavedChanges && !autoSaveInProgress) {
        handleAutoSave();
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [hasUnsavedChanges, autoSaveInProgress]);

  // Handle existing plan changes (when switching between edit modes)
  useEffect(() => {
    if (existingPlan) {
      console.log('Loading existing plan:', existingPlan); // Debug log
      
      // Set the current plan ID for auto-save tracking
      setCurrentPlanId(existingPlan.id);
      
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
      
      const newPlanData = {
        plan_name: existingPlan.plan_name || '',
        plot_width: existingPlan.plot_width || 100,
        plot_height: existingPlan.plot_height || 100,
        rooms: parsedPlanData?.rooms || [],
        notes: existingPlan.notes || '',
        scale_ratio: parsedPlanData?.scale_ratio || 1.2
      };
      
      // Load floor information if available
      if (parsedPlanData?.floors) {
        setTotalFloors(parsedPlanData.floors.total_floors || 1);
        setCurrentFloor(parsedPlanData.floors.current_floor || 1);
        setFloorNames(parsedPlanData.floors.floor_names || { 1: 'Ground Floor' });
        setFloorOffsets(parsedPlanData.floors.floor_offsets || { 1: { x: 0, y: 0 } });
      } else {
        // Initialize default floor info for existing plans without floor data
        setTotalFloors(1);
        setCurrentFloor(1);
        setFloorNames({ 1: 'Ground Floor' });
        setFloorOffsets({ 1: { x: 0, y: 0 } });
      }
      
      console.log('Setting plan data:', newPlanData); // Debug log
      setPlanData(newPlanData);
      
      // Reset history when loading existing plan
      setHistory([]);
      setHistoryIndex(-1);
      setHasUnsavedChanges(false);
      setLastSaved(existingPlan.updated_at ? new Date(existingPlan.updated_at) : null);
      
      // Save initial state to history after a short delay
      setTimeout(() => {
        saveToHistory();
      }, 200);
    }
  }, [existingPlan?.id, existingPlan?.updated_at]); // Only trigger when plan ID or update time changes

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

  // Define updateSelectedRoom early to avoid initialization errors
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
    
    // Save immediately after room deletion
    setTimeout(() => saveImmediately(), 100);
  };

  // Helper functions for area calculations
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

  // Enhanced auto-save function with retry logic
  const handleAutoSave = async (retryCount = 0) => {
    // Prevent multiple simultaneous auto-saves
    if (autoSaveInProgress) {
      return;
    }

    // Don't auto-save if there's no meaningful data
    if (!planData.plan_name.trim() && planData.rooms.length === 0) {
      return;
    }

    setAutoSaveInProgress(true);

    try {
      const payload = {
        plan_name: planData.plan_name || `Draft Plan ${new Date().toLocaleString()}`,
        layout_request_id: layoutRequestId,
        plot_width: planData.plot_width,
        plot_height: planData.plot_height,
        plan_data: {
          rooms: planData.rooms,
          scale_ratio: planData.scale_ratio,
          total_layout_area: calculateTotalArea(),
          total_construction_area: calculateConstructionArea(),
          floors: {
            total_floors: totalFloors,
            current_floor: currentFloor,
            floor_names: floorNames,
            floor_offsets: floorOffsets
          }
        },
        notes: planData.notes,
        status: 'draft' // Always save as draft for auto-save
      };

      // Use currentPlanId to determine if we should update or create
      const isUpdate = currentPlanId || existingPlan?.id;
      const url = isUpdate 
        ? '/buildhub/backend/api/architect/update_house_plan.php'
        : '/buildhub/backend/api/architect/create_house_plan.php';

      if (isUpdate) {
        payload.plan_id = currentPlanId || existingPlan.id;
      }

      const response = await fetch(url, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const responseText = await response.text();
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        throw new Error(`JSON parse error: ${parseError.message}`);
      }
      
      if (result.success) {
        setLastSaved(new Date());
        setHasUnsavedChanges(false);
        
        // Update currentPlanId if this was a new plan creation
        if (result.plan_id && !currentPlanId) {
          setCurrentPlanId(result.plan_id);
          console.log('Auto-save created new plan with ID:', result.plan_id);
        }
        
        // Show success message only occasionally to avoid spam
        if (retryCount === 0) {
          const action = isUpdate ? 'updated' : 'created';
          showInfo('Auto-saved', `Your changes have been automatically ${action} as draft`, 2000);
        }
        
        // Trigger real-time dashboard refresh for auto-saves
        if (window.refreshDashboard) {
          setTimeout(() => window.refreshDashboard(), 1000);
        }
      } else {
        throw new Error(result.message || 'Auto-save failed');
      }
    } catch (error) {
      console.error('Auto-save error:', error);
      
      // Retry logic - try up to 3 times with exponential backoff
      if (retryCount < 3) {
        const retryDelay = Math.pow(2, retryCount) * 1000; // 1s, 2s, 4s
        setTimeout(() => {
          handleAutoSave(retryCount + 1);
        }, retryDelay);
      } else {
        // Show error only after all retries failed
        showError('Auto-save Failed', 'Unable to save changes automatically. Please save manually.', 5000);
      }
    } finally {
      setAutoSaveInProgress(false);
    }
  };

  // Immediate save for critical actions (room creation, deletion, major changes)
  const saveImmediately = async () => {
    // Clear any pending auto-save timer
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
      setAutoSaveTimer(null);
    }
    
    // Save immediately
    await handleAutoSave();
  };

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
      
      // Rotation shortcuts for selected room
      if (selectedRoom !== null) {
        const currentRoom = planData.rooms[selectedRoom];
        if (currentRoom) {
          if (e.key === 'r' || e.key === 'R') {
            e.preventDefault();
            // R key: rotate 15° clockwise
            const newRotation = ((currentRoom.rotation || 0) + 15) % 360;
            updateSelectedRoom({ rotation: newRotation });
            showInfo('Room Rotated', `Rotated ${currentRoom.name} to ${newRotation}°`);
          } else if (e.key === 'e' || e.key === 'E') {
            e.preventDefault();
            // E key: rotate 15° counter-clockwise
            const newRotation = ((currentRoom.rotation || 0) - 15 + 360) % 360;
            updateSelectedRoom({ rotation: newRotation });
            showInfo('Room Rotated', `Rotated ${currentRoom.name} to ${newRotation}°`);
          } else if (e.key === 't' || e.key === 'T') {
            e.preventDefault();
            // T key: rotate 90° clockwise
            const newRotation = ((currentRoom.rotation || 0) + 90) % 360;
            updateSelectedRoom({ rotation: newRotation });
            showInfo('Room Rotated', `Rotated ${currentRoom.name} to ${newRotation}°`);
          } else if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            // Q key: reset rotation to 0°
            updateSelectedRoom({ rotation: 0 });
            showInfo('Rotation Reset', `Reset ${currentRoom.name} rotation to 0°`);
          }
        }
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [undo, redo, selectedRoom, planData.rooms, updateSelectedRoom, showInfo]);

  // Save to history when rooms change (but not during undo/redo)
  useEffect(() => {
    if (!isUndoRedo && planData.rooms.length >= 0) {
      const timeoutId = setTimeout(() => {
        saveToHistory();
      }, 500); // Debounce to avoid too many history entries
      
      return () => clearTimeout(timeoutId);
    }
  }, [planData.rooms, saveToHistory, isUndoRedo]);

  // Auto-save when plan data changes (more aggressive timing)
  useEffect(() => {
    if ((planData.plan_name || planData.rooms.length > 0) && !isUndoRedo) {
      setHasUnsavedChanges(true);
      
      // Clear existing timer
      if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
      }
      
      // Set new timer for auto-save (5 seconds after last change - much faster)
      const timer = setTimeout(() => {
        handleAutoSave();
      }, 5000); // Reduced from 30000 to 5000 (5 seconds)
      
      setAutoSaveTimer(timer);
    }
    
    return () => {
      if (autoSaveTimer) {
        clearTimeout(autoSaveTimer);
      }
    };
  }, [planData, isUndoRedo, totalFloors, floorNames, floorOffsets]); // Added more dependencies

  // Periodic auto-save every 2 minutes (as backup)
  useEffect(() => {
    const periodicTimer = setInterval(() => {
      if (hasUnsavedChanges && !autoSaveInProgress) {
        handleAutoSave();
      }
    }, 120000); // Every 2 minutes
    
    setPeriodicSaveTimer(periodicTimer);
    
    return () => {
      if (periodicTimer) {
        clearInterval(periodicTimer);
      }
    };
  }, [hasUnsavedChanges, autoSaveInProgress]);

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

  const drawRoom = (room, isSelected, isCurrentlyRotating = false) => {
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
    const rotation = room.rotation || 0; // Get rotation angle in degrees

    // Additional validation for calculated dimensions
    if (!isFinite(width) || !isFinite(height) || width <= 0 || height <= 0) {
      console.warn('Invalid calculated dimensions:', { width, height, room });
      return;
    }

    const centerX = x + width / 2;
    const centerY = y + height / 2;

    // Check if this room type should be highlighted
    const isHighlighted = highlightedRoomType && 
      (room.type === highlightedRoomType || room.name.toLowerCase().includes(highlightedRoomType.replace(/_/g, ' ')));

    // Save canvas state
    canvas.save();

    // Apply rotation if needed
    if (rotation !== 0) {
      canvas.translate(centerX, centerY);
      canvas.rotate((rotation * Math.PI) / 180);
      canvas.translate(-centerX, -centerY);
    }

    // Room background with gradient
    let roomColor = room.color || '#e3f2fd';
    if (isHighlighted) {
      roomColor = '#fef3c7'; // Highlight color
    }
    
    const gradient = canvas.createLinearGradient(x, y, x + width, y + height);
    gradient.addColorStop(0, roomColor);
    gradient.addColorStop(1, adjustColor(roomColor, isHighlighted ? -20 : -10));
    
    canvas.fillStyle = gradient;
    canvas.fillRect(x, y, width, height);

    // Room border
    canvas.strokeStyle = isSelected ? '#1976d2' : isHighlighted ? '#f59e0b' : '#666';
    canvas.lineWidth = isSelected ? 3 : isHighlighted ? 2 : 1;
    canvas.setLineDash([]);
    canvas.strokeRect(x, y, width, height);

    // Add highlight glow effect
    if (isHighlighted && !isSelected) {
      canvas.shadowColor = '#f59e0b';
      canvas.shadowBlur = 8;
      canvas.strokeRect(x, y, width, height);
      canvas.shadowBlur = 0;
    }

    // Room label and dimensions
    canvas.fillStyle = isHighlighted ? '#92400e' : '#333';
    canvas.font = 'bold 12px Arial';
    canvas.textAlign = 'center';
    
    // Room name
    canvas.fillText(room.name, centerX, centerY - 15);
    
    // Show rotation angle if rotated or currently rotating
    if (rotation !== 0 || (isSelected && isCurrentlyRotating)) {
      canvas.font = '10px Arial';
      canvas.fillStyle = isCurrentlyRotating ? '#ff0000' : '#ff6b6b';
      canvas.fillText(`↻ ${rotation}°`, centerX, centerY - 30);
    }
    
    // Layout dimensions
    if (measurementMode === 'layout' || measurementMode === 'both') {
      canvas.font = '10px Arial';
      canvas.fillStyle = isHighlighted ? '#92400e' : '#666';
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
    canvas.fillStyle = isHighlighted ? '#92400e' : '#888';
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

    // Restore canvas state
    canvas.restore();

    // Draw resize handles and rotation handle for selected room (after restore to avoid rotation)
    if (isSelected) {
      drawResizeHandles(x, y, width, height);
      drawRotationHandle(x, y, width, height, rotation);
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

  const drawRotationHandle = (x, y, width, height, rotation) => {
    const centerX = x + width / 2;
    const centerY = y + height / 2;
    const handleDistance = Math.max(width, height) / 2 + 30; // Increased distance for better visibility
    
    // Calculate rotation handle position (always at the top of the room when rotation is 0)
    const handleAngle = (rotation - 90) * Math.PI / 180;
    const handleX = centerX + Math.cos(handleAngle) * handleDistance;
    const handleY = centerY + Math.sin(handleAngle) * handleDistance;
    
    // Draw connection line from room center to handle
    canvas.strokeStyle = '#ff6b6b';
    canvas.lineWidth = 2;
    canvas.setLineDash([5, 5]);
    canvas.beginPath();
    canvas.moveTo(centerX, centerY);
    canvas.lineTo(handleX, handleY);
    canvas.stroke();
    canvas.setLineDash([]);
    
    // Draw rotation handle circle (larger and more visible)
    canvas.fillStyle = '#ff6b6b';
    canvas.strokeStyle = '#fff';
    canvas.lineWidth = 3;
    canvas.beginPath();
    canvas.arc(handleX, handleY, ROTATION_HANDLE_SIZE, 0, 2 * Math.PI);
    canvas.fill();
    canvas.stroke();
    
    // Draw rotation icon (curved arrow) - larger and more visible
    canvas.fillStyle = '#fff';
    canvas.font = 'bold 16px Arial';
    canvas.textAlign = 'center';
    canvas.fillText('↻', handleX, handleY + 5);
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
    
    // Draw rooms (only current floor)
    const currentFloorRooms = getCurrentFloorRooms();
    currentFloorRooms.forEach((room) => {
      const roomIndex = planData.rooms.findIndex(r => r.id === room.id);
      const isSelected = roomIndex === selectedRoom;
      const isCurrentlyRotating = isSelected && isRotating;
      drawRoom(room, isSelected, isCurrentlyRotating);
    });

    // Draw measurements if enabled
    if (showMeasurements) {
      drawMeasurements();
    }
  }, [canvas, planData, selectedRoom, showMeasurements, measurementMode, highlightedRoomType, isRotating]);

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
  }, [planData, selectedRoom, showMeasurements, measurementMode, highlightedRoomType, drawCanvas]);

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

  // Helper function to format budget display
  const formatBudget = (budget) => {
    if (!budget) return 'N/A';
    const num = Number(budget);
    if (num >= 10000000) return `₹${(num / 10000000).toFixed(1)} Cr`;
    if (num >= 100000) return `₹${(num / 100000).toFixed(1)} L`;
    return `₹${num.toLocaleString()}`;
  };

  // Helper function to get room count summary
  const getRoomCountSummary = (requirements) => {
    if (!requirements?.floor_rooms) return null;
    
    const totalRooms = {};
    Object.values(requirements.floor_rooms).forEach(floorRooms => {
      Object.entries(floorRooms).forEach(([roomType, count]) => {
        totalRooms[roomType] = (totalRooms[roomType] || 0) + count;
      });
    });
    
    return totalRooms;
  };

  const snapToGrid = (value) => {
    return Math.round(value / GRID_SIZE) * GRID_SIZE;
  };

  // Helper function to check if a point is inside a rotated rectangle
  const isPointInRotatedRect = (pointX, pointY, rectX, rectY, rectWidth, rectHeight, rotation) => {
    if (rotation === 0) {
      // No rotation, simple rectangle check
      return pointX >= rectX && pointX <= rectX + rectWidth &&
             pointY >= rectY && pointY <= rectY + rectHeight;
    }
    
    // For rotated rectangles, transform the point to the rectangle's local coordinate system
    const centerX = rectX + rectWidth / 2;
    const centerY = rectY + rectHeight / 2;
    
    // Translate point to origin
    const translatedX = pointX - centerX;
    const translatedY = pointY - centerY;
    
    // Rotate point in opposite direction
    const radians = (-rotation * Math.PI) / 180;
    const rotatedX = translatedX * Math.cos(radians) - translatedY * Math.sin(radians);
    const rotatedY = translatedX * Math.sin(radians) + translatedY * Math.cos(radians);
    
    // Translate back and check if inside rectangle
    const finalX = rotatedX + centerX;
    const finalY = rotatedY + centerY;
    
    return finalX >= rectX && finalX <= rectX + rectWidth &&
           finalY >= rectY && finalY <= rectY + rectHeight;
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

  const getRotationHandle = (x, y, roomX, roomY, roomWidth, roomHeight, rotation) => {
    const centerX = roomX + roomWidth / 2;
    const centerY = roomY + roomHeight / 2;
    const handleDistance = Math.max(roomWidth, roomHeight) / 2 + 30; // Match the increased distance
    
    const handleAngle = (rotation - 90) * Math.PI / 180;
    const handleX = centerX + Math.cos(handleAngle) * handleDistance;
    const handleY = centerY + Math.sin(handleAngle) * handleDistance;
    
    const distance = Math.sqrt(Math.pow(x - handleX, 2) + Math.pow(y - handleY, 2));
    // Use the larger rotation handle size for better clickability
    return distance <= ROTATION_HANDLE_SIZE ? 'rotate' : null;
  };

  const handleRoomRotation = (event) => {
    if (!isRotating || selectedRoom === null) return;

    const coords = getCanvasCoordinates(event);
    const room = planData.rooms[selectedRoom];
    
    if (!room) return;
    
    const roomX = room.x + 20;
    const roomY = room.y + 20;
    const roomWidth = room.layout_width * PIXELS_PER_FOOT;
    const roomHeight = room.layout_height * PIXELS_PER_FOOT;
    
    const centerX = roomX + roomWidth / 2;
    const centerY = roomY + roomHeight / 2;
    
    // Calculate angle from center to mouse position
    let angle = Math.atan2(coords.y - centerY, coords.x - centerX) * 180 / Math.PI;
    
    // Normalize angle to 0-360 degrees
    if (angle < 0) {
      angle += 360;
    }
    
    // Adjust angle to make rotation more intuitive (0 degrees = top)
    angle = (angle + 90) % 360;
    
    // Snap to 5-degree increments for smoother rotation (reduced from 15 degrees)
    const snappedAngle = Math.round(angle / 5) * 5;
    
    // Ensure angle stays within 0-359 range
    const finalAngle = snappedAngle % 360;
    
    updateSelectedRoom({ rotation: finalAngle });
  };



  const handleCanvasMouseDown = (event) => {
    const coords = getCanvasCoordinates(event);
    const currentTime = Date.now();
    
    setMouseDownTime(currentTime);
    setMouseDownPos(coords);
    setIsDragCandidate(false);
    
    if (selectedTool === 'select') {
      // Find ALL rooms on current floor that contain the click point (for overlapping selection)
      const clickedRooms = [];
      const currentFloorRooms = getCurrentFloorRooms();
      
      currentFloorRooms.forEach((room) => {
        const roomIndex = planData.rooms.findIndex(r => r.id === room.id);
        
        // Validate room properties before calculations
        if (!room || !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
            room.layout_width <= 0 || room.layout_height <= 0) {
          return;
        }
        
        const roomX = room.x + 20;
        const roomY = room.y + 20;
        const roomWidth = room.layout_width * PIXELS_PER_FOOT;
        const roomHeight = room.layout_height * PIXELS_PER_FOOT;
        
        if (isPointInRotatedRect(coords.x, coords.y, roomX, roomY, roomWidth, roomHeight, room.rotation || 0)) {
          clickedRooms.push(roomIndex);
        }
      });
      
      if (clickedRooms.length > 0) {
        // If clicking on already selected room, prepare for potential drag
        if (clickedRooms.includes(selectedRoom)) {
          const room = planData.rooms[selectedRoom];
          
          if (room && isFinite(room.layout_width) && isFinite(room.layout_height) &&
              room.layout_width > 0 && room.layout_height > 0) {
            const roomX = room.x + 20;
            const roomY = room.y + 20;
            const roomWidth = room.layout_width * PIXELS_PER_FOOT;
            const roomHeight = room.layout_height * PIXELS_PER_FOOT;

            // Check if clicking on resize handle or rotation handle
            const resizeHandle = getResizeHandle(coords.x, coords.y, roomX, roomY, roomWidth, roomHeight);
            const rotationHandle = getRotationHandle(coords.x, coords.y, roomX, roomY, roomWidth, roomHeight, room.rotation || 0);
            
            if (resizeHandle) {
              setIsResizing(true);
              setResizeHandle(resizeHandle);
              setDragStart({ x: coords.x, y: coords.y });
            } else if (rotationHandle) {
              setIsRotating(true);
              setDragStart({ x: coords.x, y: coords.y });
            } else {
              // Prepare for dragging - don't start immediately
              setIsDragCandidate(true);
              setDragStart({ 
                x: coords.x - room.x, 
                y: coords.y - room.y 
              });
            }
          }
        } else {
          // Select new room - handle overlapping selection
          let newSelectedRoom;
          
          if (clickedRooms.length > 1) {
            // Multiple rooms overlap - select the topmost one first
            newSelectedRoom = clickedRooms[clickedRooms.length - 1];
            showInfo('Multiple Items', `${clickedRooms.length} items overlap. Click again to cycle.`);
          } else {
            // Only one room clicked
            newSelectedRoom = clickedRooms[0];
          }
          
          setSelectedRoom(newSelectedRoom);
        }
      } else {
        setSelectedRoom(null);
      }
    }
  };

  const handleCanvasMouseMove = (event) => {
    if (!mouseDownPos) return;
    
    const coords = getCanvasCoordinates(event);
    const distance = Math.sqrt(
      Math.pow(coords.x - mouseDownPos.x, 2) + 
      Math.pow(coords.y - mouseDownPos.y, 2)
    );
    
    // If mouse moved more than 5 pixels, start dragging
    if (distance > 5 && isDragCandidate && !isDragging && !isResizing && !isRotating) {
      setIsDragging(true);
      setIsDragCandidate(false);
    }
    
    // Handle actual dragging, resizing, or rotating
    if (isDragging && selectedRoom !== null) {
      handleRoomDrag(event);
    } else if (isResizing && selectedRoom !== null) {
      handleRoomResize(event);
    } else if (isRotating && selectedRoom !== null) {
      handleRoomRotation(event);
    }
  };

  const handleCanvasMouseUp = (event) => {
    const currentTime = Date.now();
    const clickDuration = mouseDownTime ? currentTime - mouseDownTime : 0;
    
    // If it was a quick click (less than 200ms) and no dragging occurred, handle selection cycling
    if (clickDuration < 200 && !isDragging && !isResizing && !isRotating && isDragCandidate) {
      const coords = getCanvasCoordinates(event);
      
      // Find overlapping rooms on current floor again
      const clickedRooms = [];
      const currentFloorRooms = getCurrentFloorRooms();
      
      currentFloorRooms.forEach((room) => {
        const roomIndex = planData.rooms.findIndex(r => r.id === room.id);
        
        if (!room || !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
            room.layout_width <= 0 || room.layout_height <= 0) {
          return;
        }
        
        const roomX = room.x + 20;
        const roomY = room.y + 20;
        const roomWidth = room.layout_width * PIXELS_PER_FOOT;
        const roomHeight = room.layout_height * PIXELS_PER_FOOT;
        
        if (isPointInRotatedRect(coords.x, coords.y, roomX, roomY, roomWidth, roomHeight, room.rotation || 0)) {
          clickedRooms.push(roomIndex);
        }
      });
      
      // Cycle through overlapping rooms
      if (clickedRooms.length > 1 && clickedRooms.includes(selectedRoom)) {
        const currentIndex = clickedRooms.indexOf(selectedRoom);
        const newSelectedRoom = clickedRooms[(currentIndex + 1) % clickedRooms.length];
        setSelectedRoom(newSelectedRoom);
        showInfo('Overlapping Selection', `Cycling through ${clickedRooms.length} overlapping items`);
      }
    }
    
    // Reset states
    setIsDragging(false);
    setIsResizing(false);
    setIsRotating(false);
    setResizeHandle(null);
    setMouseDownTime(null);
    setMouseDownPos(null);
    setIsDragCandidate(false);
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
      rotation: 0, // Add rotation property
      color: template.color,
      icon: template.icon,
      floor: currentFloor, // Assign to current floor
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
    showSuccess('Room Added', `${template.name} has been added to ${floorNames[currentFloor] || `Floor ${currentFloor}`}`);
    
    // Save immediately after room addition
    setTimeout(() => saveImmediately(), 100);
  };

  // Quick room addition for common circulation and structural elements
  const addQuickRoom = (type, name, width, height, color, icon) => {
    const newRoom = {
      id: Date.now(),
      name: name,
      category: type.includes('stair') || type.includes('column') || type.includes('beam') ? 'structural' : 
                type.includes('door') || type.includes('window') || type.includes('arch') ? 'doors' : 'circulation',
      type: type,
      x: 50,
      y: 50,
      layout_width: width,
      layout_height: height,
      actual_width: width * planData.scale_ratio,
      actual_height: height * planData.scale_ratio,
      rotation: 0, // Add rotation property
      color: color,
      icon: icon,
      floor: currentFloor, // Assign to current floor
      // Construction specifications
      wall_thickness: type.includes('stair') ? 1.0 : type.includes('door') || type.includes('window') ? 0.25 : 0.5,
      ceiling_height: type.includes('stair') ? 10 : type.includes('door') || type.includes('window') ? 8 : 9,
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
    showSuccess('Element Added', `${name} has been added to ${floorNames[currentFloor] || `Floor ${currentFloor}`}`);
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

  // Floor Management Functions
  const addNewFloor = () => {
    const newFloorNumber = totalFloors + 1;
    setTotalFloors(newFloorNumber);
    setFloorNames(prev => ({
      ...prev,
      [newFloorNumber]: `Floor ${newFloorNumber}`
    }));
    
    // Set default offset for new floor (can be customized later)
    setFloorOffsets(prev => ({
      ...prev,
      [newFloorNumber]: { x: 0, y: newFloorNumber === 1 ? 0 : (newFloorNumber - 1) * 300 }
    }));
    
    setCurrentFloor(newFloorNumber);
    showSuccess('Floor Added', `Floor ${newFloorNumber} has been added to your plan`);
    setHasUnsavedChanges(true);
    
    // Save immediately after floor addition
    setTimeout(() => saveImmediately(), 100);
  };

  const removeFloor = (floorNumber) => {
    if (totalFloors <= 1) {
      showWarning('Cannot Remove', 'You must have at least one floor in your plan');
      return;
    }

    // Remove rooms from this floor
    setPlanData(prev => ({
      ...prev,
      rooms: prev.rooms.filter(room => room.floor !== floorNumber)
    }));

    // Update floor names
    const newFloorNames = {};
    let newFloorCounter = 1;
    
    Object.entries(floorNames).forEach(([floor, name]) => {
      const floorNum = parseInt(floor);
      if (floorNum !== floorNumber) {
        newFloorNames[newFloorCounter] = floorNum < floorNumber ? name : name;
        newFloorCounter++;
      }
    });

    setFloorNames(newFloorNames);
    setTotalFloors(totalFloors - 1);
    
    // Switch to ground floor if current floor was removed
    if (currentFloor === floorNumber) {
      setCurrentFloor(1);
    } else if (currentFloor > floorNumber) {
      setCurrentFloor(currentFloor - 1);
    }

    showWarning('Floor Removed', `Floor ${floorNumber} and its rooms have been removed`);
    setHasUnsavedChanges(true);
  };

  const switchToFloor = (floorNumber) => {
    setCurrentFloor(floorNumber);
    setSelectedRoom(null); // Deselect any selected room when switching floors
    showInfo('Floor Switched', `Now viewing ${floorNames[floorNumber] || `Floor ${floorNumber}`}`);
  };

  // Floor positioning functions
  const updateFloorOffset = (floorNumber, offsetX, offsetY) => {
    setFloorOffsets(prev => ({
      ...prev,
      [floorNumber]: { x: offsetX, y: offsetY }
    }));
    
    // Update all rooms on this floor with the new offset
    setPlanData(prev => ({
      ...prev,
      rooms: prev.rooms.map(room => {
        if (room.floor === floorNumber) {
          const baseX = room.x - (prev.floorOffsets?.[floorNumber]?.x || 0);
          const baseY = room.y - (prev.floorOffsets?.[floorNumber]?.y || 0);
          return {
            ...room,
            x: baseX + offsetX,
            y: baseY + offsetY
          };
        }
        return room;
      })
    }));
    
    setHasUnsavedChanges(true);
    showInfo('Floor Repositioned', `${floorNames[floorNumber]} position updated`);
  };

  const resetFloorPosition = (floorNumber) => {
    const defaultY = floorNumber === 1 ? 0 : (floorNumber - 1) * 300;
    updateFloorOffset(floorNumber, 0, defaultY);
  };

  const moveFloorRooms = (floorNumber, deltaX, deltaY) => {
    setPlanData(prev => ({
      ...prev,
      rooms: prev.rooms.map(room => {
        if (room.floor === floorNumber) {
          return {
            ...room,
            x: room.x + deltaX,
            y: room.y + deltaY
          };
        }
        return room;
      })
    }));
    
    // Update floor offset to reflect the move
    const currentOffset = floorOffsets[floorNumber] || { x: 0, y: 0 };
    setFloorOffsets(prev => ({
      ...prev,
      [floorNumber]: { 
        x: currentOffset.x + deltaX, 
        y: currentOffset.y + deltaY 
      }
    }));
    
    setHasUnsavedChanges(true);
  };

  const updateFloorName = (floorNumber, newName) => {
    setFloorNames(prev => ({
      ...prev,
      [floorNumber]: newName
    }));
    setHasUnsavedChanges(true);
  };

  const getCurrentFloorRooms = () => {
    return planData.rooms.filter(room => room.floor === currentFloor);
  };

  const copyRoomToFloor = (room, targetFloor) => {
    const newRoom = {
      ...room,
      id: Date.now() + Math.random(), // Generate new unique ID
      floor: targetFloor,
      name: `${room.name} (Copy)`
    };

    setPlanData(prev => ({
      ...prev,
      rooms: [...prev.rooms, newRoom]
    }));

    showSuccess('Room Copied', `${room.name} copied to ${floorNames[targetFloor] || `Floor ${targetFloor}`}`);
    setHasUnsavedChanges(true);
  };

  // Download Functions for HousePlanDrawer
  const downloadCurrentPlanAsPDF = async () => {
    if (!planData.plan_name.trim()) {
      showError('Validation Error', 'Please enter a plan name before downloading');
      return;
    }

    setDownloadLoading(true);
    try {
      // Dynamic import for jsPDF
      const { default: jsPDF } = await import('jspdf');
      
      const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape orientation
      
      // Add title and header
      pdf.setFontSize(24);
      pdf.setFont('helvetica', 'bold');
      pdf.text(planData.plan_name, 20, 20);
      
      // Add subtitle
      pdf.setFontSize(14);
      pdf.setFont('helvetica', 'normal');
      pdf.text(`House Plan with Room-Specific Dimensions`, 20, 30);
      pdf.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 38);
      
      // Add basic project info
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'bold');
      let yPos = 50;
      
      pdf.text('PROJECT OVERVIEW', 20, yPos);
      yPos += 8;
      
      pdf.setFont('helvetica', 'normal');
      pdf.text(`Plot Size: ${planData.plot_width}' × ${planData.plot_height}' (${(planData.plot_width * planData.plot_height).toFixed(0)} sq ft)`, 20, yPos);
      yPos += 6;
      pdf.text(`Total Floors: ${totalFloors}`, 20, yPos);
      yPos += 6;
      pdf.text(`Total Rooms: ${planData.rooms.length}`, 20, yPos);
      yPos += 6;
      pdf.text(`Layout Area: ${calculateTotalArea().toFixed(0)} sq ft`, 20, yPos);
      yPos += 6;
      pdf.text(`Construction Area: ${calculateConstructionArea().toFixed(0)} sq ft`, 20, yPos);
      yPos += 6;
      pdf.text(`Scale Ratio: 1:${planData.scale_ratio} (Layout to Construction)`, 20, yPos);
      yPos += 6;
      pdf.text(`Coverage: ${((calculateConstructionArea() / (planData.plot_width * planData.plot_height)) * 100).toFixed(1)}%`, 20, yPos);
      yPos += 15;
      
      // Add detailed room specifications by floor
      if (planData.rooms && planData.rooms.length > 0) {
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('DETAILED ROOM SPECIFICATIONS', 20, yPos);
        yPos += 10;
        
        // Group rooms by floor
        for (let floorNum = 1; floorNum <= totalFloors; floorNum++) {
          const floorRooms = planData.rooms.filter(room => room.floor === floorNum);
          
          if (floorRooms.length > 0) {
            // Floor header
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.text(`${floorNames[floorNum] || `Floor ${floorNum}`} - ${floorRooms.length} Rooms`, 20, yPos);
            yPos += 8;
            
            // Calculate floor totals
            const floorLayoutArea = floorRooms.reduce((total, room) => total + (room.layout_width * room.layout_height), 0);
            const floorConstructionArea = floorRooms.reduce((total, room) => {
              const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
              const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
              return total + (actualWidth * actualHeight);
            }, 0);
            
            pdf.setFontSize(10);
            pdf.setFont('helvetica', 'italic');
            pdf.text(`Floor Total - Layout: ${floorLayoutArea.toFixed(0)} sq ft | Construction: ${floorConstructionArea.toFixed(0)} sq ft`, 25, yPos);
            yPos += 8;
            
            // Room details table header
            pdf.setFont('helvetica', 'bold');
            pdf.text('Room Name', 25, yPos);
            pdf.text('Layout Dims', 90, yPos);
            pdf.text('Construction Dims', 140, yPos);
            pdf.text('Area (sq ft)', 200, yPos);
            pdf.text('Position', 240, yPos);
            yPos += 6;
            
            // Draw header line
            pdf.line(25, yPos, 280, yPos);
            yPos += 4;
            
            pdf.setFont('helvetica', 'normal');
            floorRooms.forEach((room, index) => {
              const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
              const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
              const layoutArea = room.layout_width * room.layout_height;
              const constructionArea = actualWidth * actualHeight;
              
              // Room name
              pdf.text(`${index + 1}. ${room.name}`, 25, yPos);
              
              // Layout dimensions
              pdf.text(`${room.layout_width}' × ${room.layout_height}'`, 90, yPos);
              
              // Construction dimensions
              pdf.text(`${actualWidth.toFixed(1)}' × ${actualHeight.toFixed(1)}'`, 140, yPos);
              
              // Areas
              pdf.text(`L: ${layoutArea.toFixed(0)} | C: ${constructionArea.toFixed(0)}`, 200, yPos);
              
              // Position
              pdf.text(`(${room.x}, ${room.y})`, 240, yPos);
              
              yPos += 6;
              
              // Add room specifications if available
              if (room.wall_thickness || room.ceiling_height || room.floor_type || room.wall_material) {
                pdf.setFontSize(8);
                pdf.setFont('helvetica', 'italic');
                let specs = [];
                if (room.wall_thickness) specs.push(`Wall: ${room.wall_thickness}'`);
                if (room.ceiling_height) specs.push(`Height: ${room.ceiling_height}'`);
                if (room.floor_type) specs.push(`Floor: ${room.floor_type}`);
                if (room.wall_material) specs.push(`Material: ${room.wall_material}`);
                if (room.rotation && room.rotation !== 0) specs.push(`Rotation: ${room.rotation}°`);
                
                if (specs.length > 0) {
                  pdf.text(`    Specs: ${specs.join(', ')}`, 30, yPos);
                  yPos += 5;
                }
                
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'normal');
              }
              
              // Start new page if needed
              if (yPos > 180) {
                pdf.addPage();
                yPos = 20;
                
                // Re-add headers on new page
                pdf.setFontSize(12);
                pdf.setFont('helvetica', 'bold');
                pdf.text(`${floorNames[floorNum] || `Floor ${floorNum}`} (continued)`, 20, yPos);
                yPos += 10;
                
                pdf.setFontSize(10);
                pdf.setFont('helvetica', 'bold');
                pdf.text('Room Name', 25, yPos);
                pdf.text('Layout Dims', 90, yPos);
                pdf.text('Construction Dims', 140, yPos);
                pdf.text('Area (sq ft)', 200, yPos);
                pdf.text('Position', 240, yPos);
                yPos += 6;
                pdf.line(25, yPos, 280, yPos);
                yPos += 4;
                pdf.setFont('helvetica', 'normal');
              }
            });
            
            yPos += 8; // Extra space between floors
          }
        }
        
        // Add summary table
        if (yPos > 150) {
          pdf.addPage();
          yPos = 20;
        }
        
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('ROOM SUMMARY BY CATEGORY', 20, yPos);
        yPos += 10;
        
        // Group rooms by category
        const roomsByCategory = {};
        planData.rooms.forEach(room => {
          const category = room.category || 'Other';
          if (!roomsByCategory[category]) {
            roomsByCategory[category] = [];
          }
          roomsByCategory[category].push(room);
        });
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Category', 25, yPos);
        pdf.text('Count', 80, yPos);
        pdf.text('Layout Area', 120, yPos);
        pdf.text('Construction Area', 180, yPos);
        pdf.text('Avg Room Size', 240, yPos);
        yPos += 6;
        pdf.line(25, yPos, 280, yPos);
        yPos += 4;
        
        pdf.setFont('helvetica', 'normal');
        Object.entries(roomsByCategory).forEach(([category, rooms]) => {
          const categoryLayoutArea = rooms.reduce((total, room) => total + (room.layout_width * room.layout_height), 0);
          const categoryConstructionArea = rooms.reduce((total, room) => {
            const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
            const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
            return total + (actualWidth * actualHeight);
          }, 0);
          const avgRoomSize = categoryConstructionArea / rooms.length;
          
          pdf.text(category, 25, yPos);
          pdf.text(rooms.length.toString(), 80, yPos);
          pdf.text(`${categoryLayoutArea.toFixed(0)} sq ft`, 120, yPos);
          pdf.text(`${categoryConstructionArea.toFixed(0)} sq ft`, 180, yPos);
          pdf.text(`${avgRoomSize.toFixed(0)} sq ft`, 240, yPos);
          yPos += 6;
        });
      }
      
      // Add canvas visualization if available
      if (canvasRef.current) {
        try {
          // Dynamic import for html2canvas
          const html2canvas = (await import('html2canvas')).default;
          const canvas = await html2canvas(canvasRef.current, {
            backgroundColor: '#ffffff',
            scale: 2 // Higher resolution for better PDF quality
          });
          const imgData = canvas.toDataURL('image/png');
          
          // Add new page for the plan visualization
          pdf.addPage();
          pdf.setFontSize(16);
          pdf.setFont('helvetica', 'bold');
          pdf.text('VISUAL PLAN LAYOUT', 20, 20);
          
          // Add scale information
          pdf.setFontSize(10);
          pdf.setFont('helvetica', 'normal');
          pdf.text(`Scale: 1 foot = ${PIXELS_PER_FOOT} pixels | Grid: ${GRID_SIZE} pixels`, 20, 30);
          pdf.text(`Layout to Construction Ratio: 1:${planData.scale_ratio}`, 20, 36);
          
          // Calculate dimensions to fit the page
          const maxWidth = 250;
          const maxHeight = 160;
          const imgWidth = Math.min(maxWidth, (canvas.width * maxHeight) / canvas.height);
          const imgHeight = (canvas.height * imgWidth) / canvas.width;
          
          pdf.addImage(imgData, 'PNG', 20, 45, imgWidth, imgHeight);
          
          // Add legend
          const legendY = 45 + imgHeight + 10;
          pdf.setFontSize(10);
          pdf.setFont('helvetica', 'bold');
          pdf.text('LEGEND:', 20, legendY);
          
          pdf.setFont('helvetica', 'normal');
          pdf.text('• Blue borders: Selected rooms', 25, legendY + 8);
          pdf.text('• Grid lines: 1 foot increments', 25, legendY + 14);
          pdf.text('• Room labels show both layout and construction dimensions', 25, legendY + 20);
          pdf.text('• Position coordinates are relative to plot origin (top-left)', 25, legendY + 26);
          
        } catch (error) {
          console.error('Error adding canvas to PDF:', error);
          // Add error note in PDF
          pdf.addPage();
          pdf.setFontSize(12);
          pdf.text('Visual Layout: Unable to generate (technical error)', 20, 20);
        }
      }
      
      // Add construction specifications page
      pdf.addPage();
      pdf.setFontSize(16);
      pdf.setFont('helvetica', 'bold');
      pdf.text('CONSTRUCTION SPECIFICATIONS', 20, 20);
      yPos = 35;
      
      // Group rooms by construction specifications
      const specGroups = {};
      planData.rooms.forEach(room => {
        const key = `${room.wall_thickness || 0.5}-${room.ceiling_height || 9}-${room.floor_type || 'ceramic'}-${room.wall_material || 'brick'}`;
        if (!specGroups[key]) {
          specGroups[key] = {
            wall_thickness: room.wall_thickness || 0.5,
            ceiling_height: room.ceiling_height || 9,
            floor_type: room.floor_type || 'ceramic',
            wall_material: room.wall_material || 'brick',
            rooms: []
          };
        }
        specGroups[key].rooms.push(room);
      });
      
      pdf.setFontSize(12);
      pdf.setFont('helvetica', 'normal');
      Object.entries(specGroups).forEach(([key, group]) => {
        pdf.setFont('helvetica', 'bold');
        pdf.text(`Specification Group (${group.rooms.length} rooms):`, 20, yPos);
        yPos += 8;
        
        pdf.setFont('helvetica', 'normal');
        pdf.text(`Wall Thickness: ${group.wall_thickness}' | Ceiling Height: ${group.ceiling_height}'`, 25, yPos);
        yPos += 6;
        pdf.text(`Floor Type: ${group.floor_type} | Wall Material: ${group.wall_material}`, 25, yPos);
        yPos += 6;
        
        pdf.text(`Rooms: ${group.rooms.map(r => r.name).join(', ')}`, 25, yPos);
        yPos += 12;
        
        if (yPos > 180) {
          pdf.addPage();
          yPos = 20;
        }
      });
      
      // Add notes if available
      if (planData.notes && planData.notes.trim()) {
        if (yPos > 150) {
          pdf.addPage();
          yPos = 20;
        }
        
        pdf.setFontSize(14);
        pdf.setFont('helvetica', 'bold');
        pdf.text('PROJECT NOTES', 20, yPos);
        yPos += 10;
        
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        
        // Split notes into lines that fit the page
        const lines = pdf.splitTextToSize(planData.notes, 250);
        pdf.text(lines, 20, yPos);
      }
      
      // Add footer with generation info
      const pageCount = pdf.internal.getNumberOfPages();
      for (let i = 1; i <= pageCount; i++) {
        pdf.setPage(i);
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'italic');
        pdf.text(`Generated by BuildHub House Plan Designer - Page ${i} of ${pageCount}`, 20, 200);
        pdf.text(`${new Date().toLocaleString()}`, 200, 200);
      }
      
      // Save the PDF
      const fileName = `${planData.plan_name.replace(/[^a-z0-9]/gi, '_')}_detailed_house_plan.pdf`;
      pdf.save(fileName);
      showSuccess('Enhanced PDF Downloaded', 'Your detailed house plan with room-specific dimensions has been downloaded as PDF');
      
    } catch (error) {
      console.error('Error generating PDF:', error);
      showError('Download Failed', 'Error generating PDF. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  const downloadCurrentPlanAsImage = async () => {
    if (!planData.plan_name.trim()) {
      showError('Validation Error', 'Please enter a plan name before downloading');
      return;
    }

    setDownloadLoading(true);
    try {
      if (canvasRef.current) {
        // Dynamic import for html2canvas
        const html2canvas = (await import('html2canvas')).default;
        const canvas = await html2canvas(canvasRef.current, {
          backgroundColor: '#ffffff',
          scale: 2 // Higher resolution
        });
        
        // Create download link
        const link = document.createElement('a');
        link.download = `${planData.plan_name.replace(/[^a-z0-9]/gi, '_')}_layout.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        showSuccess('Image Downloaded', 'Your house plan layout has been downloaded as PNG');
      } else {
        showError('Download Failed', 'Plan canvas not available for download.');
      }
    } catch (error) {
      console.error('Error generating image:', error);
      showError('Download Failed', 'Error generating image. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  const downloadCurrentPlanAsJSON = () => {
    if (!planData.plan_name.trim()) {
      showError('Validation Error', 'Please enter a plan name before downloading');
      return;
    }

    try {
      // Calculate detailed statistics
      const roomsByCategory = {};
      const roomsByFloor = {};
      let totalLayoutArea = 0;
      let totalConstructionArea = 0;
      
      planData.rooms.forEach(room => {
        const category = room.category || 'Other';
        const floor = room.floor || 1;
        const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
        const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
        const layoutArea = room.layout_width * room.layout_height;
        const constructionArea = actualWidth * actualHeight;
        
        totalLayoutArea += layoutArea;
        totalConstructionArea += constructionArea;
        
        // Group by category
        if (!roomsByCategory[category]) {
          roomsByCategory[category] = {
            count: 0,
            layout_area: 0,
            construction_area: 0,
            rooms: []
          };
        }
        roomsByCategory[category].count++;
        roomsByCategory[category].layout_area += layoutArea;
        roomsByCategory[category].construction_area += constructionArea;
        roomsByCategory[category].rooms.push(room.name);
        
        // Group by floor
        if (!roomsByFloor[floor]) {
          roomsByFloor[floor] = {
            floor_name: floorNames[floor] || `Floor ${floor}`,
            count: 0,
            layout_area: 0,
            construction_area: 0,
            rooms: []
          };
        }
        roomsByFloor[floor].count++;
        roomsByFloor[floor].layout_area += layoutArea;
        roomsByFloor[floor].construction_area += constructionArea;
        roomsByFloor[floor].rooms.push({
          name: room.name,
          category: room.category,
          layout_dimensions: `${room.layout_width}' × ${room.layout_height}'`,
          construction_dimensions: `${actualWidth.toFixed(1)}' × ${actualHeight.toFixed(1)}'`,
          layout_area: layoutArea,
          construction_area: constructionArea,
          position: { x: room.x, y: room.y },
          rotation: room.rotation || 0
        });
      });

      const exportData = {
        metadata: {
          export_version: "2.0",
          generated_at: new Date().toISOString(),
          generated_by: "BuildHub House Plan Designer",
          export_type: "detailed_room_specifications"
        },
        plan_info: {
          name: planData.plan_name,
          plot_dimensions: {
            width: planData.plot_width,
            height: planData.plot_height,
            area: planData.plot_width * planData.plot_height,
            units: "feet"
          },
          areas: {
            plot_area: planData.plot_width * planData.plot_height,
            total_layout_area: totalLayoutArea,
            total_construction_area: totalConstructionArea,
            coverage_percentage: ((totalConstructionArea / (planData.plot_width * planData.plot_height)) * 100).toFixed(2),
            efficiency_ratio: (totalConstructionArea / totalLayoutArea).toFixed(3)
          },
          scale_ratio: planData.scale_ratio,
          total_floors: totalFloors,
          total_rooms: planData.rooms.length,
          created_at: new Date().toISOString()
        },
        floor_management: {
          total_floors: totalFloors,
          current_floor: currentFloor,
          floor_names: floorNames,
          floor_offsets: floorOffsets,
          rooms_by_floor: roomsByFloor
        },
        room_categories: roomsByCategory,
        detailed_room_specifications: planData.rooms.map((room, index) => {
          const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
          const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
          const layoutArea = room.layout_width * room.layout_height;
          const constructionArea = actualWidth * actualHeight;
          
          return {
            id: room.id || index + 1,
            name: room.name,
            category: room.category || 'Other',
            type: room.type || 'room',
            floor: room.floor || 1,
            floor_name: floorNames[room.floor] || `Floor ${room.floor}`,
            dimensions: {
              layout: {
                width: room.layout_width,
                height: room.layout_height,
                area: layoutArea,
                perimeter: 2 * (room.layout_width + room.layout_height),
                units: "feet"
              },
              construction: {
                width: actualWidth,
                height: actualHeight,
                area: constructionArea,
                perimeter: 2 * (actualWidth + actualHeight),
                units: "feet"
              },
              scale_factor: planData.scale_ratio
            },
            position: {
              x: room.x,
              y: room.y,
              rotation: room.rotation || 0,
              coordinates_system: "canvas_pixels_from_top_left"
            },
            construction_specs: {
              wall_thickness: room.wall_thickness || 0.5,
              ceiling_height: room.ceiling_height || 9,
              floor_type: room.floor_type || 'ceramic',
              wall_material: room.wall_material || 'brick',
              volume: constructionArea * (room.ceiling_height || 9)
            },
            visual_properties: {
              color: room.color || '#e3f2fd',
              icon: room.icon || '🏠'
            },
            notes: room.notes || ''
          };
        }),
        construction_summary: {
          total_volume: planData.rooms.reduce((total, room) => {
            const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
            const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
            const constructionArea = actualWidth * actualHeight;
            return total + (constructionArea * (room.ceiling_height || 9));
          }, 0),
          material_estimates: {
            wall_area_estimate: planData.rooms.reduce((total, room) => {
              const actualWidth = room.actual_width || room.layout_width * planData.scale_ratio;
              const actualHeight = room.actual_height || room.layout_height * planData.scale_ratio;
              const perimeter = 2 * (actualWidth + actualHeight);
              return total + (perimeter * (room.ceiling_height || 9));
            }, 0),
            floor_area: totalConstructionArea,
            ceiling_area: totalConstructionArea
          }
        },
        project_notes: planData.notes || '',
        requirements_reference: requirementsData ? {
          homeowner_name: requirementsData.homeowner_name,
          budget: requirementsData.budget,
          location: requirementsData.location,
          plot_size: requirementsData.plot_size,
          requirements: requirementsData.parsed_requirements
        } : null,
        technical_specifications: {
          grid_size: 20,
          pixels_per_foot: 20,
          measurement_units: "feet",
          coordinate_system: "top_left_origin",
          canvas_dimensions: canvasRef.current ? {
            width: canvasRef.current.width,
            height: canvasRef.current.height
          } : null
        }
      };
      
      const dataStr = JSON.stringify(exportData, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      
      const link = document.createElement('a');
      link.href = URL.createObjectURL(dataBlob);
      link.download = `${planData.plan_name.replace(/[^a-z0-9]/gi, '_')}_detailed_specifications.json`;
      link.click();
      
      // Clean up
      URL.revokeObjectURL(link.href);
      
      showSuccess('Enhanced JSON Downloaded', 'Your detailed house plan specifications have been downloaded as JSON');
    } catch (error) {
      console.error('Error generating JSON:', error);
      showError('Download Failed', 'Error generating JSON file. Please try again.');
    }
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

    // First save the plan if it has changes
    if (hasUnsavedChanges) {
      showInfo('Saving Plan', 'Saving plan before submission...');
      try {
        await handleSave();
      } catch (error) {
        showError('Save Failed', 'Please save the plan first before submitting');
        return;
      }
    }

    // Show technical details modal
    setShowTechnicalModal(true);
  };

  const handleTechnicalDetailsSubmit = async (technicalDetails) => {
    setSubmissionLoading(true);
    
    try {
      // First ensure the plan is saved
      let currentPlanId = existingPlan?.id;
      
      if (!currentPlanId) {
        // Save the plan first if it's new
        const saveResult = await savePlanForSubmission();
        if (!saveResult.success) {
          throw new Error(saveResult.message || 'Failed to save plan');
        }
        currentPlanId = saveResult.plan_id;
      }

      // Submit with technical details
      const submissionPayload = {
        plan_id: currentPlanId,
        technical_details: technicalDetails,
        plan_data: {
          rooms: planData.rooms,
          scale_ratio: planData.scale_ratio,
          total_layout_area: calculateTotalArea(),
          total_construction_area: calculateConstructionArea(),
          floors: {
            total_floors: totalFloors,
            current_floor: currentFloor,
            floor_names: floorNames
          }
        }
      };

      console.log('Submitting with technical details:', submissionPayload);

      const response = await fetch('/buildhub/backend/api/architect/submit_house_plan_with_details.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(submissionPayload)
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      
      if (result.success) {
        setShowTechnicalModal(false);
        showSuccess(
          'Plan Submitted Successfully!', 
          `Your house plan "${planData.plan_name}" with technical details has been submitted to the homeowner for review.`
        );
        
        // Trigger real-time dashboard refresh
        if (window.refreshDashboard) {
          setTimeout(() => window.refreshDashboard(), 500);
        }
        
        // Trigger parent component refresh if available
        if (window.refreshHousePlans) {
          setTimeout(() => window.refreshHousePlans(), 500);
        }
        
        if (onSave) {
          setTimeout(() => onSave(result), 1500);
        }
      } else {
        throw new Error(result.message || 'Submission failed');
      }
    } catch (error) {
      console.error('Error submitting plan:', error);
      showError(
        'Submission Failed', 
        `Failed to submit plan: ${error.message}. Please try again.`
      );
    } finally {
      setSubmissionLoading(false);
    }
  };

  // Helper function to save plan for submission
  const savePlanForSubmission = async () => {
    const payload = {
      plan_name: planData.plan_name,
      layout_request_id: layoutRequestId,
      plot_width: planData.plot_width,
      plot_height: planData.plot_height,
      plan_data: {
        rooms: planData.rooms,
        scale_ratio: planData.scale_ratio,
        total_layout_area: calculateTotalArea(),
        total_construction_area: calculateConstructionArea(),
        floors: {
          total_floors: totalFloors,
          current_floor: currentFloor,
          floor_names: floorNames,
          floor_offsets: floorOffsets
        }
      },
      notes: planData.notes,
      status: 'draft'
    };

    // Use currentPlanId to determine if we should update or create
    const isUpdate = currentPlanId || existingPlan?.id;
    const url = isUpdate 
      ? '/buildhub/backend/api/architect/update_house_plan.php'
      : '/buildhub/backend/api/architect/create_house_plan.php';

    if (isUpdate) {
      payload.plan_id = currentPlanId || existingPlan.id;
    }

    const response = await fetch(url, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    
    // Update currentPlanId if this was a new plan creation
    if (result.success && result.plan_id && !currentPlanId) {
      setCurrentPlanId(result.plan_id);
    }

    return result;
  };

  // Helper function to validate plan data before saving
  const validatePlanData = (data) => {
    const errors = [];
    
    if (!data.plan_name || data.plan_name.trim().length === 0) {
      errors.push('Plan name is required');
    }
    
    if (!data.plot_width || data.plot_width <= 0) {
      errors.push('Plot width must be greater than 0');
    }
    
    if (!data.plot_height || data.plot_height <= 0) {
      errors.push('Plot height must be greater than 0');
    }
    
    if (!data.plan_data || !data.plan_data.rooms || !Array.isArray(data.plan_data.rooms)) {
      errors.push('Plan data must contain rooms array');
    }
    
    // Validate each room
    if (data.plan_data && data.plan_data.rooms) {
      data.plan_data.rooms.forEach((room, index) => {
        if (!room.name || room.name.trim().length === 0) {
          errors.push(`Room ${index + 1}: Name is required`);
        }
        
        if (!room.layout_width || room.layout_width <= 0) {
          errors.push(`Room ${index + 1}: Layout width must be greater than 0`);
        }
        
        if (!room.layout_height || room.layout_height <= 0) {
          errors.push(`Room ${index + 1}: Layout height must be greater than 0`);
        }
        
        if (typeof room.x !== 'number' || typeof room.y !== 'number') {
          errors.push(`Room ${index + 1}: Position coordinates must be numbers`);
        }
      });
    }
    
    return errors;
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

    // Prepare payload
    const payload = {
      plan_name: planData.plan_name,
      layout_request_id: layoutRequestId,
      plot_width: planData.plot_width,
      plot_height: planData.plot_height,
      plan_data: {
        rooms: planData.rooms,
        scale_ratio: planData.scale_ratio,
        total_layout_area: calculateTotalArea(),
        total_construction_area: calculateConstructionArea(),
        floors: {
          total_floors: totalFloors,
          current_floor: currentFloor,
          floor_names: floorNames,
          floor_offsets: floorOffsets
        }
      },
      notes: planData.notes,
      status: 'draft'
    };

    // Use currentPlanId to determine if we should update or create
    const isUpdate = currentPlanId || existingPlan?.id;
    if (isUpdate) {
      payload.plan_id = currentPlanId || existingPlan.id;
    }

    // Validate payload
    const validationErrors = validatePlanData(payload);
    if (validationErrors.length > 0) {
      showError('Validation Error', validationErrors.join('; '));
      return;
    }

    setLoading(true);
    showInfo('Saving Plan', 'Please wait while we save your house plan...');
    
    try {
      const url = isUpdate 
        ? '/buildhub/backend/api/architect/update_house_plan.php'
        : '/buildhub/backend/api/architect/create_house_plan.php';

      console.log('Saving plan with payload:', payload); // Debug log
      console.log('Using URL:', url); // Debug log
      console.log('Is Update:', isUpdate, 'Current Plan ID:', currentPlanId); // Debug log

      const response = await fetch(url, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include', // Include session cookies
        body: JSON.stringify(payload)
      });

      console.log('Response status:', response.status); // Debug log

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const responseText = await response.text();
      console.log('Raw response:', responseText); // Debug log

      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Response text:', responseText);
        throw new Error('Invalid JSON response from server. Check console for details.');
      }

      console.log('Parsed result:', result); // Debug log
      
      if (result.success) {
        // Update local state to reflect saved data
        setLastSaved(new Date());
        setHasUnsavedChanges(false);
        
        // Update currentPlanId if this was a new plan creation
        if (result.plan_id && !currentPlanId) {
          setCurrentPlanId(result.plan_id);
          console.log('Manual save created new plan with ID:', result.plan_id);
        }
        
        // Clear auto-save timer
        if (autoSaveTimer) {
          clearTimeout(autoSaveTimer);
          setAutoSaveTimer(null);
        }
        
        // Show success toast notification
        const action = isUpdate ? 'updated' : 'created';
        showSuccess(
          'Plan Saved Successfully!', 
          `Your house plan "${planData.plan_name}" has been ${action} with ${planData.rooms.length} rooms covering ${calculateConstructionArea().toFixed(0)} sq ft.`
        );
        
        // Clear history after successful save and reinitialize
        setHistory([]);
        setHistoryIndex(-1);
        setTimeout(() => {
          saveToHistory();
        }, 100);
        
        // Trigger real-time dashboard refresh
        if (window.refreshDashboard) {
          setTimeout(() => window.refreshDashboard(), 500);
        }
        
        // Trigger parent component refresh if available
        if (window.refreshHousePlans) {
          setTimeout(() => window.refreshHousePlans(), 500);
        }
        
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
        `Failed to connect to the server: ${error.message}. Please check your internet connection and try again.`
      );
    } finally {
      setLoading(false);
    }
  };

  const currentRoom = selectedRoom !== null ? planData.rooms[selectedRoom] : null;

  return (
    <>
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
      
      <TechnicalDetailsModal
        isOpen={showTechnicalModal}
        onClose={() => setShowTechnicalModal(false)}
        onSubmit={handleTechnicalDetailsSubmit}
        planData={planData}
        loading={submissionLoading}
        requestInfo={requirementsData}
      />
      
      <div className="drawer-header">
        <div className="header-title">
          <h2>House Plan Designer</h2>
          {hasUnsavedChanges && (
            <span className="unsaved-indicator" title="You have unsaved changes">
              ● Unsaved Changes
            </span>
          )}
          {autoSaveInProgress && (
            <span className="auto-save-indicator" title="Auto-saving your changes">
              💾 Auto-saving...
            </span>
          )}
          {lastSaved && !hasUnsavedChanges && (
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
            <button 
              onClick={() => setShowKeyboardShortcuts(true)}
              className="action-btn shortcuts-btn"
              title="View keyboard shortcuts"
            >
              ⌨️ Shortcuts
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
            <div className="plan-actions">
              <div className="download-actions">
                <button 
                  onClick={downloadCurrentPlanAsPDF} 
                  disabled={loading || downloadLoading || !planData.plan_name.trim()}
                  className="download-btn pdf-btn"
                  title="Download as PDF with visual design and room-specific dimensions"
                >
                  {downloadLoading ? '⏳' : '📄'} PDF
                </button>
                <button 
                  onClick={downloadCurrentPlanAsImage} 
                  disabled={loading || downloadLoading || !planData.plan_name.trim()}
                  className="download-btn image-btn"
                  title="Download layout as high-resolution image"
                >
                  {downloadLoading ? '⏳' : '🖼️'} PNG
                </button>
                <button 
                  onClick={downloadCurrentPlanAsJSON} 
                  disabled={loading || downloadLoading || !planData.plan_name.trim()}
                  className="download-btn json-btn"
                  title="Download as structured JSON data with room specifications"
                >
                  {downloadLoading ? '⏳' : '📊'} JSON
                </button>
              </div>
              <div className="status-actions">
                <button onClick={handleSave} disabled={loading} className="edit-btn">
                  {loading ? 'Saving...' : 'Save Plan'}
                </button>
                <button onClick={handleSubmitToHomeowner} disabled={loading} className="submit-btn">
                  {loading ? 'Submitting...' : 'Submit'}
                </button>
                <button onClick={onCancel} className="delete-btn">Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="drawer-content">
        <div className="tools-panel">
          {/* Requirements Reference Section */}
          {requirementsData && (
            <div className="requirements-reference">
              <div className="requirements-header">
                <h4>📋 Client Requirements</h4>
                <button 
                  className="toggle-requirements-btn"
                  onClick={() => setShowRequirements(!showRequirements)}
                  title={showRequirements ? "Hide requirements" : "Show requirements"}
                >
                  {showRequirements ? '🔽' : '▶️'}
                </button>
              </div>
              
              {showRequirements && (
                <div className="requirements-content">
                  <div className="requirement-item">
                    <span className="requirement-label">Client:</span>
                    <span className="requirement-value">{requirementsData.homeowner_name || 'N/A'}</span>
                  </div>
                  
                  <div className="requirement-item">
                    <span className="requirement-label">Budget:</span>
                    <span className="requirement-value">{formatBudget(requirementsData.budget)}</span>
                  </div>
                  
                  <div className="requirement-item">
                    <span className="requirement-label">Plot Size:</span>
                    <span className="requirement-value">{requirementsData.plot_size || 'N/A'} sq ft</span>
                  </div>
                  
                  <div className="requirement-item">
                    <span className="requirement-label">Location:</span>
                    <span className="requirement-value">{requirementsData.location || 'N/A'}</span>
                  </div>
                  
                  {requirementsData.parsed_requirements && (
                    <>
                      {/* Quick Room Summary */}
                      {(() => {
                        const roomSummary = getRoomCountSummary(requirementsData.parsed_requirements);
                        return roomSummary && (
                          <div className="requirement-section room-summary">
                            <h5>🏠 Room Summary</h5>
                            <div className="room-summary-grid">
                              {Object.entries(roomSummary).map(([roomType, count]) => (
                                <div 
                                  key={roomType} 
                                  className="room-summary-item"
                                  onMouseEnter={() => setHighlightedRoomType(roomType)}
                                  onMouseLeave={() => setHighlightedRoomType(null)}
                                  title={`Hover to highlight ${roomType.replace(/_/g, ' ')} in plan`}
                                >
                                  <span className="room-summary-name">{roomType.replace(/_/g, ' ')}</span>
                                  <span className="room-summary-count">{count}</span>
                                </div>
                              ))}
                            </div>
                          </div>
                        );
                      })()}
                      
                      {/* Room Requirements */}
                      {requirementsData.parsed_requirements.floor_rooms && (
                        <div className="requirement-section">
                          <h5>🏠 Room Requirements</h5>
                          {Object.entries(requirementsData.parsed_requirements.floor_rooms).map(([floor, rooms]) => (
                            <div key={floor} className="floor-requirements">
                              <div className="floor-title">{floor.charAt(0).toUpperCase() + floor.slice(1)}:</div>
                              <div className="room-list">
                                {Object.entries(rooms).map(([roomType, count]) => (
                                  <div 
                                    key={roomType} 
                                    className="room-requirement"
                                    onMouseEnter={() => setHighlightedRoomType(roomType)}
                                    onMouseLeave={() => setHighlightedRoomType(null)}
                                    title={`Hover to highlight ${roomType.replace(/_/g, ' ')} in plan`}
                                  >
                                    <span className="room-name">{roomType.replace(/_/g, ' ')}</span>
                                    <span className="room-count">×{count}</span>
                                  </div>
                                ))}
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                      
                      {/* Special Features */}
                      {requirementsData.parsed_requirements.special_features && (
                        <div className="requirement-section">
                          <h5>✨ Special Features</h5>
                          <div className="features-list">
                            {requirementsData.parsed_requirements.special_features.map((feature, index) => (
                              <div key={index} className="feature-item">
                                • {feature}
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                      
                      {/* Style Preferences */}
                      {requirementsData.parsed_requirements.style_preference && (
                        <div className="requirement-section">
                          <h5>🎨 Style Preference</h5>
                          <div className="style-preference">
                            {requirementsData.parsed_requirements.style_preference}
                          </div>
                        </div>
                      )}
                      
                      {/* Additional Notes */}
                      {requirementsData.parsed_requirements.additional_notes && (
                        <div className="requirement-section">
                          <h5>📝 Additional Notes</h5>
                          <div className="additional-notes">
                            {requirementsData.parsed_requirements.additional_notes}
                          </div>
                        </div>
                      )}
                    </>
                  )}
                  
                  {/* Raw Requirements Display (fallback) */}
                  {requirementsData.requirements && !requirementsData.parsed_requirements && (
                    <div className="requirement-section">
                      <h5>📄 Requirements</h5>
                      <div className="raw-requirements">
                        {typeof requirementsData.requirements === 'string' 
                          ? requirementsData.requirements 
                          : JSON.stringify(requirementsData.requirements, null, 2)
                        }
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
          )}

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

          {/* Floor Management Section */}
          <div className="floor-management-section">
            <div className="floor-header">
              <h4>🏢 Floor Management</h4>
              <button 
                className="collapse-btn"
                onClick={() => setFloorSectionCollapsed(!floorSectionCollapsed)}
                title={floorSectionCollapsed ? "Expand floor controls" : "Collapse floor controls"}
              >
                {floorSectionCollapsed ? '▶️' : '▼️'}
              </button>
            </div>
            
            <div className="floor-controls">
              <div className="current-floor-info">
                <span className="floor-label">Current Floor:</span>
                <span className="floor-name">{floorNames[currentFloor] || `Floor ${currentFloor}`}</span>
                <span className="floor-rooms-count">({getCurrentFloorRooms().length} rooms)</span>
              </div>
              
              <div className="floor-tabs">
                {Array.from({ length: totalFloors }, (_, i) => i + 1).map(floorNum => {
                  const hasCustomPosition = floorOffsets[floorNum] && 
                    (floorOffsets[floorNum].x !== 0 || 
                     floorOffsets[floorNum].y !== (floorNum === 1 ? 0 : (floorNum - 1) * 300));
                  
                  return (
                    <button
                      key={floorNum}
                      className={`floor-tab ${currentFloor === floorNum ? 'active' : ''} ${hasCustomPosition ? 'has-custom-position' : ''}`}
                      onClick={() => switchToFloor(floorNum)}
                      title={`Switch to ${floorNames[floorNum] || `Floor ${floorNum}`}${hasCustomPosition ? ' (Custom Position)' : ''}`}
                    >
                      {floorNum}
                    </button>
                  );
                })}
                <button
                  className="floor-tab add-floor"
                  onClick={addNewFloor}
                  title="Add new floor"
                >
                  +
                </button>
              </div>
              
              {!floorSectionCollapsed && (
                <>
                  <div className="floor-actions">
                    <div className="floor-name-edit">
                      <input
                        type="text"
                        value={floorNames[currentFloor] || `Floor ${currentFloor}`}
                        onChange={(e) => updateFloorName(currentFloor, e.target.value)}
                        className="floor-name-input"
                        placeholder="Floor name"
                      />
                    </div>
                    
                    <div className="floor-buttons">
                      {totalFloors > 1 && (
                        <button
                          className="floor-action-btn remove-floor"
                          onClick={() => removeFloor(currentFloor)}
                          title="Remove current floor"
                        >
                          🗑️ Remove Floor
                        </button>
                      )}
                      
                      {selectedRoom !== null && (
                        <div className="copy-room-section">
                          <span className="copy-label">Copy selected room to:</span>
                          <div className="copy-floor-buttons">
                            {Array.from({ length: totalFloors }, (_, i) => i + 1)
                              .filter(floorNum => floorNum !== currentFloor)
                              .map(floorNum => (
                                <button
                                  key={floorNum}
                                  className="copy-floor-btn"
                                  onClick={() => copyRoomToFloor(planData.rooms[selectedRoom], floorNum)}
                                  title={`Copy to ${floorNames[floorNum] || `Floor ${floorNum}`}`}
                                >
                                  → {floorNum}
                                </button>
                              ))}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                  
                  {/* Floor Positioning Controls - Collapsible */}
                  <div className="floor-positioning-section">
                    <div className="positioning-header">
                      <h5>📐 Floor Position</h5>
                      <button 
                        className="collapse-positioning-btn"
                        onClick={() => setPositioningSectionCollapsed(!positioningSectionCollapsed)}
                        title={positioningSectionCollapsed ? "Show positioning controls" : "Hide positioning controls"}
                      >
                        {positioningSectionCollapsed ? '▶️' : '▼️'}
                      </button>
                    </div>
                    
                    {!positioningSectionCollapsed && (
                      <div className="positioning-controls">
                        <div className="position-info">
                          <span className="position-label">Position:</span>
                          <span className="position-values">
                            X: {floorOffsets[currentFloor]?.x || 0}px, 
                            Y: {floorOffsets[currentFloor]?.y || 0}px
                          </span>
                        </div>
                        
                        <div className="position-inputs">
                          <div className="position-input-group">
                            <label>X:</label>
                            <input
                              type="number"
                              value={floorOffsets[currentFloor]?.x || 0}
                              onChange={(e) => {
                                const newX = parseInt(e.target.value) || 0;
                                const currentY = floorOffsets[currentFloor]?.y || 0;
                                updateFloorOffset(currentFloor, newX, currentY);
                              }}
                              className="position-input"
                              step="10"
                            />
                          </div>
                          
                          <div className="position-input-group">
                            <label>Y:</label>
                            <input
                              type="number"
                              value={floorOffsets[currentFloor]?.y || 0}
                              onChange={(e) => {
                                const newY = parseInt(e.target.value) || 0;
                                const currentX = floorOffsets[currentFloor]?.x || 0;
                                updateFloorOffset(currentFloor, currentX, newY);
                              }}
                              className="position-input"
                              step="10"
                            />
                          </div>
                        </div>
                        
                        <div className="position-buttons">
                          <button
                            className="position-btn move-btn"
                            onClick={() => moveFloorRooms(currentFloor, 0, -50)}
                            title="Move floor up"
                          >
                            ⬆️
                          </button>
                          <button
                            className="position-btn move-btn"
                            onClick={() => moveFloorRooms(currentFloor, 0, 50)}
                            title="Move floor down"
                          >
                            ⬇️
                          </button>
                          <button
                            className="position-btn move-btn"
                            onClick={() => moveFloorRooms(currentFloor, -50, 0)}
                            title="Move floor left"
                          >
                            ⬅️
                          </button>
                          <button
                            className="position-btn move-btn"
                            onClick={() => moveFloorRooms(currentFloor, 50, 0)}
                            title="Move floor right"
                          >
                            ➡️
                          </button>
                        </div>
                        
                        <div className="position-presets">
                          <button
                            className="position-btn preset-btn"
                            onClick={() => resetFloorPosition(currentFloor)}
                            title="Reset to default position"
                          >
                            🔄 Reset
                          </button>
                          <button
                            className="position-btn preset-btn"
                            onClick={() => updateFloorOffset(currentFloor, 0, 400)}
                            title="Move to bottom area"
                          >
                            📍 Bottom
                          </button>
                          <button
                            className="position-btn preset-btn"
                            onClick={() => updateFloorOffset(currentFloor, 500, 0)}
                            title="Move to right side"
                          >
                            📍 Right
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>
          </div>

          <div className="plan-stats">
            <h4>Plan Statistics</h4>
            <div className="stats">
              <div>Plot Area: {(planData.plot_width * planData.plot_height).toFixed(0)} sq ft</div>
              <div>Total Floors: {totalFloors}</div>
              <div>Current Floor: {getCurrentFloorRooms().length} rooms</div>
              <div>Total Rooms: {planData.rooms.length}</div>
              <div>Layout Area: {calculateTotalArea().toFixed(0)} sq ft</div>
              <div>Construction Area: {calculateConstructionArea().toFixed(0)} sq ft</div>
              <div>Coverage: {((calculateConstructionArea() / (planData.plot_width * planData.plot_height)) * 100).toFixed(1)}%</div>
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

            {/* Quick Access for Doors & Openings */}
            <div className="quick-access">
              <h5>🚪 Doors & Openings</h5>
              <div className="quick-access-grid">
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('main_door', 'Main Door', 4, 1, '#b3d9ff', '🚪')}
                  title="Add Main Door (4' × 1')"
                >
                  🚪 Main Door
                </button>
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('interior_door', 'Interior Door', 3, 1, '#cce7ff', '🚪')}
                  title="Add Interior Door (3' × 1')"
                >
                  🚪 Interior Door
                </button>
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('sliding_door', 'Sliding Door', 6, 1, '#e0f2ff', '🚪')}
                  title="Add Sliding Door (6' × 1')"
                >
                  🚪 Sliding Door
                </button>
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('window', 'Window', 4, 1, '#d9ecff', '🪟')}
                  title="Add Window (4' × 1')"
                >
                  🪟 Window
                </button>
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('french_door', 'French Door', 5, 1, '#f0f8ff', '🚪')}
                  title="Add French Door (5' × 1')"
                >
                  🚪 French Door
                </button>
                <button
                  className="quick-btn doors"
                  onClick={() => addQuickRoom('arch_opening', 'Arch Opening', 4, 1, '#bfd6ff', '🏛️')}
                  title="Add Arch Opening (4' × 1')"
                >
                  🏛️ Arch Opening
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
                <div className="legend-color" style={{ backgroundColor: '#b3d9ff' }}></div>
                <span className="legend-label">Doors/Openings</span>
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

                <label>
                  Floor:
                  <select
                    value={currentRoom.floor || 1}
                    onChange={(e) => {
                      const newFloor = parseInt(e.target.value);
                      updateSelectedRoom({ floor: newFloor });
                      showInfo('Room Moved', `${currentRoom.name} moved to ${floorNames[newFloor] || `Floor ${newFloor}`}`);
                    }}
                  >
                    {Array.from({ length: totalFloors }, (_, i) => i + 1).map(floorNum => (
                      <option key={floorNum} value={floorNum}>
                        {floorNames[floorNum] || `Floor ${floorNum}`}
                      </option>
                    ))}
                  </select>
                </label>

                <div className="rotation-section">
                  <h5>Rotation</h5>
                  <div className="rotation-controls">
                    <label>
                      Angle:
                      <input
                        type="number"
                        value={currentRoom.rotation || 0}
                        onChange={(e) => {
                          const newRotation = parseFloat(e.target.value) || 0;
                          updateSelectedRoom({ rotation: newRotation });
                        }}
                        min="-180"
                        max="180"
                        step="15"
                      /> °
                    </label>
                    <div className="rotation-buttons">
                      <button
                        type="button"
                        className="rotation-btn"
                        onClick={() => updateSelectedRoom({ rotation: ((currentRoom.rotation || 0) - 15 + 360) % 360 })}
                        title="Rotate 15° counter-clockwise"
                      >
                        ↺ -15°
                      </button>
                      <button
                        type="button"
                        className="rotation-btn"
                        onClick={() => updateSelectedRoom({ rotation: ((currentRoom.rotation || 0) + 15) % 360 })}
                        title="Rotate 15° clockwise"
                      >
                        ↻ +15°
                      </button>
                      <button
                        type="button"
                        className="rotation-btn"
                        onClick={() => updateSelectedRoom({ rotation: ((currentRoom.rotation || 0) + 90) % 360 })}
                        title="Rotate 90° clockwise"
                      >
                        ↻ 90°
                      </button>
                      <button
                        type="button"
                        className="rotation-btn reset-btn"
                        onClick={() => updateSelectedRoom({ rotation: 0 })}
                        title="Reset rotation"
                      >
                        ⟲ Reset
                      </button>
                    </div>
                  </div>
                </div>
                
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

          <div className="interaction-help">
            <h4>How to Use</h4>
            <ul>
              <li>📋 Hover over requirements to highlight rooms in plan</li>
              <li>🖱️ Click rooms to select them</li>
              <li>🔄 Drag rooms to move them</li>
              <li>📏 Drag blue handles to resize</li>
              <li>↻ Drag red handle to rotate (5° snaps)</li>
              <li>🔄 Click overlapping items to cycle selection</li>
              <li>🚪 Use door templates for openings</li>
              <li>⌨️ Ctrl+Z to undo, Ctrl+Y to redo</li>
              <li>🗑️ Delete key to remove selected room</li>
              <li>🔄 R/E keys: rotate ±15°, T: rotate 90°, Q: reset rotation</li>
              <li>📐 Layout = Visual, Construction = Actual</li>
              <li>⚖️ Adjust scale ratio for realistic sizing</li>
            </ul>
          </div>
        </div>

        <div className="canvas-container">
          <div className="canvas-toolbar">
            <div className="canvas-info">
              <span>Plot: {planData.plot_width}' × {planData.plot_height}'</span>
              <span>Floor: {floorNames[currentFloor] || `Floor ${currentFloor}`} ({getCurrentFloorRooms().length} rooms)</span>
              <span>Total: {planData.rooms.length} rooms</span>
              <span>Scale: 1:{planData.scale_ratio}</span>
            </div>
            <div className="canvas-controls">
              <button onClick={() => setShowRequirements(!showRequirements)}>
                {showRequirements ? '📋 Hide' : '📋 Show'} Requirements
              </button>
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
              onMouseMove={handleCanvasMouseMove}
              onMouseUp={handleCanvasMouseUp}
              style={{ 
                cursor: isDragging ? 'grabbing' : isResizing ? 'nw-resize' : isRotating ? 'crosshair' : isDragCandidate ? 'grab' : 'default'
              }}
            />
          </div>
        </div>
      </div>

      {/* Keyboard Shortcuts Modal */}
      {showKeyboardShortcuts && (
        <div className="modal-overlay" onClick={() => setShowKeyboardShortcuts(false)}>
          <div className="keyboard-shortcuts-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>⌨️ Keyboard Shortcuts</h3>
              <button 
                className="modal-close-btn"
                onClick={() => setShowKeyboardShortcuts(false)}
                title="Close shortcuts"
              >
                ✕
              </button>
            </div>
            
            <div className="shortcuts-content">
              <div className="shortcuts-section">
                <h4>🎯 General Actions</h4>
                <div className="shortcut-list">
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>Ctrl</kbd> + <kbd>Z</kbd>
                    </span>
                    <span className="shortcut-description">Undo last action</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>Ctrl</kbd> + <kbd>Y</kbd>
                    </span>
                    <span className="shortcut-description">Redo last undone action</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>Delete</kbd>
                    </span>
                    <span className="shortcut-description">Delete selected room</span>
                  </div>
                </div>
              </div>

              <div className="shortcuts-section">
                <h4>🔄 Room Rotation</h4>
                <div className="shortcut-list">
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>R</kbd>
                    </span>
                    <span className="shortcut-description">Rotate room 15° clockwise</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>E</kbd>
                    </span>
                    <span className="shortcut-description">Rotate room 15° counter-clockwise</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>T</kbd>
                    </span>
                    <span className="shortcut-description">Rotate room 90° clockwise</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <kbd>Q</kbd>
                    </span>
                    <span className="shortcut-description">Reset room rotation to 0°</span>
                  </div>
                </div>
              </div>

              <div className="shortcuts-section">
                <h4>🖱️ Mouse Controls</h4>
                <div className="shortcut-list">
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <span className="mouse-action">Click</span>
                    </span>
                    <span className="shortcut-description">Select room</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <span className="mouse-action">Drag</span>
                    </span>
                    <span className="shortcut-description">Move selected room</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <span className="mouse-action">Drag Blue Handle</span>
                    </span>
                    <span className="shortcut-description">Resize room</span>
                  </div>
                  <div className="shortcut-item">
                    <span className="shortcut-keys">
                      <span className="mouse-action">Drag Red Handle</span>
                    </span>
                    <span className="shortcut-description">Rotate room (5° snaps)</span>
                  </div>
                </div>
              </div>

              <div className="shortcuts-section">
                <h4>💡 Tips</h4>
                <div className="tips-list">
                  <div className="tip-item">
                    <span className="tip-icon">🎯</span>
                    <span className="tip-text">Select a room first before using rotation shortcuts</span>
                  </div>
                  <div className="tip-item">
                    <span className="tip-icon">🔄</span>
                    <span className="tip-text">Click overlapping rooms multiple times to cycle selection</span>
                  </div>
                  <div className="tip-item">
                    <span className="tip-icon">📐</span>
                    <span className="tip-text">Use the properties panel for precise angle input</span>
                  </div>
                  <div className="tip-item">
                    <span className="tip-icon">⚡</span>
                    <span className="tip-text">Rotation handles appear outside selected rooms</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button 
                className="modal-close-btn-primary"
                onClick={() => setShowKeyboardShortcuts(false)}
              >
                Got it!
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
    </>
  );
};

export default HousePlanDrawer;