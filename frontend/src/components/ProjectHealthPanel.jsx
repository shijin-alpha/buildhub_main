import React, { useState, useEffect } from 'react';
import './ProjectHealthPanel.css';

const LEVELS = { low: 0, medium: 1, high: 2 };

export const computeHealthScore = (costRisk, timeRisk) => {
  const c = LEVELS[(costRisk || 'low').toLowerCase()] ?? 0;
  const t = LEVELS[(timeRisk || 'low').toLowerCase()] ?? 0;
  const score = Math.round(((c * 0.55 + t * 0.45) / 2) * 100);
  let difficulty, color, emoji, subtitle;
  if (c === 0 && t === 0) {
    difficulty = 'Low Risk';  color = '#10b981'; emoji = '🟢';
    subtitle = 'Your project is on a healthy track';
  } else if (c + t <= 1) {
    difficulty = 'Moderate';  color = '#f59e0b'; emoji = '🟡';
    subtitle = 'Minor risks detected — manageable with planning';
  } else if (c + t === 2 && c !== 2 && t !== 2) {
    difficulty = 'High Risk'; color = '#f97316'; emoji = '🟠';
    subtitle = 'Multiple risk factors need attention';
  } else {
    difficulty = 'Critical';  color = '#ef4444'; emoji = '🔴';
    subtitle = 'Immediate action required to protect your project';
  }
  return { score, difficulty, color, emoji, subtitle, costLevel: c, timeLevel: t };
};

const getRiskTips = (costLevel, timeLevel, role) => {
  const tips = [];
  if (costLevel >= 1) tips.push(role === 'contractor'
    ? 'Review material quotes — prices may have shifted since estimate'
    : 'Keep 10-20% contingency budget ready for unexpected costs');
  if (costLevel === 2) tips.push(role === 'contractor'
    ? 'Break scope into phases to control cash flow'
    : 'Consider reducing design complexity or special features');
  if (timeLevel >= 1) tips.push(role === 'contractor'
    ? 'Add buffer milestones — flag delays early before they compound'
    : 'Plan for 1-3 months extra; avoid scheduling move-in too close to deadline');
  if (timeLevel === 2) tips.push(role === 'contractor'
    ? 'Confirm subcontractor availability now — late booking causes most delays'
    : 'Ask contractor for a weekly progress check-in to catch slippage early');
  if (tips.length === 0) {
    tips.push('Project looks healthy — maintain regular progress updates');
    tips.push('Keep communication open between all parties');
  }
  return tips;
};

const getDefaultChecklist = (costLevel, timeLevel, metrics) => {
  const items = [];
  const terrain = metrics?.terrain || '';
  const rainfall = metrics?.rainfall || '';
  const floodRisk = metrics?.flood_risk || 'low';
  if (floodRisk === 'high' || terrain === 'backwater') {
    items.push({ id: 'flood_insurance', label: 'Arrange flood insurance for the site', done: false });
    items.push({ id: 'drainage_plan', label: 'Get drainage & waterproofing plan from architect', done: false });
  }
  if (terrain === 'highland' || terrain === 'hilly') {
    items.push({ id: 'soil_test', label: 'Conduct soil stability / landslide risk assessment', done: false });
    items.push({ id: 'access_road', label: 'Confirm material delivery access road to site', done: false });
  }
  if (terrain === 'coastal') {
    items.push({ id: 'crz_clearance', label: 'Verify CRZ (Coastal Regulation Zone) clearance', done: false });
    items.push({ id: 'salt_materials', label: 'Specify corrosion-resistant materials for coastal climate', done: false });
  }
  if (rainfall === 'very_high' || rainfall === 'high') {
    items.push({ id: 'monsoon_schedule', label: 'Plan construction schedule around monsoon months', done: false });
    items.push({ id: 'waterproofing', label: 'Include waterproofing budget in estimate', done: false });
  }
  if (rainfall === 'low')
    items.push({ id: 'water_source', label: 'Confirm water source for construction (dry zone)', done: false });
  if (costLevel >= 1) items.push({ id: 'budget_review', label: 'Review and confirm budget allocation', done: false });
  if (costLevel === 2) items.push({ id: 'scope_reduce', label: 'Evaluate scope reduction options', done: false });
  if (timeLevel >= 1) items.push({ id: 'schedule_buffer', label: 'Add schedule buffer to timeline', done: false });
  if (timeLevel === 2) items.push({ id: 'subcontractor', label: 'Confirm subcontractor availability', done: false });
  items.push({ id: 'progress_update', label: 'Submit latest progress update', done: false });
  items.push({ id: 'inspector_review', label: 'Schedule next inspection', done: false });
  return items;
};

