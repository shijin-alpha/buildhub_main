import React from 'react';

/**
 * Wall System Component - Manages shared walls between rooms
 * This is an architectural enhancement that adds wall entities as first-class objects
 * while maintaining backward compatibility with existing room-based system
 */

export class WallSystem {
  constructor() {
    this.walls = new Map(); // Wall ID -> Wall object
    this.roomWalls = new Map(); // Room ID -> Set of Wall IDs
    this.wallConnections = new Map(); // Wall ID -> Set of connected Wall IDs
    this.openings = new Map(); // Opening ID -> Opening object
    this.wallOpenings = new Map(); // Wall ID -> Set of Opening IDs
  }

  /**
   * Create a wall entity from room boundaries
   * Walls are derived from room edges and can be shared between adjacent rooms
   */
  createWallFromRoomEdge(roomId, edge, roomData) {
    const wallId = `wall_${roomId}_${edge}`;
    
    // Calculate wall coordinates based on room position and edge
    const { x, y, layout_width, layout_height, rotation = 0 } = roomData;
    const wallThickness = roomData.wall_thickness || 0.5;
    
    let wallGeometry;
    
    switch (edge) {
      case 'top':
        wallGeometry = {
          x1: x,
          y1: y - wallThickness / 2,
          x2: x + layout_width,
          y2: y - wallThickness / 2,
          thickness: wallThickness,
          length: layout_width
        };
        break;
      case 'right':
        wallGeometry = {
          x1: x + layout_width + wallThickness / 2,
          y1: y,
          x2: x + layout_width + wallThickness / 2,
          y2: y + layout_height,
          thickness: wallThickness,
          length: layout_height
        };
        break;
      case 'bottom':
        wallGeometry = {
          x1: x,
          y1: y + layout_height + wallThickness / 2,
          x2: x + layout_width,
          y2: y + layout_height + wallThickness / 2,
          thickness: wallThickness,
          length: layout_width
        };
        break;
      case 'left':
        wallGeometry = {
          x1: x - wallThickness / 2,
          y1: y,
          x2: x - wallThickness / 2,
          y2: y + layout_height,
          thickness: wallThickness,
          length: layout_height
        };
        break;
      default:
        return null;
    }

    // Apply rotation if room is rotated
    if (rotation !== 0) {
      const centerX = x + layout_width / 2;
      const centerY = y + layout_height / 2;
      const rotatedGeometry = this.rotateWallGeometry(wallGeometry, centerX, centerY, rotation);
      wallGeometry = { ...wallGeometry, ...rotatedGeometry };
    }

    const wall = {
      id: wallId,
      roomIds: new Set([roomId]),
      edge,
      geometry: wallGeometry,
      material: roomData.wall_material || 'brick',
      thickness: wallThickness,
      height: roomData.ceiling_height || 9,
      structural: false, // Can be upgraded to structural wall
      shared: false, // Will be true if shared between rooms
      openings: new Set(),
      constraints: {
        loadBearing: false,
        fireRated: false,
        soundProof: false
      },
      metadata: {
        createdFrom: 'room_edge',
        parentRoom: roomId,
        lastUpdated: Date.now()
      }
    };

    this.walls.set(wallId, wall);
    
    // Update room-wall mapping
    if (!this.roomWalls.has(roomId)) {
      this.roomWalls.set(roomId, new Set());
    }
    this.roomWalls.get(roomId).add(wallId);

    return wall;
  }

  /**
   * Detect and merge walls that should be shared between adjacent rooms
   */
  detectSharedWalls(tolerance = 1.0) {
    const wallsArray = Array.from(this.walls.values());
    const sharedWalls = [];

    for (let i = 0; i < wallsArray.length; i++) {
      for (let j = i + 1; j < wallsArray.length; j++) {
        const wall1 = wallsArray[i];
        const wall2 = wallsArray[j];

        // Skip if walls are from the same room
        if (wall1.roomIds.has(Array.from(wall2.roomIds)[0])) continue;

        // Check if walls are collinear and overlapping
        if (this.areWallsCollinearAndOverlapping(wall1.geometry, wall2.geometry, tolerance)) {
          // Merge walls
          const mergedWall = this.mergeWalls(wall1, wall2);
          if (mergedWall) {
            sharedWalls.push(mergedWall);
          }
        }
      }
    }

    return sharedWalls;
  }

