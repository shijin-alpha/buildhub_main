import React, { useEffect, useMemo, useState } from 'react';
import Stepper from './wizard/Stepper';
import WizardLayout from './wizard/WizardLayout';
import TechnicalDetailsForm from './TechnicalDetailsForm';
import '../styles/TechnicalDetailsForm.css';

export default function ArchitectUploadWizard() {
  const steps = ['Select', 'Details', 'Files', 'Technical Details', 'Review', 'Submit']; // Files step now requires: Preview Image + Layout file
  const [step, setStep] = useState(0);
  const [data, setData] = useState({
    architect_id: '',
    request_id: '',
    homeowner_id: '',
    design_title: '',
    description: '',
    layout_json: '',
    technical_details: {},
    // New structured files
    preview_image: null,
    layout_file: null,
    files: [], // legacy field for backward compatibility
    // Payment pricing
    view_price: '', // Price for homeowners to view the layout
  });
  const [loading, setLoading] = useState(false);

  // Current user & role handling
  const [currentUser, setCurrentUser] = useState(null);
  const isArchitect = currentUser?.role === 'architect';

  useEffect(() => {
    try {
      const raw = sessionStorage.getItem('user');
      const u = raw ? JSON.parse(raw) : null;
      setCurrentUser(u);
      // If logged in as architect, prefill architect_id
      if (u?.role === 'architect' && u?.id) {
        setData(prev => ({ ...prev, architect_id: String(u.id) }));
      }
    } catch {}
  }, []);

  // Load all architects (approved) for selection
  const [architects, setArchitects] = useState([]);
  const [archLoading, setArchLoading] = useState(true);
  const [archError, setArchError] = useState('');

  useEffect(() => {
    let cancelled = false;
    async function loadArchitects() {
      if (isArchitect) { // no need to load list for self-upload
        setArchLoading(false);
        return;
      }
      setArchLoading(true);
      setArchError('');
      try {
        const res = await fetch('/buildhub/backend/api/admin/get_all_users.php?role=architect&status=approved');
        const json = await res.json();
        if (!cancelled) {
          if (json?.success) {
            setArchitects(Array.isArray(json.users) ? json.users : []);
          } else {
            setArchError(json?.message || 'Failed to load architects');
          }
        }
      } catch (e) {
        if (!cancelled) setArchError('Network error while loading architects');
      } finally {
        if (!cancelled) setArchLoading(false);
      }
    }
    loadArchitects();
    return () => {
      cancelled = true;
    };
  }, [isArchitect]);

  const selectedArchitect = useMemo(() => {
    if (isArchitect && currentUser?.id) {
      return {
        id: currentUser.id,
        first_name: currentUser.first_name || '',
        last_name: currentUser.last_name || '',
        email: currentUser.email || null,
      };
    }
    return architects.find(a => String(a.id) === String(data.architect_id));
  }, [isArchitect, currentUser, architects, data.architect_id]);

  const next = () => setStep(s => Math.min(s + 1, steps.length - 1));
  const prev = () => setStep(s => Math.max(s - 1, 0));

  async function submit() {
    setLoading(true);
    try {
      const formData = new FormData();
      if (data.request_id) formData.append('request_id', data.request_id);
      if (data.homeowner_id) formData.append('homeowner_id', data.homeowner_id);
      if (data.design_title) formData.append('design_title', data.design_title);
      if (data.description) formData.append('description', data.description);
      if (data.layout_json) formData.append('layout_json', data.layout_json);
      if (data.technical_details && Object.keys(data.technical_details).length > 0) {
        formData.append('technical_details', JSON.stringify(data.technical_details));
      }
      // Payment pricing
      if (data.view_price) formData.append('view_price', data.view_price);
      // Prefer new structured fields
      if (data.preview_image) formData.append('preview_image', data.preview_image);
      if (data.layout_file) formData.append('layout_file', data.layout_file);
      // Fallback legacy multiple files
      (data.files || []).forEach(f => formData.append('design_files[]', f));
      const res = await fetch('/buildhub/backend/api/architect/upload_design.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.success) window.history.back(); else toast.error(json.message || 'Failed');
    } catch {
      toast.error('Network error');
    } finally {
      setLoading(false);
    }
  }

  return (
    <WizardLayout
      title="Upload Design"
      subtitle="Provide details and upload your files"
      stepper={<Stepper steps={steps} current={step} />}
      onBack={step > 0 ? prev : () => window.history.back()}
      onClose={() => window.history.back()}
    >
      {step === 0 && (
        <div className="section">
          <div className="section-header">Select Target</div>
          <div className="section-body grid-2">
            {/* Choose Architect (hidden for architects themselves) */}
            {!isArchitect && (
              <div className="field" style={{ gridColumn: '1 / -1' }}>
                <label>Choose Architect</label>
                {archLoading ? (
                  <div className="muted">Loading architects…</div>
                ) : archError ? (
                  <div className="text-danger">{archError}</div>
                ) : (
                  <select
                    value={data.architect_id}
                    onChange={e => setData({ ...data, architect_id: e.target.value })}
                  >
                    <option value="">Select architect…</option>
                    {architects.map(a => (
                      <option key={a.id} value={a.id}>
                        {a.first_name} {a.last_name} (#{a.id})
                      </option>
                    ))}
                  </select>
                )}
              </div>
            )}

            {/* Show selected/self architect summary */}
            {selectedArchitect && (
              <div className="muted" style={{ marginTop: 8, gridColumn: '1 / -1' }}>
                <strong>{isArchitect ? 'You (Architect)' : 'Selected'}:</strong> {selectedArchitect.first_name} {selectedArchitect.last_name}
                {selectedArchitect.email ? ` • ${selectedArchitect.email}` : ''}
              </div>
            )}

            {/* Optional: Route by Request/Homeowner */}
            <div className="field">
              <label>Layout Request ID (optional)</label>
              <input
                value={data.request_id}
                onChange={e => setData({ ...data, request_id: e.target.value })}
              />
            </div>
            <div className="field">
              <label>Homeowner ID (optional)</label>
              <input
                value={data.homeowner_id}
                onChange={e => setData({ ...data, homeowner_id: e.target.value })}
              />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 1 && (
        <div className="section">
          <div className="section-header">Details</div>
          <div className="section-body grid-2">
            <div className="field">
              <label>Design Title</label>
              <input value={data.design_title} onChange={e=>setData({...data, design_title: e.target.value})} />
            </div>
            <div className="field" style={{gridColumn:'1 / -1'}}>
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
          <div className="section-header">Files</div>
          <div className="section-body grid-2">
            <div className="field">
              <label>Preview Image (shown on homeowner dashboard)</label>
              <input type="file" accept="image/*" onChange={e=>setData({...data, preview_image: (e.target.files||[])[0] || null})} />
              {data.preview_image && <div className="muted" style={{marginTop:6}}>Selected: {data.preview_image.name}</div>}
            </div>
            <div className="field">
              <label>Layout File (image/PDF/CAD)</label>
              <input type="file" onChange={e=>setData({...data, layout_file: (e.target.files||[])[0] || null})} />
              {data.layout_file && <div className="muted" style={{marginTop:6}}>Selected: {data.layout_file.name}</div>}
            </div>
            <div className="field" style={{gridColumn:'1 / -1'}}>
              <label>Additional Files (optional)</label>
              <input type="file" multiple onChange={e=>setData({...data, files: Array.from(e.target.files || [])})} />
              {data.files?.length ? <div className="muted" style={{marginTop:6}}>{data.files.length} file(s) selected</div> : null}
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="section">
          <div className="section-header">Technical Design Details</div>
          <div className="section-body">
            <TechnicalDetailsForm 
              data={data} 
              setData={setData} 
              onNext={next} 
              onPrev={prev} 
            />
          </div>
        </div>
      )}

      {step === 4 && (
        <div className="section">
          <div className="section-header">Review Your Submission</div>
          <div className="section-body">
            <div className="review-card">
              <div className="review-section">
                <h4 className="review-title">Design Information</h4>
                <div className="review-grid">
                  <div className="review-item">
                    <div className="review-label">Title</div>
                    <div className="review-value">{data.title || 'Not provided'}</div>
                  </div>
                  <div className="review-item">
                    <div className="review-label">Type</div>
                    <div className="review-value">{data.type || 'Not specified'}</div>
                  </div>
                  <div className="review-item">
                    <div className="review-label">Description</div>
                    <div className="review-value">{data.description || 'No description'}</div>
                  </div>
                  <div className="review-item">
                    <div className="review-label">View Price</div>
                    <div className="review-value">{data.view_price ? `₹${data.view_price}` : 'Free'}</div>
                  </div>
                </div>
              </div>
              
              <div className="review-section">
                <h4 className="review-title">Files</h4>
                <div className="review-files">
                  {data.preview_image && (
                    <div className="review-file">
                      <div className="file-icon">🖼️</div>
                      <div className="file-details">
                        <div className="file-name">Preview Image</div>
                        <div className="file-meta">{data.preview_image.name}</div>
                      </div>
                    </div>
                  )}
                  
                  {data.layout_file && (
                    <div className="review-file">
                      <div className="file-icon">📐</div>
                      <div className="file-details">
                        <div className="file-name">Layout File</div>
                        <div className="file-meta">{data.layout_file.name}</div>
                      </div>
                    </div>
                  )}
                  
                  {data.files && data.files.length > 0 && (
                    <div className="review-file-group">
                      <div className="file-group-header">Additional Files ({data.files.length})</div>
                      <div className="file-group-list">
                        {data.files.map((file, index) => (
                          <div key={index} className="file-item">
                            <span className="file-item-icon">📄</span>
                            <span className="file-item-name">{file.name}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
              
              {data.technical_details && Object.keys(data.technical_details).length > 0 && (
                <div className="review-section">
                  <h4 className="review-title">Technical Details</h4>
                  <div className="technical-review">
                    {data.technical_details.floor_plans?.layout_description && (
                      <div className="review-item">
                        <div className="review-label">Floor Plan Layout</div>
                        <div className="review-value">{data.technical_details.floor_plans.layout_description}</div>
                      </div>
                    )}
                    {data.technical_details.floor_plans?.living_room_dimensions && (
                      <div className="review-item">
                        <div className="review-label">Living Room Dimensions</div>
                        <div className="review-value">{data.technical_details.floor_plans.living_room_dimensions}</div>
                      </div>
                    )}
                    {data.technical_details.floor_plans?.master_bedroom_dimensions && (
                      <div className="review-item">
                        <div className="review-label">Master Bedroom Dimensions</div>
                        <div className="review-value">{data.technical_details.floor_plans.master_bedroom_dimensions}</div>
                      </div>
                    )}
                    {data.technical_details.site_orientation?.orientation && (
                      <div className="review-item">
                        <div className="review-label">Site Orientation</div>
                        <div className="review-value">{data.technical_details.site_orientation.orientation}</div>
                      </div>
                    )}
                    {data.technical_details.construction?.critical_instructions && (
                      <div className="review-item">
                        <div className="review-label">Critical Instructions</div>
                        <div className="review-value">{data.technical_details.construction.critical_instructions}</div>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {selectedArchitect && (
                <div className="review-section">
                  <h4 className="review-title">Architect</h4>
                  <div className="review-architect">
                    <div className="architect-avatar">👤</div>
                    <div className="architect-details">
                      <div className="architect-name">{`${selectedArchitect.first_name} ${selectedArchitect.last_name}`}</div>
                      <div className="architect-email">{selectedArchitect.email || 'No email provided'}</div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Proceed</button>
          </div>
        </div>
      )}

      {step === 5 && (
        <div className="section">
          <div className="section-header">Submit</div>
          <div className="section-body">
            <p className="muted">Files will be uploaded and visible to the target homeowner/request.</p>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" disabled={loading} onClick={submit}>{loading ? 'Uploading...' : 'Submit'}</button>
          </div>
        </div>
      )}
    </WizardLayout>
  );
}