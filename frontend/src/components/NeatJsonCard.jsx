import React, { useState } from 'react';
import './NeatJsonCard.css';

// Renders a neat UI card for requirement-like JSON
// Accepts either `data` (object) or `raw` (string JSON)
const NeatJsonCard = ({ data, raw, title = 'Requirements', expandable = true }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  
  let obj = null;
  if (data && typeof data === 'object') {
    obj = data;
  } else if (typeof raw === 'string') {
    // Try to extract JSON if wrapped in HTML or contains extra whitespace
    const trimmed = raw.trim()
      .replace(/^<[^>]+>/, '') // remove possible opening tag
      .replace(/<\/[^>]+>$/, '') // remove possible closing tag
      .trim();
    try { obj = JSON.parse(trimmed); } catch { obj = null; }
  }

  const entries = obj ? [
    { key: 'plot_shape', label: 'Plot Shape' },
    { key: 'topography', label: 'Topography' },
    { key: 'development_laws', label: 'Development Laws' },
    { key: 'family_needs', label: 'Family Needs' },
    { key: 'rooms', label: 'Rooms' },
    { key: 'aesthetic', label: 'Aesthetic' },
    { key: 'notes', label: 'Notes' },
  ].filter(({ key }) => obj[key] !== undefined && obj[key] !== null && String(obj[key]).trim() !== '') : [];

  const handleHeaderClick = () => {
    if (expandable) {
      setIsExpanded(!isExpanded);
    }
  };

  return (
    <div className="json-card">
      <div 
        className={`json-card__header ${expandable ? 'json-card__header--clickable' : ''}`}
        onClick={handleHeaderClick}
        style={{ cursor: expandable ? 'pointer' : 'default' }}
      >
        <div className="json-card__icon" aria-hidden>🗂️</div>
        <h4 className="json-card__title">{title}</h4>
        {expandable && (
          <div className="json-card__expand-icon">
            {isExpanded ? '▼' : '▶'}
          </div>
        )}
      </div>

      {expandable ? (
        isExpanded && (
          obj ? (
            <div className="json-card__body">
              {entries.length ? (
                <dl className="json-grid">
                  {entries.map(({ key, label }) => (
                    <div className="json-row" key={key}>
                      <dt className="json-label">{label}</dt>
                      <dd className="json-value">{String(obj[key])}</dd>
                    </div>
                  ))}
                </dl>
              ) : (
                <div className="json-empty">No details provided</div>
              )}
            </div>
          ) : (
            <pre className="json-raw">{(raw || '').trim()}</pre>
          )
        )
      ) : (
        obj ? (
          <div className="json-card__body">
            {entries.length ? (
              <dl className="json-grid">
                {entries.map(({ key, label }) => (
                  <div className="json-row" key={key}>
                    <dt className="json-label">{label}</dt>
                    <dd className="json-value">{String(obj[key])}</dd>
                  </div>
                ))}
              </dl>
            ) : (
              <div className="json-empty">No details provided</div>
            )}
          </div>
        ) : (
          <pre className="json-raw">{(raw || '').trim()}</pre>
        )
      )}
    </div>
  );
};

export default NeatJsonCard;