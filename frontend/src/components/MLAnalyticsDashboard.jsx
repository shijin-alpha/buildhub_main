import React, { useState, useEffect } from 'react';
import { Chart, registerables } from 'chart.js';
import './MLAnalyticsDashboard.css';
import ProjectHealthWidget from './ProjectHealthWidget.jsx';

Chart.register(...registerables);

const MLAnalyticsDashboard = ({ projectId, userRole }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [analyticsData, setAnalyticsData] = useState(null);
    const [chartInstances, setChartInstances] = useState({});

    useEffect(() => {
        loadAnalytics();
        
        // Cleanup charts on unmount
        return () => {
            Object.values(chartInstances).forEach(chart => {
                if (chart) chart.destroy();
            });
        };
    }, [projectId]);

    const loadAnalytics = async () => {
        try {
            setLoading(true);
            setError(null);

            const endpoint = projectId 
                ? `/buildhub/backend/api/ml/get_project_analytics.php?project_id=${projectId}&_t=${Date.now()}`
                : `/buildhub/backend/api/ml/get_project_analytics.php?_t=${Date.now()}`;

            const response = await fetch(endpoint, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await response.json();

            if (data.success) {
                setAnalyticsData(data.data);
                // Wait for DOM to render before creating charts
                setTimeout(() => createCharts(data.data), 100);
            } else {
                setError(data.message || 'Failed to load analytics');
            }
        } catch (err) {
            setError('Error loading ML analytics: ' + err.message);
        } finally {
            setLoading(false);
        }
    };

    const createCharts = (data) => {
        // Destroy existing charts
        Object.values(chartInstances).forEach(chart => {
            if (chart) chart.destroy();
        });

        const newCharts = {};

        // Risk Prediction Accuracy Chart
        if (data.prediction && document.getElementById('riskPredictionChart')) {
            newCharts.riskPrediction = createRiskPredictionChart(data.prediction);
        }

        // Cost vs Actual Chart
        if (data.cost_analysis && document.getElementById('costAnalysisChart')) {
            newCharts.costAnalysis = createCostAnalysisChart(data.cost_analysis);
        }

        // Time Progress Chart
        if (data.time_analysis && document.getElementById('timeProgressChart')) {
            newCharts.timeProgress = createTimeProgressChart(data.time_analysis);
        }

        // Model Performance Chart
        if (data.model_performance && document.getElementById('modelPerformanceChart')) {
            newCharts.modelPerformance = createModelPerformanceChart(data.model_performance);
        }

        setChartInstances(newCharts);
    };

    const createRiskPredictionChart = (prediction) => {
        const ctx = document.getElementById('riskPredictionChart');
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                datasets: [{
                    label: 'Cost Risk',
                    data: [
                        prediction.cost_risk_probabilities?.Low || 0,
                        prediction.cost_risk_probabilities?.Medium || 0,
                        prediction.cost_risk_probabilities?.High || 0
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    title: {
                        display: true,
                        text: 'AI Risk Assessment',
                        font: { size: 16, weight: 'bold' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + (context.parsed * 100).toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    };

    const createCostAnalysisChart = (costData) => {
        const ctx = document.getElementById('costAnalysisChart');
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Predicted Budget', 'Actual Spent', 'Remaining'],
                datasets: [{
                    label: 'Amount (₹)',
                    data: [
                        costData.predicted_budget || 0,
                        costData.actual_spent || 0,
                        costData.remaining || 0
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Budget Analysis',
                        font: { size: 16, weight: 'bold' }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹' + context.parsed.y.toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value / 100000).toFixed(1) + 'L';
                            }
                        }
                    }
                }
            }
        });
    };

    const createTimeProgressChart = (timeData) => {
        const ctx = document.getElementById('timeProgressChart');
        if (!ctx) return null;

        const labels = timeData.timeline?.map(t => t.date) || [];
        const predicted = timeData.timeline?.map(t => t.predicted_progress) || [];
        const actual = timeData.timeline?.map(t => t.actual_progress) || [];

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Predicted Progress',
                        data: predicted,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Actual Progress',
                        data: actual,
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    title: {
                        display: true,
                        text: 'Progress Timeline',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    };

    const createModelPerformanceChart = (performance) => {
        const ctx = document.getElementById('modelPerformanceChart');
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Accuracy', 'Precision', 'Recall', 'F1 Score'],
                datasets: [
                    {
                        label: 'Cost Model',
                        data: [
                            performance.cost_model?.accuracy || 0,
                            performance.cost_model?.precision || 0,
                            performance.cost_model?.recall || 0,
                            performance.cost_model?.f1_score || 0
                        ],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        borderWidth: 2
                    },
                    {
                        label: 'Time Model',
                        data: [
                            performance.time_model?.accuracy || 0,
                            performance.time_model?.precision || 0,
                            performance.time_model?.recall || 0,
                            performance.time_model?.f1_score || 0
                        ],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15 }
                    },
                    title: {
                        display: true,
                        text: 'ML Model Performance',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    };

    if (loading) {
        return (
            <div className="ml-analytics-container">
                <div className="analytics-loading">
                    <div className="spinner"></div>
                    <p>Loading AI Analytics...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="ml-analytics-container">
                <div className="analytics-error">
                    <span className="error-icon">⚠️</span>
                    <p>{error}</p>
                    <button onClick={loadAnalytics} className="retry-btn">Retry</button>
                </div>
            </div>
        );
    }

    if (!analyticsData) {
        return (
            <div className="ml-analytics-container">
                <div className="analytics-empty">
                    <span className="empty-icon">📊</span>
                    <p>No analytics data available for this project</p>
                </div>
            </div>
        );
    }

    return (
        <div className="ml-analytics-container">
            <div className="analytics-header">
                <h2>🤖 AI-Powered Project Analytics</h2>
                <button onClick={loadAnalytics} className="refresh-btn">
                    <span>🔄</span> Refresh
                </button>
            </div>

            {/* Key Metrics Cards — replaced by unified health panel */}
            <div className="metrics-grid" style={{ marginBottom: '20px' }}>
                {/* Unified health panel replaces the two separate risk cards */}
                <div style={{ gridColumn: '1 / -1' }}>
                  <ProjectHealthWidget projectId={projectId} role={userRole === 'contractor' ? 'contractor' : 'homeowner'} compact={false} />
                </div>

                <div className="metric-card accuracy-card">
                    <div className="metric-icon">🎯</div>
                    <div className="metric-content">
                        <h3>Model Accuracy</h3>
                        <p className="metric-value">
                            {analyticsData.model_performance?.overall_accuracy?.toFixed(1) || 'N/A'}%
                        </p>
                        <span className="metric-subtitle">Combined models</span>
                    </div>
                </div>

                <div className="metric-card progress-card">
                    <div className="metric-icon">📈</div>
                    <div className="metric-content">
                        <h3>Project Progress</h3>
                        <p className="metric-value">
                            {analyticsData.time_analysis?.current_progress?.toFixed(1) || 0}%
                        </p>
                        <span className="metric-subtitle">
                            {analyticsData.time_analysis?.days_elapsed || 0} days elapsed
                        </span>
                    </div>
                </div>
            </div>

            {/* Charts Grid */}
            <div className="charts-grid">
                <div className="chart-card">
                    <canvas id="riskPredictionChart"></canvas>
                </div>

                <div className="chart-card">
                    <canvas id="costAnalysisChart"></canvas>
                </div>

                <div className="chart-card chart-wide">
                    <canvas id="timeProgressChart"></canvas>
                </div>

                <div className="chart-card">
                    <canvas id="modelPerformanceChart"></canvas>
                </div>
            </div>

            {/* Insights Section */}
            {analyticsData.insights && analyticsData.insights.length > 0 && (
                <div className="insights-section">
                    <h3>💡 AI Insights & Recommendations</h3>
                    <div className="insights-list">
                        {analyticsData.insights.map((insight, index) => (
                            <div key={index} className={`insight-item insight-${insight.type}`}>
                                <span className="insight-icon">
                                    {insight.type === 'warning' ? '⚠️' : 
                                     insight.type === 'success' ? '✅' : 'ℹ️'}
                                </span>
                                <div className="insight-content">
                                    <h4>{insight.title}</h4>
                                    <p>{insight.message}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

export default MLAnalyticsDashboard;
