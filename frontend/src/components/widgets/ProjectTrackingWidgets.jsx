import React, { useState, useEffect } from 'react';


// Project Progress Chart Component
export const ProjectProgressChart = ({ project }) => {
  const [progressData, setProgressData] = useState({
    weeks: [],
    planned: [],
    actual: []
  });

  useEffect(() => {
    // Only use project data, no demo data
    if (project && project.progressData) {
      setProgressData({
        weeks: project.progressData.labels || [],
        planned: project.progressData.datasets?.[0]?.data || [],
        actual: project.progressData.datasets?.[1]?.data || []
      });
    } else {
      // Empty state when no data is available
      setProgressData({
        weeks: [],
        planned: [],
        actual: []
      });
    }
  }, [project]);

  // Check if we have any data to display
  if (!progressData.weeks.length) {
    return <div className="empty-state">No progress data available</div>;
  }

  return (
    <div className="progress-chart-container">
      <div className="chart-legend">
        <div className="legend-item">
          <div className="legend-color" style={{ backgroundColor: '#2563eb' }}></div>
          <span>Planned Progress</span>
        </div>
        <div className="legend-item">
          <div className="legend-color" style={{ backgroundColor: '#10b981' }}></div>
          <span>Actual Progress</span>
        </div>
      </div>
      
      <div className="simple-chart">
        {progressData.weeks.map((week, index) => (
          <div key={index} className="chart-column">
            <div className="chart-bars">
              <div 
                className="chart-bar planned" 
                style={{ height: `${progressData.planned[index]}%` }}
                title={`Planned: ${progressData.planned[index]}%`}
              ></div>
              <div 
                className="chart-bar actual" 
                style={{ height: `${progressData.actual[index]}%` }}
                title={`Actual: ${progressData.actual[index]}%`}
              ></div>
            </div>
            <div className="chart-label">{week}</div>
          </div>
        ))}
      </div>
    </div>
  );
};

// Project Timeline Component
export const ProjectTimeline = ({ project }) => {
  // Only use project timeline data, no demo data
  const timeline = project?.timeline || [];

  // Format date
  const formatDate = (dateString) => {
    const options = { month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
  };

  // Check if we have any timeline data to display
  if (!timeline.length) {
    return <div className="empty-state">No timeline data available</div>;
  }

  return (
    <div className="timeline-container">
      {timeline.map((phase, index) => (
        <div key={index} className={`timeline-item ${phase.status}`}>
          <div className="timeline-marker"></div>
          <div className="timeline-content">
            <h4 className="phase-name">{phase.phase}</h4>
            <div className="phase-dates">
              {formatDate(phase.startDate)} - {formatDate(phase.endDate)}
            </div>
            <div className="phase-status">
              <span className={`status-badge ${phase.status}`}>
                {phase.status === 'completed' ? 'Completed' : 
                 phase.status === 'in-progress' ? 'In Progress' : 'Upcoming'}
              </span>
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

// Budget Tracker Component
export const BudgetTracker = ({ project }) => {
  const [budgetData, setBudgetData] = useState(null);

  useEffect(() => {
    // Only use project data, no demo data
    if (project && project.budgetData) {
      setBudgetData(project.budgetData);
    } else {
      // Return null when no data is available
      setBudgetData(null);
    }
  }, [project]);

  if (!budgetData) return <div className="empty-state">No budget data available</div>;

  const spentPercentage = Math.round((budgetData.spent / budgetData.totalBudget) * 100);

  return (
    <div className="budget-tracker">
      <div className="budget-summary">
        <div className="budget-total">
          <h3>Total Budget</h3>
          <p className="budget-amount">${budgetData.totalBudget.toLocaleString()}</p>
        </div>
        
        <div className="budget-progress">
          <div className="progress-bar-container">
            <div 
              className="progress-bar" 
              style={{ width: `${spentPercentage}%` }}
            ></div>
          </div>
          <div className="budget-stats">
            <div className="stat">
              <span className="label">Spent</span>
              <span className="value">${budgetData.spent.toLocaleString()} ({spentPercentage}%)</span>
            </div>
            <div className="stat">
              <span className="label">Remaining</span>
              <span className="value">${budgetData.remaining.toLocaleString()}</span>
            </div>
          </div>
        </div>
      </div>

      <div className="budget-categories">
        <h3>Budget Breakdown</h3>
        {budgetData.categories.map((category, index) => {
          const categorySpentPercentage = Math.round((category.spent / category.allocated) * 100);
          
          return (
            <div key={index} className="category">
              <div className="category-header">
                <span className="category-name">{category.name}</span>
                <span className="category-figures">
                  <span className="spent">${category.spent.toLocaleString()}</span>
                  <span className="allocated">/${category.allocated.toLocaleString()}</span>
                </span>
              </div>
              <div className="category-progress-container">
                <div 
                  className="category-progress" 
                  style={{ width: `${categorySpentPercentage}%` }}
                ></div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};