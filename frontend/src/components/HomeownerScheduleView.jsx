import React, { useState, useEffect } from 'react';
import './HomeownerScheduleView.css';

/**
 * Homeowner Schedule View Component
 * 
 * Read-only display of project schedule for homeowners
 * Shows planned vs actual timeline with performance metrics
 * Backward compatible - gracefully handles projects without schedule data
 */

const HomeownerScheduleView = ({ projectId }) => {
    const [schedule, setSchedule] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchSchedule();
    }, [projectId]);

    const fetchSchedule = async () => {
        try {
            setLoading(true);
            const response = await fetch(
                `/buildhub/backend/api/project/get_schedule_summary.php?project_id=${projectId}`,
                { credentials: 'include' }
            );
            
            const data = await response.json();
            
            if (data.success) {
                setSchedule(data.data);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to load schedule data');
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'Not set';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    };

    const getStatusBadgeClass = (status) => {
        const statusMap = {
            'Completed': 'status-completed',
            'In Progress': 'status-in-progress',
            'Delayed': 'status-delayed',
            'Scheduled': 'status-scheduled',
            'Not Scheduled': 'status-not-scheduled'
        };
        return statusMap[status] || 'status-default';
    };

    if (loading) {
        return (
            <div className="homeowner-schedule-view loading">
                <div className="loading-spinner"></div>
                <p>Loading schedule information...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="homeowner-schedule-view error">
                <p>⚠️ {error}</p>
            </div>
        );
    }

    if (!schedule) {
        return null;
    }

    // If no schedule data exists, show minimal message
    if (schedule.schedule_status === 'Not Scheduled') {
        return (
            <div className="homeowner-schedule-view no-schedule">
                <div className="no-schedule-icon">📅</div>
                <h4>Schedule Not Yet Set</h4>
                <p>Your contractor will set the project schedule soon.</p>
            </div>
        );
    }

    return (
        <div className="homeowner-schedule-view">
            <div className="schedule-header">
                <h3>Project Schedule</h3>
                <span className={`status-badge ${getStatusBadgeClass(schedule.schedule_status)}`}>
                    {schedule.schedule_status}
                </span>
            </div>

            {schedule.message && (
                <div className={`schedule-message ${schedule.performance.is_delayed ? 'warning' : 'info'}`}>
                    {schedule.message}
                </div>
            )}

            <div className="schedule-grid">
                {/* Planned Schedule Card */}
                <div className="schedule-card planned-card">
                    <div className="card-header">
                        <h4>📋 Planned Schedule</h4>
                        {schedule.permissions.schedule_locked && (
                            <span className="locked-indicator" title="Schedule is locked">🔒</span>
                        )}
                    </div>
                    <div className="card-content">
                        {schedule.planned.is_set ? (
                            <>
                                <div className="date-row">
                                    <span className="label">Start Date:</span>
                                    <span className="value">{formatDate(schedule.planned.start_date)}</span>
                                </div>
                                <div className="date-row">
                                    <span className="label">End Date:</span>
                                    <span className="value">{formatDate(schedule.planned.end_date)}</span>
                                </div>
                                <div className="date-row highlight">
                                    <span className="label">Duration:</span>
                                    <span className="value">{schedule.planned.duration_days} days</span>
                                </div>
                            </>
                        ) : (
                            <p className="no-data">Planned schedule not yet set</p>
                        )}
                    </div>
                </div>

                {/* Actual Schedule Card */}
                <div className="schedule-card actual-card">
                    <div className="card-header">
                        <h4>✅ Actual Progress</h4>
                    </div>
                    <div className="card-content">
                        {schedule.actual.is_started ? (
                            <>
                                <div className="date-row">
                                    <span className="label">Started On:</span>
                                    <span className="value">{formatDate(schedule.actual.start_date)}</span>
                                </div>
                                <div className="date-row">
                                    <span className="label">
                                        {schedule.actual.is_completed ? 'Completed On:' : 'Expected Completion:'}
                                    </span>
                                    <span className="value">
                                        {schedule.actual.is_completed 
                                            ? formatDate(schedule.actual.end_date)
                                            : formatDate(schedule.planned.end_date)}
                                    </span>
                                </div>
                                {schedule.actual.duration_days && (
                                    <div className="date-row highlight">
                                        <span className="label">Actual Duration:</span>
                                        <span className="value">{schedule.actual.duration_days} days</span>
                                    </div>
                                )}
                            </>
                        ) : (
                            <p className="no-data">Work has not started yet</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Performance Metrics */}
            {schedule.performance.delay_days !== null && (
                <div className="performance-section">
                    <h4>Schedule Performance</h4>
                    <div className="performance-metrics">
                        <div className={`metric-card ${schedule.performance.is_delayed ? 'delayed' : 'on-time'}`}>
                            <div className="metric-icon">
                                {schedule.performance.is_delayed ? '⏰' : '✨'}
                            </div>
                            <div className="metric-content">
                                <span className="metric-label">Schedule Status</span>
                                <span className="metric-value">
                                    {schedule.performance.delay_days > 0 
                                        ? `${schedule.performance.delay_days} days behind`
                                        : schedule.performance.delay_days < 0
                                        ? `${Math.abs(schedule.performance.delay_days)} days ahead`
                                        : 'On schedule'}
                                </span>
                            </div>
                        </div>

                        {schedule.performance.time_overrun_percentage !== null && (
                            <div className="metric-card">
                                <div className="metric-icon">📊</div>
                                <div className="metric-content">
                                    <span className="metric-label">Time Overrun</span>
                                    <span className="metric-value">
                                        {schedule.performance.time_overrun_percentage > 0 ? '+' : ''}
                                        {schedule.performance.time_overrun_percentage.toFixed(1)}%
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Timeline Visualization */}
            {schedule.planned.is_set && schedule.actual.is_started && (
                <div className="timeline-section">
                    <h4>Timeline Comparison</h4>
                    <div className="timeline-bars">
                        <div className="timeline-bar planned-bar">
                            <div className="bar-label">Planned</div>
                            <div className="bar-visual" style={{ width: '100%' }}>
                                <span className="bar-duration">{schedule.planned.duration_days} days</span>
                            </div>
                        </div>
                        {schedule.actual.duration_days && (
                            <div className="timeline-bar actual-bar">
                                <div className="bar-label">Actual</div>
                                <div 
                                    className="bar-visual" 
                                    style={{ 
                                        width: `${Math.min((schedule.actual.duration_days / schedule.planned.duration_days) * 100, 150)}%`,
                                        background: schedule.performance.is_delayed ? '#e74c3c' : '#27ae60'
                                    }}
                                >
                                    <span className="bar-duration">{schedule.actual.duration_days} days</span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default HomeownerScheduleView;
