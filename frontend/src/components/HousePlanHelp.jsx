import React, { useState } from 'react';
import '../styles/HousePlanHelp.css';

const HousePlanHelp = ({ isOpen, onClose }) => {
  const [activeSection, setActiveSection] = useState('getting-started');
  const [searchQuery, setSearchQuery] = useState('');
  const [expandedItems, setExpandedItems] = useState({});

  const toggleExpanded = (itemId) => {
    setExpandedItems(prev => ({
      ...prev,
      [itemId]: !prev[itemId]
    }));
  };

  const helpSections = {
    'getting-started': {
      title: 'Getting Started',
      icon: '🚀',
      content: (
        <div className="help-content">
          <div className="help-hero">
            <h3>Welcome to House Plan Designer</h3>
            <p>Create professional architectural plans with precision and efficiency.</p>
          </div>
          
          <div className="quick-start-cards">
            <div className="quick-start-card">
              <div className="card-icon">📝</div>
              <h4>1. Plan Details</h4>
              <p>Enter project name and plot dimensions</p>
            </div>
            <div className="quick-start-card">
              <div className="card-icon">🏠</div>
              <h4>2. Add Rooms</h4>
              <p>Click templates to add rooms to canvas</p>
            </div>
            <div className="quick-start-card">
              <div className="card-icon">⚙️</div>
              <h4>3. Customize</h4>
              <p>Adjust dimensions and materials</p>
            </div>
            <div className="quick-start-card">
              <div className="card-icon">💾</div>
              <h4>4. Save & Submit</h4>
              <p>Save locally or submit to homeowner</p>
            </div>
          </div>
          
          <div className="interactive-demo">
            <h4>Try It Now</h4>
            <div className="demo-buttons">
              <button className="demo-btn" onClick={() => setActiveSection('interface')}>
                Explore Interface →
              </button>
              <button className="demo-btn secondary" onClick={() => setActiveSection('measurements')}>
                Learn Measurements →
              </button>
            </div>
          </div>
        </div>
      )
    },
    'interface': {
      title: 'Interface Guide',
      icon: '🖥️',
      content: (
        <div className="help-content">
          <div className="interface-overview">
            <h3>Interface Overview</h3>
            
            <div className="interface-section">
              <div 
                className="collapsible-header"
                onClick={() => toggleExpanded('header-controls')}
              >
                <span className="section-icon">🎛️</span>
                <h4>Header Controls</h4>
                <span className={`expand-icon ${expandedItems['header-controls'] ? 'expanded' : ''}`}>▼</span>
              </div>
              {expandedItems['header-controls'] && (
                <div className="collapsible-content">
                  <div className="control-grid">
                    <div className="control-item">
                      <span className="control-icon">🎯</span>
                      <div>
                        <strong>Tour</strong>
                        <p>Launch guided walkthrough</p>
                      </div>
                    </div>
                    <div className="control-item">
                      <span className="control-icon">📖</span>
                      <div>
                        <strong>Help</strong>
                        <p>Open this user manual</p>
                      </div>
                    </div>
                    <div className="control-item">
                      <span className="control-icon">↶</span>
                      <div>
                        <strong>Undo/Redo</strong>
                        <p>Navigate design history</p>
                      </div>
                    </div>
                    <div className="control-item">
                      <span className="control-icon">🗑️</span>
                      <div>
                        <strong>Delete</strong>
                        <p>Remove selected rooms</p>
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="interface-section">
              <div 
                className="collapsible-header"
                onClick={() => toggleExpanded('sidebar-panels')}
              >
                <span className="section-icon">📋</span>
                <h4>Sidebar Panels</h4>
                <span className={`expand-icon ${expandedItems['sidebar-panels'] ? 'expanded' : ''}`}>▼</span>
              </div>
              {expandedItems['sidebar-panels'] && (
                <div className="collapsible-content">
                  <div className="panel-list">
                    <div className="panel-item">
                      <strong>Plan Details</strong> - Project information and dimensions
                    </div>
                    <div className="panel-item">
                      <strong>Statistics</strong> - Real-time metrics and coverage
                    </div>
                    <div className="panel-item">
                      <strong>Measurements</strong> - Dimension controls and scale
                    </div>
                    <div className="panel-item">
                      <strong>Room Templates</strong> - Pre-configured room types
                    </div>
                    <div className="panel-item">
                      <strong>Room Properties</strong> - Detailed customization
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )
    },
    'measurements': {
      title: 'Measurement System',
      icon: '📏',
      content: (
        <div className="help-content">
          <h3>Understanding Measurements</h3>
          
          <div className="measurement-comparison">
            <div className="measurement-card layout">
              <div className="card-header">
                <span className="measurement-icon">📐</span>
                <h4>Layout Dimensions</h4>
              </div>
              <div className="card-content">
                <p>Visual representation for design convenience</p>
                <ul>
                  <li>Optimized for screen display</li>
                  <li>Easy to work with during design</li>
                  <li>Automatically converted</li>
                </ul>
              </div>
            </div>
            
            <div className="measurement-card construction">
              <div className="card-header">
                <span className="measurement-icon">🏗️</span>
                <h4>Construction Dimensions</h4>
              </div>
              <div className="card-content">
                <p>Actual building measurements for contractors</p>
                <ul>
                  <li>Real-world accurate</li>
                  <li>Used for material calculations</li>
                  <li>Includes construction tolerances</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div className="scale-calculator">
            <h4>Scale Ratio Calculator</h4>
            <div className="calculator-content">
              <div className="ratio-examples">
                <div className="ratio-item">
                  <span className="ratio-value">1.0</span>
                  <span className="ratio-desc">Same size (Layout = Construction)</span>
                </div>
                <div className="ratio-item active">
                  <span className="ratio-value">1.2</span>
                  <span className="ratio-desc">20% larger construction (Default)</span>
                </div>
                <div className="ratio-item">
                  <span className="ratio-value">1.5</span>
                  <span className="ratio-desc">50% larger construction</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )
    },
    'shortcuts': {
      title: 'Keyboard Shortcuts',
      icon: '⌨️',
      content: (
        <div className="help-content">
          <h3>Keyboard Shortcuts</h3>
          
          <div className="shortcuts-categories">
            <div className="shortcut-category">
              <h4>🎨 Design Actions</h4>
              <div className="shortcut-list">
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>Ctrl</kbd> + <kbd>Z</kbd>
                  </div>
                  <span className="shortcut-desc">Undo last action</span>
                </div>
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>Ctrl</kbd> + <kbd>Y</kbd>
                  </div>
                  <span className="shortcut-desc">Redo last undone action</span>
                </div>
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>Delete</kbd>
                  </div>
                  <span className="shortcut-desc">Delete selected room</span>
                </div>
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>Esc</kbd>
                  </div>
                  <span className="shortcut-desc">Deselect all rooms</span>
                </div>
              </div>
            </div>
            
            <div className="shortcut-category">
              <h4>💾 File Operations</h4>
              <div className="shortcut-list">
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>Ctrl</kbd> + <kbd>S</kbd>
                  </div>
                  <span className="shortcut-desc">Save plan</span>
                </div>
                <div className="shortcut-item">
                  <div className="shortcut-keys">
                    <kbd>F1</kbd>
                  </div>
                  <span className="shortcut-desc">Open help</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )
    },
    'tips': {
      title: 'Pro Tips',
      icon: '💡',
      content: (
        <div className="help-content">
          <h3>Professional Tips</h3>
          
          <div className="tips-grid">
            <div className="tip-card">
              <div className="tip-icon">⚡</div>
              <h4>Speed Up Your Workflow</h4>
              <p>Use keyboard shortcuts and save frequently. The undo/redo system keeps 50 states in memory.</p>
            </div>
            
            <div className="tip-card">
              <div className="tip-icon">📊</div>
              <h4>Monitor Statistics</h4>
              <p>Keep an eye on the real-time statistics panel to ensure your design meets requirements.</p>
            </div>
            
            <div className="tip-card">
              <div className="tip-icon">🎯</div>
              <h4>Precision Placement</h4>
              <p>Use the grid system for precise alignment. Rooms automatically snap to grid lines.</p>
            </div>
            
            <div className="tip-card">
              <div className="tip-icon">🔄</div>
              <h4>Iterative Design</h4>
              <p>Don't be afraid to experiment. The undo system lets you try different approaches safely.</p>
            </div>
          </div>
          
          <div className="best-practices">
            <h4>Best Practices Checklist</h4>
            <div className="checklist">
              <label className="checklist-item">
                <input type="checkbox" />
                <span>Start with major rooms (living, dining, bedrooms)</span>
              </label>
              <label className="checklist-item">
                <input type="checkbox" />
                <span>Verify construction dimensions before finalizing</span>
              </label>
              <label className="checklist-item">
                <input type="checkbox" />
                <span>Add detailed notes for special requirements</span>
              </label>
              <label className="checklist-item">
                <input type="checkbox" />
                <span>Save regularly and before major changes</span>
              </label>
              <label className="checklist-item">
                <input type="checkbox" />
                <span>Review total area and coverage percentages</span>
              </label>
            </div>
          </div>
        </div>
      )
    }
  };

  const filteredSections = Object.entries(helpSections).filter(([key, section]) =>
    section.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    key.includes(searchQuery.toLowerCase())
  );

  if (!isOpen) return null;

  return (
    <div className="help-modal-overlay" onClick={onClose}>
      <div className="help-modal" onClick={e => e.stopPropagation()}>
        <div className="help-header">
          <div className="help-title-section">
            <h2>House Plan Designer Guide</h2>
            <div className="help-search">
              <input
                type="text"
                placeholder="Search help topics..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="help-search-input"
              />
            </div>
          </div>
          <button className="help-close-btn" onClick={onClose}>×</button>
        </div>
        
        <div className="help-body">
          <div className="help-sidebar">
            {filteredSections.map(([key, section]) => (
              <button
                key={key}
                className={`help-nav-item ${activeSection === key ? 'active' : ''}`}
                onClick={() => setActiveSection(key)}
              >
                <span className="help-nav-icon">{section.icon}</span>
                <span className="help-nav-title">{section.title}</span>
              </button>
            ))}
          </div>
          
          <div className="help-content-area">
            {helpSections[activeSection]?.content}
          </div>
        </div>
      </div>
    </div>
  );
};

export default HousePlanHelp;