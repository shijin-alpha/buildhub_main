import React from 'react';

/**
 * Construction Metadata System - Enhanced construction specifications and metadata
 * This system extends the existing room properties with detailed construction
 * specifications while maintaining backward compatibility
 */

export class ConstructionMetadata {
  constructor() {
    this.specifications = new Map(); // Room ID -> Construction specifications
    this.materials = new Map(); // Material ID -> Material properties
    this.finishes = new Map(); // Finish ID -> Finish properties
    this.systems = new Map(); // System ID -> Building system properties
    
    // Initialize standard materials and finishes
    this.initializeStandardMaterials();
    this.initializeStandardFinishes();
    this.initializeStandardSystems();
  }

  /**
   * Initialize standard construction materials
   */
  initializeStandardMaterials() {
    const standardMaterials = [
      {
        id: 'brick_red',
        name: 'Red Clay Brick',
        category: 'masonry',
        properties: {
          strength: 'high',
          insulation: 'medium',
          durability: 'high',
          cost: 'medium',
          thickness: 0.33, // feet
          weight: 120, // lbs per sq ft
          fireRating: 4, // hours
          thermalMass: 'high'
        },
        specifications: {
          compressiveStrength: '3000-5000 psi',
          absorptionRate: '10-15%',
          frostResistance: 'excellent',
          maintenance: 'low'
        }
      },
      {
        id: 'concrete_block',
        name: 'Concrete Masonry Unit',
        category: 'masonry',
        properties: {
          strength: 'high',
          insulation: 'low',
          durability: 'high',
          cost: 'low',
          thickness: 0.67, // 8-inch block
          weight: 80,
          fireRating: 4,
          thermalMass: 'high'
        },
        specifications: {
          compressiveStrength: '1900+ psi',
          density: '125-140 pcf',
          shrinkage: 'low',
          maintenance: 'very low'
        }
      },
      {
        id: 'wood_frame',
        name: 'Wood Frame Construction',
        category: 'frame',
        properties: {
          strength: 'medium',
          insulation: 'high',
          durability: 'medium',
          cost: 'low',
          thickness: 0.5, // 2x6 framing
          weight: 15,
          fireRating: 1,
          thermalMass: 'low'
        },
        specifications: {
          lumber: 'SPF or Douglas Fir',
          spacing: '16" or 24" OC',
          insulation: 'R-19 to R-21',
          maintenance: 'medium'
        }
      },
      {
        id: 'steel_frame',
        name: 'Steel Frame Construction',
        category: 'frame',
        properties: {
          strength: 'very high',
          insulation: 'low',
          durability: 'high',
          cost: 'high',
          thickness: 0.25,
          weight: 25,
          fireRating: 2,
          thermalMass: 'medium'
        },
        specifications: {
          gauge: '20-25 gauge',
          coating: 'galvanized',
          thermalBridge: 'requires thermal break',
          maintenance: 'low'
        }
      }
    ];

    for (const material of standardMaterials) {
      this.materials.set(material.id, material);
    }
  }

