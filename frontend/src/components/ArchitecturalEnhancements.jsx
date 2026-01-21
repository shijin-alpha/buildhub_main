import React, { useRef, useEffect, useState } from 'react';
import { WallSystem } from './WallSystem';
import { GeometryConstraints } from './GeometryConstraints';
import { EnhancedMeasurements } from './EnhancedMeasurements';
import { ConstructionMetadata } from './ConstructionMetadata';
import { EnhancedCanvasRenderer } from './EnhancedCanvasRenderer';

/**
 * Architectural Enhancements Integration Layer
 * This component integrates all architectural-grade enhancements with the existing
 * HousePlanDrawer while maintaining full backward compatibility
 */

export const useArchitecturalEnhancements = (planData, canvas, options = {}) => {
  // Initialize enhancement systems
  const wallSystemRef = useRef(new WallSystem());
  const constraintsRef = useRef(new GeometryConstraints());
  const measurementsRef = useRef(new EnhancedMeasurements());
  const metadataRef = useRef(new ConstructionMetadata());
  const rendererRef = useRef(null);

  // Enhancement state
  const [enhancementsEnabled, setEnhancementsEnabled] = useState(options.enabled !== false);
  const [wallsVisible, setWallsVisible] = useState(true);
  const [constraintsVisible, setConstraintsVisible] = useState(true);
  const [enhancedMeasurementsVisible, setEnhancedMeasurementsVisible] = useState(true);
  const [constructionDetailsVisible, setConstructionDetailsVisible] = useState(false);

  // Initialize renderer when canvas is available
  useEffect(() => {
    if (canvas && enhancementsEnabled) {
      rendererRef.current = new EnhancedCanvasRenderer(canvas, 20);
    }
  }, [canvas, enhancementsEnabled]);

  // Update wall system when rooms change
  useEffect(() => {
    if (!enhancementsEnabled || !planData.rooms) return;

    const wallSystem = wallSystemRef.current;
    
    // Update walls for all rooms
    for (const room of planData.rooms) {
      if (room.id) {
        wallSystem.updateWallsForRoom(room.id, room);
      }
    }

    // Detect and create shared walls
    wallSystem.detectSharedWalls();
  }, [planData.rooms, enhancementsEnabled]);

  // Update measurements when rooms change
  useEffect(() => {
    if (!enhancementsEnabled || !planData.rooms) return;

    const measurements = measurementsRef.current;
    
    // Update measurements for all rooms
    for (const room of planData.rooms) {
      if (room.id) {
        measurements.updateRoomMeasurements(room.id, room);
        measurements.createRoomDimensions(room.id, room);
      }
    }
  }, [planData.rooms, enhancementsEnabled]);

  // Update construction metadata when rooms change
  useEffect(() => {
    if (!enhancementsEnabled || !planData.rooms) return;

    const metadata = metadataRef.current;
    
    // Update specifications for all rooms
    for (const room of planData.rooms) {
      if (room.id) {
        metadata.updateRoomSpecifications(room.id, room);
      }
    }
  }, [planData.rooms, enhancementsEnabled]);

  /**
   * Enhanced drawing function that integrates with existing canvas drawing
   */
  const drawEnhancedCanvas = (existingDrawFunction, selectedRoom, showMeasurements, measurementMode) => {
    if (!enhancementsEnabled || !rendererRef.current) {
      // Fall back to existing drawing function
      if (existingDrawFunction) {
        existingDrawFunction();
      }
      return;
    }

    const renderer = rendererRef.current;
    const wallSystem = wallSystemRef.current;
    const constraints = constraintsRef.current;
    const measurements = measurementsRef.current;

    // Prepare rendering data
    const renderData = {
      rooms: planData.rooms || [],
      walls: wallsVisible ? wallSystem.getWallsForRendering() : [],
      openings: wallsVisible ? Array.from(wallSystem.openings.values()) : [],
      measurements: enhancedMeasurementsVisible ? measurements.getMeasurementsForRendering() : [],
      constraints: constraintsVisible ? {
        alignmentGuides: constraints.getAlignmentGuides(),
        snapPoints: constraints.getSnapPoints()
      } : {},
      selectedRoom,
      showMeasurements,
      measurementMode,
      plotWidth: planData.plot_width || 100,
      plotHeight: planData.plot_height || 100
    };

    // Render all elements
    renderer.renderArchitecturalElements(renderData);
  };

  /**
   * Enhanced room movement with constraints
   */
  const handleEnhancedRoomMovement = (roomIndex, newX, newY, rooms) => {
    if (!enhancementsEnabled) {
      return { x: newX, y: newY };
    }

    const constraints = constraintsRef.current;
    const room = rooms[roomIndex];
    
    if (!room) {
      return { x: newX, y: newY };
    }

    // Generate snap points and alignment guides
    constraints.generateSnapPoints(rooms, room.id);
    
    // Convert pixel coordinates to room coordinates
    const roomX = newX - 20;
    const roomY = newY - 20;
    
    // Check for snap points
    const nearestSnap = constraints.findNearestSnapPoint(newX, newY);
    if (nearestSnap) {
      return {
        x: nearestSnap.x - 20,
        y: nearestSnap.y - 20,
        snapped: true,
        snapPoint: nearestSnap
      };
    }

    // Apply grid snap if enabled
    const gridSnapped = constraints.applyGridSnap(roomX, roomY, 20);
    
    // Generate alignment guides for visual feedback
    const movingRoom = { ...room, x: gridSnapped.x, y: gridSnapped.y };
    constraints.generateAlignmentGuides(rooms, movingRoom);

    return gridSnapped;
  };

  /**
   * Add opening to wall
   */
  const addOpeningToWall = (wallId, openingData) => {
    if (!enhancementsEnabled) return null;

    const wallSystem = wallSystemRef.current;
    return wallSystem.addOpening(wallId, openingData);
  };

  /**
   * Get wall at position (for opening placement)
   */
  const getWallAtPosition = (x, y, tolerance = 10) => {
    if (!enhancementsEnabled) return null;

    const wallSystem = wallSystemRef.current;
    const walls = wallSystem.getWallsForRendering();

    for (const wall of walls) {
      const { geometry } = wall;
      const distance = calculateDistanceToWall(x, y, geometry);
      
      if (distance <= tolerance) {
        return wall;
      }
    }

    return null;
  };

  /**
   * Calculate distance from point to wall
   */
  const calculateDistanceToWall = (x, y, geometry) => {
    const { x1, y1, x2, y2 } = geometry;
    
    // Convert to pixel coordinates
    const wallX1 = x1 * 20 + 20;
    const wallY1 = y1 * 20 + 20;
    const wallX2 = x2 * 20 + 20;
    const wallY2 = y2 * 20 + 20;

    // Calculate distance from point to line segment
    const A = x - wallX1;
    const B = y - wallY1;
    const C = wallX2 - wallX1;
    const D = wallY2 - wallY1;

    const dot = A * C + B * D;
    const lenSq = C * C + D * D;
    
    if (lenSq === 0) return Math.sqrt(A * A + B * B);
    
    const param = dot / lenSq;
    let xx, yy;

    if (param < 0) {
      xx = wallX1;
      yy = wallY1;
    } else if (param > 1) {
      xx = wallX2;
      yy = wallY2;
    } else {
      xx = wallX1 + param * C;
      yy = wallY1 + param * D;
    }

    const dx = x - xx;
    const dy = y - yy;
    return Math.sqrt(dx * dx + dy * dy);
  };

  /**
   * Get enhanced room properties
   */
  const getEnhancedRoomProperties = (roomId) => {
    if (!enhancementsEnabled) return null;

    const metadata = metadataRef.current;
    const measurements = measurementsRef.current;
    const wallSystem = wallSystemRef.current;

    return {
      specifications: metadata.getRoomSpecifications(roomId),
      measurements: measurements.getMeasurementsForRendering({ roomId }),
      walls: wallSystem.roomWalls.get(roomId) || new Set()
    };
  };

  /**
   * Generate construction report
   */
  const generateConstructionReport = () => {
    if (!enhancementsEnabled) return null;

    const metadata = metadataRef.current;
    const measurements = measurementsRef.current;

    return {
      construction: metadata.generateConstructionSummary(planData.rooms || []),
      measurements: measurements.generateMeasurementReport(
        planData.rooms || [],
        planData.plot_width || 100,
        planData.plot_height || 100
      )
    };
  };

  /**
   * Auto-arrange rooms with constraints
   */
  const autoArrangeRooms = (arrangement = 'grid') => {
    if (!enhancementsEnabled) return planData.rooms;

    const constraints = constraintsRef.current;
    return constraints.autoArrangeRooms(planData.rooms || [], arrangement);
  };

  /**
   * Validate room placement
   */
  const validateRoomPlacement = (room, rooms) => {
    if (!enhancementsEnabled) return [];

    const constraints = constraintsRef.current;
    return constraints.validateRoomPlacement(room, rooms);
  };

  /**
   * Export enhanced plan data
   */
  const exportEnhancedPlanData = () => {
    if (!enhancementsEnabled) {
      return {
        rooms: planData.rooms,
        scale_ratio: planData.scale_ratio
      };
    }

    return {
      rooms: planData.rooms,
      scale_ratio: planData.scale_ratio,
      walls: wallSystemRef.current.exportData(),
      constraints: constraintsRef.current.exportData(),
      measurements: measurementsRef.current.exportData(),
      construction: metadataRef.current.exportData(),
      enhancements: {
        version: '1.0',
        enabled: enhancementsEnabled,
        settings: {
          wallsVisible,
          constraintsVisible,
          enhancedMeasurementsVisible,
          constructionDetailsVisible
        }
      }
    };
  };

  /**
   * Import enhanced plan data
   */
  const importEnhancedPlanData = (data) => {
    if (!data || !enhancementsEnabled) return;

    if (data.walls) {
      wallSystemRef.current.importData(data.walls);
    }

    if (data.constraints) {
      constraintsRef.current.importData(data.constraints);
    }

    if (data.measurements) {
      measurementsRef.current.importData(data.measurements);
    }

    if (data.construction) {
      metadataRef.current.importData(data.construction);
    }

    if (data.enhancements && data.enhancements.settings) {
      const settings = data.enhancements.settings;
      setWallsVisible(settings.wallsVisible !== false);
      setConstraintsVisible(settings.constraintsVisible !== false);
      setEnhancedMeasurementsVisible(settings.enhancedMeasurementsVisible !== false);
      setConstructionDetailsVisible(settings.constructionDetailsVisible || false);
    }
  };

  /**
   * Clear temporary visual elements
   */
  const clearTemporaryElements = () => {
    if (!enhancementsEnabled) return;

    constraintsRef.current.clearTemporaryGuides();
  };

  // Return enhancement interface
  return {
    // Core systems
    wallSystem: wallSystemRef.current,
    constraints: constraintsRef.current,
    measurements: measurementsRef.current,
    metadata: metadataRef.current,
    renderer: rendererRef.current,

    // State
    enhancementsEnabled,
    wallsVisible,
    constraintsVisible,
    enhancedMeasurementsVisible,
    constructionDetailsVisible,

    // State setters
    setEnhancementsEnabled,
    setWallsVisible,
    setConstraintsVisible,
    setEnhancedMeasurementsVisible,
    setConstructionDetailsVisible,

    // Enhanced functions
    drawEnhancedCanvas,
    handleEnhancedRoomMovement,
    addOpeningToWall,
    getWallAtPosition,
    getEnhancedRoomProperties,
    generateConstructionReport,
    autoArrangeRooms,
    validateRoomPlacement,
    exportEnhancedPlanData,
    importEnhancedPlanData,
    clearTemporaryElements,

    // Utility functions
    calculateDistanceToWall
  };
};

