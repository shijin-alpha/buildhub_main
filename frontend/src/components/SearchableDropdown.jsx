import React, { useState, useEffect, useRef } from 'react';
import '../styles/SearchableDropdown.css';

const SearchableDropdown = ({ options, value, onChange, placeholder }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filteredOptions, setFilteredOptions] = useState(options);
  const dropdownRef = useRef(null);

  useEffect(() => {
    // Filter options based on search term
    if (searchTerm) {
      const filtered = options.filter(option =>
        option.toLowerCase().includes(searchTerm.toLowerCase())
      );
      setFilteredOptions(filtered);
    } else {
      setFilteredOptions(options);
    }
  }, [searchTerm, options]);

  useEffect(() => {
    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  const handleSelect = (option) => {
    onChange(option);
    setIsOpen(false);
    setSearchTerm('');
  };

  return (
    <div className="searchable-dropdown" ref={dropdownRef}>
      <div className="dropdown-input-container">
        <input
          type="text"
          className="dropdown-input"
          value={value || searchTerm}
          onChange={(e) => {
            setSearchTerm(e.target.value);
            if (!isOpen) setIsOpen(true);
            if (!e.target.value) onChange('');
          }}
          onClick={() => setIsOpen(true)}
          placeholder={placeholder || 'Search...'}
        />
        <div className="dropdown-arrow" onClick={() => setIsOpen(!isOpen)}>
          ▼
        </div>
      </div>
      
      {isOpen && (
        <ul className="dropdown-options">
          {filteredOptions.length > 0 ? (
            filteredOptions.map((option, index) => (
              <li 
                key={index} 
                onClick={() => handleSelect(option)}
                className={value === option ? 'selected' : ''}
              >
                {option}
              </li>
            ))
          ) : (
            <li className="no-options">No matches found</li>
          )}
        </ul>
      )}
    </div>
  );
};

export default SearchableDropdown;