  /**
   * Initialize standard finishes
   */
  initializeStandardFinishes() {
    const standardFinishes = [
      {
        id: 'ceramic_tile',
        name: 'Ceramic Tile',
        category: 'flooring',
        properties: {
          durability: 'high',
          waterResistance: 'excellent',
          maintenance: 'low',
          cost: 'medium',
          thickness: 0.04, // ~0.5 inches
          slip: 'medium'
        },
        applications: ['bathroom', 'kitchen', 'utility'],
        specifications: {
          size: '12"x12" to 24"x24"',
          grout: 'sanded or unsanded',
          sealant: 'required for natural stone',
          installation: 'adhesive or mortar bed'
        }
      },
      {
        id: 'hardwood_oak',
        name: 'Oak Hardwood Flooring',
        category: 'flooring',
        properties: {
          durability: 'high',
          waterResistance: 'low',
          maintenance: 'medium',
          cost: 'high',
          thickness: 0.06, // 3/4 inch
          slip: 'low'
        },
        applications: ['living_room', 'bedroom', 'dining_room'],
        specifications: {
          grade: 'Select, Common, or Character',
          finish: 'site-finished or pre-finished',
          installation: 'nail-down or glue-down',
          maintenance: 'periodic refinishing'
        }
      },
      {
        id: 'granite_counter',
        name: 'Granite Countertop',
        category: 'countertop',
        properties: {
          durability: 'very high',
          waterResistance: 'excellent',
          maintenance: 'low',
          cost: 'high',
          thickness: 0.1, // 1.25 inches
          heat: 'excellent'
        },
        applications: ['kitchen', 'bathroom'],
        specifications: {
          edge: 'polished, honed, or flamed',
          sealant: 'annual application recommended',
          support: 'requires proper substrate',
          installation: 'professional required'
        }
      },
      {
        id: 'paint_latex',
        name: 'Latex Paint',
        category: 'wall_finish',
        properties: {
          durability: 'medium',
          washability: 'good',
          maintenance: 'medium',
          cost: 'low',
          coverage: 350, // sq ft per gallon
          drying: 'fast'
        },
        applications: ['interior_walls', 'ceiling'],
        specifications: {
          sheen: 'flat, eggshell, satin, semi-gloss',
          primer: 'required for new surfaces',
          coats: '2 coats recommended',
          cleanup: 'water-based'
        }
      }
    ];

    for (const finish of standardFinishes) {
      this.finishes.set(finish.id, finish);
    }
  }

  /**
   * Initialize standard building systems
   */
  initializeStandardSystems() {
    const standardSystems = [
      {
        id: 'hvac_central',
        name: 'Central HVAC System',
        category: 'hvac',
        properties: {
          efficiency: 'high',
          coverage: 'whole_house',
          maintenance: 'medium',
          cost: 'high',
          noise: 'low',
          zoning: 'possible'
        },
        specifications: {
          type: 'forced air with ductwork',
          capacity: 'sized per Manual J calculation',
          efficiency: 'SEER 14+ cooling, 80%+ heating',
          controls: 'programmable thermostat'
        }
      },
      {
        id: 'electrical_standard',
        name: 'Standard Electrical System',
        category: 'electrical',
        properties: {
          capacity: '200 amp service',
          outlets: 'code compliant',
          lighting: 'LED compatible',
          safety: 'GFCI/AFCI protected',
          smart: 'ready for automation'
        },
        specifications: {
          panel: '200A main breaker panel',
          wiring: '12 AWG copper (20A) / 14 AWG (15A)',
          outlets: 'every 12 feet maximum',
          lighting: 'switched in all rooms'
        }
      },
      {
        id: 'plumbing_standard',
        name: 'Standard Plumbing System',
        category: 'plumbing',
        properties: {
          supply: 'copper or PEX',
          waste: 'PVC or cast iron',
          fixtures: 'water efficient',
          pressure: 'adequate throughout',
          access: 'cleanouts provided'
        },
        specifications: {
          supply: '3/4" main, 1/2" branches',
          waste: '4" main, 3" branches',
          venting: 'proper venting per code',
          fixtures: 'WaterSense certified'
        }
      }
    ];

    for (const system of standardSystems) {
      this.systems.set(system.id, system);
    }
  }

