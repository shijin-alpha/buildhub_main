import React, { useEffect, useState } from "react";
import { BrowserRouter as Router, Routes, Route, Link, useNavigate } from "react-router-dom";
import Register from "./components/Register.jsx";
import Login from "./components/Login.jsx";
import ForgotPassword from "./components/ForgotPassword.jsx";

import HomeownerDashboard from "./components/HomeownerDashboard.jsx";
import HomeownerProfile from "./components/HomeownerProfile.jsx";
import ContractorDashboard from "./components/ContractorDashboard.jsx";
import ArchitectDashboard from "./components/ArchitectDashboard.jsx";
import AuthorizedRedirectURIs from "./components/AuthorizedRedirectURIs.jsx";
import HomeownerRequestWizard from "./components/HomeownerRequestWizard.jsx";
import ArchitectUploadWizard from "./components/ArchitectUploadWizard.jsx";
import ContractorEstimateWizard from "./components/ContractorEstimateWizard.jsx";
import AdminMaterialWizard from "./components/AdminMaterialWizard.jsx";
import AdminLogin from "./components/AdminLogin.jsx";
import AdminDashboard from "./components/AdminDashboard.jsx";
import ProtectedAdminRoute from "./components/ProtectedAdminRoute.jsx";
import HomeownerRoute from "./components/HomeownerRoute.jsx";
import ArchitectRoute from "./components/ArchitectRoute.jsx";
import ContractorRoute from "./components/ContractorRoute.jsx";
import { ToastProvider, useToast } from "./components/ToastProvider.jsx";
import RequestAssistant from "./components/RequestAssistant";
import "./components/RequestAssistant/styles.css";
import ArchitectFullPageUpload from "./components/ArchitectFullPageUpload.jsx";
import PageLoader from "./components/PageLoader.jsx";
import NavigationWrapper from "./components/NavigationWrapper.jsx";

