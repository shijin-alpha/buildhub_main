import React from 'react';

/**
 * Enhanced Canvas Renderer - Renders architectural elements with walls, openings, and constraints
 * This renderer extends the existing canvas drawing system to include architectural-grade
 * elements while maintaining full compatibility with existing room rendering
 */

export class EnhancedCanvasRenderer {
  constructor(canvas, pixelsPerFoot = 20) {
    this.canvas = canvas;
    this.PIXELS_PER_FOOT = pixelsPerFoot;
    this.GRID_SIZE = 20;
    
    // Rendering layers (drawn in order)
    this.LAYERS = {
      GRID: 0,
      PLOT_BOUNDARY: 1,
      WALLS: 2,
      ROOMS: 3,
      OPENINGS: 4,
      MEASUREMENTS: 5,
      CONSTRAINTS: 6,
      ANNOTATIONS: 7,
      SELECTION: 8
    };

    // Style configurations
    this.styles = {
      walls: {
        shared: {
          color: '#8b5cf6',
          width: 3,
          opacity: 0.8
        },
        individual: {
          color: '#6b7280',
          width: 2,
          opacity: 0.6
        },
        structural: {
          color: '#dc2626',
          width: 4,
          opacity: 0.9
        }
      },
      openings: {
        door: {
          color: '#059669',
          width: 2,
          fillColor: 'rgba(5, 150, 105, 0.2)'
        },
        window: {
          color: '#0ea5e9',
          width: 2,
          fillColor: 'rgba(14, 165, 233, 0.2)'
        },
        arch: {
          color: '#f59e0b',
          width: 2,
          fillColor: 'rgba(245, 158, 11, 0.2)'
        }
      },
      constraints: {
        alignment: {
          color: '#10b981',
          width: 1,
          dash: [5, 5],
          opacity: 0.7
        },
        snap: {
          color: '#f59e0b',
          size: 6,
          opacity: 0.8
        }
      },
      measurements: {
        dimension: {
          color: '#2563eb',
          width: 1,
          fontSize: 10,
          offset: 15
        },
        annotation: {
          backgroundColor: 'rgba(255, 255, 255, 0.9)',
          borderColor: '#d1d5db',
          fontSize: 12,
          padding: 4
        }
      }
    };
  }

  /**
   * Render all architectural elements
   */
  renderArchitecturalElements(renderData) {
    const {
      rooms = [],
      walls = [],
      openings = [],
      measurements = [],
      constraints = {},
      selectedRoom = null,
      showMeasurements = true,
      showConstraints = true,
      plotWidth = 100,
      plotHeight = 100
    } = renderData;

    // Clear canvas
    this.clearCanvas();

    // Render in layer order
    this.renderGrid();
    this.renderPlotBoundary(plotWidth, plotHeight);
    
    if (walls.length > 0) {
      this.renderWalls(walls);
    }
    
    this.renderRooms(rooms, selectedRoom);
    
    if (openings.length > 0) {
      this.renderOpenings(openings, walls);
    }
    
    if (showMeasurements && measurements.length > 0) {
      this.renderMeasurements(measurements);
    }
    
    if (showConstraints) {
      this.renderConstraints(constraints);
    }
  }

  /**
   * Clear the canvas
   */
  clearCanvas() {
    const canvasElement = this.canvas.canvas;
    this.canvas.clearRect(0, 0, canvasElement.width, canvasElement.height);
  }

  /**
   * Render grid (existing functionality)
   */
  renderGrid() {
    this.canvas.strokeStyle = '#e8e8e8';
    this.canvas.lineWidth = 0.5;

    const canvasElement = this.canvas.canvas;
    
    // Vertical lines
    for (let x = 0; x <= canvasElement.width; x += this.GRID_SIZE) {
      this.canvas.beginPath();
      this.canvas.moveTo(x, 0);
      this.canvas.lineTo(x, canvasElement.height);
      this.canvas.stroke();
    }

    // Horizontal lines
    for (let y = 0; y <= canvasElement.height; y += this.GRID_SIZE) {
      this.canvas.beginPath();
      this.canvas.moveTo(0, y);
      this.canvas.lineTo(canvasElement.width, y);
      this.canvas.stroke();
    }

    // Major grid lines every 5 feet
    this.canvas.strokeStyle = '#d0d0d0';
    this.canvas.lineWidth = 1;
    
    for (let x = 0; x <= canvasElement.width; x += this.GRID_SIZE * 5) {
      this.canvas.beginPath();
      this.canvas.moveTo(x, 0);
      this.canvas.lineTo(x, canvasElement.height);
      this.canvas.stroke();
    }

    for (let y = 0; y <= canvasElement.height; y += this.GRID_SIZE * 5) {
      this.canvas.beginPath();
      this.canvas.moveTo(0, y);
      this.canvas.lineTo(canvasElement.width, y);
      this.canvas.stroke();
    }
  }