  /**
   * Create construction specifications for a room
   */
  createRoomSpecifications(roomId, roomData, options = {}) {
    const specifications = {
      id: roomId,
      roomName: roomData.name,
      roomType: roomData.type || 'general',
      
      // Structural specifications
      structure: {
        wallMaterial: roomData.wall_material || 'brick',
        wallThickness: roomData.wall_thickness || 0.5,
        ceilingHeight: roomData.ceiling_height || 9,
        floorSystem: options.floorSystem || 'concrete_slab',
        roofSystem: options.roofSystem || 'truss',
        loadBearing: options.loadBearing || false
      },

      // Finish specifications
      finishes: {
        flooring: this.getRecommendedFlooring(roomData.type),
        walls: this.getRecommendedWallFinish(roomData.type),
        ceiling: this.getRecommendedCeilingFinish(roomData.type),
        trim: options.trim || 'painted_wood',
        doors: this.getRecommendedDoors(roomData.type),
        windows: this.getRecommendedWindows(roomData.type)
      },

      // Building systems
      systems: {
        hvac: this.getHVACRequirements(roomData.type),
        electrical: this.getElectricalRequirements(roomData.type),
        plumbing: this.getPlumbingRequirements(roomData.type),
        lighting: this.getLightingRequirements(roomData.type)
      },

      // Performance requirements
      performance: {
        insulation: this.getInsulationRequirements(roomData.type),
        ventilation: this.getVentilationRequirements(roomData.type),
        acoustics: this.getAcousticRequirements(roomData.type),
        accessibility: options.accessibility || 'standard'
      },

      // Cost estimates
      costs: {
        structure: this.estimateStructuralCost(roomData),
        finishes: this.estimateFinishCost(roomData),
        systems: this.estimateSystemsCost(roomData),
        total: 0 // Will be calculated
      },

      // Compliance and codes
      compliance: {
        buildingCode: 'IBC 2021',
        energyCode: 'IECC 2021',
        accessibility: 'ADA compliant where required',
        fire: this.getFireRequirements(roomData.type),
        seismic: options.seismicZone || 'standard'
      },

      metadata: {
        createdAt: Date.now(),
        lastUpdated: Date.now(),
        version: '1.0'
      }
    };

    // Calculate total cost
    specifications.costs.total = 
      specifications.costs.structure + 
      specifications.costs.finishes + 
      specifications.costs.systems;

    this.specifications.set(roomId, specifications);
    return specifications;
  }

  /**
   * Get recommended flooring based on room type
   */
  getRecommendedFlooring(roomType) {
    const flooringMap = {
      'bathroom': 'ceramic_tile',
      'master_bathroom': 'ceramic_tile',
      'kitchen': 'ceramic_tile',
      'utility_room': 'ceramic_tile',
      'laundry_room': 'ceramic_tile',
      'living_room': 'hardwood_oak',
      'dining_room': 'hardwood_oak',
      'bedroom': 'hardwood_oak',
      'master_bedroom': 'hardwood_oak',
      'study_room': 'hardwood_oak'
    };

    return flooringMap[roomType] || 'ceramic_tile';
  }

  /**
   * Get recommended wall finish based on room type
   */
  getRecommendedWallFinish(roomType) {
    const wallFinishMap = {
      'bathroom': 'ceramic_tile_partial',
      'master_bathroom': 'ceramic_tile_partial',
      'kitchen': 'ceramic_tile_backsplash'
    };

    return wallFinishMap[roomType] || 'paint_latex';
  }

  /**
   * Get recommended ceiling finish based on room type
   */
  getRecommendedCeilingFinish(roomType) {
    const ceilingMap = {
      'bathroom': 'moisture_resistant_paint',
      'kitchen': 'moisture_resistant_paint'
    };

    return ceilingMap[roomType] || 'paint_latex';
  }

  /**
   * Get recommended doors based on room type
   */
  getRecommendedDoors(roomType) {
    const doorMap = {
      'bathroom': 'privacy_door',
      'master_bathroom': 'privacy_door',
      'bedroom': 'privacy_door',
      'master_bedroom': 'privacy_door',
      'study_room': 'privacy_door'
    };

    return doorMap[roomType] || 'standard_door';
  }

  /**
   * Get recommended windows based on room type
   */
  getRecommendedWindows(roomType) {
    const windowMap = {
      'bathroom': 'privacy_window',
      'master_bathroom': 'privacy_window',
      'bedroom': 'standard_window',
      'living_room': 'large_window',
      'dining_room': 'standard_window'
    };

    return windowMap[roomType] || 'standard_window';
  }

  /**
   * Get HVAC requirements based on room type
   */
  getHVACRequirements(roomType) {
    const hvacMap = {
      'bathroom': {
        heating: 'required',
        cooling: 'recommended',
        ventilation: 'exhaust_fan_required',
        capacity: 'standard'
      },
      'kitchen': {
        heating: 'required',
        cooling: 'required',
        ventilation: 'range_hood_required',
        capacity: 'increased'
      },
      'bedroom': {
        heating: 'required',
        cooling: 'required',
        ventilation: 'natural_or_mechanical',
        capacity: 'standard'
      }
    };

    return hvacMap[roomType] || {
      heating: 'required',
      cooling: 'recommended',
      ventilation: 'natural',
      capacity: 'standard'
    };
  }

