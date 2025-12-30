import React, { useState } from 'react';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

const TechnicalDetailsDisplay = ({ technicalDetails, startExpanded = false }) => {
  const [activeSection, setActiveSection] = useState('floor-plans');
  const [isCollapsed, setIsCollapsed] = useState(!startExpanded);
  const [downloadingPDF, setDownloadingPDF] = useState(false);

  if (!technicalDetails || Object.keys(technicalDetails).length === 0) {
    return (
      <div className="technical-details-display">
        <div className="no-technical-details">
          <p>No technical details provided for this design.</p>
        </div>
      </div>
    );
  }

  const sections = [
    { id: 'floor-plans', title: '🏠 Floor Plans & Layout', icon: '🏠' },
    { id: 'site-orientation', title: '🌍 Site & Orientation', icon: '🌍' },
    { id: 'structural', title: '🏗️ Structural Elements', icon: '🏗️' },
    { id: 'elevations', title: '📐 Key Elevations & Sections', icon: '📐' },
    { id: 'construction', title: '📋 Construction Notes', icon: '📋' }
  ];

  const renderFloorPlansSection = () => {
    const data = technicalDetails.floor_plans || {};
    return (
      <div className="technical-section-content">
        {data.layout_description && (
          <div className="detail-item">
            <h4>Floor Plan Layout</h4>
            <p>{data.layout_description}</p>
          </div>
        )}
        
        {(data.living_room_dimensions || data.master_bedroom_dimensions || data.kitchen_dimensions || data.other_room_dimensions) && (
          <div className="detail-item">
            <h4>Room Dimensions</h4>
            <div className="dimensions-list">
              {data.living_room_dimensions && (
                <div className="dimension-item">
                  <strong>Living Room:</strong> {data.living_room_dimensions}
                </div>
              )}
              {data.master_bedroom_dimensions && (
                <div className="dimension-item">
                  <strong>Master Bedroom:</strong> {data.master_bedroom_dimensions}
                </div>
              )}
              {data.kitchen_dimensions && (
                <div className="dimension-item">
                  <strong>Kitchen:</strong> {data.kitchen_dimensions}
                </div>
              )}
              {data.other_room_dimensions && (
                <div className="dimension-item">
                  <strong>Other Rooms:</strong>
                  <div className="other-rooms">{data.other_room_dimensions}</div>
                </div>
              )}
            </div>
          </div>
        )}

        {data.door_window_positions && (
          <div className="detail-item">
            <h4>Door & Window Positions</h4>
            <p>{data.door_window_positions}</p>
          </div>
        )}

        {data.circulation_paths && (
          <div className="detail-item">
            <h4>Main Circulation Paths</h4>
            <p>{data.circulation_paths}</p>
          </div>
        )}
      </div>
    );
  };

  const renderSiteOrientationSection = () => {
    const data = technicalDetails.site_orientation || {};
    return (
      <div className="technical-section-content">
        {data.plot_boundaries && (
          <div className="detail-item">
            <h4>Plot Boundaries</h4>
            <p>{data.plot_boundaries}</p>
          </div>
        )}
        {data.orientation && (
          <div className="detail-item">
            <h4>Orientation (North Direction)</h4>
            <p>{data.orientation}</p>
          </div>
        )}
        {data.access_points && (
          <div className="detail-item">
            <h4>Access Points</h4>
            <p>{data.access_points}</p>
          </div>
        )}
      </div>
    );
  };

  const renderStructuralSection = () => {
    const data = technicalDetails.structural || {};
    return (
      <div className="technical-section-content">
        {data.load_bearing_walls && (
          <div className="detail-item">
            <h4>Load-bearing Walls</h4>
            <p>{data.load_bearing_walls}</p>
          </div>
        )}
        {data.column_positions && (
          <div className="detail-item">
            <h4>Column Positions</h4>
            <p>{data.column_positions}</p>
          </div>
        )}
        {data.foundation_outline && (
          <div className="detail-item">
            <h4>Foundation Outline</h4>
            <p>{data.foundation_outline}</p>
          </div>
        )}
        {data.roof_outline && (
          <div className="detail-item">
            <h4>Roof Outline</h4>
            <p>{data.roof_outline}</p>
          </div>
        )}
      </div>
    );
  };

  const renderElevationsSection = () => {
    const data = technicalDetails.elevations || {};
    return (
      <div className="technical-section-content">
        {data.front_elevation && (
          <div className="detail-item">
            <h4>Front Elevation</h4>
            <p>{data.front_elevation}</p>
          </div>
        )}
        {data.cross_sections && (
          <div className="detail-item">
            <h4>Cross Sections</h4>
            <p>{data.cross_sections}</p>
          </div>
        )}
        {data.height_details && (
          <div className="detail-item">
            <h4>Height Details</h4>
            <p>{data.height_details}</p>
          </div>
        )}
      </div>
    );
  };

  const renderConstructionSection = () => {
    const data = technicalDetails.construction || {};
    return (
      <div className="technical-section-content">
        {data.wall_thickness && (
          <div className="detail-item">
            <h4>Wall Thickness</h4>
            <p>{data.wall_thickness}</p>
          </div>
        )}
        {data.ceiling_heights && (
          <div className="detail-item">
            <h4>Ceiling Heights</h4>
            <p>{data.ceiling_heights}</p>
          </div>
        )}
        {data.building_codes && (
          <div className="detail-item">
            <h4>Building Codes</h4>
            <p>{data.building_codes}</p>
          </div>
        )}
        {data.critical_instructions && (
          <div className="detail-item">
            <h4>Critical Instructions</h4>
            <p>{data.critical_instructions}</p>
          </div>
        )}
      </div>
    );
  };

  const renderSectionContent = () => {
    switch (activeSection) {
      case 'floor-plans':
        return renderFloorPlansSection();
      case 'site-orientation':
        return renderSiteOrientationSection();
      case 'structural':
        return renderStructuralSection();
      case 'elevations':
        return renderElevationsSection();
      case 'construction':
        return renderConstructionSection();
      default:
        return renderFloorPlansSection();
    }
  };

  const hasSectionData = (sectionId) => {
    const sectionData = technicalDetails[sectionId];
    if (!sectionData) return false;
    return Object.values(sectionData).some(value => value && value.trim() !== '');
  };

  const downloadPDF = async () => {
    setDownloadingPDF(true);
    try {
      const currentDate = new Date().toLocaleDateString('en-IN', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });

      const formatValue = (value) => {
        if (!value) return '—';
        if (typeof value === 'object') return JSON.stringify(value, null, 2);
        return String(value);
      };

      const html = `
        <div style="font-family: 'Times New Roman', serif; color: #1a1a1a; margin: 0; padding: 20px; line-height: 1.6; background: white;">
          <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px;">
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-bottom: 8px;">Technical Details Report</div>
            <div style="font-size: 14px; color: #6c757d;">Generated: ${currentDate}</div>
          </div>
          
          ${Object.entries(technicalDetails).map(([key, value]) => {
            if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) return '';
            const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            return `
              <div style="margin-bottom: 24px; page-break-inside: avoid;">
                <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 8px; margin-bottom: 16px;">${formattedKey}</h3>
                ${typeof value === 'object' ? (
                  Object.entries(value).map(([subKey, subValue]) => `
                    <div style="margin: 12px 0; padding: 12px; background: #f8f9fa; border-left: 4px solid #2c3e50;">
                      <strong style="display: block; margin-bottom: 6px; color: #2c3e50;">${subKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}:</strong>
                      <div style="color: #495057; white-space: pre-wrap;">${formatValue(subValue)}</div>
                    </div>
                  `).join('')
                ) : `
                  <div style="padding: 12px; background: #f8f9fa; border-left: 4px solid #2c3e50;">
                    <div style="color: #495057; white-space: pre-wrap;">${formatValue(value)}</div>
                  </div>
                `}
              </div>
            `;
          }).filter(Boolean).join('')}
        </div>`;

      const tempDiv = document.createElement('div');
      tempDiv.style.position = 'absolute';
      tempDiv.style.left = '-9999px';
      tempDiv.style.width = '210mm';
      tempDiv.style.padding = '20mm';
      tempDiv.style.backgroundColor = 'white';
      tempDiv.innerHTML = html;
      document.body.appendChild(tempDiv);

      const canvas = await html2canvas(tempDiv, { 
        scale: 2, 
        useCORS: true, 
        allowTaint: true, 
        backgroundColor: '#ffffff' 
      });
      document.body.removeChild(tempDiv);

      const pdf = new jsPDF('p', 'mm', 'a4');
      const imgData = canvas.toDataURL('image/png');
      const imgWidth = 210;
      const pageHeight = 295;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;
      let position = 0;

      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;

      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }

      const fileName = `Technical_Details_${Date.now().toString().slice(-6)}.pdf`;
      pdf.save(fileName);
    } catch (e) {
      console.error('Error generating PDF:', e);
      alert('Error generating PDF');
    } finally {
      setDownloadingPDF(false);
    }
  };

  return (
    <div className="technical-details-display">
      <div className="technical-header" style={{
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        color: 'white',
        padding: '20px',
        borderRadius: '12px',
        marginBottom: '20px',
        boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
      }}>
        <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
          <div>
            <h3 style={{margin: '0 0 8px 0', fontSize: '20px', fontWeight: '600'}}>Technical Design Details</h3>
            <p style={{margin: 0, fontSize: '14px', opacity: 0.9}}>Comprehensive architectural specifications and construction details</p>
          </div>
          <div style={{display: 'flex', gap: '8px'}}>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => setIsCollapsed(prev => !prev)}
              style={{background: 'rgba(255,255,255,0.2)', border: '1px solid rgba(255,255,255,0.3)', color: 'white'}}
            >
              {isCollapsed ? '👁️ View details' : '👁️ Hide details'}
            </button>
            <button
              type="button"
              className="btn btn-primary"
              onClick={downloadPDF}
              disabled={downloadingPDF}
              style={{background: '#10b981', border: '1px solid #10b981', color: 'white'}}
            >
              {downloadingPDF ? '📄 Generating...' : '📄 Download PDF'}
            </button>
          </div>
        </div>
      </div>

      {!isCollapsed && (
      <div className="technical-layout">
        {(() => {
          const hasAnySection = sections.some(s => hasSectionData(s.id));
          
          if (!hasAnySection) {
            // Fallback: Show all technical details as key-value pairs
            return (
              <div className="technical-grid">
                {Object.entries(technicalDetails).map(([key, value]) => (
                  <div 
                    key={key} 
                    style={{
                      padding: '16px',
                      background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
                      borderRadius: '10px',
                      border: '1px solid #e5e7eb',
                      boxShadow: '0 2px 6px rgba(0, 0, 0, 0.05)',
                      transition: 'all 0.2s ease',
                      height: '100%',
                      display: 'flex',
                      flexDirection: 'column'
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.transform = 'translateY(-2px)';
                      e.currentTarget.style.boxShadow = '0 6px 12px rgba(0, 0, 0, 0.08)';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.transform = 'translateY(0)';
                      e.currentTarget.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.05)';
                    }}
                  >
                    <div style={{
                      display: 'flex',
                      alignItems: 'center',
                      marginBottom: '10px',
                      paddingBottom: '8px',
                      borderBottom: '2px solid #667eea'
                    }}>
                      <span style={{fontSize: '20px', marginRight: '8px'}}>
                        {key.includes('floor') ? '🏠' : 
                         key.includes('site') ? '🌍' :
                         key.includes('structural') ? '🏗️' :
                         key.includes('elevation') ? '📐' :
                         key.includes('construction') ? '📋' :
                         key.includes('room') ? '📏' :
                         key.includes('cost') || key.includes('price') ? '💰' :
                         key.includes('material') ? '🧱' :
                         key.includes('labor') ? '👷' : '📄'}
                      </span>
                      <h4 style={{
                        margin: 0,
                        color: '#374151',
                        fontSize: '14px',
                        fontWeight: '600',
                        lineHeight: '1.3'
                      }}>
                        {key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                      </h4>
                    </div>
                    <div style={{
                      color: '#6b7280',
                      whiteSpace: 'pre-wrap',
                      lineHeight: '1.5',
                      fontSize: '13px',
                      flex: 1
                    }}>
                      {typeof value === 'object' && value !== null ? (
                        Object.entries(value).map(([subKey, subValue]) => (
                          <div key={subKey} style={{
                            margin: '6px 0',
                            padding: '6px',
                            background: 'white',
                            borderRadius: '4px',
                            borderLeft: '2px solid #667eea',
                            fontSize: '12px'
                          }}>
                            <strong style={{color: '#495057', fontSize: '12px'}}>
                              {subKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}:{' '}
                            </strong>
                            <span style={{color: '#6b7280', fontSize: '12px'}}>
                              {typeof subValue === 'object' ? JSON.stringify(subValue, null, 2) : String(subValue)}
                            </span>
                          </div>
                        ))
                      ) : (
                        <div style={{
                          fontSize: '13px',
                          lineHeight: '1.4',
                          wordBreak: 'break-word'
                        }}>
                          {String(value)}
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            );
          }
          
          return (
            <>
              <div className="technical-sidebar">
                <div className="section-nav">
                  {sections.map(section => {
                    if (!hasSectionData(section.id)) return null;
                    return (
                      <button
                        key={section.id}
                        className={`section-nav-item ${activeSection === section.id ? 'active' : ''}`}
                        onClick={() => setActiveSection(section.id)}
                      >
                        <span className="section-icon">{section.icon}</span>
                        <span className="section-title">{section.title}</span>
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="technical-content">
                {renderSectionContent()}
              </div>
            </>
          );
        })()}
      </div>
      )}
    </div>
  );
};

export default TechnicalDetailsDisplay;