  /**
   * Render plot boundary (existing functionality)
   */
  renderPlotBoundary(plotWidth, plotHeight) {
    this.canvas.strokeStyle = '#2c3e50';
    this.canvas.lineWidth = 3;
    this.canvas.setLineDash([]);

    const plotPixelWidth = plotWidth * this.PIXELS_PER_FOOT;
    const plotPixelHeight = plotHeight * this.PIXELS_PER_FOOT;

    this.canvas.strokeRect(20, 20, plotPixelWidth, plotPixelHeight);

    // Add plot dimensions
    this.canvas.fillStyle = '#2c3e50';
    this.canvas.font = 'bold 14px Arial';
    this.canvas.textAlign = 'center';
    
    // Top dimension
    this.canvas.fillText(`${plotWidth}'`, 20 + plotPixelWidth / 2, 15);
    
    // Left dimension
    this.canvas.save();
    this.canvas.translate(10, 20 + plotPixelHeight / 2);
    this.canvas.rotate(-Math.PI / 2);
    this.canvas.fillText(`${plotHeight}'`, 0, 0);
    this.canvas.restore();
  }

  /**
   * Render walls with enhanced visualization
   */
  renderWalls(walls) {
    for (const wall of walls) {
      this.renderWall(wall);
    }
  }

  /**
   * Render a single wall
   */
  renderWall(wall) {
    const { geometry, shared, structural, material } = wall;
    
    // Choose style based on wall properties
    let style;
    if (structural) {
      style = this.styles.walls.structural;
    } else if (shared) {
      style = this.styles.walls.shared;
    } else {
      style = this.styles.walls.individual;
    }

    // Convert geometry to pixel coordinates
    const x1 = (geometry.x1 * this.PIXELS_PER_FOOT) + 20;
    const y1 = (geometry.y1 * this.PIXELS_PER_FOOT) + 20;
    const x2 = (geometry.x2 * this.PIXELS_PER_FOOT) + 20;
    const y2 = (geometry.y2 * this.PIXELS_PER_FOOT) + 20;
    const thickness = geometry.thickness * this.PIXELS_PER_FOOT;

    // Draw wall as thick line
    this.canvas.save();
    this.canvas.globalAlpha = style.opacity;
    this.canvas.strokeStyle = style.color;
    this.canvas.lineWidth = Math.max(thickness, style.width);
    this.canvas.lineCap = 'round';

    this.canvas.beginPath();
    this.canvas.moveTo(x1, y1);
    this.canvas.lineTo(x2, y2);
    this.canvas.stroke();

    // Add wall material indicator for structural walls
    if (structural) {
      this.renderWallMaterialIndicator(x1, y1, x2, y2, material);
    }

    // Add shared wall indicator
    if (shared) {
      this.renderSharedWallIndicator(x1, y1, x2, y2);
    }

    this.canvas.restore();
  }

  /**
   * Render wall material indicator
   */
  renderWallMaterialIndicator(x1, y1, x2, y2, material) {
    const centerX = (x1 + x2) / 2;
    const centerY = (y1 + y2) / 2;

    // Material symbols
    const materialSymbols = {
      'brick': '🧱',
      'concrete': '⬜',
      'wood': '🪵',
      'steel': '🔩'
    };

    const symbol = materialSymbols[material] || '■';

    this.canvas.font = '12px Arial';
    this.canvas.fillStyle = '#ffffff';
    this.canvas.textAlign = 'center';
    
    // Background circle
    this.canvas.beginPath();
    this.canvas.arc(centerX, centerY, 8, 0, 2 * Math.PI);
    this.canvas.fill();
    
    // Symbol
    this.canvas.fillStyle = '#374151';
    this.canvas.fillText(symbol, centerX, centerY + 4);
  }