const estimateImpact = (budget, costLevel, timeLevel) => {
  const costPct = costLevel === 0 ? 0 : costLevel === 1 ? 0.12 : 0.22;
  const extraCost = budget ? Math.round(budget * costPct) : null;
  const extraWeeks = timeLevel === 0 ? 0 : timeLevel === 1 ? 4 : 12;
  return { extraCost, extraWeeks, costPct };
};

const Sparkline = ({ data, color }) => {
  if (!data || data.length < 2) return null;
  const w = 120, h = 32, pad = 4;
  const max = Math.max(...data, 1);
  const pts = data.map((v, i) => {
    const x = pad + (i / (data.length - 1)) * (w - pad * 2);
    const y = h - pad - ((v / max) * (h - pad * 2));
    return `${x},${y}`;
  }).join(' ');
  return (
    <svg width={w} height={h} style={{ display: 'block' }}>
      <polyline points={pts} fill="none" stroke={color} strokeWidth="2" strokeLinejoin="round" />
      {data.map((v, i) => {
        const x = pad + (i / (data.length - 1)) * (w - pad * 2);
        const y = h - pad - ((v / max) * (h - pad * 2));
        return <circle key={i} cx={x} cy={y} r="3" fill={color} />;
      })}
    </svg>
  );
};

const RiskPill = ({ level, label }) => {
  const bg    = level === 0 ? '#d1fae5' : level === 1 ? '#fef3c7' : '#fee2e2';
  const color = level === 0 ? '#065f46' : level === 1 ? '#92400e' : '#991b1b';
  const dot   = level === 0 ? '#10b981' : level === 1 ? '#f59e0b' : '#ef4444';
  return (
    <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, background: bg, color, padding: '3px 10px', borderRadius: 20, fontSize: 12, fontWeight: 700 }}>
      <span style={{ width: 7, height: 7, borderRadius: '50%', background: dot, display: 'inline-block' }} />
      {label}
    </span>
  );
};