  /**
   * Get electrical requirements based on room type
   */
  getElectricalRequirements(roomType) {
    const electricalMap = {
      'kitchen': {
        outlets: 'GFCI_required_countertop',
        circuits: 'dedicated_appliance_circuits',
        lighting: 'task_and_ambient',
        special: 'dishwasher_disposal_circuits'
      },
      'bathroom': {
        outlets: 'GFCI_required',
        circuits: 'dedicated_bathroom_circuit',
        lighting: 'vanity_and_general',
        special: 'exhaust_fan_circuit'
      },
      'bedroom': {
        outlets: 'every_12_feet',
        circuits: 'general_purpose',
        lighting: 'switched_general',
        special: 'ceiling_fan_ready'
      }
    };

    return electricalMap[roomType] || {
      outlets: 'code_minimum',
      circuits: 'general_purpose',
      lighting: 'switched_general',
      special: 'none'
    };
  }

  /**
   * Get plumbing requirements based on room type
   */
  getPlumbingRequirements(roomType) {
    const plumbingMap = {
      'bathroom': {
        fixtures: 'toilet_sink_shower_or_tub',
        supply: 'hot_and_cold',
        waste: 'connected_to_main',
        venting: 'proper_venting_required'
      },
      'master_bathroom': {
        fixtures: 'toilet_dual_sinks_shower_tub',
        supply: 'hot_and_cold_increased_capacity',
        waste: 'connected_to_main',
        venting: 'proper_venting_required'
      },
      'kitchen': {
        fixtures: 'sink_dishwasher_connection',
        supply: 'hot_and_cold',
        waste: 'connected_to_main_with_disposal',
        venting: 'proper_venting_required'
      },
      'utility_room': {
        fixtures: 'utility_sink_washer_connections',
        supply: 'hot_and_cold',
        waste: 'connected_to_main',
        venting: 'proper_venting_required'
      }
    };

    return plumbingMap[roomType] || {
      fixtures: 'none',
      supply: 'none',
      waste: 'none',
      venting: 'none'
    };
  }

  /**
   * Get lighting requirements based on room type
   */
  getLightingRequirements(roomType) {
    const lightingMap = {
      'kitchen': {
        general: 'recessed_or_flush_mount',
        task: 'under_cabinet_LED',
        accent: 'pendant_over_island',
        controls: 'multiple_switches'
      },
      'bathroom': {
        general: 'ceiling_fixture',
        task: 'vanity_lighting',
        accent: 'none',
        controls: 'switched'
      },
      'living_room': {
        general: 'ceiling_fixture_or_fan',
        task: 'table_floor_lamps',
        accent: 'wall_sconces_optional',
        controls: 'switched_and_outlets'
      }
    };

    return lightingMap[roomType] || {
      general: 'ceiling_fixture',
      task: 'none',
      accent: 'none',
      controls: 'switched'
    };
  }

  /**
   * Get insulation requirements based on room type
   */
  getInsulationRequirements(roomType) {
    return {
      walls: 'R-13 to R-21',
      ceiling: 'R-30 to R-49',
      floor: 'R-19 to R-25',
      windows: 'Double pane, Low-E',
      airSealing: 'continuous air barrier'
    };
  }

  /**
   * Get ventilation requirements based on room type
   */
  getVentilationRequirements(roomType) {
    const ventilationMap = {
      'bathroom': {
        type: 'exhaust',
        rate: '50 CFM',
        controls: 'switch_or_humidity_sensor'
      },
      'kitchen': {
        type: 'range_hood',
        rate: '100-400 CFM',
        controls: 'variable_speed'
      }
    };

    return ventilationMap[roomType] || {
      type: 'natural',
      rate: 'per_building_code',
      controls: 'operable_windows'
    };
  }