  /**
   * Render shared wall indicator
   */
  renderSharedWallIndicator(x1, y1, x2, y2) {
    const centerX = (x1 + x2) / 2;
    const centerY = (y1 + y2) / 2;

    // Draw connection symbol
    this.canvas.fillStyle = '#8b5cf6';
    this.canvas.beginPath();
    this.canvas.arc(centerX, centerY, 4, 0, 2 * Math.PI);
    this.canvas.fill();

    // Draw connection lines
    this.canvas.strokeStyle = '#8b5cf6';
    this.canvas.lineWidth = 1;
    this.canvas.setLineDash([2, 2]);
    
    this.canvas.beginPath();
    this.canvas.moveTo(centerX - 6, centerY - 6);
    this.canvas.lineTo(centerX + 6, centerY + 6);
    this.canvas.moveTo(centerX + 6, centerY - 6);
    this.canvas.lineTo(centerX - 6, centerY + 6);
    this.canvas.stroke();
    
    this.canvas.setLineDash([]);
  }

  /**
   * Render rooms (enhanced version of existing functionality)
   */
  renderRooms(rooms, selectedRoomIndex) {
    for (let i = 0; i < rooms.length; i++) {
      const room = rooms[i];
      const isSelected = i === selectedRoomIndex;
      this.renderRoom(room, isSelected);
    }
  }

  /**
   * Render a single room (enhanced version)
   */
  renderRoom(room, isSelected) {
    // Validate room properties
    if (!room || typeof room.x !== 'number' || typeof room.y !== 'number' ||
        !isFinite(room.layout_width) || !isFinite(room.layout_height) ||
        room.layout_width <= 0 || room.layout_height <= 0) {
      return;
    }

    const x = room.x + 20;
    const y = room.y + 20;
    const width = room.layout_width * this.PIXELS_PER_FOOT;
    const height = room.layout_height * this.PIXELS_PER_FOOT;
    const rotation = room.rotation || 0;

    const centerX = x + width / 2;
    const centerY = y + height / 2;

    // Save canvas state
    this.canvas.save();

    // Apply rotation if needed
    if (rotation !== 0) {
      this.canvas.translate(centerX, centerY);
      this.canvas.rotate((rotation * Math.PI) / 180);
      this.canvas.translate(-centerX, -centerY);
    }

    // Room background with enhanced gradient
    let roomColor = room.color || '#e3f2fd';
    const gradient = this.canvas.createLinearGradient(x, y, x + width, y + height);
    gradient.addColorStop(0, roomColor);
    gradient.addColorStop(1, this.adjustColor(roomColor, -10));
    
    this.canvas.fillStyle = gradient;
    this.canvas.fillRect(x, y, width, height);

    // Enhanced room border
    this.canvas.strokeStyle = isSelected ? '#1976d2' : '#666';
    this.canvas.lineWidth = isSelected ? 3 : 1;
    this.canvas.setLineDash([]);
    this.canvas.strokeRect(x, y, width, height);

    // Room content
    this.renderRoomContent(room, x, y, width, height, centerX, centerY, isSelected);

    // Restore canvas state
    this.canvas.restore();

    // Render selection handles (after restore to avoid rotation)
    if (isSelected) {
      this.renderSelectionHandles(x, y, width, height, rotation);
    }
  }

  /**
   * Render room content (labels, dimensions, etc.)
   */
  renderRoomContent(room, x, y, width, height, centerX, centerY, isSelected) {
    this.canvas.fillStyle = '#333';
    this.canvas.font = 'bold 12px Arial';
    this.canvas.textAlign = 'center';
    
    // Room name
    this.canvas.fillText(room.name, centerX, centerY - 15);
    
    // Room type indicator
    if (room.type && room.type !== room.name.toLowerCase().replace(/\s+/g, '_')) {
      this.canvas.font = '10px Arial';
      this.canvas.fillStyle = '#666';
      this.canvas.fillText(`(${room.type.replace(/_/g, ' ')})`, centerX, centerY - 5);
    }
    
    // Dimensions
    this.canvas.font = '10px Arial';
    this.canvas.fillStyle = '#666';
    this.canvas.fillText(`${room.layout_width}' × ${room.layout_height}'`, centerX, centerY + 5);
    
    // Area
    const area = (room.layout_width * room.layout_height).toFixed(1);
    this.canvas.fillText(`${area} sq ft`, centerX, centerY + 15);

    // Construction specifications indicator
    if (room.wall_material || room.ceiling_height !== 9) {
      this.renderConstructionIndicator(centerX, centerY + 25, room);
    }
  }

