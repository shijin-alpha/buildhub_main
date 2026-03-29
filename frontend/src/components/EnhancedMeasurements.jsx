import React from 'react';

/**
 * Enhanced Measurements System - Provides accurate measurements derived from geometry
 * This system improves measurement accuracy by calculating from the underlying model
 * geometry instead of screen pixels, while maintaining compatibility with existing UI
 */

export class EnhancedMeasurements {
  constructor() {
    this.measurements = new Map(); // Measurement ID -> Measurement object
    this.dimensionLines = new Map(); // Dimension line ID -> Dimension line object
    this.annotations = new Map(); // Annotation ID -> Annotation object
    
    // Measurement types
    this.MEASUREMENT_TYPES = {
      LINEAR: 'linear', // Distance between two points
      AREA: 'area', // Area of a room or region
      PERIMETER: 'perimeter', // Perimeter of a room
      ANGLE: 'angle', // Angle between walls/lines
      RADIUS: 'radius', // Radius of curved elements
      ELEVATION: 'elevation' // Height measurements
    };

    // Units and conversion factors
    this.UNITS = {
      FEET: 'ft',
      INCHES: 'in',
      METERS: 'm',
      CENTIMETERS: 'cm',
      SQUARE_FEET: 'sq ft',
      SQUARE_METERS: 'sq m'
    };

    this.CONVERSION_FACTORS = {
      'ft_to_in': 12,
      'ft_to_m': 0.3048,
      'ft_to_cm': 30.48,
      'sqft_to_sqm': 0.092903
    };

    // Precision settings
    this.PRECISION = {
      LINEAR: 2, // Decimal places for linear measurements
      AREA: 1, // Decimal places for area measurements
      ANGLE: 1 // Decimal places for angles
    };
  }

  /**
   * Create a linear measurement between two points
   */
  createLinearMeasurement(id, point1, point2, options = {}) {
    const distance = this.calculateDistance(point1, point2);
    
    const measurement = {
      id,
      type: this.MEASUREMENT_TYPES.LINEAR,
      points: [point1, point2],
      value: distance,
      unit: options.unit || this.UNITS.FEET,
      precision: options.precision || this.PRECISION.LINEAR,
      label: options.label || `${distance.toFixed(this.PRECISION.LINEAR)}'`,
      visible: options.visible !== false,
      style: {
        color: options.color || '#ff6b6b',
        lineWidth: options.lineWidth || 1,
        fontSize: options.fontSize || 10,
        ...options.style
      },
      metadata: {
        createdAt: Date.now(),
        lastUpdated: Date.now(),
        ...options.metadata
      }
    };

    this.measurements.set(id, measurement);
    return measurement;
  }

  /**
   * Create an area measurement for a room
   */
  createAreaMeasurement(id, room, options = {}) {
    const area = this.calculateRoomArea(room);
    const perimeter = this.calculateRoomPerimeter(room);
    
    const measurement = {
      id,
      type: this.MEASUREMENT_TYPES.AREA,
      room: room,
      area: area,
      perimeter: perimeter,
      unit: options.unit || this.UNITS.SQUARE_FEET,
      precision: options.precision || this.PRECISION.AREA,
      label: options.label || `${area.toFixed(this.PRECISION.AREA)} sq ft`,
      visible: options.visible !== false,
      showPerimeter: options.showPerimeter || false,
      style: {
        fillColor: options.fillColor || 'rgba(255, 107, 107, 0.1)',
        borderColor: options.borderColor || '#ff6b6b',
        fontSize: options.fontSize || 12,
        ...options.style
      },
      metadata: {
        createdAt: Date.now(),
        lastUpdated: Date.now(),
        ...options.metadata
      }
    };

    this.measurements.set(id, measurement);
    return measurement;
  }

