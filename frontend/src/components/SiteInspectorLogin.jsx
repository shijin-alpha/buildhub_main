import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/SiteInspectorLogin.css';

const SiteInspectorLogin = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    if (!formData.email.trim()) {
      setError('Email is required.');
      setLoading(false);
      return;
    }
    if (!formData.password.trim()) {
      setError('Password is required.');
      setLoading(false);
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/inspector/inspector_login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(formData)
      });

      const result = await response.json();
      
      if (result.success) {
        // Store inspector session info
        localStorage.setItem('inspector_logged_in', 'true');
        localStorage.setItem('inspector_id', result.inspector.id);
        localStorage.setItem('inspector_name', `${result.inspector.first_name} ${result.inspector.last_name}`);
        
        // Redirect to site inspection dashboard
        navigate('/site-inspection');
      } else {
        setError(result.message || 'Login failed.');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="inspector-login-page">
      <div className="inspector-login-container">
        <div className="inspector-login-header">
          <h1>🔍 Site Inspector Login</h1>
          <p>Access your assigned construction projects for inspection</p>
        </div>

        <form onSubmit={handleSubmit} className="inspector-login-form">
          <div className="form-group">
            <label htmlFor="email">Email Address</label>
            <input
              id="email"
              name="email"
              type="email"
              required
              placeholder="Enter your email address"
              value={formData.email}
              onChange={handleChange}
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              required
              placeholder="Enter your password"
              value={formData.password}
              onChange={handleChange}
              disabled={loading}
            />
          </div>

          {error && (
            <div className="error-message">
              <span className="error-icon">⚠️</span>
              {error}
            </div>
          )}

          <button 
            type="submit" 
            className="login-button"
            disabled={loading}
          >
            {loading ? (
              <>
                <span className="loading-spinner"></span>
                Signing In...
              </>
            ) : (
              'Sign In'
            )}
          </button>
        </form>

        <div className="inspector-login-footer">
          <p>Need help accessing your account?</p>
          <a href="/contact-admin" className="contact-link">Contact Administrator</a>
        </div>
      </div>
    </div>
  );
};

export default SiteInspectorLogin;