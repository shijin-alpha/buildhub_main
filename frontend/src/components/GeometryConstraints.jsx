import React from 'react';

/**
 * Geometry Constraints System - Manages spatial relationships and constraints
 * This system provides architectural-grade constraints for room placement,
 * wall alignment, and geometric relationships while maintaining compatibility
 * with the existing free-form room placement system.
 */

export class GeometryConstraints {
  constructor() {
    this.constraints = new Map(); // Constraint ID -> Constraint object
    this.roomConstraints = new Map(); // Room ID -> Set of Constraint IDs
    this.snapPoints = new Map(); // Snap point ID -> Snap point data
    this.alignmentGuides = new Set(); // Active alignment guides
    this.constraintViolations = new Set(); // Current constraint violations
    
    // Constraint types
    this.CONSTRAINT_TYPES = {
      ALIGNMENT: 'alignment', // Rooms/walls aligned
      ADJACENCY: 'adjacency', // Rooms touching
      DISTANCE: 'distance', // Minimum/maximum distance
      PARALLEL: 'parallel', // Parallel walls
      PERPENDICULAR: 'perpendicular', // Perpendicular walls
      GRID_SNAP: 'grid_snap', // Snap to grid
      WALL_CONTINUITY: 'wall_continuity' // Continuous wall lines
    };

    // Snap tolerance in pixels
    this.SNAP_TOLERANCE = 10;
    this.ALIGNMENT_TOLERANCE = 5;
  }

