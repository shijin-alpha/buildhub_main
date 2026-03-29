import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'
import { ToastProvider } from './components/ToastProvider.jsx'

// Suppress React DevTools message in development
if (process.env.NODE_ENV === 'development') {
  const originalConsoleInfo = console.info;
  console.info = function(...args) {
    if (typeof args[0] === 'string' && args[0].includes('React DevTools')) {
      return; // Suppress React DevTools messages
    }
    originalConsoleInfo.apply(console, args);
  };
}

// Ensure Razorpay is available globally
if (typeof window !== 'undefined' && !window.Razorpay) {
  // Load Razorpay SDK dynamically if not already loaded
  const script = document.createElement('script');
  script.src = 'https://checkout.razorpay.com/v1/checkout.js';
  script.async = true;
  script.onerror = function() {
    console.error('Failed to load Razorpay SDK');
  };
  document.head.appendChild(script);
  
  // Set a flag to indicate Razorpay is loading
  window._razorpayLoading = true;
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <ToastProvider>
      <App />
    </ToastProvider>
  </React.StrictMode>,
)