  /**
   * Check if two walls are collinear and overlapping (can be shared)
   */
  areWallsCollinearAndOverlapping(geom1, geom2, tolerance) {
    // Calculate wall directions
    const dir1 = this.getWallDirection(geom1);
    const dir2 = this.getWallDirection(geom2);

    // Check if walls are parallel (same or opposite direction)
    const dotProduct = dir1.x * dir2.x + dir1.y * dir2.y;
    if (Math.abs(Math.abs(dotProduct) - 1) > 0.1) return false; // Not parallel

    // Check if walls are on the same line
    const distance = this.distanceFromPointToLine(
      { x: geom2.x1, y: geom2.y1 },
      { x: geom1.x1, y: geom1.y1 },
      { x: geom1.x2, y: geom1.y2 }
    );

    if (distance > tolerance) return false;

    // Check for overlap
    const overlap = this.calculateWallOverlap(geom1, geom2);
    return overlap > 0;
  }

  /**
   * Merge two walls into a shared wall
   */
  mergeWalls(wall1, wall2) {
    const mergedId = `shared_${wall1.id}_${wall2.id}`;
    
    // Calculate merged geometry
    const mergedGeometry = this.calculateMergedGeometry(wall1.geometry, wall2.geometry);
    
    const mergedWall = {
      id: mergedId,
      roomIds: new Set([...wall1.roomIds, ...wall2.roomIds]),
      edge: 'shared',
      geometry: mergedGeometry,
      material: wall1.material, // Use first wall's material, can be customized
      thickness: Math.max(wall1.thickness, wall2.thickness),
      height: Math.max(wall1.height, wall2.height),
      structural: wall1.structural || wall2.structural,
      shared: true,
      openings: new Set([...wall1.openings, ...wall2.openings]),
      constraints: {
        loadBearing: wall1.constraints.loadBearing || wall2.constraints.loadBearing,
        fireRated: wall1.constraints.fireRated || wall2.constraints.fireRated,
        soundProof: wall1.constraints.soundProof || wall2.constraints.soundProof
      },
      metadata: {
        createdFrom: 'wall_merge',
        originalWalls: [wall1.id, wall2.id],
        lastUpdated: Date.now()
      }
    };

    // Update mappings
    this.walls.set(mergedId, mergedWall);
    
    // Update room-wall mappings
    for (const roomId of mergedWall.roomIds) {
      if (!this.roomWalls.has(roomId)) {
        this.roomWalls.set(roomId, new Set());
      }
      this.roomWalls.get(roomId).add(mergedId);
      
      // Remove old wall references
      this.roomWalls.get(roomId).delete(wall1.id);
      this.roomWalls.get(roomId).delete(wall2.id);
    }

    // Remove old walls
    this.walls.delete(wall1.id);
    this.walls.delete(wall2.id);

    return mergedWall;
  }

