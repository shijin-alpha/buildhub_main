import React, { useState, useEffect } from 'react';
import axios from 'axios';

/**
 * SCHEDULE TRACKING PANEL
 * Backward-compatible component for displaying and managing project schedules
 * Supports both contractor input and homeowner read-only views
 */

const ScheduleTrackingPanel = ({ projectId, userRole, userId }) => {
    const [scheduleData, setScheduleData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [editMode, setEditMode] = useState(false);
    
    // Form state for planned dates
    const [plannedStartDate, setPlannedStartDate] = useState('');
    const [plannedEndDate, setPlannedEndDate] = useState('');
    const [actualStartDate, setActualStartDate] = useState('');
    const [actualEndDate, setActualEndDate] = useState('');
    
    const API_BASE_URL = 'http://localhost/backend/api';

    // Fetch schedule data
    useEffect(() => {
        fetchScheduleData();
    }, [projectId]);

    const fetchScheduleData = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`${API_BASE_URL}/schedule_tracking.php`, {
                params: { project_id: projectId }
            });
            
            if (response.data.success) {
                setScheduleData(response.data.data);
                const project = response.data.data.project;
                setPlannedStartDate(project.planned_start_date || '');
                setPlannedEndDate(project.planned_end_date || '');
                setActualStartDate(project.actual_start_date || '');
                setActualEndDate(project.actual_end_date || '');
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError('Failed to load schedule data');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    // Update planned dates (contractor only)
    const handleUpdatePlannedDates = async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'update_planned_dates');
            formData.append('project_id', projectId);
            formData.append('planned_start_date', plannedStartDate);
            formData.append('planned_end_date', plannedEndDate);
            
            const response = await axios.post(`${API_BASE_URL}/schedule_tracking.php`, formData);
            
            if (response.data.success) {
                alert('Planned dates updated successfully');
                setEditMode(false);
                fetchScheduleData();
            } else {
                alert(response.data.message);
            }
        } catch (err) {
            alert('Failed to update planned dates');
            console.error(err);
        }
    };

    // Update actual start date (contractor only)
    const handleUpdateActualStart = async () => {
        if (!confirm('Recording actual start date will lock planned dates. Continue?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_actual_start');
            formData.append('project_id', projectId);
            formData.append('actual_start_date', actualStartDate);
            
            const response = await axios.post(`${API_BASE_URL}/schedule_tracking.php`, formData);
            
            if (response.data.success) {
                alert('Actual start date recorded. Planned dates are now locked.');
                fetchScheduleData();
            } else {
                alert(response.data.message);
            }
        } catch (err) {
            alert('Failed to update actual start date');
            console.error(err);
        }
    };

    // Update actual end date (contractor only)
    const handleUpdateActualEnd = async () => {
        if (!confirm('This will mark the project as completed. Continue?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_actual_end');
            formData.append('project_id', projectId);
            formData.append('actual_end_date', actualEndDate);
            
            const response = await axios.post(`${API_BASE_URL}/schedule_tracking.php`, formData);
            
            if (response.data.success) {
                alert('Project completion recorded successfully');
                fetchScheduleData();
            } else {
                alert(response.data.message);
            }
        } catch (err) {
            alert('Failed to update actual end date');
            console.error(err);
        }
    };

    // Calculate planned duration
    const calculateDuration = (startDate, endDate) => {
        if (!startDate || !endDate) return null;
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    };

    // Format date for display
    const formatDate = (dateString) => {
        if (!dateString) return 'Not set';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    if (loading) {
        return <div className="schedule-panel loading">Loading schedule data...</div>;
    }

    if (error) {
        return <div className="schedule-panel error">Error: {error}</div>;
    }

    const { project, delay_days, is_delayed, is_early } = scheduleData;
    const isContractor = userRole === 'contractor';
    const plannedDuration = calculateDuration(project.planned_start_date, project.planned_end_date);
    const actualDuration = calculateDuration(project.actual_start_date, project.actual_end_date);

    return (
        <div className="schedule-tracking-panel">
            <style>{`
                .schedule-tracking-panel {
                    background: #ffffff;
                    border-radius: 8px;
                    padding: 24px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    margin: 20px 0;
                }
                
                .schedule-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 24px;
                    border-bottom: 2px solid #e0e0e0;
                    padding-bottom: 16px;
                }
                
                .schedule-header h3 {
                    margin: 0;
                    color: #333;
                    font-size: 20px;
                }
                
                .schedule-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 20px;
                    margin-bottom: 24px;
                }
                
                .schedule-card {
                    background: #f8f9fa;
                    border-radius: 6px;
                    padding: 16px;
                    border-left: 4px solid #007bff;
                }
                
                .schedule-card.planned {
                    border-left-color: #17a2b8;
                }
                
                .schedule-card.actual {
                    border-left-color: #28a745;
                }
                
                .schedule-card.overrun {
                    border-left-color: #dc3545;
                }
                
                .schedule-card.early {
                    border-left-color: #28a745;
                }
                
                .schedule-card h4 {
                    margin: 0 0 12px 0;
                    color: #555;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .schedule-value {
                    font-size: 18px;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                }
                
                .schedule-label {
                    font-size: 12px;
                    color: #666;
                    margin-top: 4px;
                }
                
                .schedule-form {
                    background: #f0f8ff;
                    border-radius: 6px;
                    padding: 20px;
                    margin-bottom: 20px;
                }
                
                .form-group {
                    margin-bottom: 16px;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 6px;
                    font-weight: 500;
                    color: #333;
                }
                
                .form-group input {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                }
                
                .form-group input:disabled {
                    background: #e9ecef;
                    cursor: not-allowed;
                }
                
                .button-group {
                    display: flex;
                    gap: 10px;
                    margin-top: 16px;
                }
                
                .btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                
                .btn-primary {
                    background: #007bff;
                    color: white;
                }
                
                .btn-primary:hover {
                    background: #0056b3;
                }
                
                .btn-success {
                    background: #28a745;
                    color: white;
                }
                
                .btn-success:hover {
                    background: #218838;
                }
                
                .btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                
                .btn-secondary:hover {
                    background: #5a6268;
                }
                
                .alert {
                    padding: 12px 16px;
                    border-radius: 4px;
                    margin-bottom: 16px;
                }
                
                .alert-warning {
                    background: #fff3cd;
                    border: 1px solid #ffc107;
                    color: #856404;
                }
                
                .alert-info {
                    background: #d1ecf1;
                    border: 1px solid #17a2b8;
                    color: #0c5460;
                }
                
                .alert-success {
                    background: #d4edda;
                    border: 1px solid #28a745;
                    color: #155724;
                }
                
                .alert-danger {
                    background: #f8d7da;
                    border: 1px solid #dc3545;
                    color: #721c24;
                }
                
                .locked-badge {
                    display: inline-block;
                    background: #ffc107;
                    color: #000;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: 600;
                }
            `}</style>

            <div className="schedule-header">
                <h3>📅 Project Schedule Tracking</h3>
                {isContractor && !project.planned_dates_locked && (
                    <button 
                        className="btn btn-primary"
                        onClick={() => setEditMode(!editMode)}
                    >
                        {editMode ? 'Cancel' : 'Edit Planned Dates'}
                    </button>
                )}
            </div>

            {project.planned_dates_locked && (
                <div className="alert alert-warning">
                    <strong>🔒 Planned dates are locked</strong> - Actual start date has been recorded
                </div>
            )}

            {is_delayed && (
                <div className="alert alert-danger">
                    <strong>⚠️ Project Delayed</strong> - {delay_days} days behind schedule
                </div>
            )}

            {is_early && (
                <div className="alert alert-success">
                    <strong>✅ Ahead of Schedule</strong> - Completed {Math.abs(delay_days)} days early
                </div>
            )}

            {/* Contractor Edit Form */}
            {isContractor && editMode && !project.planned_dates_locked && (
                <div className="schedule-form">
                    <h4>Set Planned Schedule</h4>
                    <div className="form-group">
                        <label>Planned Start Date</label>
                        <input 
                            type="date" 
                            value={plannedStartDate}
                            onChange={(e) => setPlannedStartDate(e.target.value)}
                        />
                    </div>
                    <div className="form-group">
                        <label>Planned End Date</label>
                        <input 
                            type="date" 
                            value={plannedEndDate}
                            onChange={(e) => setPlannedEndDate(e.target.value)}
                        />
                    </div>
                    <div className="button-group">
                        <button className="btn btn-primary" onClick={handleUpdatePlannedDates}>
                            Save Planned Dates
                        </button>
                        <button className="btn btn-secondary" onClick={() => setEditMode(false)}>
                            Cancel
                        </button>
                    </div>
                </div>
            )}

            {/* Schedule Display Grid */}
            <div className="schedule-grid">
                {/* Planned Schedule */}
                <div className="schedule-card planned">
                    <h4>Planned Schedule</h4>
                    <div className="schedule-value">{formatDate(project.planned_start_date)}</div>
                    <div className="schedule-label">Start Date</div>
                    <div className="schedule-value" style={{marginTop: '12px'}}>
                        {formatDate(project.planned_end_date)}
                    </div>
                    <div className="schedule-label">End Date</div>
                    {plannedDuration && (
                        <div style={{marginTop: '12px', fontSize: '14px', color: '#666'}}>
                            Duration: {plannedDuration} days
                        </div>
                    )}
                </div>

                {/* Actual Schedule */}
                <div className="schedule-card actual">
                    <h4>Actual Schedule</h4>
                    <div className="schedule-value">{formatDate(project.actual_start_date)}</div>
                    <div className="schedule-label">Start Date</div>
                    <div className="schedule-value" style={{marginTop: '12px'}}>
                        {formatDate(project.actual_end_date)}
                    </div>
                    <div className="schedule-label">End Date</div>
                    {actualDuration && (
                        <div style={{marginTop: '12px', fontSize: '14px', color: '#666'}}>
                            Duration: {actualDuration} days
                        </div>
                    )}
                </div>

                {/* Performance Metrics */}
                {project.actual_time_overrun_percentage !== null && (
                    <div className={`schedule-card ${project.actual_time_overrun_percentage > 0 ? 'overrun' : 'early'}`}>
                        <h4>Time Performance</h4>
                        <div className="schedule-value">
                            {project.actual_time_overrun_percentage > 0 ? '+' : ''}
                            {project.actual_time_overrun_percentage}%
                        </div>
                        <div className="schedule-label">Time Overrun</div>
                        {delay_days !== null && (
                            <div style={{marginTop: '12px', fontSize: '14px', color: '#666'}}>
                                {delay_days > 0 ? `${delay_days} days late` : `${Math.abs(delay_days)} days early`}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Contractor Actions */}
            {isContractor && (
                <div className="schedule-form">
                    <h4>Record Actual Dates</h4>
                    
                    {!project.actual_start_date && (
                        <div className="form-group">
                            <label>Actual Start Date</label>
                            <input 
                                type="date" 
                                value={actualStartDate}
                                onChange={(e) => setActualStartDate(e.target.value)}
                            />
                            <button 
                                className="btn btn-success" 
                                style={{marginTop: '10px'}}
                                onClick={handleUpdateActualStart}
                                disabled={!actualStartDate}
                            >
                                Record Actual Start
                            </button>
                        </div>
                    )}
                    
                    {project.actual_start_date && !project.actual_end_date && (
                        <div className="form-group">
                            <label>Actual End Date</label>
                            <input 
                                type="date" 
                                value={actualEndDate}
                                onChange={(e) => setActualEndDate(e.target.value)}
                                min={project.actual_start_date}
                            />
                            <button 
                                className="btn btn-success" 
                                style={{marginTop: '10px'}}
                                onClick={handleUpdateActualEnd}
                                disabled={!actualEndDate}
                            >
                                Mark Project Complete
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default ScheduleTrackingPanel;
