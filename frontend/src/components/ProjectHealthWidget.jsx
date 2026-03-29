import React, { useState, useEffect } from 'react';
import ProjectHealthPanel from './ProjectHealthPanel.jsx';

/**
 * Self-fetching wrapper around ProjectHealthPanel.
 * Drop this anywhere with a projectId and role prop.
 */
const ProjectHealthWidget = ({ projectId, role = 'homeowner', compact = false }) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (!projectId) { setLoading(false); return; }
    setLoading(true);
    fetch(`/buildhub/backend/api/ml/get_project_health.php?project_id=${projectId}`, {
      credentials: 'include',
      headers: { 'Cache-Control': 'no-cache' }
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) setData(res.data);
        else setError(res.message || 'No health data');
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false));
  }, [projectId]);

  if (loading) {
    return (
      <div style={{ padding: '12px', textAlign: 'center', color: '#9ca3af', fontSize: '13px' }}>
        🔄 Loading health score...
      </div>
    );
  }

  if (error || !data) {
    // Silently hide if no prediction exists yet
    return null;
  }

  return (
    <ProjectHealthPanel
      costRisk={data.cost_risk_level}
      timeRisk={data.time_risk_level}
      costProbability={data.cost_risk_probability}
      timeProbability={data.time_risk_probability}
      costFactors={data.cost_factors}
      timeFactors={data.time_factors}
      budget={data.budget}
      role={role}
      trendHistory={data.trend_history}
      projectId={projectId}
      compact={compact}
      aiDerivedMetrics={data.derived_metrics || null}
      shapExplanation={data.shap_explanation || null}
    />
  );
};

export default ProjectHealthWidget;