/**
 * Enhanced Room Properties Panel Component
 */
export const EnhancedRoomPropertiesPanel = ({ 
  room, 
  onUpdateRoom, 
  enhancements,
  visible = true 
}) => {
  if (!visible || !room || !enhancements.enhancementsEnabled) {
    return null;
  }

  const roomProperties = enhancements.getEnhancedRoomProperties(room.id);
  const specifications = roomProperties?.specifications;

  return (
    <div className="enhanced-room-properties">
      <h5>🏗️ Construction Details</h5>
      
      {specifications && (
        <div className="construction-specs">
          <div className="spec-section">
            <h6>Structure</h6>
            <div className="spec-grid">
              <div className="spec-item">
                <label>Wall Material:</label>
                <select
                  value={specifications.structure.wallMaterial}
                  onChange={(e) => {
                    onUpdateRoom({ wall_material: e.target.value });
                    enhancements.metadata.updateRoomSpecifications(room.id, {
                      ...room,
                      wall_material: e.target.value
                    });
                  }}
                >
                  <option value="brick">Brick</option>
                  <option value="concrete">Concrete Block</option>
                  <option value="wood">Wood Frame</option>
                  <option value="steel">Steel Frame</option>
                </select>
              </div>
              
              <div className="spec-item">
                <label>Wall Thickness:</label>
                <input
                  type="number"
                  value={specifications.structure.wallThickness}
                  onChange={(e) => {
                    const thickness = parseFloat(e.target.value) || 0.5;
                    onUpdateRoom({ wall_thickness: thickness });
                    enhancements.metadata.updateRoomSpecifications(room.id, {
                      ...room,
                      wall_thickness: thickness
                    });
                  }}
                  min="0.25"
                  max="2"
                  step="0.25"
                />
                <span>ft</span>
              </div>
            </div>
          </div>

          <div className="spec-section">
            <h6>Finishes</h6>
            <div className="spec-grid">
              <div className="spec-item">
                <label>Flooring:</label>
                <select
                  value={specifications.finishes.flooring}
                  onChange={(e) => {
                    onUpdateRoom({ floor_type: e.target.value });
                  }}
                >
                  <option value="ceramic_tile">Ceramic Tile</option>
                  <option value="hardwood_oak">Oak Hardwood</option>
                  <option value="marble">Marble</option>
                  <option value="granite">Granite</option>
                  <option value="vinyl">Vinyl</option>
                  <option value="concrete">Polished Concrete</option>
                </select>
              </div>
            </div>
          </div>

          <div className="spec-section">
            <h6>Cost Estimate</h6>
            <div className="cost-breakdown">
              <div className="cost-item">
                <span>Structure:</span>
                <span>${specifications.costs.structure.toLocaleString()}</span>
              </div>
              <div className="cost-item">
                <span>Finishes:</span>
                <span>${specifications.costs.finishes.toLocaleString()}</span>
              </div>
              <div className="cost-item">
                <span>Systems:</span>
                <span>${specifications.costs.systems.toLocaleString()}</span>
              </div>
              <div className="cost-item total">
                <span>Total:</span>
                <span>${specifications.costs.total.toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

/**
 * Architectural Enhancements Control Panel
 */
export const ArchitecturalControlPanel = ({ enhancements, onGenerateReport }) => {
  if (!enhancements.enhancementsEnabled) {
    return (
      <div className="architectural-controls">
        <button
          onClick={() => enhancements.setEnhancementsEnabled(true)}
          className="enable-enhancements-btn"
        >
          🏗️ Enable Architectural Features
        </button>
      </div>
    );
  }

  return (
    <div className="architectural-controls">
      <h5>🏗️ Architectural Features</h5>
      
      <div className="control-section">
        <h6>Visibility</h6>
        <div className="control-grid">
          <label className="control-item">
            <input
              type="checkbox"
              checked={enhancements.wallsVisible}
              onChange={(e) => enhancements.setWallsVisible(e.target.checked)}
            />
            Show Walls & Openings
          </label>
          
          <label className="control-item">
            <input
              type="checkbox"
              checked={enhancements.constraintsVisible}
              onChange={(e) => enhancements.setConstraintsVisible(e.target.checked)}
            />
            Show Alignment Guides
          </label>
          
          <label className="control-item">
            <input
              type="checkbox"
              checked={enhancements.enhancedMeasurementsVisible}
              onChange={(e) => enhancements.setEnhancedMeasurementsVisible(e.target.checked)}
            />
            Enhanced Measurements
          </label>
          
          <label className="control-item">
            <input
              type="checkbox"
              checked={enhancements.constructionDetailsVisible}
              onChange={(e) => enhancements.setConstructionDetailsVisible(e.target.checked)}
            />
            Construction Details
          </label>
        </div>
      </div>

      <div className="control-section">
        <h6>Tools</h6>
        <div className="tool-buttons">
          <button
            onClick={() => enhancements.autoArrangeRooms('grid')}
            className="tool-btn"
          >
            📐 Auto-Arrange (Grid)
          </button>
          
          <button
            onClick={() => enhancements.autoArrangeRooms('linear')}
            className="tool-btn"
          >
            📏 Auto-Arrange (Linear)
          </button>
          
          <button
            onClick={onGenerateReport}
            className="tool-btn"
          >
            📊 Generate Report
          </button>
        </div>
      </div>
    </div>
  );
};

export default {
  useArchitecturalEnhancements,
  EnhancedRoomPropertiesPanel,
  ArchitecturalControlPanel
};