  /**
   * Render construction specifications indicator
   */
  renderConstructionIndicator(x, y, room) {
    const specs = [];
    
    if (room.wall_material && room.wall_material !== 'brick') {
      specs.push(room.wall_material);
    }
    
    if (room.ceiling_height && room.ceiling_height !== 9) {
      specs.push(`${room.ceiling_height}' ceiling`);
    }

    if (specs.length > 0) {
      this.canvas.font = '8px Arial';
      this.canvas.fillStyle = '#059669';
      this.canvas.fillText(specs.join(', '), x, y);
    }
  }

  /**
   * Render selection handles
   */
  renderSelectionHandles(x, y, width, height, rotation) {
    this.canvas.fillStyle = '#1976d2';
    this.canvas.strokeStyle = '#fff';
    this.canvas.lineWidth = 2;

    const handleSize = 8;
    const handles = [
      { x: x + width - handleSize/2, y: y + height/2 - handleSize/2, type: 'right' },
      { x: x + width/2 - handleSize/2, y: y + height - handleSize/2, type: 'bottom' },
      { x: x + width - handleSize/2, y: y + height - handleSize/2, type: 'corner' }
    ];

    handles.forEach(handle => {
      this.canvas.fillRect(handle.x, handle.y, handleSize, handleSize);
      this.canvas.strokeRect(handle.x, handle.y, handleSize, handleSize);
    });

    // Rotation handle
    this.renderRotationHandle(x, y, width, height, rotation);
  }

  /**
   * Render rotation handle
   */
  renderRotationHandle(x, y, width, height, rotation) {
    const centerX = x + width / 2;
    const centerY = y + height / 2;
    const handleDistance = Math.max(width, height) / 2 + 30;
    
    const handleAngle = (rotation - 90) * Math.PI / 180;
    const handleX = centerX + Math.cos(handleAngle) * handleDistance;
    const handleY = centerY + Math.sin(handleAngle) * handleDistance;
    
    // Connection line
    this.canvas.strokeStyle = '#ff6b6b';
    this.canvas.lineWidth = 2;
    this.canvas.setLineDash([5, 5]);
    this.canvas.beginPath();
    this.canvas.moveTo(centerX, centerY);
    this.canvas.lineTo(handleX, handleY);
    this.canvas.stroke();
    this.canvas.setLineDash([]);
    
    // Handle circle
    this.canvas.fillStyle = '#ff6b6b';
    this.canvas.strokeStyle = '#fff';
    this.canvas.lineWidth = 3;
    this.canvas.beginPath();
    this.canvas.arc(handleX, handleY, 16, 0, 2 * Math.PI);
    this.canvas.fill();
    this.canvas.stroke();
    
    // Rotation icon
    this.canvas.fillStyle = '#fff';
    this.canvas.font = 'bold 16px Arial';
    this.canvas.textAlign = 'center';
    this.canvas.fillText('↻', handleX, handleY + 5);
  }

  /**
   * Render openings (doors, windows, etc.)
   */
  renderOpenings(openings, walls) {
    for (const opening of openings) {
      const wall = walls.find(w => w.id === opening.wallId);
      if (wall) {
        this.renderOpening(opening, wall);
      }
    }
  }

  /**
   * Render a single opening
   */
  renderOpening(opening, wall) {
    const style = this.styles.openings[opening.type] || this.styles.openings.door;
    const { geometry } = wall;
    
    // Calculate opening position along wall
    const wallLength = geometry.length;
    const openingStart = opening.position * wallLength - opening.width / 2;
    const openingEnd = opening.position * wallLength + opening.width / 2;
    
    // Calculate opening coordinates
    const wallVector = {
      x: (geometry.x2 - geometry.x1) / wallLength,
      y: (geometry.y2 - geometry.y1) / wallLength
    };
    
    const startX = (geometry.x1 + wallVector.x * openingStart) * this.PIXELS_PER_FOOT + 20;
    const startY = (geometry.y1 + wallVector.y * openingStart) * this.PIXELS_PER_FOOT + 20;
    const endX = (geometry.x1 + wallVector.x * openingEnd) * this.PIXELS_PER_FOOT + 20;
    const endY = (geometry.y1 + wallVector.y * openingEnd) * this.PIXELS_PER_FOOT + 20;

    // Draw opening
    this.canvas.save();
    this.canvas.strokeStyle = style.color;
    this.canvas.lineWidth = style.width;
    this.canvas.fillStyle = style.fillColor;

    // Draw opening as a gap in the wall
    this.canvas.beginPath();
    this.canvas.moveTo(startX, startY);
    this.canvas.lineTo(endX, endY);
    this.canvas.stroke();

    // Add opening symbol
    this.renderOpeningSymbol(opening, (startX + endX) / 2, (startY + endY) / 2, wallVector);

    this.canvas.restore();
  }

  /**
   * Render opening symbol (door swing, window, etc.)
   */
  renderOpeningSymbol(opening, centerX, centerY, wallVector) {
    const symbolSize = 20;
    
    this.canvas.save();
    this.canvas.translate(centerX, centerY);
    
    // Rotate to align with wall
    const angle = Math.atan2(wallVector.y, wallVector.x);
    this.canvas.rotate(angle);

    switch (opening.type) {
      case 'door':
        this.renderDoorSymbol(symbolSize, opening.swing);
        break;
      case 'window':
        this.renderWindowSymbol(symbolSize);
        break;
      case 'arch':
        this.renderArchSymbol(symbolSize);
        break;
      default:
        this.renderGenericOpeningSymbol(symbolSize);
    }

    this.canvas.restore();
  }

  /**
   * Render door symbol with swing indication
   */
  renderDoorSymbol(size, swing) {
    // Door frame
    this.canvas.strokeStyle = '#059669';
    this.canvas.lineWidth = 2;
    this.canvas.strokeRect(-size/2, -2, size, 4);

    // Door swing arc
    if (swing === 'inward' || swing === 'outward') {
      this.canvas.strokeStyle = '#10b981';
      this.canvas.lineWidth = 1;
      this.canvas.setLineDash([2, 2]);
      
      const swingRadius = size * 0.7;
      const startAngle = swing === 'inward' ? 0 : Math.PI;
      const endAngle = swing === 'inward' ? Math.PI/2 : Math.PI * 1.5;
      
      this.canvas.beginPath();
      this.canvas.arc(-size/2, 0, swingRadius, startAngle, endAngle);
      this.canvas.stroke();
      this.canvas.setLineDash([]);
    }

    // Door handle
    this.canvas.fillStyle = '#059669';
    this.canvas.beginPath();
    this.canvas.arc(size/3, 0, 2, 0, 2 * Math.PI);
    this.canvas.fill();
  }

  /**
   * Render window symbol
   */
  renderWindowSymbol(size) {
    // Window frame
    this.canvas.strokeStyle = '#0ea5e9';
    this.canvas.lineWidth = 2;
    this.canvas.strokeRect(-size/2, -3, size, 6);

    // Window panes
    this.canvas.strokeStyle = '#0ea5e9';
    this.canvas.lineWidth = 1;
    this.canvas.beginPath();
    this.canvas.moveTo(0, -3);
    this.canvas.lineTo(0, 3);
    this.canvas.moveTo(-size/2, 0);
    this.canvas.lineTo(size/2, 0);
    this.canvas.stroke();
  }

  /**
   * Render arch symbol
   */
  renderArchSymbol(size) {
    // Arch opening
    this.canvas.strokeStyle = '#f59e0b';
    this.canvas.lineWidth = 2;
    this.canvas.beginPath();
    this.canvas.arc(0, 0, size/2, 0, Math.PI);
    this.canvas.stroke();

    // Arch base
    this.canvas.beginPath();
    this.canvas.moveTo(-size/2, 0);
    this.canvas.lineTo(size/2, 0);
    this.canvas.stroke();
  }

  /**
   * Render generic opening symbol
   */
  renderGenericOpeningSymbol(size) {
    this.canvas.strokeStyle = '#6b7280';
    this.canvas.lineWidth = 2;
    this.canvas.strokeRect(-size/2, -2, size, 4);
  }

  /**
   * Render measurements and dimensions
   */
  renderMeasurements(measurements) {
    for (const measurement of measurements) {
      switch (measurement.type) {
        case 'linear':
          this.renderLinearMeasurement(measurement);
          break;
        case 'area':
          this.renderAreaMeasurement(measurement);
          break;
        case 'dimension':
          this.renderDimensionLine(measurement);
          break;
      }
    }
  }

