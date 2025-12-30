import React from 'react';
import '../../styles/Wizard.css';

// Reusable stepper with numbered steps and progress line
export default function Stepper({ steps = [], current = 0 }) {
  return (
    <div className="wizard-stepper" role="navigation" aria-label="Form progress">
      {steps.map((s, i) => (
        <div key={i} className={`step ${i <= current ? 'active' : ''}`}>
          <div className="step-index">{String(i + 1).padStart(2, '0')}</div>
          <div className="step-label">{s}</div>
          {i < steps.length - 1 && <div className="step-divider" />}
        </div>
      ))}
    </div>
  );
}