  /**
   * Create dimension lines for a room
   */
  createRoomDimensions(roomId, room, options = {}) {
    const dimensions = [];
    
    // Horizontal dimension (width)
    const widthDimId = `${roomId}_width`;
    const widthDimension = {
      id: widthDimId,
      type: 'horizontal',
      roomId: roomId,
      startPoint: { x: room.x, y: room.y - 2 }, // 2 feet above room
      endPoint: { x: room.x + room.layout_width, y: room.y - 2 },
      value: room.layout_width,
      actualValue: room.actual_width || room.layout_width * (room.scale_ratio || 1.2),
      unit: this.UNITS.FEET,
      label: this.formatDimensionLabel(room.layout_width, room.actual_width, room.scale_ratio),
      visible: options.showDimensions !== false,
      style: {
        color: options.dimensionColor || '#2563eb',
        lineWidth: 1,
        fontSize: 10,
        offset: 2, // Offset from room edge
        ...options.dimensionStyle
      }
    };

    // Vertical dimension (height)
    const heightDimId = `${roomId}_height`;
    const heightDimension = {
      id: heightDimId,
      type: 'vertical',
      roomId: roomId,
      startPoint: { x: room.x - 2, y: room.y }, // 2 feet left of room
      endPoint: { x: room.x - 2, y: room.y + room.layout_height },
      value: room.layout_height,
      actualValue: room.actual_height || room.layout_height * (room.scale_ratio || 1.2),
      unit: this.UNITS.FEET,
      label: this.formatDimensionLabel(room.layout_height, room.actual_height, room.scale_ratio),
      visible: options.showDimensions !== false,
      style: {
        color: options.dimensionColor || '#2563eb',
        lineWidth: 1,
        fontSize: 10,
        offset: 2,
        ...options.dimensionStyle
      }
    };

    dimensions.push(widthDimension, heightDimension);

    // Store dimension lines
    this.dimensionLines.set(widthDimId, widthDimension);
    this.dimensionLines.set(heightDimId, heightDimension);

    return dimensions;
  }

  /**
   * Format dimension label showing both layout and actual measurements
   */
  formatDimensionLabel(layoutValue, actualValue, scaleRatio = 1.2) {
    const actual = actualValue || layoutValue * scaleRatio;
    
    if (Math.abs(layoutValue - actual) < 0.1) {
      // Values are essentially the same
      return `${layoutValue.toFixed(1)}'`;
    } else {
      // Show both layout and actual
      return `${layoutValue.toFixed(1)}' (${actual.toFixed(1)}')`;
    }
  }

  /**
   * Calculate accurate distance between two points
   */
  calculateDistance(point1, point2) {
    return Math.sqrt(
      Math.pow(point2.x - point1.x, 2) + 
      Math.pow(point2.y - point1.y, 2)
    );
  }

  /**
   * Calculate room area with high precision
   */
  calculateRoomArea(room) {
    // Use actual dimensions if available, otherwise use layout dimensions with scale
    const width = room.actual_width || room.layout_width * (room.scale_ratio || 1.2);
    const height = room.actual_height || room.layout_height * (room.scale_ratio || 1.2);
    
    // For rotated rooms, calculate area of rotated rectangle
    if (room.rotation && room.rotation !== 0) {
      // Area remains the same regardless of rotation for rectangles
      return width * height;
    }
    
    return width * height;
  }

  /**
   * Calculate room perimeter
   */
  calculateRoomPerimeter(room) {
    const width = room.actual_width || room.layout_width * (room.scale_ratio || 1.2);
    const height = room.actual_height || room.layout_height * (room.scale_ratio || 1.2);
    
    return 2 * (width + height);
  }

  /**
   * Calculate angle between two lines
   */
  calculateAngle(line1Start, line1End, line2Start, line2End) {
    const vector1 = {
      x: line1End.x - line1Start.x,
      y: line1End.y - line1Start.y
    };
    
    const vector2 = {
      x: line2End.x - line2Start.x,
      y: line2End.y - line2Start.y
    };
    
    const dot = vector1.x * vector2.x + vector1.y * vector2.y;
    const mag1 = Math.sqrt(vector1.x * vector1.x + vector1.y * vector1.y);
    const mag2 = Math.sqrt(vector2.x * vector2.x + vector2.y * vector2.y);
    
    const cosAngle = dot / (mag1 * mag2);
    const angleRad = Math.acos(Math.max(-1, Math.min(1, cosAngle)));
    const angleDeg = angleRad * (180 / Math.PI);
    
    return angleDeg;
  }

