import React, { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import "../styles/Register.css";
import { Eye, EyeOff } from "lucide-react";

const GOOGLE_CLIENT_ID = "1024134456606-et46lrm2ce8tl567a4m4s4e0u3v5t4sa.apps.googleusercontent.com";

const Register = () => {
  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
    role: "",
  });

  const [error, setError] = useState("");
  const [emailExists, setEmailExists] = useState(null); // null = unknown, true/false = checked
  const [checkingEmail, setCheckingEmail] = useState(false);
  const [loading, setLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState("");
  const [documents, setDocuments] = useState({
    license: null,
    portfolio: null,
    architectLicense: null,
  });
  const [showRegPwd, setShowRegPwd] = useState(false);
  const [showRegConfirm, setShowRegConfirm] = useState(false);

  // Google registration state
  const [googleUserInfo, setGoogleUserInfo] = useState(null);
  const [googleRole, setGoogleRole] = useState("");
  const [googleError, setGoogleError] = useState("");
  const [googleLoading, setGoogleLoading] = useState(false);
  const [googleDocuments, setGoogleDocuments] = useState({
    license: null,
    portfolio: null,
    architectLicense: null,
  });
  const googleBtn = useRef(null);
  const navigate = useNavigate();

  // Helper to decode JWT (Google ID token)
  function parseJwt(token) {
    if (!token) return {};
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(
      atob(base64)
        .split('')
        .map(function (c) {
          return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        })
        .join('')
    );
    return JSON.parse(jsonPayload);
  }

  // Initialize Google Sign-In button
  useEffect(() => {
    let canceled = false;
    let attempts = 0;
    const maxAttempts = 30; // 30 * 150ms = 4.5s

    const tryInit = () => {
      if (window.google && googleBtn.current && !canceled) {
        try {
          // Remove fallback message
          const fallback = document.getElementById('google-btn-fallback');
          if (fallback) {
            fallback.style.display = 'none';
          }
          
          window.google.accounts.id.initialize({
            client_id: GOOGLE_CLIENT_ID, // <-- Use your provided client ID here
            callback: handleGoogleResponse,
          });
          window.google.accounts.id.renderButton(googleBtn.current, {
            theme: "outline",
            size: "large",
            text: "signup_with", // Add explicit text
            shape: "rectangular", // Try rectangular shape
            logo_alignment: "left"
          });
          console.log("Google button rendered successfully in Register");
          return true;
        } catch (error) {
          console.error("Error rendering Google button in Register:", error);
          return false;
        }
      } else if (!window.google) {
        console.log("Google API not loaded yet in Register, attempt:", attempts);
      } else if (!googleBtn.current) {
        console.log("Google button container not found in Register");
      }
      return false;
    };

    // Try immediately
    if (!tryInit()) {
      const intervalId = setInterval(() => {
        attempts += 1;
        if (tryInit() || attempts >= maxAttempts) {
          if (attempts >= maxAttempts) {
            console.log("Max attempts reached, Google button not rendered in Register");
            // Update fallback message
            const fallback = document.getElementById('google-btn-fallback');
            if (fallback) {
              fallback.textContent = "Unable to load Google Sign-Up. Please refresh the page.";
            }
          }
          clearInterval(intervalId);
        }
      }, 150);
    }

    return () => {
      canceled = true;
      if (window.google?.accounts?.id) {
        try { window.google.accounts.id.cancel(); } catch {}
      }
    };
  }, []);

  // Google Sign-In callback
  const handleGoogleResponse = (response) => {
    if (!response.credential) {
      setGoogleError("Google sign-in failed. Please try again.");
      return;
    }
    const profile = parseJwt(response.credential);
    
    // Debug: log the full profile to see what Google provides
    console.log("GOOGLE PROFILE DATA:", profile);
    
    // Extract names with multiple fallbacks
    let firstName = "";
    let lastName = "";
    
    if (profile.given_name) {
      firstName = profile.given_name;
    } else if (profile.name) {
      const nameParts = profile.name.split(' ');
      firstName = nameParts[0] || "";
    }
    
    if (profile.family_name) {
      lastName = profile.family_name;
    } else if (profile.name) {
      const nameParts = profile.name.split(' ');
      lastName = nameParts.slice(1).join(' ') || "";
    }
    
    // If we still don't have names, use email prefix as fallback
    if (!firstName && profile.email) {
      firstName = profile.email.split('@')[0];
    }
    
    console.log("EXTRACTED NAMES:", { firstName, lastName, originalName: profile.name });
    
    setGoogleUserInfo({
      firstName: firstName,
      lastName: lastName,
      email: profile.email || "",
      picture: profile.picture || "",
    });
  };

  // Handle Google role and registration submit
  const handleGoogleRoleSubmit = async (e) => {
    e.preventDefault();
    setGoogleError("");
    setGoogleLoading(true);

    if (!googleUserInfo) {
      setGoogleError("Google user info missing. Please sign in again.");
      setGoogleLoading(false);
      return;
    }
    
    // Additional validation for required fields
    if (!googleUserInfo.firstName || !googleUserInfo.firstName.trim()) {
      setGoogleError("First name is missing from Google profile. Please try signing in again.");
      setGoogleLoading(false);
      return;
    }
    if (!googleUserInfo.lastName || !googleUserInfo.lastName.trim()) {
      setGoogleError("Last name is missing from Google profile. Please try signing in again.");
      setGoogleLoading(false);
      return;
    }
    if (!googleUserInfo.email || !googleUserInfo.email.trim()) {
      setGoogleError("Email is missing from Google profile. Please try signing in again.");
      setGoogleLoading(false);
      return;
    }
    if (!googleRole) {
      setGoogleError("Please select your role.");
      setGoogleLoading(false);
      return;
    }
    if (googleRole === "contractor" && !googleDocuments.license) {
      setGoogleError("Contractor license document is required.");
      setGoogleLoading(false);
      return;
    }
    if (googleRole === "architect" && (!googleDocuments.architectLicense || !googleDocuments.portfolio)) {
      setGoogleError("Both architect license and portfolio documents are required.");
      setGoogleLoading(false);
      return;
    }

    // Debug: log payload before sending
    console.log("GOOGLE SIGNUP PAYLOAD:", {
      firstName: googleUserInfo.firstName,
      lastName: googleUserInfo.lastName,
      email: googleUserInfo.email,
      role: googleRole,
      license: googleDocuments.license,
      portfolio: googleDocuments.portfolio,
    });

    try {
      const payload = new FormData();
      payload.append("firstName", googleUserInfo.firstName);
      payload.append("lastName", googleUserInfo.lastName);
      payload.append("email", googleUserInfo.email);
      payload.append("role", googleRole);
      // Generate a random password for Google users
      const randomPassword = Math.random().toString(36).slice(-12) + "!A1";
      payload.append("password", randomPassword);

      if (googleRole === "contractor" && googleDocuments.license) {
        payload.append("license", googleDocuments.license);
      }
      if (googleRole === "architect") {
        if (googleDocuments.architectLicense) {
          payload.append("architectLicense", googleDocuments.architectLicense);
        }
        if (googleDocuments.portfolio) {
          payload.append("portfolio", googleDocuments.portfolio);
        }
      }

      const res = await fetch("/buildhub/backend/api/google_register.php", {
        method: "POST",
        body: payload,
      });

      const responseText = await res.text();
      let result;
      try {
        result = JSON.parse(responseText);
      } catch {
        setGoogleError("Server returned invalid response.");
        setGoogleLoading(false);
        return;
      }

      if (result.success) {
        setGoogleError("");
        setGoogleUserInfo(null);
        setGoogleRole("");
        setGoogleDocuments({ license: null, portfolio: null, architectLicense: null });
        setTimeout(() => {
          if (result.redirect === "homeowner-dashboard" || googleRole === "homeowner") {
            navigate("/homeowner-dashboard");
          } else {
            navigate("/login");
          }
        }, 1000);
      } else {
        setGoogleError(result.message || "Google registration failed.");
      }
    } catch (error) {
      setGoogleError("Network or server error. Please try again.");
    } finally {
      setGoogleLoading(false);
    }
  };

  // File change handler for Google docs
  const handleGoogleFileChange = (e) => {
    const { name, files } = e.target;
    setGoogleDocuments((prev) => ({
      ...prev,
      [name]: files[0] || null,
    }));
  };

  const handleChange = (e) => {
    const { name, value } = e.target;

    // Sanitize values strictly for whitespace
    let sanitized = value;
    if (name === 'firstName' || name === 'lastName') {
      // Collapse internal whitespace to single space and prevent leading
      sanitized = value.replace(/\s+/g, ' ').replace(/^\s+/, '');
    } else if (name === 'email') {
      // Remove all whitespace from email
      sanitized = value.replace(/\s+/g, '');
    } else if (name === 'password' || name === 'confirmPassword') {
      // Remove all whitespace in passwords
      sanitized = value.replace(/\s+/g, '');
    }

    setFormData((prev) => ({ ...prev, [name]: sanitized }));

    // Reset email existence state when user types a new email
    if (name === 'email') {
      setEmailExists(null);
    }

    // Update password strength on the fly
    if (name === 'password') {
      setPwdStrength(computeStrength(sanitized));
    }

    setError("");
    setSuccessMessage("");
  };

  const handleFileChange = (e) => {
    const { name, files } = e.target;
    setDocuments((prev) => ({
      ...prev,
      [name]: files[0] || null,
    }));
    setError("");
    setSuccessMessage("");
  };

  // Allow any valid email domain
  const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

  // Require: 8+ chars, at least one letter, one number, and one special character
  const validatePassword = (password) => {
    if (password.length < 8) return "Password must be at least 8 characters long.";
    if (/\s/.test(password)) return "Password cannot contain spaces.";
    if (!/[A-Za-z]/.test(password)) return "Password must include at least one letter.";
    if (!/[0-9]/.test(password)) return "Password must include at least one number.";
    if (!/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?`~]/.test(password)) return "Password must include at least one special character.";
    return "";
  };

  // Password strength computation (0-4)
  const computeStrength = (pwd) => {
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?`~]/.test(pwd)) score++;
    return score;
  };

  const [pwdStrength, setPwdStrength] = useState(0);

  // Debounced email existence check while typing
  useEffect(() => {
    const email = formData.email;

    // If empty or invalid, reset and skip
    if (!email || !validateEmail(email)) {
      setCheckingEmail(false);
      setEmailExists(null);
      return;
    }

    setCheckingEmail(true);
    const controller = new AbortController();

    const t = setTimeout(async () => {
      try {
        const res = await fetch(`/buildhub/backend/api/check_email.php?email=${encodeURIComponent(email)}`, { signal: controller.signal });
        const data = await res.json().catch(() => ({}));

        // If the input changed while we were checking, ignore this result
        if (formData.email !== email) return;

        if (data && data.success === true) {
          setEmailExists(!!data.exists);
        } else {
          setEmailExists(null);
        }
      } catch (err) {
        // Ignore abort errors
        if (err && err.name !== 'AbortError') {
          setEmailExists(null);
        }
      } finally {
        if (formData.email === email) {
          setCheckingEmail(false);
        }
      }
    }, 400); // debounce 400ms

    return () => {
      clearTimeout(t);
      controller.abort();
    };
  }, [formData.email]);

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Clear previous messages
    setError("");
    setSuccessMessage("");

    if (!formData.firstName.trim()) return setError("First Name is required.");
    if (!formData.lastName.trim()) return setError("Last Name is required.");
    if (!formData.email) return setError("Email is required.");
    if (!validateEmail(formData.email)) return setError("Enter a valid email address.");
    if (emailExists === true) return setError("This email is already registered.");

    const passwordError = validatePassword(formData.password);
    if (passwordError) return setError(passwordError);
    if (formData.password !== formData.confirmPassword) return setError("Passwords do not match!");
    if (!formData.role) return setError("Please select your role.");

    if (formData.role === "contractor" && !documents.license) {
      return setError("Contractor license document is required.");
    }
    if (formData.role === "architect" && (!documents.architectLicense || !documents.portfolio)) {
      return setError("Both architect license and portfolio documents are required.");
    }

    setLoading(true);

    const data = new FormData();
    data.append("firstName", formData.firstName);
    data.append("lastName", formData.lastName);
    data.append("email", formData.email);
    data.append("password", formData.password);
    data.append("role", formData.role);

    if (formData.role === "contractor" && documents.license) {
      data.append("license", documents.license);
    }
    if (formData.role === "architect") {
      if (documents.architectLicense) {
        data.append("architectLicense", documents.architectLicense);
      }
      if (documents.portfolio) {
        data.append("portfolio", documents.portfolio);
      }
    }

    try {
      const res = await fetch("/buildhub/backend/api/register.php", {
        method: "POST",
        body: data,
      });

      // Check if response is ok
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }

      // Get response text first to check if it's valid JSON
      const responseText = await res.text();
      let result;

      try {
        result = JSON.parse(responseText);
      } catch (jsonError) {
        console.error("Invalid JSON response:", responseText);
        throw new Error("Server returned invalid response. Please try again.");
      }

      if (result.success) {
        // Show success message
        setSuccessMessage(result.message || "Registration successful!");

        // Clear form
        setFormData({
          firstName: "",
          lastName: "",
          email: "",
          password: "",
          confirmPassword: "",
          role: "",
        });
        setDocuments({ license: null, portfolio: null, architectLicense: null });
        setError("");

        // Redirect after a short delay to show success message
        setTimeout(() => {
          if (result.redirect === "homeowner-dashboard") {
            navigate("/homeowner-dashboard");
          } else {
            navigate("/login");
          }
        }, 2000);
      } else {
        setError(result.message || "Registration failed.");
      }
    } catch (error) {
      console.error("Registration error:", error);
      setError("Server error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const resetGoogleSignup = () => {
    setGoogleUserInfo(null);
    setGoogleRole("");
    setGoogleDocuments({ license: null, portfolio: null, architectLicense: null });
    setGoogleError("");
    setGoogleLoading(false);
    setSuccessMessage("");
  };

  return (
    <main className="register-page" aria-label="Registration section">
      {/* Left: Brand / Identity */}
      <section className="register-logo-section" aria-label="BuildHub brand">
        <div className="brand">
          <img src="/images/logo.png" alt="BuildHub Logo" className="brand-logo" />
          <h1 className="brand-title">BuildHub</h1>
          <p className="brand-tagline">Where homeowners meet trusted contractors and architects</p>
          <ul className="brand-highlights" aria-label="Key highlights">
            <li>Secure onboarding</li>
            <li>Verified professionals</li>
            <li>Fast project matching</li>
          </ul>
        </div>
      </section>

      {/* Right: Registration Form */}
      <section className="register-form-section" aria-label="Registration form">
        <h2>Create Your Account</h2>
        <p className="subtitle">Join us today and start your journey 🚀</p>

        <form onSubmit={handleSubmit} noValidate>
          {/* Name Fields */}
          <div className="grid-two-col">
            <div>
              <label htmlFor="firstName">First Name</label>
              <input
                id="firstName"
                name="firstName"
                type="text"
                required
                placeholder="First name"
                value={formData.firstName}
                onChange={handleChange}
                onKeyDown={(e)=>{ if (e.key === ' ' && e.currentTarget.selectionStart === 0) e.preventDefault(); }}
                onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/^\s+/.test(t)) e.preventDefault(); }}
              />
            </div>
            <div>
              <label htmlFor="lastName">Last Name</label>
              <input
                id="lastName"
                name="lastName"
                type="text"
                required
                placeholder="Last name"
                value={formData.lastName}
                onChange={handleChange}
                onKeyDown={(e)=>{ if (e.key === ' ' && e.currentTarget.selectionStart === 0) e.preventDefault(); }}
                onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/^\s+/.test(t)) e.preventDefault(); }}
              />
            </div>
          </div>

          {/* Email */}
          <div className="full-width">
            <label htmlFor="email">Email Address</label>
            <input
              id="email"
              name="email"
              type="email"
              required
              placeholder="Email address"
              value={formData.email}
              onChange={handleChange}
              onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
              onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
            />
            {checkingEmail && (
              <div className="muted" style={{ marginTop: 6, fontSize: 13, color: '#6b7280' }}>Checking email…</div>
            )}
            {emailExists === true && (
              <p className="error" style={{ marginTop: 6 }}>This email is already registered.</p>
            )}
            {emailExists === false && validateEmail(formData.email) && (
              <div className="muted" style={{ marginTop: 6, fontSize: 13, color: '#16a34a' }}>Email is available.</div>
            )}
          </div>

          {/* Password Fields */}
          <div className="grid-two-col">
            <div>
              <label htmlFor="password">Password</label>
              <div style={{ position: 'relative' }}>
                <input
                  id="password"
                  name="password"
                  type={showRegPwd ? 'text' : 'password'}
                  required
                  placeholder="Create a strong password"
                  value={formData.password}
                  onChange={handleChange}
                  style={{ paddingRight: 36 }}
                  onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                  onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
                />
                <button
                  type="button"
                  aria-label={showRegPwd ? 'Hide password' : 'Show password'}
                  onClick={() => setShowRegPwd(v=>!v)}
                  style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer', padding: 4, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  {showRegPwd ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
                </button>
              </div>
              {/* Password strength meter */}
              <div className="pwd-meter" aria-live="polite">
                <div
                  className={`pwd-meter-bar s${pwdStrength}`}
                  role="progressbar"
                  aria-valuemin={0}
                  aria-valuemax={4}
                  aria-valuenow={pwdStrength}
                />
                <div className={`pwd-meter-label s${pwdStrength}`}>
                  {pwdStrength <= 1 ? 'Weak' : pwdStrength === 2 ? 'Fair' : pwdStrength === 3 ? 'Good' : 'Strong'}
                </div>
              </div>
            </div>
            <div>
              <label htmlFor="confirmPassword">Confirm Password</label>
              <div style={{ position: 'relative' }}>
                <input
                  id="confirmPassword"
                  name="confirmPassword"
                  type={showRegConfirm ? 'text' : 'password'}
                  required
                  placeholder="Re-enter password"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  style={{ paddingRight: 36 }}
                  onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                  onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
                />
                <button
                  type="button"
                  aria-label={showRegConfirm ? 'Hide password' : 'Show password'}
                  onClick={() => setShowRegConfirm(v=>!v)}
                  style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer', padding: 4, display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                  {showRegConfirm ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
                </button>
              </div>
            </div>
          </div>

          {/* Role */}
          <div className="full-width">
            <label htmlFor="role">Select Role</label>
            <select
              id="role"
              name="role"
              required
              value={formData.role}
              onChange={handleChange}
            >
              <option value="" disabled>
                Select your role
              </option>
              <option value="homeowner">Homeowner</option>
              <option value="contractor">Contractor</option>
              <option value="architect">Architect</option>
            </select>
          </div>

          {/* Documents: only visible for the selected role */}
          {formData.role === "contractor" && (
            <div className="full-width">
              <label htmlFor="license">
                Upload Contractor License <span style={{ color: "red" }}>*</span>
              </label>
              <input
                id="license"
                name="license"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                required
                onChange={handleFileChange}
              />
              {documents.license && <span className="file-name">{documents.license.name}</span>}
            </div>
          )}

          {formData.role === "architect" && (
            <>
              <div className="full-width">
                <label htmlFor="architectLicense">
                  Upload Architect License <span style={{ color: "red" }}>*</span>
                </label>
                <input
                  id="architectLicense"
                  name="architectLicense"
                  type="file"
                  accept=".pdf,.jpg,.jpeg,.png"
                  required
                  onChange={handleFileChange}
                />
                {documents.architectLicense && <span className="file-name">{documents.architectLicense.name}</span>}
              </div>
              <div className="full-width">
                <label htmlFor="portfolio">
                  Upload Portfolio <span style={{ color: "red" }}>*</span>
                </label>
                <input
                  id="portfolio"
                  name="portfolio"
                  type="file"
                  accept=".pdf,.jpg,.jpeg,.png"
                  required
                  onChange={handleFileChange}
                />
                {documents.portfolio && <span className="file-name">{documents.portfolio.name}</span>}
              </div>
            </>
          )}

          {error && <p className="error">{error}</p>}
          {successMessage && (
            <p className="success" style={{ color: "green", fontWeight: "bold" }}>
              {successMessage}
            </p>
          )}
          <button type="submit" className="btn-submit" disabled={loading}>
            {loading ? "Creating account..." : "Create account"}
          </button>



          {/* Links and Google button below inside card */}
          <div className="form-links">
            <a href="/forgot-password">Forgot password?</a>
            <a href="/login">Already have an account? Login</a>
          </div>

          {/* Google Sign Up button at the bottom */}
          <div className="google-signin-block" style={{ marginTop: 12 }}>
            <div className="google-btn-container" ref={googleBtn}>
              {/* Fallback message if Google button doesn't load */}
              <div id="google-btn-fallback" style={{ textAlign: 'center', padding: '10px', color: '#6c757d', fontSize: '14px' }}>
                Google Sign-Up button loading...
              </div>
            </div>
          </div>
        </form>

        {/* Google role selection form - moved outside main form to fix nesting issue */}
        {googleUserInfo && (
          <div className="google-role-form">
            <p>
              Welcome,{" "}
              <b>
                {googleUserInfo.firstName} {googleUserInfo.lastName}
              </b>
              !
              <br />
              Please select your role to complete registration:
            </p>
            <form onSubmit={handleGoogleRoleSubmit}>
              <select
                value={googleRole}
                onChange={(e) => setGoogleRole(e.target.value)}
                required
                disabled={googleLoading}
              >
                <option value="">Select your role</option>
                <option value="homeowner">Homeowner</option>
                <option value="contractor">Contractor</option>
                <option value="architect">Architect</option>
              </select>

              {/* Conditional uploads for Google registration - only show for contractor and architect */}
              {googleRole === "contractor" && (
                <div className="full-width" style={{ marginTop: 8 }}>
                  <label htmlFor="google_license">
                    Contractor License <span style={{ color: "red" }}>*</span>
                  </label>
                  <input
                    id="google_license"
                    name="license"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    required
                    disabled={googleLoading}
                    onChange={handleGoogleFileChange}
                  />
                  {googleDocuments.license && <span className="file-name">{googleDocuments.license.name}</span>}
                </div>
              )}

              {googleRole === "architect" && (
                <>
                  <div className="full-width" style={{ marginTop: 8 }}>
                    <label htmlFor="google_architectLicense">
                      Architect License <span style={{ color: "red" }}>*</span>
                    </label>
                    <input
                      id="google_architectLicense"
                      name="architectLicense"
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      required
                      disabled={googleLoading}
                      onChange={handleGoogleFileChange}
                    />
                    {googleDocuments.architectLicense && <span className="file-name">{googleDocuments.architectLicense.name}</span>}
                  </div>
                  <div className="full-width" style={{ marginTop: 8 }}>
                    <label htmlFor="google_portfolio">
                      Portfolio <span style={{ color: "red" }}>*</span>
                    </label>
                    <input
                      id="google_portfolio"
                      name="portfolio"
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      required
                      disabled={googleLoading}
                      onChange={handleGoogleFileChange}
                    />
                    {googleDocuments.portfolio && <span className="file-name">{googleDocuments.portfolio.name}</span>}
                  </div>
                </>
              )}

              <div style={{ display: "flex", gap: "12px", marginTop: 12 }}>
                <button type="submit" className="btn-submit" disabled={googleLoading}>
                  {googleLoading ? "Processing..." : "Complete Registration"}
                </button>
                <button
                  type="button"
                  className="btn-secondary"
                  onClick={resetGoogleSignup}
                  disabled={googleLoading}
                  style={{
                    backgroundColor: "#6c757d",
                    color: "white",
                    border: "none",
                    padding: "10px 20px",
                    borderRadius: "5px",
                    cursor: googleLoading ? "not-allowed" : "pointer",
                  }}
                >
                  Cancel
                </button>
              </div>
            </form>
            {googleError && <p className="error" style={{ marginTop: 8 }}>{googleError}</p>}
            {successMessage && (
              <p className="success" style={{ color: "green", fontWeight: "bold", marginTop: 8 }}>
                {successMessage}
              </p>
            )}
          </div>
        )}
      </section>
    </main>
  );
};

export default Register;