// Home page component
function Home() {
  const navigate = useNavigate();
  const toast = useToast();

  useEffect(() => {
    const heroTitle = document.querySelector(".hero h1");
    const heroText = document.querySelector(".hero p");
    const btnGroup = document.querySelector(".btn-group");

    setTimeout(() => (heroTitle.style.opacity = "1"), 100);
    setTimeout(() => (heroText.style.opacity = "1"), 600);
    setTimeout(() => (btnGroup.style.opacity = "1"), 1100);

    // Scroll progress indicator
    const updateScrollProgress = () => {
      const scrollTop = window.pageYOffset;
      const docHeight = document.body.scrollHeight - window.innerHeight;
      const scrollPercent = (scrollTop / docHeight) * 100;
      const progressBar = document.querySelector('.scroll-progress');
      if (progressBar) {
        progressBar.style.width = scrollPercent + '%';
      }
    };

    // Intersection Observer for scroll animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, observerOptions);

    // Observe sections for animations
    const sections = document.querySelectorAll('.features, .contact, .feature-card, .projects-gallery, .services-showcase, .team-section, .gallery-item, .service-item, .team-member');
    sections.forEach(section => {
      observer.observe(section);
    });

    // Add scroll progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'scroll-progress';
    document.body.appendChild(progressBar);

    // Add scroll-to-top button
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.className = 'scroll-to-top';
    scrollToTopBtn.innerHTML = '↑';
    scrollToTopBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollToTopBtn);

    const updateScrollToTop = () => {
      if (window.pageYOffset > 300) {
        scrollToTopBtn.style.opacity = '1';
        scrollToTopBtn.style.visibility = 'visible';
      } else {
        scrollToTopBtn.style.opacity = '0';
        scrollToTopBtn.style.visibility = 'hidden';
      }
    };

    const updateHeaderScroll = () => {
      const header = document.querySelector('.hero-header');
      if (header) {
        if (window.pageYOffset > 50) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }
      }
    };

    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', () => {
      updateScrollProgress();
      updateScrollToTop();
      updateHeaderScroll();
      updateActiveNav();
    });
    
    return () => {
      window.removeEventListener('scroll', updateScrollProgress);
      observer.disconnect();
      const existingProgressBar = document.querySelector('.scroll-progress');
      const existingScrollToTop = document.querySelector('.scroll-to-top');
      if (existingProgressBar) {
        existingProgressBar.remove();
      }
      if (existingScrollToTop) {
        existingScrollToTop.remove();
      }
    };
  }, []);

  const handleScroll = (id) => {
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    setActiveSection(id);
  };

  const updateActiveNav = () => {
    const sections = ['home', 'features', 'services', 'projects', 'team', 'contact'];
    const scrollPosition = window.scrollY + 150; // Offset for fixed header

    for (let i = sections.length - 1; i >= 0; i--) {
      const section = document.getElementById(sections[i]);
      if (section && section.offsetTop <= scrollPosition) {
        setActiveSection(sections[i]);
        break;
      }
    }
  };

  // Enhanced button click effects
  const handleButtonClick = (callback) => {
    return (e) => {
      // Create ripple effect
      const button = e.currentTarget;
      const ripple = document.createElement('span');
      const rect = button.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        z-index: 1;
      `;
      
      button.style.position = 'relative';
      button.style.overflow = 'hidden';
      button.appendChild(ripple);
      
      setTimeout(() => {
        ripple.remove();
      }, 600);
      
      if (callback) callback();
    };
  };

  function Counter({ end, label }) {
    const [count, setCount] = useState(0);
    useEffect(() => {
      let startTime = null;
      const duration = 2000;

      function animate(timestamp) {
        if (!startTime) startTime = timestamp;
        const progress = timestamp - startTime;
        const current = Math.min(Math.floor((progress / duration) * end), end);
        setCount(current);
        if (progress < duration) {
          requestAnimationFrame(animate);
        }
      }
      requestAnimationFrame(animate);
    }, [end]);

    return (
      <div className="counter" aria-label={`${label} counter`}>
        {count}+
        <div style={{ fontWeight: 600, fontSize: "1rem", marginTop: "0.3rem" }}>{label}</div>
      </div>
    );
  }

  const [activeIndex, setActiveIndex] = useState(null);
  const [activeSection, setActiveSection] = useState('home');
  const faqs = [
    {
      question: "How does BuildHub simplify construction planning?",
      answer:
        "BuildHub provides AI-powered tools and smart suggestions to make your project planning fast and effective.",
    },
    {
      question: "Can I get accurate cost estimation?",
      answer:
        "Yes! Our platform offers detailed and transparent cost breakdowns to help you manage your budget.",
    },
    {
      question: "Do you provide design assistance?",
      answer:
        "Absolutely. Get professional layouts and designs created by top architects tailored to your project.",
    },
  ];

  const toggleFAQ = (index) => {
    setActiveIndex(activeIndex === index ? null : index);
  };

  return (
    <>
      {/* Interactive Side Panel */}
      <div className="side-panel">
        <div className="panel-toggle">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 12h18m-9-9l9 9-9 9" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
        </div>
        <div className="panel-content">
          <div className="panel-section">
            <h4>Quick Actions</h4>
            <div className="action-icons">
              <div className="action-item" onClick={() => handleScroll("home")} title="Home">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => handleScroll("services")} title="Services">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => handleScroll("projects")} title="Projects">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <path d="M8 21v-4a2 2 0 012-2h4a2 2 0 012 2v4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => handleScroll("team")} title="Team">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <circle cx="9" cy="7" r="4" stroke="currentColor" strokeWidth="2"/>
                  <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => handleScroll("contact")} title="Contact">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <polyline points="22,6 12,13 2,6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
            </div>
          </div>
          
          <div className="panel-section">
            <h4>Tools</h4>
            <div className="action-icons">
              <div className="action-item" onClick={() => toast.info("Calculator coming soon!")} title="Calculator">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="4" y="2" width="16" height="20" rx="2" stroke="currentColor" strokeWidth="2"/>
                  <path d="M8 6h8M8 10h8M8 14h4M8 18h4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => toast.info("Estimator coming soon!")} title="Cost Estimator">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => toast.info("Designer coming soon!")} title="Design Tool">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => toast.info("Planner coming soon!")} title="Project Planner">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
            </div>
          </div>

          <div className="panel-section">
            <h4>Social</h4>
            <div className="action-icons">
              <div className="action-item" onClick={() => toast.info("LinkedIn coming soon!")} title="LinkedIn">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  <circle cx="4" cy="4" r="2" stroke="currentColor" strokeWidth="2"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => toast.info("Twitter coming soon!")} title="Twitter">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
              <div className="action-item" onClick={() => toast.info("Facebook coming soon!")} title="Facebook">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Fixed Header - Always at top */}
      <header className="hero-header">
        <div className="hero-logo">
          <div className="logo-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 21h18l-1-7H4l-1 7zM5 14h14l1-7H4l1 7zM9 3h6v4H9V3zM7 7h10v2H7V7z" fill="currentColor"/>
            </svg>
          </div>
          <span className="logo-text">BuildHub</span>
        </div>
        <nav className="hero-nav">
          <a
            href="#home"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("home");
            }}
            className={`nav-link ${activeSection === 'home' ? 'active' : ''}`}
          >
            Home
          </a>
          <a
            href="/login"
            onClick={(e) => {
              e.preventDefault();
              navigate("/login");
            }}
            className="nav-link login-link"
          >
            Login
          </a>
          <a
            href="#features"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("features");
            }}
            className={`nav-link ${activeSection === 'features' ? 'active' : ''}`}
          >
            About
          </a>
          <a
            href="#services"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("services");
            }}
            className={`nav-link ${activeSection === 'services' ? 'active' : ''}`}
          >
            Services
          </a>
          <a
            href="#projects"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("projects");
            }}
            className={`nav-link ${activeSection === 'projects' ? 'active' : ''}`}
          >
            Work
          </a>
          <a
            href="#team"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("team");
            }}
            className={`nav-link ${activeSection === 'team' ? 'active' : ''}`}
          >
            Team
          </a>
          <a
            href="#contact"
            onClick={(e) => {
              e.preventDefault();
              handleScroll("contact");
            }}
            className={`nav-link ${activeSection === 'contact' ? 'active' : ''}`}
          >
            Contact
          </a>
          <div className="contact-info">
            <span className="phone-number">7558-8956-67</span>
          </div>
        </nav>
      </header>

      <section className="hero" id="home" role="banner">
        <h1>BuildHub – Smart Construction Platform</h1>
        <p>Plan, estimate, and design your construction projects with ease.</p>
        <div className="btn-group">
          <button 
            className="primary" 
            onClick={handleButtonClick(() => navigate("/register"))}
          >
            Get Started
          </button>
          <button 
            className="secondary" 
            onClick={handleButtonClick(() => toast.info("Learn more coming soon!"))}
          >
            Learn More
          </button>
        </div>
      </section>

      <section className="features" id="features" role="region" aria-label="Features Section">
        <h2>Features</h2>
        <div className="features-grid">
          <div className="feature-card glass-card">
            <div className="interactive-icon planning">
              <div className="icon-bg">
                <svg className="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 21h18l-1-7H4l-1 7zM5 14h14l1-7H4l1 7zM9 3h6v4H9V3zM7 7h10v2H7V7z" fill="currentColor"/>
                </svg>
              </div>
            </div>
            <h3>Smart Planning</h3>
            <p>Plan your construction projects with advanced tools and AI-powered suggestions.</p>
          </div>
          <div className="feature-card glass-card">
            <div className="interactive-icon cost">
              <div className="icon-bg">
                <svg className="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="currentColor"/>
                </svg>
              </div>
            </div>
            <h3>Cost Estimation</h3>
            <p>Accurate and transparent project cost breakdown to fit your budget.</p>
          </div>
          <div className="feature-card glass-card">
            <div className="interactive-icon design">
              <div className="icon-bg">
                <svg className="icon-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/>
                </svg>
              </div>
            </div>
            <h3>Design Assistance</h3>
            <p>Get professional layouts and designs from top architects in the industry.</p>
          </div>
        </div>

        <div className="counters" aria-live="polite" aria-atomic="true">
          <Counter end={150} label="Projects Completed" />
          <Counter end={75} label="Trusted Contractors" />
          <Counter end={20} label="Award-Winning Designs" />
        </div>

        <div className="accordion" aria-label="Frequently Asked Questions">
          {faqs.map(({ question, answer }, i) => (
            <div className="accordion-item" key={i}>
              <div
                className={`accordion-header ${activeIndex === i ? "active" : ""}`}
                onClick={() => toggleFAQ(i)}
                onKeyDown={(e) => {
                  if (e.key === "Enter" || e.key === " ") toggleFAQ(i);
                }}
                role="button"
                tabIndex={0}
                aria-expanded={activeIndex === i}
                aria-controls={`faq${i}-content`}
                id={`faq${i}-header`}
              >
                {question}
              </div>
              <div
                id={`faq${i}-content`}
                className={`accordion-content ${activeIndex === i ? "active" : ""}`}
                role="region"
                aria-labelledby={`faq${i}-header`}
              >
                <p>{answer}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* Construction Projects Gallery */}
      <section className="projects-gallery" id="projects" role="region" aria-label="Projects Gallery">
        <h2>Our Construction Projects</h2>
        <div className="gallery-grid">
          <div className="gallery-item">
            <div className="image-container">
              <div className="project-image residential" style={{backgroundImage: "url('./images/projects/residential-1.jpg')"}}>
                <div className="image-overlay">
                  <h3>Residential Projects</h3>
                  <p>Modern homes with sustainable design</p>
                  <div className="project-stats">
                    <span>🏠 50+ Homes Built</span>
                    <span>⭐ 4.9/5 Rating</span>
                  </div>
                </div>
                <div className="image-fallback">
                  <div className="fallback-icon">🏠</div>
                  <div className="fallback-text">Residential Construction</div>
                </div>
              </div>
            </div>
          </div>
          <div className="gallery-item">
            <div className="image-container">
              <div className="project-image commercial" style={{backgroundImage: "url('./images/projects/commercial-1.jpg')"}}>
                <div className="image-overlay">
                  <h3>Commercial Buildings</h3>
                  <p>Office spaces and retail complexes</p>
                  <div className="project-stats">
                    <span>🏢 25+ Buildings</span>
                    <span>⭐ 4.8/5 Rating</span>
                  </div>
                </div>
                <div className="image-fallback">
                  <div className="fallback-icon">🏢</div>
                  <div className="fallback-text">Commercial Construction</div>
                </div>
              </div>
            </div>
          </div>
          <div className="gallery-item">
            <div className="image-container">
              <div className="project-image industrial" style={{backgroundImage: "url('./images/projects/industrial-1.jpg')"}}>
                <div className="image-overlay">
                  <h3>Industrial Facilities</h3>
                  <p>Manufacturing and warehouse projects</p>
                  <div className="project-stats">
                    <span>🏭 15+ Facilities</span>
                    <span>⭐ 4.9/5 Rating</span>
                  </div>
                </div>
                <div className="image-fallback">
                  <div className="fallback-icon">🏭</div>
                  <div className="fallback-text">Industrial Construction</div>
                </div>
              </div>
            </div>
          </div>
          <div className="gallery-item">
            <div className="image-container">
              <div className="project-image renovation" style={{backgroundImage: "url('./images/projects/renovation-1.jpg')"}}>
                <div className="image-overlay">
                  <h3>Renovation Projects</h3>
                  <p>Transforming existing structures</p>
                  <div className="project-stats">
                    <span>🔨 30+ Renovations</span>
                    <span>⭐ 4.7/5 Rating</span>
                  </div>
                </div>
                <div className="image-fallback">
                  <div className="fallback-icon">🔨</div>
                  <div className="fallback-text">Renovation Projects</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Services Showcase */}
      <section className="services-showcase" id="services" role="region" aria-label="Services Showcase">
        <h2>Our Services</h2>
        <div className="services-grid">
          <div className="service-item">
            <div className="service-image-container">
              <div 
                className="service-image planning"
                style={{
                  backgroundImage: "url('./images/services/project-planning.jpg')"
                }}
              >
                <div className="image-fallback">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="service-overlay">
                  <h3>Project Planning</h3>
                  <p>Comprehensive project management</p>
                </div>
              </div>
            </div>
            <div className="service-content">
              <h3>Smart Project Planning</h3>
              <p>Advanced project management tools with AI-powered scheduling and resource optimization.</p>
            </div>
          </div>
          <div className="service-item">
            <div className="service-image-container">
              <div 
                className="service-image design"
                style={{
                  backgroundImage: "url('./images/services/architectural-design.jpg')"
                }}
              >
                <div className="image-fallback">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="service-overlay">
                  <h3>Architectural Design</h3>
                  <p>Professional design services</p>
                </div>
              </div>
            </div>
            <div className="service-content">
              <h3>Architectural Excellence</h3>
              <p>Innovative designs from certified architects with modern construction techniques.</p>
            </div>
          </div>
          <div className="service-item">
            <div className="service-image-container">
              <div 
                className="service-image construction"
                style={{
                  backgroundImage: "url('./images/services/construction-management.jpg')"
                }}
              >
                <div className="image-fallback">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M8 21v-4a2 2 0 012-2h4a2 2 0 012 2v4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="service-overlay">
                  <h3>Construction Management</h3>
                  <p>End-to-end project execution</p>
                </div>
              </div>
            </div>
            <div className="service-content">
              <h3>Construction Management</h3>
              <p>Complete project oversight from start to finish with quality assurance and timeline management.</p>
            </div>
          </div>
          <div className="service-item">
            <div className="service-image-container">
              <div 
                className="service-image consultation"
                style={{
                  backgroundImage: "url('./images/services/consultation.jpg')"
                }}
              >
                <div className="image-fallback">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <circle cx="9" cy="7" r="4" stroke="currentColor" strokeWidth="2"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87m-4-12a4 4 0 010 7.75" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="service-overlay">
                  <h3>Expert Consultation</h3>
                  <p>Professional guidance and advice</p>
                </div>
              </div>
            </div>
            <div className="service-content">
              <h3>Expert Consultation</h3>
              <p>Get professional advice from experienced construction experts for your project needs.</p>
            </div>
          </div>
          <div className="service-item">
            <div className="service-image-container">
              <div 
                className="service-image maintenance"
                style={{
                  backgroundImage: "url('./images/services/maintenance.jpg')"
                }}
              >
                <div className="image-fallback">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="service-overlay">
                  <h3>Maintenance & Support</h3>
                  <p>Ongoing project support</p>
                </div>
              </div>
            </div>
            <div className="service-content">
              <h3>Maintenance & Support</h3>
              <p>Comprehensive maintenance services and ongoing support for your construction projects.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Team & Expertise */}
      <section className="team-section" id="team" role="region" aria-label="Team Section">
        <h2>Our Expert Team</h2>
        <div className="team-grid">
          <div className="team-member">
            <div className="member-image">
              <div className="member-photo architect">
                <div className="role-icon">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="member-overlay">
                  <h4>Senior Architect</h4>
                  <p>15+ years experience</p>
                </div>
              </div>
            </div>
            <div className="member-info">
              <h3>Lead Architect</h3>
              <p>15+ years experience</p>
            </div>
          </div>
          <div className="team-member">
            <div className="member-image">
              <div className="member-photo engineer">
                <div className="role-icon">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </div>
                <div className="member-overlay">
                  <h4>Structural Engineer</h4>
                  <p>20+ years experience</p>
                </div>
              </div>
            </div>
            <div className="member-info">
              <h3>Senior Engineer</h3>
              <p>20+ years experience</p>
            </div>
          </div>
          <div className="team-member">
            <div className="member-image">
              <div className="member-photo manager">
                <div className="role-icon">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20z" stroke="currentColor" strokeWidth="2"/>
                  </svg>
                </div>
                <div className="member-overlay">
                  <h4>Project Manager</h4>
                  <p>12+ years experience</p>
                </div>
              </div>
            </div>
            <div className="member-info">
              <h3>Project Manager</h3>
              <p>12+ years experience</p>
            </div>
          </div>
        </div>
      </section>

      <section className="contact" id="contact" role="region" aria-label="Contact Section">
        <h2>Contact Us</h2>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            toast.success("Thank you for reaching out! We will get back to you soon.");
            e.target.reset();
          }}
        >
          <input type="text" name="name" placeholder="Your Name" required aria-label="Your Name" />
          <input type="email" name="email" placeholder="Your Email" required aria-label="Your Email" />
          <textarea name="message" placeholder="Your Message" required aria-label="Your Message"></textarea>
          <button type="submit">Send Message</button>
        </form>
      </section>

      <footer>© {new Date().getFullYear()} BuildHub. All rights reserved.</footer>
    </>
  );
}

export default function App() {
  const [isLoading, setIsLoading] = useState(false);
  const [loadingTimeout, setLoadingTimeout] = useState(null);

  // Global loading state management
  useEffect(() => {
    const handleRouteChange = () => {
      setIsLoading(true);
      // Clear any existing timeout
      if (loadingTimeout) {
        clearTimeout(loadingTimeout);
      }
      // Set minimum loading time for smooth UX
      const timeout = setTimeout(() => {
        setIsLoading(false);
      }, 1500);
      setLoadingTimeout(timeout);
    };

    // Listen for route changes
    window.addEventListener('beforeunload', handleRouteChange);
    
    return () => {
      if (loadingTimeout) {
        clearTimeout(loadingTimeout);
      }
      window.removeEventListener('beforeunload', handleRouteChange);
    };
  }, [loadingTimeout]);

  return (
    <ToastProvider>
      <Router>
        <NavigationWrapper>
        <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/register" element={<Register />} />
        <Route path="/login" element={<Login />} />
        <Route path="/homeowner-dashboard" element={
          <HomeownerRoute>
            <HomeownerDashboard />
          </HomeownerRoute>
        } />
        <Route path="/contractor-dashboard" element={
          <ContractorRoute>
            <ContractorDashboard />
          </ContractorRoute>
        } />
        <Route path="/architect-dashboard" element={
          <ArchitectRoute>
            <ArchitectDashboard />
          </ArchitectRoute>
        } />

        {/* Old dashboard preserved for reference */}
        <Route path="/architect-dashboard-old" element={
          <ArchitectRoute>
            <ArchitectDashboard />
          </ArchitectRoute>
        } />

        {/* Full-page wizards */}
        <Route path="/homeowner/request" element={<HomeownerRequestWizard />} />
        <Route path="/homeowner/profile" element={
          <HomeownerRoute>
            <HomeownerProfile />
          </HomeownerRoute>
        } />
        <Route path="/architect/upload" element={<ArchitectUploadWizard />} />
        <Route path="/architect/upload/simple" element={<ArchitectFullPageUpload />} />
        <Route path="/contractor/estimate" element={<ContractorEstimateWizard />} />
        <Route path="/admin/material/new" element={<AdminMaterialWizard />} />





        <Route path="/authorized-redirect-uris" element={<AuthorizedRedirectURIs />} />
        <Route path="/admin" element={<AdminLogin />} />
        <Route path="/admin-dashboard" element={
          <ProtectedAdminRoute>
            <AdminDashboard />
          </ProtectedAdminRoute>
        } />
        <Route path="/forgot-password" element={<ForgotPassword />} />

        </Routes>
      </NavigationWrapper>
      <RequestAssistant />
    </Router>
    </ToastProvider>
  );
}