  /**
   * Add an opening (door/window) to a wall
   */
  addOpening(wallId, openingData) {
    const wall = this.walls.get(wallId);
    if (!wall) return null;

    const openingId = `opening_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    
    const opening = {
      id: openingId,
      wallId,
      type: openingData.type || 'door', // door, window, arch, etc.
      position: openingData.position || 0.5, // Position along wall (0-1)
      width: openingData.width || 3, // Opening width in feet
      height: openingData.height || 7, // Opening height in feet
      sillHeight: openingData.sillHeight || 0, // Height from floor (for windows)
      swing: openingData.swing || 'inward', // door swing direction
      material: openingData.material || 'wood',
      hardware: openingData.hardware || 'standard',
      constraints: {
        structural: false, // Cannot be placed in structural walls without engineering
        minDistance: 1.0, // Minimum distance from wall ends
        maxWidth: wall.geometry.length * 0.8 // Maximum 80% of wall length
      },
      metadata: {
        createdAt: Date.now(),
        lastUpdated: Date.now()
      }
    };

    // Validate opening placement
    if (!this.validateOpeningPlacement(wall, opening)) {
      return null;
    }

    this.openings.set(openingId, opening);
    
    // Update wall-opening mapping
    if (!this.wallOpenings.has(wallId)) {
      this.wallOpenings.set(wallId, new Set());
    }
    this.wallOpenings.get(wallId).add(openingId);
    wall.openings.add(openingId);

    return opening;
  }

  /**
   * Validate that an opening can be placed on a wall
   */
  validateOpeningPlacement(wall, opening) {
    // Check if opening fits within wall length
    const wallLength = wall.geometry.length;
    const openingStart = opening.position * wallLength - opening.width / 2;
    const openingEnd = opening.position * wallLength + opening.width / 2;

    if (openingStart < opening.constraints.minDistance || 
        openingEnd > wallLength - opening.constraints.minDistance) {
      return false;
    }

    // Check for conflicts with existing openings
    const existingOpenings = Array.from(wall.openings).map(id => this.openings.get(id));
    for (const existing of existingOpenings) {
      const existingStart = existing.position * wallLength - existing.width / 2;
      const existingEnd = existing.position * wallLength + existing.width / 2;
      
      // Check for overlap
      if (!(openingEnd < existingStart || openingStart > existingEnd)) {
        return false; // Overlap detected
      }
    }

    // Check structural constraints
    if (wall.constraints.loadBearing && opening.width > 4) {
      return false; // Large openings in load-bearing walls need engineering
    }

    return true;
  }

  /**
   * Update walls when a room is moved, resized, or rotated
   */
  updateWallsForRoom(roomId, newRoomData) {
    const roomWallIds = this.roomWalls.get(roomId);
    if (!roomWallIds) return;

    // Remove old walls for this room
    for (const wallId of roomWallIds) {
      const wall = this.walls.get(wallId);
      if (wall && !wall.shared) {
        this.walls.delete(wallId);
      } else if (wall && wall.shared) {
        // Handle shared wall updates
        wall.roomIds.delete(roomId);
        if (wall.roomIds.size === 0) {
          this.walls.delete(wallId);
        }
      }
    }

    // Clear room-wall mapping
    this.roomWalls.delete(roomId);

    // Create new walls for the updated room
    const edges = ['top', 'right', 'bottom', 'left'];
    for (const edge of edges) {
      this.createWallFromRoomEdge(roomId, edge, newRoomData);
    }

    // Re-detect shared walls
    this.detectSharedWalls();
  }

  /**
   * Get all walls for rendering
   */
  getWallsForRendering() {
    return Array.from(this.walls.values()).map(wall => ({
      ...wall,
      openings: Array.from(wall.openings).map(id => this.openings.get(id))
    }));
  }

  /**
   * Get openings for a specific wall
   */
  getWallOpenings(wallId) {
    const openingIds = this.wallOpenings.get(wallId) || new Set();
    return Array.from(openingIds).map(id => this.openings.get(id));
  }

  /**
   * Export wall system data for saving
   */
  exportData() {
    return {
      walls: Object.fromEntries(this.walls),
      roomWalls: Object.fromEntries(
        Array.from(this.roomWalls.entries()).map(([k, v]) => [k, Array.from(v)])
      ),
      openings: Object.fromEntries(this.openings),
      wallOpenings: Object.fromEntries(
        Array.from(this.wallOpenings.entries()).map(([k, v]) => [k, Array.from(v)])
      ),
      metadata: {
        version: '1.0',
        lastUpdated: Date.now()
      }
    };
  }

  /**
   * Import wall system data from saved plan
   */
  importData(data) {
    if (!data) return;

    // Import walls
    if (data.walls) {
      this.walls.clear();
      for (const [id, wall] of Object.entries(data.walls)) {
        // Convert sets back from arrays
        wall.roomIds = new Set(wall.roomIds || []);
        wall.openings = new Set(wall.openings || []);
        this.walls.set(id, wall);
      }
    }

    // Import room-wall mappings
    if (data.roomWalls) {
      this.roomWalls.clear();
      for (const [roomId, wallIds] of Object.entries(data.roomWalls)) {
        this.roomWalls.set(roomId, new Set(wallIds));
      }
    }

    // Import openings
    if (data.openings) {
      this.openings.clear();
      for (const [id, opening] of Object.entries(data.openings)) {
        this.openings.set(id, opening);
      }
    }

    // Import wall-opening mappings
    if (data.wallOpenings) {
      this.wallOpenings.clear();
      for (const [wallId, openingIds] of Object.entries(data.wallOpenings)) {
        this.wallOpenings.set(wallId, new Set(openingIds));
      }
    }
  }

  // Helper methods
  rotateWallGeometry(geometry, centerX, centerY, rotation) {
    const radians = (rotation * Math.PI) / 180;
    const cos = Math.cos(radians);
    const sin = Math.sin(radians);

    const rotatePoint = (x, y) => ({
      x: centerX + (x - centerX) * cos - (y - centerY) * sin,
      y: centerY + (x - centerX) * sin + (y - centerY) * cos
    });

    const p1 = rotatePoint(geometry.x1, geometry.y1);
    const p2 = rotatePoint(geometry.x2, geometry.y2);

    return {
      x1: p1.x,
      y1: p1.y,
      x2: p2.x,
      y2: p2.y
    };
  }

  getWallDirection(geometry) {
    const length = Math.sqrt(
      Math.pow(geometry.x2 - geometry.x1, 2) + 
      Math.pow(geometry.y2 - geometry.y1, 2)
    );
    return {
      x: (geometry.x2 - geometry.x1) / length,
      y: (geometry.y2 - geometry.y1) / length
    };
  }

  distanceFromPointToLine(point, lineStart, lineEnd) {
    const A = point.x - lineStart.x;
    const B = point.y - lineStart.y;
    const C = lineEnd.x - lineStart.x;
    const D = lineEnd.y - lineStart.y;

    const dot = A * C + B * D;
    const lenSq = C * C + D * D;
    
    if (lenSq === 0) return Math.sqrt(A * A + B * B);
    
    const param = dot / lenSq;
    let xx, yy;

    if (param < 0) {
      xx = lineStart.x;
      yy = lineStart.y;
    } else if (param > 1) {
      xx = lineEnd.x;
      yy = lineEnd.y;
    } else {
      xx = lineStart.x + param * C;
      yy = lineStart.y + param * D;
    }

    const dx = point.x - xx;
    const dy = point.y - yy;
    return Math.sqrt(dx * dx + dy * dy);
  }

  calculateWallOverlap(geom1, geom2) {
    // Simplified overlap calculation for collinear walls
    const start1 = Math.min(geom1.x1, geom1.x2);
    const end1 = Math.max(geom1.x1, geom1.x2);
    const start2 = Math.min(geom2.x1, geom2.x2);
    const end2 = Math.max(geom2.x1, geom2.x2);

    const overlapStart = Math.max(start1, start2);
    const overlapEnd = Math.min(end1, end2);

    return Math.max(0, overlapEnd - overlapStart);
  }

  calculateMergedGeometry(geom1, geom2) {
    // Calculate the union of two collinear wall segments
    const allX = [geom1.x1, geom1.x2, geom2.x1, geom2.x2];
    const allY = [geom1.y1, geom1.y2, geom2.y1, geom2.y2];

    return {
      x1: Math.min(...allX),
      y1: Math.min(...allY),
      x2: Math.max(...allX),
      y2: Math.max(...allY),
      thickness: Math.max(geom1.thickness, geom2.thickness),
      length: Math.sqrt(
        Math.pow(Math.max(...allX) - Math.min(...allX), 2) +
        Math.pow(Math.max(...allY) - Math.min(...allY), 2)
      )
    };
  }
}

export default WallSystem;