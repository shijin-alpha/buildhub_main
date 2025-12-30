import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'
import { ToastProvider } from './components/ToastProvider.jsx'

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