  /**
   * Convert measurement units
   */
  convertUnits(value, fromUnit, toUnit) {
    if (fromUnit === toUnit) return value;
    
    const conversionKey = `${fromUnit}_to_${toUnit}`;
    const factor = this.CONVERSION_FACTORS[conversionKey];
    
    if (factor) {
      return value * factor;
    }
    
    // Handle reverse conversions
    const reverseKey = `${toUnit}_to_${fromUnit}`;
    const reverseFactor = this.CONVERSION_FACTORS[reverseKey];
    
    if (reverseFactor) {
      return value / reverseFactor;
    }
    
    // No conversion available
    return value;
  }

  /**
   * Create measurement annotation
   */
  createAnnotation(id, position, text, options = {}) {
    const annotation = {
      id,
      position,
      text,
      type: options.type || 'text',
      visible: options.visible !== false,
      style: {
        fontSize: options.fontSize || 12,
        color: options.color || '#374151',
        backgroundColor: options.backgroundColor || 'rgba(255, 255, 255, 0.9)',
        padding: options.padding || 4,
        borderRadius: options.borderRadius || 4,
        border: options.border || '1px solid #d1d5db',
        ...options.style
      },
      metadata: {
        createdAt: Date.now(),
        ...options.metadata
      }
    };

    this.annotations.set(id, annotation);
    return annotation;
  }

  /**
   * Update measurements for a room when it changes
   */
  updateRoomMeasurements(roomId, room) {
    // Update area measurement
    const areaMeasurementId = `${roomId}_area`;
    if (this.measurements.has(areaMeasurementId)) {
      const areaMeasurement = this.measurements.get(areaMeasurementId);
      areaMeasurement.area = this.calculateRoomArea(room);
      areaMeasurement.perimeter = this.calculateRoomPerimeter(room);
      areaMeasurement.label = `${areaMeasurement.area.toFixed(this.PRECISION.AREA)} sq ft`;
      areaMeasurement.metadata.lastUpdated = Date.now();
    }

    // Update dimension lines
    const widthDimId = `${roomId}_width`;
    const heightDimId = `${roomId}_height`;
    
    if (this.dimensionLines.has(widthDimId)) {
      const widthDim = this.dimensionLines.get(widthDimId);
      widthDim.startPoint = { x: room.x, y: room.y - 2 };
      widthDim.endPoint = { x: room.x + room.layout_width, y: room.y - 2 };
      widthDim.value = room.layout_width;
      widthDim.actualValue = room.actual_width || room.layout_width * (room.scale_ratio || 1.2);
      widthDim.label = this.formatDimensionLabel(room.layout_width, room.actual_width, room.scale_ratio);
    }
    
    if (this.dimensionLines.has(heightDimId)) {
      const heightDim = this.dimensionLines.get(heightDimId);
      heightDim.startPoint = { x: room.x - 2, y: room.y };
      heightDim.endPoint = { x: room.x - 2, y: room.y + room.layout_height };
      heightDim.value = room.layout_height;
      heightDim.actualValue = room.actual_height || room.layout_height * (room.scale_ratio || 1.2);
      heightDim.label = this.formatDimensionLabel(room.layout_height, room.actual_height, room.scale_ratio);
    }
  }

  /**
   * Calculate total measurements for all rooms
   */
  calculateTotalMeasurements(rooms) {
    let totalLayoutArea = 0;
    let totalConstructionArea = 0;
    let totalPerimeter = 0;
    let roomCount = rooms.length;

    for (const room of rooms) {
      // Layout area
      totalLayoutArea += room.layout_width * room.layout_height;
      
      // Construction area
      const actualWidth = room.actual_width || room.layout_width * (room.scale_ratio || 1.2);
      const actualHeight = room.actual_height || room.layout_height * (room.scale_ratio || 1.2);
      totalConstructionArea += actualWidth * actualHeight;
      
      // Perimeter
      totalPerimeter += this.calculateRoomPerimeter(room);
    }

    return {
      roomCount,
      totalLayoutArea: parseFloat(totalLayoutArea.toFixed(this.PRECISION.AREA)),
      totalConstructionArea: parseFloat(totalConstructionArea.toFixed(this.PRECISION.AREA)),
      totalPerimeter: parseFloat(totalPerimeter.toFixed(this.PRECISION.LINEAR)),
      averageRoomSize: roomCount > 0 ? parseFloat((totalConstructionArea / roomCount).toFixed(this.PRECISION.AREA)) : 0,
      efficiency: totalLayoutArea > 0 ? parseFloat(((totalConstructionArea / totalLayoutArea) * 100).toFixed(1)) : 100
    };
  }

