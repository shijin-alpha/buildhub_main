import React, { useState, useEffect } from 'react';
import '../styles/FullPageForm.css';

// Full-page, clean form layout for uploading a design
export default function ArchitectFullPageUpload() {
  const [form, setForm] = useState({
    request_id: '',
    homeowner_id: '',
    design_title: '',
    description: '',
    preview_image: null,
    layout_file: null,
    extra_files: [],
  });
  const [submitting, setSubmitting] = useState(false);
  const [requests, setRequests] = useState([]); // optional: could be populated from API later

  // Optional: preload current user if needed
  const [currentUser, setCurrentUser] = useState(null);
  useEffect(() => {
    try {
      const u = sessionStorage.getItem('user');
      setCurrentUser(u ? JSON.parse(u) : null);
    } catch {}
  }, []);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  }

  function handleFile(name, files) {
    setForm(prev => ({ ...prev, [name]: files?.[0] || null }));
  }

  function handleExtraFiles(files) {
    setForm(prev => ({ ...prev, extra_files: Array.from(files || []) }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    if (!form.design_title) {
      toast.error('Please provide a Design Title');
      return;
    }
    if (!form.preview_image || !form.layout_file) {
      toast.error('Please attach both a Preview Image and a Layout File');
      return;
    }

    setSubmitting(true);
    try {
      const fd = new FormData();
      if (form.request_id) fd.append('request_id', form.request_id);
      if (form.homeowner_id) fd.append('homeowner_id', form.homeowner_id);
      fd.append('design_title', form.design_title);
      if (form.description) fd.append('description', form.description);
      if (form.preview_image) fd.append('preview_image', form.preview_image);
      if (form.layout_file) fd.append('layout_file', form.layout_file);
      for (const f of form.extra_files) fd.append('design_files[]', f);

      // If backend expects architect_id and you have it on session user, enable below
      // if (currentUser?.role === 'architect' && currentUser?.id) {
      //   fd.append('architect_id', String(currentUser.id));
      // }

      const res = await fetch('/buildhub/backend/api/architect/upload_design.php', {
        method: 'POST',
        body: fd,
      });
      const json = await res.json().catch(() => ({}));
      if (json?.success) {
        toast.success('Design uploaded successfully');
        window.history.back();
      } else {
        toast.error(json?.message || 'Upload failed');
      }
    } catch (err) {
      toast.error('Network error while uploading');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="form-page">
      <header className="form-header">
        <h1>Upload Design</h1>
        <p>Submit your architectural design for a client request</p>
      </header>

      <form id="design-upload-form" className="form-body" onSubmit={handleSubmit}>
        {/* Send To */}
        <div className="form-group">
          <label className="label-lg">Send To</label>
          <div className="inline-2">
            <div>
              <label className="label-sm">By Request (optional)</label>
              <select
                name="request_id"
                value={form.request_id}
                onChange={handleChange}
              >
                <option value="">Choose a client request</option>
                {requests.map(r => (
                  <option key={r.id} value={r.id}>
                    {r.title || `Request #${r.id}`}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="label-sm">Direct Homeowner ID (optional)</label>
              <input
                type="number"
                name="homeowner_id"
                placeholder="e.g., 123"
                value={form.homeowner_id}
                onChange={handleChange}
              />
            </div>
          </div>
        </div>

        {/* Title */}
        <div className="form-group">
          <label>Design Title *</label>
          <input
            type="text"
            name="design_title"
            placeholder="e.g., Modern 3BHK Villa Design"
            value={form.design_title}
            onChange={handleChange}
            required
          />
        </div>

        {/* Description */}
        <div className="form-group">
          <label>Description</label>
          <textarea
            name="description"
            rows={5}
            placeholder="Describe your design features, materials, and special considerations..."
            value={form.description}
            onChange={handleChange}
          />
        </div>

        {/* Files */}
        <div className="form-group">
          <label className="label-lg">Design Files</label>
          <div className="inline-2">
            <div>
              <label className="label-sm">Preview Image</label>
              <input
                type="file"
                accept="image/*"
                onChange={e => handleFile('preview_image', e.target.files)}
              />
              {form.preview_image && (
                <p className="form-help">Selected: {form.preview_image.name}</p>
              )}
            </div>
            <div>
              <label className="label-sm">Layout File (image/PDF/CAD)</label>
              <input
                type="file"
                onChange={e => handleFile('layout_file', e.target.files)}
              />
              {form.layout_file && (
                <p className="form-help">Selected: {form.layout_file.name}</p>
              )}
            </div>
          </div>
          <div style={{ marginTop: 12 }}>
            <label className="label-sm">Additional Files (optional)</label>
            <input
              type="file"
              multiple
              onChange={e => handleExtraFiles(e.target.files)}
            />
            {!!form.extra_files.length && (
              <p className="form-help">{form.extra_files.length} file(s) selected</p>
            )}
          </div>
        </div>
      </form>

      <footer className="form-actions">
        <button
          type="button"
          className="btn btn-secondary"
          onClick={() => window.history.back()}
        >
          Cancel
        </button>
        <button
          type="submit"
          form="design-upload-form"
          className="btn btn-primary"
          disabled={submitting}
        >
          {submitting ? 'Uploading…' : 'Upload Design'}
        </button>
      </footer>
    </div>
  );
}