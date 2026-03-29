import React from 'react';
import '../../styles/Wizard.css';

export default function WizardLayout({ title, subtitle, stepper, children, onBack, onClose }) {
  return (
    <div className="wizard-page">
      <aside className="wizard-aside" aria-label="help">
        <div className="aside-card">
          <div className="aside-illustration">🏛️</div>
          <h3>BuildHub</h3>
          <p>Guided forms to collect the right information quickly.</p>
        </div>
      </aside>
      <main className="wizard-main">
        <header className="wizard-header">
          <div>
            <h1>{title}</h1>
            {subtitle && <p className="muted">{subtitle}</p>}
          </div>
          <div className="wizard-actions">
            {onBack && (
              <button className="btn btn-secondary" onClick={onBack} type="button">Back</button>
            )}
            {onClose && (
              <button className="btn btn-ghost" onClick={onClose} type="button" aria-label="Close">×</button>
            )}
          </div>
        </header>
        {stepper}
        <section className="wizard-content">
          {children}
        </section>
      </main>
    </div>
  );
}