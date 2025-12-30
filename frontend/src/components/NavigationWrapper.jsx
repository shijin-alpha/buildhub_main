import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import PageLoader from './PageLoader';

const NavigationWrapper = ({ children }) => {
  const [isLoading, setIsLoading] = useState(false);
  const [loadingTimeout, setLoadingTimeout] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    // Show loading when route changes
    setIsLoading(true);
    
    // Clear any existing timeout
    if (loadingTimeout) {
      clearTimeout(loadingTimeout);
    }
    
    // Set minimum loading time for smooth UX
    const timeout = setTimeout(() => {
      setIsLoading(false);
    }, 1200);
    
    setLoadingTimeout(timeout);

    return () => {
      if (timeout) {
        clearTimeout(timeout);
      }
    };
  }, [location.pathname]);

  // Enhanced navigation with loading
  const navigateWithLoading = (path) => {
    setIsLoading(true);
    const timeout = setTimeout(() => {
      navigate(path);
      setIsLoading(false);
    }, 300);
    setLoadingTimeout(timeout);
  };

  return (
    <>
      <PageLoader isLoading={isLoading} />
      {React.cloneElement(children, { navigateWithLoading })}
    </>
  );
};

export default NavigationWrapper;

