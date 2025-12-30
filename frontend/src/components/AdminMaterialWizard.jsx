import React, { useState } from 'react';
import Stepper from './wizard/Stepper';
import WizardLayout from './wizard/WizardLayout';

export default function AdminMaterialWizard() {
  const steps = ['Basics', 'Pricing', 'Review', 'Submit'];
  const [step, setStep] = useState(0);
  const [data, setData] = useState({ name: '', category: '', unit: '', price: '', description: '' });
  const [loading, setLoading] = useState(false);
  const next = () => setStep(s => Math.min(s + 1, steps.length - 1));
  const prev = () => setStep(s => Math.max(s - 1, 0));

  async function submit() {
    setLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/admin/add_material.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      const json = await res.json();
      if (json.success) window.history.back(); else toast.error(json.message || 'Failed');
    } catch { toast.error('Network error'); } finally { setLoading(false); }
  }

  return (
    <WizardLayout
      title="Add Material"
      subtitle="Create a new material entry"
      stepper={<Stepper steps={steps} current={step} />}
      onBack={step > 0 ? prev : () => window.history.back()}
      onClose={() => window.history.back()}
    >
      {step === 0 && (
        <div className="section">
          <div className="section-header">Basics</div>
          <div className="section-body grid-3">
            <div className="field">
              <label>Name</label>
              <input value={data.name} onChange={e=>setData({...data, name: e.target.value})} />
            </div>
            <div className="field">
              <label>Category</label>
              <input value={data.category} onChange={e=>setData({...data, category: e.target.value})} />
            </div>
            <div className="field">
              <label>Unit</label>
              <input value={data.unit} onChange={e=>setData({...data, unit: e.target.value})} />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 1 && (
        <div className="section">
          <div className="section-header">Pricing</div>
          <div className="section-body grid-2">
            <div className="field">
              <label>Price</label>
              <input type="number" step="0.01" value={data.price} onChange={e=>setData({...data, price: e.target.value})} />
            </div>
            <div className="field">
              <label>Description</label>
              <textarea rows={4} value={data.description} onChange={e=>setData({...data, description: e.target.value})} />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 2 && (
        <div className="section">
          <div className="section-header">Review</div>
          <div className="section-body">
            <pre style={{background:'#f8fafc', padding:12, borderRadius:8, overflow:'auto'}}>{JSON.stringify(data, null, 2)}</pre>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Proceed</button>
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="section">
          <div className="section-header">Submit</div>
          <div className="section-body">
            <p className="muted">Material will be added to the catalog.</p>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" disabled={loading} onClick={submit}>{loading ? 'Saving...' : 'Add Material'}</button>
          </div>
        </div>
      )}
    </WizardLayout>
  );
}