  /**
   * Render linear measurement
   */
  renderLinearMeasurement(measurement) {
    const { points, style, label } = measurement;
    const [p1, p2] = points;
    
    const x1 = p1.x * this.PIXELS_PER_FOOT + 20;
    const y1 = p1.y * this.PIXELS_PER_FOOT + 20;
    const x2 = p2.x * this.PIXELS_PER_FOOT + 20;
    const y2 = p2.y * this.PIXELS_PER_FOOT + 20;

    this.canvas.save();
    this.canvas.strokeStyle = style.color;
    this.canvas.lineWidth = style.lineWidth;
    this.canvas.setLineDash([2, 2]);

    // Measurement line
    this.canvas.beginPath();
    this.canvas.moveTo(x1, y1);
    this.canvas.lineTo(x2, y2);
    this.canvas.stroke();

    // End markers
    this.canvas.setLineDash([]);
    this.canvas.fillStyle = style.color;
    this.canvas.beginPath();
    this.canvas.arc(x1, y1, 3, 0, 2 * Math.PI);
    this.canvas.arc(x2, y2, 3, 0, 2 * Math.PI);
    this.canvas.fill();

    // Label
    const centerX = (x1 + x2) / 2;
    const centerY = (y1 + y2) / 2;
    this.renderMeasurementLabel(label, centerX, centerY, style);

    this.canvas.restore();
  }

  /**
   * Render dimension line
   */
  renderDimensionLine(dimension) {
    const { startPoint, endPoint, label, style, type } = dimension;
    
    const x1 = startPoint.x * this.PIXELS_PER_FOOT + 20;
    const y1 = startPoint.y * this.PIXELS_PER_FOOT + 20;
    const x2 = endPoint.x * this.PIXELS_PER_FOOT + 20;
    const y2 = endPoint.y * this.PIXELS_PER_FOOT + 20;

    this.canvas.save();
    this.canvas.strokeStyle = style.color;
    this.canvas.lineWidth = style.lineWidth;

    // Dimension line
    this.canvas.beginPath();
    this.canvas.moveTo(x1, y1);
    this.canvas.lineTo(x2, y2);
    this.canvas.stroke();

    // Extension lines
    const offset = style.offset || 15;
    if (type === 'horizontal') {
      // Vertical extension lines
      this.canvas.beginPath();
      this.canvas.moveTo(x1, y1 - offset/2);
      this.canvas.lineTo(x1, y1 + offset/2);
      this.canvas.moveTo(x2, y2 - offset/2);
      this.canvas.lineTo(x2, y2 + offset/2);
      this.canvas.stroke();
    } else if (type === 'vertical') {
      // Horizontal extension lines
      this.canvas.beginPath();
      this.canvas.moveTo(x1 - offset/2, y1);
      this.canvas.lineTo(x1 + offset/2, y1);
      this.canvas.moveTo(x2 - offset/2, y2);
      this.canvas.lineTo(x2 + offset/2, y2);
      this.canvas.stroke();
    }

    // Arrowheads
    this.renderArrowheads(x1, y1, x2, y2, type);

    // Label
    const centerX = (x1 + x2) / 2;
    const centerY = (y1 + y2) / 2;
    this.renderMeasurementLabel(label, centerX, centerY, style);

    this.canvas.restore();
  }

  /**
   * Render arrowheads for dimension lines
   */
  renderArrowheads(x1, y1, x2, y2, type) {
    const arrowSize = 6;
    this.canvas.fillStyle = this.canvas.strokeStyle;

    if (type === 'horizontal') {
      // Left arrow
      this.canvas.beginPath();
      this.canvas.moveTo(x1, y1);
      this.canvas.lineTo(x1 + arrowSize, y1 - arrowSize/2);
      this.canvas.lineTo(x1 + arrowSize, y1 + arrowSize/2);
      this.canvas.closePath();
      this.canvas.fill();

      // Right arrow
      this.canvas.beginPath();
      this.canvas.moveTo(x2, y2);
      this.canvas.lineTo(x2 - arrowSize, y2 - arrowSize/2);
      this.canvas.lineTo(x2 - arrowSize, y2 + arrowSize/2);
      this.canvas.closePath();
      this.canvas.fill();
    } else if (type === 'vertical') {
      // Top arrow
      this.canvas.beginPath();
      this.canvas.moveTo(x1, y1);
      this.canvas.lineTo(x1 - arrowSize/2, y1 + arrowSize);
      this.canvas.lineTo(x1 + arrowSize/2, y1 + arrowSize);
      this.canvas.closePath();
      this.canvas.fill();

      // Bottom arrow
      this.canvas.beginPath();
      this.canvas.moveTo(x2, y2);
      this.canvas.lineTo(x2 - arrowSize/2, y2 - arrowSize);
      this.canvas.lineTo(x2 + arrowSize/2, y2 - arrowSize);
      this.canvas.closePath();
      this.canvas.fill();
    }
  }