  /**
   * Add a constraint between elements
   */
  addConstraint(type, elements, parameters = {}) {
    const constraintId = `constraint_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const constraint = {
      id: constraintId,
      type,
      elements: new Set(elements), // Room IDs, Wall IDs, etc.
      parameters: {
        tolerance: parameters.tolerance || this.ALIGNMENT_TOLERANCE,
        priority: parameters.priority || 1, // Higher priority constraints are enforced first
        enabled: parameters.enabled !== false,
        ...parameters
      },
      metadata: {
        createdAt: Date.now(),
        lastChecked: null,
        violationCount: 0
      }
    };

    this.constraints.set(constraintId, constraint);

    // Update element-constraint mappings
    for (const elementId of elements) {
      if (!this.roomConstraints.has(elementId)) {
        this.roomConstraints.set(elementId, new Set());
      }
      this.roomConstraints.get(elementId).add(constraintId);
    }

    return constraint;
  }

  /**
   * Generate snap points for room placement
   */
  generateSnapPoints(rooms, selectedRoomId = null) {
    this.snapPoints.clear();
    
    for (const room of rooms) {
      if (room.id === selectedRoomId) continue; // Skip the room being moved
      
      const roomPixelX = room.x + 20;
      const roomPixelY = room.y + 20;
      const roomPixelWidth = room.layout_width * 20; // PIXELS_PER_FOOT
      const roomPixelHeight = room.layout_height * 20;

      // Corner snap points
      this.addSnapPoint(`${room.id}_corner_tl`, roomPixelX, roomPixelY, 'corner');
      this.addSnapPoint(`${room.id}_corner_tr`, roomPixelX + roomPixelWidth, roomPixelY, 'corner');
      this.addSnapPoint(`${room.id}_corner_bl`, roomPixelX, roomPixelY + roomPixelHeight, 'corner');
      this.addSnapPoint(`${room.id}_corner_br`, roomPixelX + roomPixelWidth, roomPixelY + roomPixelHeight, 'corner');

      // Edge midpoint snap points
      this.addSnapPoint(`${room.id}_edge_top`, roomPixelX + roomPixelWidth / 2, roomPixelY, 'edge');
      this.addSnapPoint(`${room.id}_edge_right`, roomPixelX + roomPixelWidth, roomPixelY + roomPixelHeight / 2, 'edge');
      this.addSnapPoint(`${room.id}_edge_bottom`, roomPixelX + roomPixelWidth / 2, roomPixelY + roomPixelHeight, 'edge');
      this.addSnapPoint(`${room.id}_edge_left`, roomPixelX, roomPixelY + roomPixelHeight / 2, 'edge');

      // Center snap point
      this.addSnapPoint(`${room.id}_center`, roomPixelX + roomPixelWidth / 2, roomPixelY + roomPixelHeight / 2, 'center');
    }
  }

  /**
   * Add a snap point
   */
  addSnapPoint(id, x, y, type, metadata = {}) {
    this.snapPoints.set(id, {
      id,
      x,
      y,
      type, // corner, edge, center, grid, custom
      active: true,
      metadata: {
        ...metadata,
        createdAt: Date.now()
      }
    });
  }

  /**
   * Find the nearest snap point to a given position
   */
  findNearestSnapPoint(x, y, excludeTypes = []) {
    let nearestPoint = null;
    let minDistance = this.SNAP_TOLERANCE;

    for (const snapPoint of this.snapPoints.values()) {
      if (!snapPoint.active || excludeTypes.includes(snapPoint.type)) continue;

      const distance = Math.sqrt(
        Math.pow(x - snapPoint.x, 2) + 
        Math.pow(y - snapPoint.y, 2)
      );

      if (distance < minDistance) {
        minDistance = distance;
        nearestPoint = snapPoint;
      }
    }

    return nearestPoint;
  }

  /**
   * Generate alignment guides for room positioning
   */
  generateAlignmentGuides(rooms, movingRoom) {
    this.alignmentGuides.clear();

    const movingRoomPixelX = movingRoom.x + 20;
    const movingRoomPixelY = movingRoom.y + 20;
    const movingRoomPixelWidth = movingRoom.layout_width * 20;
    const movingRoomPixelHeight = movingRoom.layout_height * 20;

    for (const room of rooms) {
      if (room.id === movingRoom.id) continue;

      const roomPixelX = room.x + 20;
      const roomPixelY = room.y + 20;
      const roomPixelWidth = room.layout_width * 20;
      const roomPixelHeight = room.layout_height * 20;

      // Horizontal alignment guides
      const horizontalAlignments = [
        { y: roomPixelY, type: 'top' }, // Top edge
        { y: roomPixelY + roomPixelHeight / 2, type: 'center_h' }, // Horizontal center
        { y: roomPixelY + roomPixelHeight, type: 'bottom' } // Bottom edge
      ];

      for (const align of horizontalAlignments) {
        if (Math.abs(movingRoomPixelY - align.y) < this.ALIGNMENT_TOLERANCE ||
            Math.abs(movingRoomPixelY + movingRoomPixelHeight / 2 - align.y) < this.ALIGNMENT_TOLERANCE ||
            Math.abs(movingRoomPixelY + movingRoomPixelHeight - align.y) < this.ALIGNMENT_TOLERANCE) {
          
          this.alignmentGuides.add({
            type: 'horizontal',
            y: align.y,
            x1: Math.min(roomPixelX, movingRoomPixelX) - 50,
            x2: Math.max(roomPixelX + roomPixelWidth, movingRoomPixelX + movingRoomPixelWidth) + 50,
            alignmentType: align.type,
            referenceRoom: room.id
          });
        }
      }

      // Vertical alignment guides
      const verticalAlignments = [
        { x: roomPixelX, type: 'left' }, // Left edge
        { x: roomPixelX + roomPixelWidth / 2, type: 'center_v' }, // Vertical center
        { x: roomPixelX + roomPixelWidth, type: 'right' } // Right edge
      ];

      for (const align of verticalAlignments) {
        if (Math.abs(movingRoomPixelX - align.x) < this.ALIGNMENT_TOLERANCE ||
            Math.abs(movingRoomPixelX + movingRoomPixelWidth / 2 - align.x) < this.ALIGNMENT_TOLERANCE ||
            Math.abs(movingRoomPixelX + movingRoomPixelWidth - align.x) < this.ALIGNMENT_TOLERANCE) {
          
          this.alignmentGuides.add({
            type: 'vertical',
            x: align.x,
            y1: Math.min(roomPixelY, movingRoomPixelY) - 50,
            y2: Math.max(roomPixelY + roomPixelHeight, movingRoomPixelY + movingRoomPixelHeight) + 50,
            alignmentType: align.type,
            referenceRoom: room.id
          });
        }
      }
    }
  }

  /**
   * Apply snap-to-grid constraint
   */
  applyGridSnap(x, y, gridSize = 20) {
    return {
      x: Math.round(x / gridSize) * gridSize,
      y: Math.round(y / gridSize) * gridSize
    };
  }

  /**
   * Check for adjacency constraints (rooms touching)
   */
  checkAdjacencyConstraints(rooms) {
    const violations = new Set();

    for (const constraint of this.constraints.values()) {
      if (constraint.type !== this.CONSTRAINT_TYPES.ADJACENCY || !constraint.parameters.enabled) continue;

      const elementIds = Array.from(constraint.elements);
      if (elementIds.length !== 2) continue;

      const room1 = rooms.find(r => r.id === elementIds[0]);
      const room2 = rooms.find(r => r.id === elementIds[1]);

      if (!room1 || !room2) continue;

      const areAdjacent = this.areRoomsAdjacent(room1, room2, constraint.parameters.tolerance || 1);
      
      if (!areAdjacent) {
        violations.add(constraint.id);
        constraint.metadata.violationCount++;
      }
    }

    return violations;
  }

  /**
   * Check if two rooms are adjacent (touching)
   */
  areRoomsAdjacent(room1, room2, tolerance = 1) {
    const r1 = {
      left: room1.x,
      right: room1.x + room1.layout_width,
      top: room1.y,
      bottom: room1.y + room1.layout_height
    };

    const r2 = {
      left: room2.x,
      right: room2.x + room2.layout_width,
      top: room2.y,
      bottom: room2.y + room2.layout_height
    };

    // Check for horizontal adjacency (rooms side by side)
    const horizontalAdjacent = (
      Math.abs(r1.right - r2.left) <= tolerance || 
      Math.abs(r2.right - r1.left) <= tolerance
    ) && (
      (r1.top < r2.bottom && r1.bottom > r2.top) // Vertical overlap
    );

    // Check for vertical adjacency (rooms above/below)
    const verticalAdjacent = (
      Math.abs(r1.bottom - r2.top) <= tolerance || 
      Math.abs(r2.bottom - r1.top) <= tolerance
    ) && (
      (r1.left < r2.right && r1.right > r2.left) // Horizontal overlap
    );

    return horizontalAdjacent || verticalAdjacent;
  }

  /**
   * Suggest room position based on constraints
   */
  suggestRoomPosition(room, targetRoom, relationship = 'adjacent') {
    const suggestions = [];

    switch (relationship) {
      case 'adjacent_right':
        suggestions.push({
          x: targetRoom.x + targetRoom.layout_width,
          y: targetRoom.y,
          description: 'Adjacent to the right'
        });
        break;

      case 'adjacent_left':
        suggestions.push({
          x: targetRoom.x - room.layout_width,
          y: targetRoom.y,
          description: 'Adjacent to the left'
        });
        break;

      case 'adjacent_below':
        suggestions.push({
          x: targetRoom.x,
          y: targetRoom.y + targetRoom.layout_height,
          description: 'Adjacent below'
        });
        break;

      case 'adjacent_above':
        suggestions.push({
          x: targetRoom.x,
          y: targetRoom.y - room.layout_height,
          description: 'Adjacent above'
        });
        break;

      case 'aligned_horizontal':
        suggestions.push({
          x: room.x, // Keep current X
          y: targetRoom.y, // Align Y with target
          description: 'Horizontally aligned'
        });
        break;

      case 'aligned_vertical':
        suggestions.push({
          x: targetRoom.x, // Align X with target
          y: room.y, // Keep current Y
          description: 'Vertically aligned'
        });
        break;
    }

    return suggestions;
  }

  /**
   * Validate room placement against constraints
   */
  validateRoomPlacement(room, rooms, constraints = []) {
    const violations = [];

    // Check minimum distance constraints
    for (const otherRoom of rooms) {
      if (otherRoom.id === room.id) continue;

      const distance = this.calculateRoomDistance(room, otherRoom);
      
      // Check if rooms overlap (distance < 0)
      if (distance < 0) {
        violations.push({
          type: 'overlap',
          severity: 'error',
          message: `Room overlaps with ${otherRoom.name}`,
          roomId: otherRoom.id
        });
      }
    }

    // Check plot boundary constraints
    const plotViolation = this.checkPlotBoundaryViolation(room);
    if (plotViolation) {
      violations.push(plotViolation);
    }

    return violations;
  }

  /**
   * Calculate distance between two rooms (negative if overlapping)
   */
  calculateRoomDistance(room1, room2) {
    const r1 = {
      left: room1.x,
      right: room1.x + room1.layout_width,
      top: room1.y,
      bottom: room1.y + room1.layout_height
    };

    const r2 = {
      left: room2.x,
      right: room2.x + room2.layout_width,
      top: room2.y,
      bottom: room2.y + room2.layout_height
    };

    // Check for overlap
    if (r1.left < r2.right && r1.right > r2.left && 
        r1.top < r2.bottom && r1.bottom > r2.top) {
      // Rooms overlap - calculate overlap amount (negative distance)
      const overlapX = Math.min(r1.right, r2.right) - Math.max(r1.left, r2.left);
      const overlapY = Math.min(r1.bottom, r2.bottom) - Math.max(r1.top, r2.top);
      return -Math.min(overlapX, overlapY);
    }

    // Calculate minimum distance between rooms
    const dx = Math.max(0, Math.max(r1.left - r2.right, r2.left - r1.right));
    const dy = Math.max(0, Math.max(r1.top - r2.bottom, r2.top - r1.bottom));
    
    return Math.sqrt(dx * dx + dy * dy);
  }

  /**
   * Check if room violates plot boundaries
   */
  checkPlotBoundaryViolation(room, plotWidth = 100, plotHeight = 100) {
    if (room.x < 0 || room.y < 0 || 
        room.x + room.layout_width > plotWidth || 
        room.y + room.layout_height > plotHeight) {
      
      return {
        type: 'plot_boundary',
        severity: 'error',
        message: 'Room extends outside plot boundaries'
      };
    }
    return null;
  }

  /**
   * Auto-arrange rooms with constraints
   */
  autoArrangeRooms(rooms, arrangement = 'grid') {
    const arrangedRooms = [...rooms];

    switch (arrangement) {
      case 'grid':
        return this.arrangeRoomsInGrid(arrangedRooms);
      
      case 'linear':
        return this.arrangeRoomsLinear(arrangedRooms);
      
      case 'clustered':
        return this.arrangeRoomsClustered(arrangedRooms);
      
      default:
        return arrangedRooms;
    }
  }

  /**
   * Arrange rooms in a grid pattern
   */
  arrangeRoomsInGrid(rooms) {
    const cols = Math.ceil(Math.sqrt(rooms.length));
    let currentX = 10;
    let currentY = 10;
    let maxHeightInRow = 0;
    let roomsInCurrentRow = 0;

    for (const room of rooms) {
      room.x = currentX;
      room.y = currentY;

      maxHeightInRow = Math.max(maxHeightInRow, room.layout_height);
      currentX += room.layout_width + 5; // 5 feet spacing
      roomsInCurrentRow++;

      if (roomsInCurrentRow >= cols) {
        currentX = 10;
        currentY += maxHeightInRow + 5;
        maxHeightInRow = 0;
        roomsInCurrentRow = 0;
      }
    }

    return rooms;
  }

  /**
   * Arrange rooms in a linear pattern
   */
  arrangeRoomsLinear(rooms) {
    let currentX = 10;
    const y = 10;

    for (const room of rooms) {
      room.x = currentX;
      room.y = y;
      currentX += room.layout_width + 5; // 5 feet spacing
    }

    return rooms;
  }

  /**
   * Arrange rooms in clusters by type
   */
  arrangeRoomsClustered(rooms) {
    const clusters = this.groupRoomsByType(rooms);
    let clusterY = 10;

    for (const [type, clusterRooms] of Object.entries(clusters)) {
      let clusterX = 10;
      let maxHeightInCluster = 0;

      for (const room of clusterRooms) {
        room.x = clusterX;
        room.y = clusterY;
        clusterX += room.layout_width + 3; // 3 feet spacing within cluster
        maxHeightInCluster = Math.max(maxHeightInCluster, room.layout_height);
      }

      clusterY += maxHeightInCluster + 8; // 8 feet spacing between clusters
    }

    return rooms;
  }

  /**
   * Group rooms by type for clustering
   */
  groupRoomsByType(rooms) {
    const clusters = {};

    for (const room of rooms) {
      const type = this.getRoomTypeCategory(room.type || room.name);
      if (!clusters[type]) {
        clusters[type] = [];
      }
      clusters[type].push(room);
    }

    return clusters;
  }

  /**
   * Get room type category for clustering
   */
  getRoomTypeCategory(roomType) {
    const typeMap = {
      'bedroom': 'sleeping',
      'master_bedroom': 'sleeping',
      'guest_bedroom': 'sleeping',
      'bathroom': 'utility',
      'master_bathroom': 'utility',
      'kitchen': 'service',
      'living_room': 'living',
      'dining_room': 'living',
      'study_room': 'work'
    };

    return typeMap[roomType] || 'other';
  }

  /**
   * Get active alignment guides for rendering
   */
  getAlignmentGuides() {
    return Array.from(this.alignmentGuides);
  }

  /**
   * Get snap points for rendering
   */
  getSnapPoints() {
    return Array.from(this.snapPoints.values()).filter(point => point.active);
  }

  /**
   * Clear temporary guides and points
   */
  clearTemporaryGuides() {
    this.alignmentGuides.clear();
  }

  /**
   * Export constraints data
   */
  exportData() {
    return {
      constraints: Object.fromEntries(
        Array.from(this.constraints.entries()).map(([id, constraint]) => [
          id, 
          {
            ...constraint,
            elements: Array.from(constraint.elements)
          }
        ])
      ),
      roomConstraints: Object.fromEntries(
        Array.from(this.roomConstraints.entries()).map(([k, v]) => [k, Array.from(v)])
      ),
      metadata: {
        version: '1.0',
        lastUpdated: Date.now()
      }
    };
  }

  /**
   * Import constraints data
   */
  importData(data) {
    if (!data) return;

    if (data.constraints) {
      this.constraints.clear();
      for (const [id, constraint] of Object.entries(data.constraints)) {
        constraint.elements = new Set(constraint.elements || []);
        this.constraints.set(id, constraint);
      }
    }

    if (data.roomConstraints) {
      this.roomConstraints.clear();
      for (const [roomId, constraintIds] of Object.entries(data.roomConstraints)) {
        this.roomConstraints.set(roomId, new Set(constraintIds));
      }
    }
  }
}

export default GeometryConstraints;