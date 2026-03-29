import React, { useState, useEffect } from 'react';
import MLAnalyticsDashboard from './MLAnalyticsDashboard';
import './MLAnalyticsTab.css';

const MLAnalyticsTab = ({ userRole }) => {
    const [projects, setProjects] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadProjects();
    }, []);

    const loadProjects = async () => {
        try {
            setLoading(true);
            const user = JSON.parse(sessionStorage.getItem('user') || '{}');
            
            let endpoint = '';
            if (userRole === 'contractor') {
                endpoint = `/buildhub/backend/api/contractor/get_projects.php?contractor_id=${user.id}`;
            } else if (userRole === 'admin') {
                endpoint = `/buildhub/backend/api/admin/get_all_projects.php`;
            }

            const response = await fetch(endpoint, { credentials: 'include' });
            const data = await response.json();

            if (data.success) {
                const projectList = data.data?.projects || data.projects || [];
                setProjects(projectList);
                
                // Auto-select first project
                if (projectList.length > 0) {
                    setSelectedProject(projectList[0].id || projectList[0].project_id);
                }
            }
        } catch (error) {
            console.error('Error loading projects:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="ml-analytics-tab-loading">
                <div className="spinner"></div>
                <p>Loading projects...</p>
            </div>
        );
    }

    if (projects.length === 0) {
        return (
            <div className="ml-analytics-tab-empty">
                <div className="empty-icon">📊</div>
                <h3>No Projects Available</h3>
                <p>ML Analytics will appear here once you have active projects</p>
            </div>
        );
    }

    return (
        <div className="ml-analytics-tab">
            <div className="ml-analytics-tab-header">
                <div>
                    <h1>🤖 AI-Powered Analytics</h1>
                    <p>Machine Learning insights for your construction projects</p>
                </div>
                
                <div className="project-selector">
                    <label htmlFor="project-select">Select Project:</label>
                    <select 
                        id="project-select"
                        value={selectedProject || ''}
                        onChange={(e) => setSelectedProject(parseInt(e.target.value))}
                        className="project-select-dropdown"
                    >
                        {projects.map(project => (
                            <option 
                                key={project.id || project.project_id} 
                                value={project.id || project.project_id}
                            >
                                {project.project_name || project.name || `Project #${project.id || project.project_id}`}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            {selectedProject && (
                <MLAnalyticsDashboard 
                    projectId={selectedProject} 
                    userRole={userRole}
                />
            )}
        </div>
    );
};

export default MLAnalyticsTab;
