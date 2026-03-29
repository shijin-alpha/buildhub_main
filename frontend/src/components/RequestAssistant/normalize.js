// Normalization utilities for the Request Assistant

const WORD_NUMBER_MAP = {
  one: 1,
  two: 2,
  three: 3,
  four: 4,
  five: 5,
  six: 6,
  seven: 7,
  eight: 8,
  nine: 9,
  ten: 10
};

const YES_VARIANTS = ["yes", "y", "yeah", "yup", "sure", "ok", "okay"];
const NO_VARIANTS = ["no", "nope", "nah", "not now", "later"];

const ROOM_REGEX = /(\d+|one|two|three|four|five|six|seven|eight|nine|ten)\s*(bhk)/i;
const FLOOR_REGEX = /(g\s*\+\s*(\d+))|(ground\s*\+\s*(\d+))|(\d+)\s*floors?/i;
const DIMENSION_REGEX = /(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)/i;
const AREA_REGEX = /(\d+(?:\.\d+)?)(?:\s*)(sq\s*ft|sqft|ft2|ft²|sq\s*m|sqm|m2|m²)/i;
const CURRENCY_REGEX = /[₹rs\.\s]*([\d,.]+)\s*(l|lac|lakh|lakhs|cr|crore|crores)?/i;

const FT_TO_M = 0.3048;
const SQFT_TO_SQM = 0.092903;

function toNumber(value) {
  if (!value) return null;
  const cleaned = value.toString().replace(/[,\s]/g, "");
  const parsed = parseFloat(cleaned);
  return Number.isFinite(parsed) ? parsed : null;
}

function formatInr(amount) {
  if (!Number.isFinite(amount)) return null;
  return `₹${amount.toLocaleString("en-IN")}`;
}

function parseBudget(input) {
  const match = input.match(CURRENCY_REGEX);
  if (!match) return null;
  const amountRaw = toNumber(match[1]);
  if (!amountRaw) return null;
  let multiplier = 1;
  const unit = match[2] ? match[2].toLowerCase() : "";
  if (["l", "lac", "lakh", "lakhs"].includes(unit)) multiplier = 100000;
  if (["cr", "crore", "crores"].includes(unit)) multiplier = 10000000;
  const amount = Math.round(amountRaw * multiplier);
  return {
    type: "budget",
    normalized: {
      amount,
      display: formatInr(amount)
    }
  };
}

function parsePlot(input) {
  const dimMatch = input.match(DIMENSION_REGEX);
  if (dimMatch) {
    const width = parseFloat(dimMatch[1]);
    const depth = parseFloat(dimMatch[2]);
    const unit = /m/.test(input) && !/ft/.test(input) ? "m" : "ft";
    const widthM = unit === "ft" ? width * FT_TO_M : width;
    const depthM = unit === "ft" ? depth * FT_TO_M : depth;
    return {
      type: "plot",
      normalized: {
        width,
        depth,
        unit,
        width_m: parseFloat(widthM.toFixed(2)),
        depth_m: parseFloat(depthM.toFixed(2))
      }
    };
  }
  const areaMatch = input.match(AREA_REGEX);
  if (areaMatch) {
    const value = parseFloat(areaMatch[1]);
    const unitRaw = areaMatch[2].toLowerCase();
    const unit = unitRaw.includes("ft") ? "sqft" : "sqm";
    const sqm = unit === "sqft" ? value * SQFT_TO_SQM : value;
    return {
      type: "plot",
      normalized: {
        area: value,
        unit,
        area_sqm: parseFloat(sqm.toFixed(2))
      }
    };
  }
  return null;
}

function parseRooms(input) {
  const match = input.match(ROOM_REGEX);
  if (!match) return null;
  const valueRaw = match[1].toLowerCase();
  const count = WORD_NUMBER_MAP[valueRaw] || parseInt(valueRaw, 10);
  if (!count) return null;
  return {
    type: "rooms",
    normalized: { bhk: count }
  };
}

function parseFloors(input) {
  const match = input.match(FLOOR_REGEX);
  if (!match) return null;
  if (match[2]) {
    const upper = parseInt(match[2], 10);
    return { type: "floors", normalized: { floors: upper + 1 } };
  }
  if (match[4]) {
    const upper = parseInt(match[4], 10);
    return { type: "floors", normalized: { floors: upper + 1 } };
  }
  if (match[5]) {
    const floors = parseInt(match[5], 10);
    return { type: "floors", normalized: { floors } };
  }
  return null;
}

function parseYesNo(input) {
  const trimmed = input.trim().toLowerCase();
  if (YES_VARIANTS.includes(trimmed)) return { type: "yesno", normalized: { value: true } };
  if (NO_VARIANTS.includes(trimmed)) return { type: "yesno", normalized: { value: false } };
  return null;
}

export function normalizeInput(rawInput) {
  const raw = (rawInput || "").trim();
  const lowered = raw.toLowerCase();

  const budget = parseBudget(lowered);
  if (budget) return { raw, ...budget };

  const plot = parsePlot(lowered);
  if (plot) return { raw, ...plot };

  const rooms = parseRooms(lowered);
  if (rooms) return { raw, ...rooms };

  const floors = parseFloors(lowered);
  if (floors) return { raw, ...floors };

  const yesno = parseYesNo(lowered);
  if (yesno) return { raw, ...yesno };

  return {
    raw,
    type: "unknown",
    normalized: { value: lowered }
  };
}

export function formatPlotConfirmation(data) {
  if (!data) return null;
  if (data.width && data.depth) {
    const base = `${data.width} × ${data.depth} ${data.unit || "ft"}`;
    if (data.width_m && data.depth_m) {
      return `${base} (≈ ${data.width_m} × ${data.depth_m} m)`;
    }
    return base;
  }
  if (data.area) {
    const base = `${data.area} ${data.unit}`;
    if (data.area_sqm) {
      return `${base} (≈ ${data.area_sqm} sqm)`;
    }
    return base;
  }
  return null;
}

export function formatBudgetConfirmation(amount) {
  if (!amount) return null;
  return formatInr(amount);
}