const ShapWaterfall = ({ items, title, color }) => {
  if (!items || items.length === 0) return null;
  const maxAbs = Math.max(...items.map(d => Math.abs(d.shap_value)), 0.001);
  const FEATURE_LABELS = {
    budget_per_sqft: 'Budget/sqft',
    building_size_sqft: 'Building size',
    num_floors: 'Floors',
    planned_duration_months: 'Planned duration',
    design_complexity_score: 'Design complexity',
    customization_level: 'Customisation',
    site_difficulty_score: 'Site difficulty',
    development_constraint_level: 'Site constraints',
    kerala_district_code: 'District',
    construction_start_month: 'Start month',
    monsoon_exposure_score: 'Monsoon exposure',
    district_risk_tier: 'District risk tier',
    topography_code: 'Topography',
    plot_shape_code: 'Plot shape',
    num_bedrooms: 'Bedrooms',
    num_bathrooms: 'Bathrooms',
    total_rooms: 'Total rooms',
    budget_amount: 'Budget amount',
    plot_size_sqft: 'Plot size',
    terrain_code: 'Terrain',
    rainfall_code: 'Rainfall',
    flood_risk_code: 'Flood risk',
    effective_monsoon_score: 'Eff. monsoon',
  };
  return (
    <div style={{ marginBottom: 12 }}>
      <div style={{ fontSize: 11, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '0.6px', marginBottom: 8 }}>{title}</div>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 5 }}>
        {items.slice(0, 6).map((d, i) => {
          const pct = Math.round((Math.abs(d.shap_value) / maxAbs) * 100);
          const positive = d.shap_value > 0;
          const barColor = positive ? '#ef4444' : '#10b981';
          const label = FEATURE_LABELS[d.feature] || d.feature.replace(/_/g, ' ');
          return (
            <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <div style={{ width: 110, fontSize: 11, color: '#374151', textAlign: 'right', flexShrink: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={label}>{label}</div>
              <div style={{ flex: 1, height: 14, background: '#f1f5f9', borderRadius: 4, overflow: 'hidden', position: 'relative' }}>
                <div style={{ position: 'absolute', [positive ? 'left' : 'right']: 0, width: `${pct}%`, height: '100%', background: barColor, borderRadius: 4, transition: 'width 0.4s ease' }} />
              </div>
              <div style={{ width: 38, fontSize: 10, fontWeight: 700, color: barColor, textAlign: 'left', flexShrink: 0 }}>
                {positive ? '+' : ''}{d.shap_value.toFixed(3)}
              </div>
            </div>
          );
        })}
      </div>
      <div style={{ marginTop: 6, display: 'flex', gap: 12, fontSize: 10, color: '#9ca3af' }}>
        <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}><span style={{ width: 10, height: 10, background: '#ef4444', borderRadius: 2, display: 'inline-block' }} /> increases risk</span>
        <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}><span style={{ width: 10, height: 10, background: '#10b981', borderRadius: 2, display: 'inline-block' }} /> reduces risk</span>
      </div>
    </div>
  );
};