  /**
   * Get acoustic requirements based on room type
   */
  getAcousticRequirements(roomType) {
    const acousticMap = {
      'bedroom': {
        stc: 'STC-50 minimum',
        iic: 'IIC-50 minimum',
        treatment: 'sound_insulation_recommended'
      },
      'study_room': {
        stc: 'STC-55 minimum',
        iic: 'IIC-55 minimum',
        treatment: 'sound_insulation_required'
      }
    };

    return acousticMap[roomType] || {
      stc: 'STC-45 minimum',
      iic: 'IIC-45 minimum',
      treatment: 'standard'
    };
  }

  /**
   * Get fire safety requirements based on room type
   */
  getFireRequirements(roomType) {
    const fireMap = {
      'bedroom': {
        egress: 'window_or_door_to_exterior',
        detection: 'smoke_detector_required',
        suppression: 'sprinkler_if_required_by_code'
      },
      'kitchen': {
        egress: 'standard',
        detection: 'smoke_and_heat_detector',
        suppression: 'fire_extinguisher_recommended'
      }
    };

    return fireMap[roomType] || {
      egress: 'standard',
      detection: 'per_building_code',
      suppression: 'per_building_code'
    };
  }

  /**
   * Estimate structural cost for a room
   */
  estimateStructuralCost(roomData) {
    const area = (roomData.actual_width || roomData.layout_width * 1.2) * 
                 (roomData.actual_height || roomData.layout_height * 1.2);
    
    // Base cost per square foot for structure
    const baseCostPerSqFt = 25; // $25 per sq ft for basic structure
    
    // Material multipliers
    const materialMultipliers = {
      'brick': 1.3,
      'concrete': 1.1,
      'wood': 1.0,
      'steel': 1.4
    };
    
    const materialMultiplier = materialMultipliers[roomData.wall_material] || 1.0;
    
    return Math.round(area * baseCostPerSqFt * materialMultiplier);
  }

  /**
   * Estimate finish cost for a room
   */
  estimateFinishCost(roomData) {
    const area = (roomData.actual_width || roomData.layout_width * 1.2) * 
                 (roomData.actual_height || roomData.layout_height * 1.2);
    
    // Base finish cost per square foot
    const finishCostMap = {
      'bathroom': 45, // Higher cost for tile, fixtures
      'kitchen': 40, // Higher cost for cabinets, counters
      'bedroom': 20,
      'living_room': 25,
      'dining_room': 25
    };
    
    const baseCost = finishCostMap[roomData.type] || 20;
    
    return Math.round(area * baseCost);
  }

  /**
   * Estimate systems cost for a room
   */
  estimateSystemsCost(roomData) {
    const area = (roomData.actual_width || roomData.layout_width * 1.2) * 
                 (roomData.actual_height || roomData.layout_height * 1.2);
    
    // Base systems cost per square foot
    const systemsCostMap = {
      'bathroom': 35, // Plumbing, electrical, ventilation
      'kitchen': 30, // Electrical, plumbing, HVAC
      'bedroom': 15,
      'living_room': 15,
      'dining_room': 15
    };
    
    const baseCost = systemsCostMap[roomData.type] || 15;
    
    return Math.round(area * baseCost);
  }

  /**
   * Update specifications when room changes
   */
  updateRoomSpecifications(roomId, roomData) {
    const existing = this.specifications.get(roomId);
    if (!existing) {
      return this.createRoomSpecifications(roomId, roomData);
    }

    // Update costs based on new dimensions
    existing.costs.structure = this.estimateStructuralCost(roomData);
    existing.costs.finishes = this.estimateFinishCost(roomData);
    existing.costs.systems = this.estimateSystemsCost(roomData);
    existing.costs.total = existing.costs.structure + existing.costs.finishes + existing.costs.systems;

    // Update structural specifications
    existing.structure.wallMaterial = roomData.wall_material || existing.structure.wallMaterial;
    existing.structure.wallThickness = roomData.wall_thickness || existing.structure.wallThickness;
    existing.structure.ceilingHeight = roomData.ceiling_height || existing.structure.ceilingHeight;

    existing.metadata.lastUpdated = Date.now();

    return existing;
  }