  /**
   * Generate measurement report
   */
  generateMeasurementReport(rooms, plotWidth, plotHeight) {
    const totals = this.calculateTotalMeasurements(rooms);
    const plotArea = plotWidth * plotHeight;
    const coverage = plotArea > 0 ? (totals.totalConstructionArea / plotArea) * 100 : 0;

    return {
      summary: {
        plotDimensions: `${plotWidth}' × ${plotHeight}'`,
        plotArea: `${plotArea.toFixed(0)} sq ft`,
        totalRooms: totals.roomCount,
        totalLayoutArea: `${totals.totalLayoutArea} sq ft`,
        totalConstructionArea: `${totals.totalConstructionArea} sq ft`,
        coverage: `${coverage.toFixed(1)}%`,
        efficiency: `${totals.efficiency}%`
      },
      roomDetails: rooms.map(room => ({
        name: room.name,
        layoutDimensions: `${room.layout_width}' × ${room.layout_height}'`,
        actualDimensions: `${(room.actual_width || room.layout_width * (room.scale_ratio || 1.2)).toFixed(1)}' × ${(room.actual_height || room.layout_height * (room.scale_ratio || 1.2)).toFixed(1)}'`,
        layoutArea: `${(room.layout_width * room.layout_height).toFixed(1)} sq ft`,
        constructionArea: `${this.calculateRoomArea(room).toFixed(1)} sq ft`,
        perimeter: `${this.calculateRoomPerimeter(room).toFixed(1)} ft`,
        rotation: room.rotation ? `${room.rotation}°` : '0°'
      })),
      metadata: {
        generatedAt: new Date().toISOString(),
        precision: this.PRECISION,
        units: this.UNITS
      }
    };
  }

  /**
   * Get measurements for rendering
   */
  getMeasurementsForRendering(filter = {}) {
    const measurements = Array.from(this.measurements.values());
    const dimensionLines = Array.from(this.dimensionLines.values());
    const annotations = Array.from(this.annotations.values());

    return {
      measurements: measurements.filter(m => 
        m.visible && (!filter.type || m.type === filter.type)
      ),
      dimensionLines: dimensionLines.filter(d => 
        d.visible && (!filter.roomId || d.roomId === filter.roomId)
      ),
      annotations: annotations.filter(a => 
        a.visible && (!filter.type || a.type === filter.type)
      )
    };
  }

  /**
   * Clear measurements for a specific room
   */
  clearRoomMeasurements(roomId) {
    // Remove area measurements
    this.measurements.delete(`${roomId}_area`);
    
    // Remove dimension lines
    this.dimensionLines.delete(`${roomId}_width`);
    this.dimensionLines.delete(`${roomId}_height`);
    
    // Remove annotations
    const annotationsToRemove = [];
    for (const [id, annotation] of this.annotations.entries()) {
      if (annotation.metadata && annotation.metadata.roomId === roomId) {
        annotationsToRemove.push(id);
      }
    }
    
    for (const id of annotationsToRemove) {
      this.annotations.delete(id);
    }
  }

  /**
   * Export measurements data
   */
  exportData() {
    return {
      measurements: Object.fromEntries(this.measurements),
      dimensionLines: Object.fromEntries(this.dimensionLines),
      annotations: Object.fromEntries(this.annotations),
      settings: {
        precision: this.PRECISION,
        units: this.UNITS
      },
      metadata: {
        version: '1.0',
        lastUpdated: Date.now()
      }
    };
  }

  /**
   * Import measurements data
   */
  importData(data) {
    if (!data) return;

    if (data.measurements) {
      this.measurements.clear();
      for (const [id, measurement] of Object.entries(data.measurements)) {
        this.measurements.set(id, measurement);
      }
    }

    if (data.dimensionLines) {
      this.dimensionLines.clear();
      for (const [id, dimensionLine] of Object.entries(data.dimensionLines)) {
        this.dimensionLines.set(id, dimensionLine);
      }
    }

    if (data.annotations) {
      this.annotations.clear();
      for (const [id, annotation] of Object.entries(data.annotations)) {
        this.annotations.set(id, annotation);
      }
    }

    if (data.settings) {
      if (data.settings.precision) {
        Object.assign(this.PRECISION, data.settings.precision);
      }
    }
  }
}

export default EnhancedMeasurements;