const ProjectHealthPanel = ({
  costRisk, timeRisk, costProbability, timeProbability,
  costFactors, timeFactors, budget, role = 'homeowner',
  trendHistory, projectId, onChecklistChange, compact = false,
  riskReductionSuggestions, aiDerivedMetrics, shapExplanation,
}) => {
  const health = computeHealthScore(costRisk, timeRisk);
  const tips   = getRiskTips(health.costLevel, health.timeLevel, role);
  const impact = estimateImpact(budget, health.costLevel, health.timeLevel);
  const [checklist, setChecklist] = useState(() =>
    getDefaultChecklist(health.costLevel, health.timeLevel, aiDerivedMetrics)
  );
  const [expanded, setExpanded] = useState(!compact);
  const [fetchedTrend, setFetchedTrend] = useState(null);

  useEffect(() => {
    setChecklist(getDefaultChecklist(health.costLevel, health.timeLevel, aiDerivedMetrics));
  }, [costRisk, timeRisk, aiDerivedMetrics]); // eslint-disable-line

  useEffect(() => {
    if (!projectId || trendHistory) return;
    fetch(`/buildhub/backend/api/ml/get_project_trend.php?project_id=${projectId}`, { credentials: 'include' })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success' && Array.isArray(res.data) && res.data.length >= 2)
          setFetchedTrend(res.data);
      })
      .catch(() => {});
  }, [projectId]); // eslint-disable-line

  const toggleItem = (id) => {
    const updated = checklist.map(item => item.id === id ? { ...item, done: !item.done } : item);
    setChecklist(updated);
    if (onChecklistChange) onChecklistChange(updated);
  };

  const confidence = Math.round(
    ((costProbability || 0.5) * 0.55 + (timeProbability || 0.5) * 0.45) * 100
  );

  if (compact) {
    return (
      <button className="php-badge" style={{ '--badge-color': health.color }}
        onClick={() => setExpanded(true)} title="Click to see full health report">
        {health.emoji} {health.difficulty}
        {expanded && (
          <ProjectHealthPanel costRisk={costRisk} timeRisk={timeRisk}
            costProbability={costProbability} timeProbability={timeProbability}
            costFactors={costFactors} timeFactors={timeFactors}
            budget={budget} role={role} trendHistory={trendHistory}
            projectId={projectId} onChecklistChange={onChecklistChange}
            riskReductionSuggestions={riskReductionSuggestions}
            shapExplanation={shapExplanation} compact={false} />
        )}
      </button>
    );
  }

  const allFactors = [
    ...(costFactors || []).slice(0, 2).map(f => ({ text: f, type: 'cost' })),
    ...(timeFactors || []).slice(0, 2).map(f => ({ text: f, type: 'time' })),
  ].slice(0, 3);

  const trend = trendHistory || fetchedTrend;
  const accentMap = { 'Low Risk': 'linear-gradient(90deg,#10b981,#34d399)', 'Moderate': 'linear-gradient(90deg,#f59e0b,#fbbf24)', 'High Risk': 'linear-gradient(90deg,#f97316,#fb923c)', 'Critical': 'linear-gradient(90deg,#ef4444,#f87171)' };

  return (
    <div className="php-panel" style={{ '--panel-color': health.color }}>
      <div style={{ height: 4, background: accentMap[health.difficulty] || accentMap['Moderate'], borderRadius: '12px 12px 0 0', margin: '-1px -1px 0' }} />

      {/* Header */}
      <div className="php-header" style={{ padding: '20px 20px 16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div style={{ width: 48, height: 48, borderRadius: 14, background: `${health.color}18`, border: `2px solid ${health.color}40`, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 22 }}>
              {health.emoji}
            </div>
            <div>
              <div style={{ fontSize: 11, fontWeight: 600, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.8px', marginBottom: 2 }}>Project Health</div>
              <div style={{ fontSize: 20, fontWeight: 800, color: health.color, lineHeight: 1.1 }}>{health.difficulty}</div>
              <div style={{ fontSize: 12, color: '#6b7280', marginTop: 2 }}>{health.subtitle}</div>
            </div>
          </div>
          <div style={{ position: 'relative', width: 64, height: 64, flexShrink: 0 }}>
            <svg viewBox="0 0 36 36" width="64" height="64">
              <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" strokeWidth="3.5" />
              <circle cx="18" cy="18" r="15.9" fill="none" stroke={health.color} strokeWidth="3.5"
                strokeDasharray={`${health.score} 100`} strokeLinecap="round" transform="rotate(-90 18 18)"
                style={{ transition: 'stroke-dasharray 0.6s ease' }} />
            </svg>
            <div style={{ position: 'absolute', inset: 0, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
              <span style={{ fontSize: 15, fontWeight: 800, color: '#1f2937', lineHeight: 1 }}>{health.score}</span>
              <span style={{ fontSize: 9, color: '#9ca3af', fontWeight: 600 }}>/ 100</span>
            </div>
          </div>
        </div>
        <div style={{ marginTop: 14, display: 'flex', alignItems: 'center', gap: 10 }}>
          <span style={{ fontSize: 11, color: '#9ca3af', fontWeight: 600, whiteSpace: 'nowrap' }}>AI Confidence</span>
          <div style={{ flex: 1, height: 6, background: '#f1f5f9', borderRadius: 4, overflow: 'hidden' }}>
            <div style={{ height: '100%', width: `${confidence}%`, background: health.color, borderRadius: 4, transition: 'width 0.6s ease' }} />
          </div>
          <span style={{ fontSize: 12, fontWeight: 700, color: health.color, minWidth: 32, textAlign: 'right' }}>{confidence}%</span>
        </div>
      </div>

      {/* Risk sub-scores */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, padding: '0 20px 16px' }}>
        {[{ icon: '💰', label: 'Budget Risk', level: health.costLevel, risk: costRisk },
          { icon: '⏰', label: 'Timeline Risk', level: health.timeLevel, risk: timeRisk }].map(({ icon, label, level, risk }) => (
          <div key={label} style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 10, padding: '12px 14px', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <span style={{ fontSize: 13, color: '#374151', fontWeight: 600 }}>{icon} {label}</span>
            <RiskPill level={level} label={risk || 'Low'} />
          </div>
        ))}
      </div>

      {/* Location Climate Profile */}
      {aiDerivedMetrics?.location && (
        <div style={{ margin: '0 20px 16px', background: 'linear-gradient(135deg,#f0fdf4,#ecfdf5)', border: '1px solid #bbf7d0', borderRadius: 12, padding: '14px 16px' }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#065f46', textTransform: 'uppercase', letterSpacing: '0.7px', marginBottom: 10 }}>📍 Location Climate Profile</div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(100px,1fr))', gap: 10 }}>
            <div>
              <div style={{ fontSize: 10, color: '#6b7280', fontWeight: 600, marginBottom: 3 }}>LOCATION</div>
              <div style={{ fontSize: 13, fontWeight: 700, color: '#1f2937' }}>{aiDerivedMetrics.location}</div>
            </div>
            {aiDerivedMetrics.terrain && (
              <div>
                <div style={{ fontSize: 10, color: '#6b7280', fontWeight: 600, marginBottom: 3 }}>TERRAIN</div>
                <span style={{ fontSize: 12, fontWeight: 600, background: '#dbeafe', color: '#1e40af', padding: '2px 8px', borderRadius: 6 }}>
                  {aiDerivedMetrics.terrain === 'highland' ? '🏔 Highland' : aiDerivedMetrics.terrain === 'hilly' ? '⛰ Hilly' : aiDerivedMetrics.terrain === 'coastal' ? '🌊 Coastal' : aiDerivedMetrics.terrain === 'backwater' ? '🛶 Backwater' : aiDerivedMetrics.terrain === 'midland' ? '🌿 Midland' : '🌾 Flat'}
                </span>
              </div>
            )}
            {aiDerivedMetrics.rainfall && (
              <div>
                <div style={{ fontSize: 10, color: '#6b7280', fontWeight: 600, marginBottom: 3 }}>RAINFALL</div>
                <span style={{ fontSize: 12, fontWeight: 600, background: aiDerivedMetrics.rainfall === 'very_high' ? '#dbeafe' : '#f0fdf4', color: aiDerivedMetrics.rainfall === 'very_high' ? '#1e40af' : '#166534', padding: '2px 8px', borderRadius: 6 }}>
                  {aiDerivedMetrics.rainfall === 'very_high' ? '🌧 Very High' : aiDerivedMetrics.rainfall === 'high' ? '🌦 High' : aiDerivedMetrics.rainfall === 'moderate' ? '🌤 Moderate' : '☀ Low'}
                </span>
              </div>
            )}
            {aiDerivedMetrics.flood_risk && (
              <div>
                <div style={{ fontSize: 10, color: '#6b7280', fontWeight: 600, marginBottom: 3 }}>FLOOD RISK</div>
                <span style={{ fontSize: 12, fontWeight: 600, background: aiDerivedMetrics.flood_risk === 'high' ? '#fee2e2' : aiDerivedMetrics.flood_risk === 'moderate' ? '#fef3c7' : '#d1fae5', color: aiDerivedMetrics.flood_risk === 'high' ? '#991b1b' : aiDerivedMetrics.flood_risk === 'moderate' ? '#92400e' : '#065f46', padding: '2px 8px', borderRadius: 6 }}>
                  {aiDerivedMetrics.flood_risk === 'high' ? '🔴 High' : aiDerivedMetrics.flood_risk === 'moderate' ? '🟡 Moderate' : '🟢 Low'}
                </span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Estimated Impact */}
      {(impact.extraCost > 0 || impact.extraWeeks > 0 || aiDerivedMetrics?.budget_per_sqft > 0 || aiDerivedMetrics?.expected_duration > 0) && (
        <div style={{ margin: '0 20px 16px', background: '#fafafa', border: '1px solid #e5e7eb', borderRadius: 12, padding: '14px 16px' }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px', marginBottom: 10 }}>📊 Estimated Impact</div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit,minmax(110px,1fr))', gap: 10 }}>
            {impact.extraCost > 0 && (
              <div>
                <div style={{ fontSize: 10, color: '#9ca3af', fontWeight: 600, marginBottom: 2 }}>EXTRA COST</div>
                <div style={{ fontSize: 15, fontWeight: 800, color: health.color }}>~Rs.{impact.extraCost.toLocaleString('en-IN')}</div>
                <div style={{ fontSize: 10, color: '#9ca3af' }}>{Math.round(impact.costPct * 100)}% overrun</div>
              </div>
            )}
            {impact.extraWeeks > 0 && (
              <div>
                <div style={{ fontSize: 10, color: '#9ca3af', fontWeight: 600, marginBottom: 2 }}>EXTRA TIME</div>
                <div style={{ fontSize: 15, fontWeight: 800, color: health.color }}>~{impact.extraWeeks} weeks</div>
              </div>
            )}
            {aiDerivedMetrics?.budget_per_sqft > 0 && (
              <div>
                <div style={{ fontSize: 10, color: '#9ca3af', fontWeight: 600, marginBottom: 2 }}>AI BUDGET/SQFT</div>
                <div style={{ fontSize: 15, fontWeight: 800, color: '#6366f1' }}>Rs.{Math.round(aiDerivedMetrics.budget_per_sqft).toLocaleString('en-IN')}</div>
                <div style={{ fontSize: 10, color: '#9ca3af' }}>per sq ft</div>
              </div>
            )}
            {aiDerivedMetrics?.expected_duration > 0 && (
              <div>
                <div style={{ fontSize: 10, color: '#9ca3af', fontWeight: 600, marginBottom: 2 }}>EXPECTED DURATION</div>
                <div style={{ fontSize: 15, fontWeight: 800, color: '#6366f1' }}>{aiDerivedMetrics.expected_duration} mo</div>
                {aiDerivedMetrics.planned_duration > 0 && aiDerivedMetrics.planned_duration !== aiDerivedMetrics.expected_duration && (
                  <div style={{ fontSize: 10, color: '#9ca3af' }}>planned: {aiDerivedMetrics.planned_duration} mo</div>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Top Risk Factors */}
      {allFactors.length > 0 && (
        <div style={{ margin: '0 20px 16px' }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px', marginBottom: 8 }}>🔍 Top Risk Factors</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {allFactors.map((f, i) => (
              <div key={i} style={{ display: 'flex', alignItems: 'flex-start', gap: 10, background: f.type === 'cost' ? '#fffbeb' : '#eff6ff', border: `1px solid ${f.type === 'cost' ? '#fde68a' : '#bfdbfe'}`, borderRadius: 8, padding: '9px 12px' }}>
                <span style={{ fontSize: 14, flexShrink: 0, marginTop: 1, background: f.type === 'cost' ? '#fef3c7' : '#dbeafe', borderRadius: 6, padding: '2px 6px' }}>
                  {f.type === 'cost' ? '💰' : '⏰'}
                </span>
                <span style={{ fontSize: 13, color: '#374151', lineHeight: 1.4 }}>{f.text}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Trend Sparkline */}
      {trend && trend.length >= 2 && (
        <div style={{ margin: '0 20px 16px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 12, padding: '12px 14px' }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px', marginBottom: 8 }}>📈 Risk Trend</div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <Sparkline data={trend.map(h => h.score)} color={health.color} />
            <div style={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
              {trend.map((h, i) => <span key={i} style={{ fontSize: 10, color: '#9ca3af' }}>{h.label}</span>)}
            </div>
          </div>
        </div>
      )}

      {/* How to Improve */}
      <div style={{ margin: '0 20px 16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px' }}>💡 How to Improve</div>
          <span style={{ fontSize: 10, fontWeight: 600, background: '#f1f5f9', color: '#64748b', padding: '2px 8px', borderRadius: 10 }}>
            {role === 'contractor' ? 'For Contractor' : 'For Homeowner'}
          </span>
        </div>
        {riskReductionSuggestions && riskReductionSuggestions.length > 0 ? (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            {riskReductionSuggestions.map((s, i) => {
              const iconMap = { month: '📅', budget: '💰', timeline: '⏰', combined: '🔧' };
              return (
                <div key={i} style={{ display: 'flex', alignItems: 'flex-start', gap: 10, background: s.type === 'month' ? '#eff6ff' : '#f0fdf4', border: `1px solid ${s.type === 'month' ? '#bfdbfe' : '#bbf7d0'}`, borderRadius: 10, padding: '10px 12px' }}>
                  <span style={{ fontSize: 16, flexShrink: 0 }}>{iconMap[s.type] || '💡'}</span>
                  <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 13, color: '#1f2937', lineHeight: 1.4 }}>{s.suggestion}</div>
                    <div style={{ marginTop: 5, display: 'flex', alignItems: 'center', gap: 6 }}>
                      <span style={{ fontSize: 11, fontWeight: 700, padding: '2px 8px', borderRadius: 8, background: s.new_risk === 'Low' ? '#d1fae5' : s.new_risk === 'Medium' ? '#fef3c7' : '#fee2e2', color: s.new_risk === 'Low' ? '#065f46' : s.new_risk === 'Medium' ? '#92400e' : '#991b1b' }}>
                        → {s.new_risk} risk
                      </span>
                      {s.score_delta > 0 && <span style={{ fontSize: 11, color: '#10b981', fontWeight: 600 }}>-{s.score_delta} pts</span>}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {tips.map((tip, i) => (
              <div key={i} style={{ display: 'flex', alignItems: 'flex-start', gap: 8, background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, padding: '9px 12px' }}>
                <span style={{ color: health.color, fontSize: 14, flexShrink: 0 }}>→</span>
                <span style={{ fontSize: 13, color: '#374151', lineHeight: 1.4 }}>{tip}</span>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Action Checklist */}
      <div style={{ margin: '0 20px 16px' }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px' }}>✅ Action Checklist</div>
          <span style={{ fontSize: 11, fontWeight: 600, background: '#f1f5f9', color: '#64748b', padding: '2px 8px', borderRadius: 10 }}>
            {checklist.filter(i => i.done).length}/{checklist.length} done
          </span>
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
          {checklist.map(item => (
            <label key={item.id} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '9px 12px', borderRadius: 8, cursor: 'pointer', background: item.done ? '#f0fdf4' : '#fafafa', border: `1px solid ${item.done ? '#bbf7d0' : '#e5e7eb'}`, transition: 'all 0.15s ease' }}>
              <input type="checkbox" checked={item.done} onChange={() => toggleItem(item.id)}
                style={{ width: 15, height: 15, accentColor: '#10b981', cursor: 'pointer', flexShrink: 0 }} />
              <span style={{ fontSize: 13, color: item.done ? '#6b7280' : '#374151', textDecoration: item.done ? 'line-through' : 'none', lineHeight: 1.4 }}>
                {item.label}
              </span>
            </label>
          ))}
        </div>
      </div>

      {/* SHAP Explanation */}
      {shapExplanation && (shapExplanation.cost?.length > 0 || shapExplanation.time?.length > 0) && (
        <div style={{ margin: '0 20px 16px', background: '#fafafa', border: '1px solid #e5e7eb', borderRadius: 12, padding: '14px 16px' }}>
          <div style={{ fontSize: 11, fontWeight: 700, color: '#374151', textTransform: 'uppercase', letterSpacing: '0.7px', marginBottom: 12 }}>🔬 Why this prediction? (SHAP)</div>
          <ShapWaterfall items={shapExplanation.cost} title="Cost overrun drivers" color="#f59e0b" />
          <ShapWaterfall items={shapExplanation.time} title="Time delay drivers" color="#6366f1" />
        </div>
      )}

      {/* Footer */}
      <div style={{ margin: '0 20px 20px', padding: '10px 14px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 8, fontSize: 11, color: '#9ca3af', textAlign: 'center', lineHeight: 1.5 }}>
        🤖 Based on 1,000+ similar construction projects · Predictions may vary with site conditions
      </div>
    </div>
  );
};

export default ProjectHealthPanel;