  /**
   * Render measurement label with background
   */
  renderMeasurementLabel(text, x, y, style) {
    this.canvas.font = `${style.fontSize}px Arial`;
    this.canvas.textAlign = 'center';
    this.canvas.textBaseline = 'middle';

    // Measure text
    const metrics = this.canvas.measureText(text);
    const padding = 4;
    const bgWidth = metrics.width + padding * 2;
    const bgHeight = style.fontSize + padding * 2;

    // Background
    this.canvas.fillStyle = 'rgba(255, 255, 255, 0.9)';
    this.canvas.fillRect(x - bgWidth/2, y - bgHeight/2, bgWidth, bgHeight);
    
    // Border
    this.canvas.strokeStyle = '#d1d5db';
    this.canvas.lineWidth = 1;
    this.canvas.strokeRect(x - bgWidth/2, y - bgHeight/2, bgWidth, bgHeight);

    // Text
    this.canvas.fillStyle = style.color;
    this.canvas.fillText(text, x, y);
  }

  /**
   * Render constraints (alignment guides, snap points, etc.)
   */
  renderConstraints(constraints) {
    const { alignmentGuides = [], snapPoints = [] } = constraints;

    // Render alignment guides
    for (const guide of alignmentGuides) {
      this.renderAlignmentGuide(guide);
    }

    // Render snap points
    for (const point of snapPoints) {
      this.renderSnapPoint(point);
    }
  }

  /**
   * Render alignment guide
   */
  renderAlignmentGuide(guide) {
    const style = this.styles.constraints.alignment;
    
    this.canvas.save();
    this.canvas.globalAlpha = style.opacity;
    this.canvas.strokeStyle = style.color;
    this.canvas.lineWidth = style.width;
    this.canvas.setLineDash(style.dash);

    this.canvas.beginPath();
    if (guide.type === 'horizontal') {
      this.canvas.moveTo(guide.x1, guide.y);
      this.canvas.lineTo(guide.x2, guide.y);
    } else if (guide.type === 'vertical') {
      this.canvas.moveTo(guide.x, guide.y1);
      this.canvas.lineTo(guide.x, guide.y2);
    }
    this.canvas.stroke();

    this.canvas.restore();
  }

  /**
   * Render snap point
   */
  renderSnapPoint(point) {
    const style = this.styles.constraints.snap;
    
    this.canvas.save();
    this.canvas.globalAlpha = style.opacity;
    this.canvas.fillStyle = style.color;
    this.canvas.strokeStyle = '#ffffff';
    this.canvas.lineWidth = 2;

    // Snap point circle
    this.canvas.beginPath();
    this.canvas.arc(point.x, point.y, style.size, 0, 2 * Math.PI);
    this.canvas.fill();
    this.canvas.stroke();

    // Type indicator
    let symbol = '●';
    switch (point.type) {
      case 'corner': symbol = '◆'; break;
      case 'edge': symbol = '■'; break;
      case 'center': symbol = '⊕'; break;
      case 'grid': symbol = '⊞'; break;
    }

    this.canvas.fillStyle = '#ffffff';
    this.canvas.font = '8px Arial';
    this.canvas.textAlign = 'center';
    this.canvas.textBaseline = 'middle';
    this.canvas.fillText(symbol, point.x, point.y);

    this.canvas.restore();
  }

  /**
   * Utility function to adjust color brightness
   */
  adjustColor(color, amount) {
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
  }

  /**
   * Update rendering styles
   */
  updateStyles(newStyles) {
    this.styles = { ...this.styles, ...newStyles };
  }

  /**
   * Get current rendering styles
   */
  getStyles() {
    return this.styles;
  }
}

export default EnhancedCanvasRenderer;