  /**
   * Get specifications for a room
   */
  getRoomSpecifications(roomId) {
    return this.specifications.get(roomId);
  }

  /**
   * Get all specifications
   */
  getAllSpecifications() {
    return Array.from(this.specifications.values());
  }

  /**
   * Generate construction summary
   */
  generateConstructionSummary(rooms) {
    const allSpecs = rooms.map(room => 
      this.specifications.get(room.id) || this.createRoomSpecifications(room.id, room)
    );

    const summary = {
      totalCost: allSpecs.reduce((sum, spec) => sum + spec.costs.total, 0),
      costBreakdown: {
        structure: allSpecs.reduce((sum, spec) => sum + spec.costs.structure, 0),
        finishes: allSpecs.reduce((sum, spec) => sum + spec.costs.finishes, 0),
        systems: allSpecs.reduce((sum, spec) => sum + spec.costs.systems, 0)
      },
      materialSummary: this.summarizeMaterials(allSpecs),
      systemsSummary: this.summarizeSystems(allSpecs),
      complianceSummary: this.summarizeCompliance(allSpecs)
    };

    return summary;
  }

  /**
   * Summarize materials used
   */
  summarizeMaterials(specifications) {
    const materials = {};
    
    for (const spec of specifications) {
      const wallMaterial = spec.structure.wallMaterial;
      if (!materials[wallMaterial]) {
        materials[wallMaterial] = { count: 0, area: 0 };
      }
      materials[wallMaterial].count++;
      
      const roomArea = (spec.roomData?.actual_width || spec.roomData?.layout_width * 1.2 || 10) * 
                      (spec.roomData?.actual_height || spec.roomData?.layout_height * 1.2 || 10);
      materials[wallMaterial].area += roomArea;
    }
    
    return materials;
  }

  /**
   * Summarize building systems
   */
  summarizeSystems(specifications) {
    const systems = {
      hvac: { rooms: 0, specialRequirements: [] },
      electrical: { rooms: 0, specialRequirements: [] },
      plumbing: { rooms: 0, fixtures: 0 }
    };

    for (const spec of specifications) {
      if (spec.systems.hvac.heating === 'required') {
        systems.hvac.rooms++;
      }
      
      if (spec.systems.electrical.circuits !== 'none') {
        systems.electrical.rooms++;
      }
      
      if (spec.systems.plumbing.fixtures !== 'none') {
        systems.plumbing.rooms++;
        systems.plumbing.fixtures++;
      }
    }

    return systems;
  }

  /**
   * Summarize compliance requirements
   */
  summarizeCompliance(specifications) {
    const compliance = {
      buildingCode: 'IBC 2021',
      energyCode: 'IECC 2021',
      accessibility: 'ADA where required',
      specialRequirements: []
    };

    for (const spec of specifications) {
      if (spec.compliance.fire.egress !== 'standard') {
        compliance.specialRequirements.push(`${spec.roomName}: ${spec.compliance.fire.egress}`);
      }
    }

    return compliance;
  }

  /**
   * Export construction metadata
   */
  exportData() {
    return {
      specifications: Object.fromEntries(this.specifications),
      materials: Object.fromEntries(this.materials),
      finishes: Object.fromEntries(this.finishes),
      systems: Object.fromEntries(this.systems),
      metadata: {
        version: '1.0',
        lastUpdated: Date.now()
      }
    };
  }

  /**
   * Import construction metadata
   */
  importData(data) {
    if (!data) return;

    if (data.specifications) {
      this.specifications.clear();
      for (const [id, spec] of Object.entries(data.specifications)) {
        this.specifications.set(id, spec);
      }
    }

    if (data.materials) {
      for (const [id, material] of Object.entries(data.materials)) {
        this.materials.set(id, material);
      }
    }

    if (data.finishes) {
      for (const [id, finish] of Object.entries(data.finishes)) {
        this.finishes.set(id, finish);
      }
    }

    if (data.systems) {
      for (const [id, system] of Object.entries(data.systems)) {
        this.systems.set(id, system);
      }
    }
  }
}

export default ConstructionMetadata;