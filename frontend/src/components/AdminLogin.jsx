import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/AdminLogin.css';

const AdminLogin = () => {
  const [formData, setFormData] = useState({
    username: '',
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

    if (!formData.username.trim()) {
      setError('Username is required.');
      setLoading(false);
      return;
    }
    if (!formData.password.trim()) {
      setError('Password is required.');
      setLoading(false);
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/admin/admin_login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();
      
      if (result.success) {
        // Store admin session info
        localStorage.setItem('admin_logged_in', 'true');
        localStorage.setItem('admin_username', formData.username);
        
        // Redirect to admin dashboard
        navigate('/admin-dashboard');
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
    <div className="admin-login-page">
      <div className="admin-login-container">
        <div className="admin-login-header">
          <h1>🔐 Admin Login</h1>
          <p>Access the BuildHub Admin Dashboard</p>
        </div>

        <form onSubmit={handleSubmit} className="admin-login-form">
          <div className="form-group">
            <label htmlFor="username">Username</label>
            <input
              id="username"
              name="username"
              type="text"
              required
              placeholder="Enter admin username"
              value={formData.username}
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
              placeholder="Enter admin password"
              value={formData.password}
              onChange={handleChange}
              disabled={loading}
            />
          </div>

          {error && <div className="error-message">{error}</div>}

          <button type="submit" className="admin-login-btn" disabled={loading}>
            {loading ? 'Logging in...' : 'Login as Admin'}
          </button>
        </form>

        <div className="admin-login-footer">
          <p>
            <a href="/login" onClick={(e) => { e.preventDefault(); navigate('/login'); }}>
              ← Use Main Login Page
            </a>
          </p>
          <div className="admin-credentials">
            <small>
              <strong>Default Credentials:</strong><br />
              Username: admin<br />
              Password: admin123
            </small>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminLogin;