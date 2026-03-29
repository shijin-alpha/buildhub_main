import React, { useState, useEffect } from 'react';
import './ContractorScheduleInput.css';

/**
 * Contractor Schedule Input Component
 * 
 * Allows contractors to set planned and actual dates for projects
 * Enforces business rules:
 * - Planned dates can only be set before actual work begins
 * - Actual start date locks planned dates
 * - Actual end date triggers automatic overrun calculation
 */

const ContractorScheduleInput = ({ projectId, onScheduleUpdate }) => {
    const [schedule, setSchedule] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const [plannedStartDate, setPlannedStartDate] = useState('');
    const [plannedEndDate, setPlannedEndDate] = useState('');
    const [actualStartDate, setActualStartDate] = useState('');
    const [actualEndDate, setActualEndDate] = useState('');

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
                setPlannedStartDate(data.data.planned.start_date || '');
                setPlannedEndDate(data.data.planned.end_date || '');
                setActualStartDate(data.data.actual.start_date || '');
                setActualEndDate(data.data.actual.end_date || '');
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to load schedule data');
        } finally {
            setLoading(false);
        }
    };

    const handleUpdatePlannedSchedule = async (e) => {
        e.preventDefault();
        setError(null);
        setSuccess(null);
        setSaving(true);

        try {
            const response = await fetch(
                '/buildhub/backend/api/contractor/update_planned_schedule.php',
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        project_id: projectId,
                        planned_start_date: plannedStartDate || null,
                        planned_end_date: plannedEndDate || null,
                        change_reason: 'Schedule planning update'
                    })
                }
            );

            const data = await response.json();

            if (data.success) {
                setSuccess('Planned schedule updated successfully');
                await fetchSchedule();
                if (onScheduleUpdate) onScheduleUpdate(data.data);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to update planned schedule');
        } finally {
            setSaving(false);
        }
    };

    const handleUpdateActualDates = async (e) => {
        e.preventDefault();
        setError(null);
        setSuccess(null);
        setSaving(true);

        try {
            const response = await fetch(
                '/buildhub/backend/api/contractor/update_actual_dates.php',
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        project_id: projectId,
                        actual_start_date: actualStartDate || null,
                        actual_end_date: actualEndDate || null,
                        change_reason: 'Actual date recording'
                    })
                }
            );

            const data = await response.json();

            if (data.success) {
                setSuccess('Actual dates updated successfully');
                await fetchSchedule();
                if (onScheduleUpdate) onScheduleUpdate(data.data);
            } else {
                setError(data.message);
            }
        } catch (err) {
            setError('Failed to update actual dates');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <div className="schedule-input-loading">Loading schedule...</div>;
    }

    if (!schedule) {
        return <div className="schedule-input-error">Failed to load schedule data</div>;
    }

    const canEditPlanned = schedule.permissions.can_edit_planned;
    const canEditActual = schedule.permissions.can_edit_actual;
    const isLocked = schedule.permissions.schedule_locked;

    return (
        <div className="contractor-schedule-input">
            <h3>Project Schedule Management</h3>

            {error && <div className="alert alert-error">{error}</div>}
            {success && <div className="alert alert-success">{success}</div>}

            {/* Planned Schedule Section */}
            <div className="schedule-section">
                <h4>
                    Planned Schedule
                    {isLocked && <span className="locked-badge">🔒 Locked</span>}
                </h4>
                
                {isLocked && (
                    <div className="info-message">
                        Planned dates are locked because actual work has begun.
                    </div>
                )}

                <form onSubmit={handleUpdatePlannedSchedule}>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="planned-start">Planned Start Date</label>
                            <input
                                type="date"
                                id="planned-start"
                                value={plannedStartDate}
                                onChange={(e) => setPlannedStartDate(e.target.value)}
                                disabled={!canEditPlanned || saving}
                                required
                            />
                        </div>

                        <div className="form-group">
                            <label htmlFor="planned-end">Planned End Date</label>
                            <input
                                type="date"
                                id="planned-end"
                                value={plannedEndDate}
                                onChange={(e) => setPlannedEndDate(e.target.value)}
                                disabled={!canEditPlanned || saving}
                                min={plannedStartDate}
                                required
                            />
                        </div>
                    </div>

                    {schedule.planned.duration_days && (
                        <div className="duration-display">
                            Planned Duration: <strong>{schedule.planned.duration_days} days</strong>
                        </div>
                    )}

                    {canEditPlanned && (
                        <button 
                            type="submit" 
                            className="btn btn-primary"
                            disabled={saving || !plannedStartDate || !plannedEndDate}
                        >
                            {saving ? 'Saving...' : 'Update Planned Schedule'}
                        </button>
                    )}
                </form>
            </div>

            {/* Actual Dates Section */}
            <div className="schedule-section">
                <h4>Actual Dates</h4>
                
                <form onSubmit={handleUpdateActualDates}>
                    <div className="form-row">
                        <div className="form-group">
                            <label htmlFor="actual-start">Actual Start Date</label>
                            <input
                                type="date"
                                id="actual-start"
                                value={actualStartDate}
                                onChange={(e) => setActualStartDate(e.target.value)}
                                disabled={!canEditActual || saving || schedule.actual.is_started}
                            />
                            {schedule.actual.is_started && (
                                <small className="help-text">Already recorded</small>
                            )}
                        </div>

                        <div className="form-group">
                            <label htmlFor="actual-end">Actual End Date</label>
                            <input
                                type="date"
                                id="actual-end"
                                value={actualEndDate}
                                onChange={(e) => setActualEndDate(e.target.value)}
                                disabled={!canEditActual || saving || !actualStartDate || schedule.actual.is_completed}
                                min={actualStartDate}
                            />
                            {schedule.actual.is_completed && (
                                <small className="help-text">Project completed</small>
                            )}
                        </div>
                    </div>

                    {schedule.actual.duration_days && (
                        <div className="duration-display">
                            Actual Duration: <strong>{schedule.actual.duration_days} days</strong>
                        </div>
                    )}

                    {canEditActual && (!schedule.actual.is_started || !schedule.actual.is_completed) && (
                        <button 
                            type="submit" 
                            className="btn btn-primary"
                            disabled={saving || (!actualStartDate && !actualEndDate)}
                        >
                            {saving ? 'Saving...' : 'Update Actual Dates'}
                        </button>
                    )}
                </form>
            </div>

            {/* Performance Summary */}
            {schedule.performance.delay_days !== null && (
                <div className="performance-summary">
                    <h4>Schedule Performance</h4>
                    <div className="metrics">
                        <div className={`metric ${schedule.performance.is_delayed ? 'delayed' : 'on-time'}`}>
                            <span className="label">Delay:</span>
                            <span className="value">
                                {schedule.performance.delay_days > 0 
                                    ? `${schedule.performance.delay_days} days behind`
                                    : schedule.performance.delay_days < 0
                                    ? `${Math.abs(schedule.performance.delay_days)} days ahead`
                                    : 'On schedule'}
                            </span>
                        </div>

                        {schedule.performance.time_overrun_percentage !== null && (
                            <div className="metric">
                                <span className="label">Time Overrun:</span>
                                <span className="value">
                                    {schedule.performance.time_overrun_percentage.toFixed(1)}%
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default ContractorScheduleInput;
