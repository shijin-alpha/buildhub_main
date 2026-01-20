/**
 * Modal Utilities for proper z-index and positioning management
 */

class ModalManager {
  constructor() {
    this.activeModals = [];
    this.baseZIndex = 9999;
  }

  /**
   * Open a modal with proper z-index management
   */
  openModal(modalElement, options = {}) {
    const {
      preventBodyScroll = true,
      centerContent = true,
      highestZIndex = true
    } = options;

    // Add modal to active list
    this.activeModals.push(modalElement);

    // Set z-index higher than any existing modals
    if (highestZIndex) {
      const zIndex = this.baseZIndex + this.activeModals.length * 10;
      modalElement.style.zIndex = zIndex;
      
      // Also set z-index for modal content if it exists
      const modalContent = modalElement.querySelector('.modal-content');
      if (modalContent) {
        modalContent.style.zIndex = zIndex + 1;
      }
    }

    // Prevent body scroll
    if (preventBodyScroll) {
      document.body.classList.add('modal-open');
      document.body.style.overflow = 'hidden';
    }

    // Center content
    if (centerContent) {
      modalElement.style.position = 'fixed';
      modalElement.style.top = '0';
      modalElement.style.left = '0';
      modalElement.style.right = '0';
      modalElement.style.bottom = '0';
      modalElement.style.display = 'flex';
      modalElement.style.alignItems = 'center';
      modalElement.style.justifyContent = 'center';
      modalElement.style.padding = '20px';
    }

    // Add escape key listener
    this.addEscapeListener(modalElement);

    return modalElement;
  }

  /**
   * Close a modal and restore proper state
   */
  closeModal(modalElement) {
    // Remove from active modals
    const index = this.activeModals.indexOf(modalElement);
    if (index > -1) {
      this.activeModals.splice(index, 1);
    }

    // Hide modal
    modalElement.style.display = 'none';

    // Restore body scroll if no other modals are open
    if (this.activeModals.length === 0) {
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
    }

    // Remove escape listener
    this.removeEscapeListener(modalElement);
  }

  /**
   * Add escape key listener for modal
   */
  addEscapeListener(modalElement) {
    const escapeHandler = (event) => {
      if (event.key === 'Escape') {
        this.closeModal(modalElement);
      }
    };

    modalElement._escapeHandler = escapeHandler;
    document.addEventListener('keydown', escapeHandler);
  }

  /**
   * Remove escape key listener
   */
  removeEscapeListener(modalElement) {
    if (modalElement._escapeHandler) {
      document.removeEventListener('keydown', modalElement._escapeHandler);
      delete modalElement._escapeHandler;
    }
  }

  /**
   * Create a properly positioned modal overlay
   */
  createModalOverlay(content, options = {}) {
    const {
      className = 'modal-overlay',
      closeOnOverlayClick = true,
      maxWidth = '800px',
      maxHeight = '90vh'
    } = options;

    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = className;
    overlay.style.cssText = `
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      background: rgba(0, 0, 0, 0.8) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      z-index: ${this.baseZIndex + this.activeModals.length * 10} !important;
      padding: 20px !important;
      overflow-y: auto !important;
    `;

    // Create modal content container
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.style.cssText = `
      position: relative !important;
      background: white !important;
      border-radius: 16px !important;
      max-width: ${maxWidth} !important;
      width: 100% !important;
      max-height: ${maxHeight} !important;
      overflow-y: auto !important;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
      z-index: ${this.baseZIndex + this.activeModals.length * 10 + 1} !important;
    `;

    // Add content
    if (typeof content === 'string') {
      modalContent.innerHTML = content;
    } else {
      modalContent.appendChild(content);
    }

    overlay.appendChild(modalContent);

    // Add close on overlay click
    if (closeOnOverlayClick) {
      overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
          this.closeModal(overlay);
        }
      });
    }

    return overlay;
  }

  /**
   * Fix existing modal positioning
   */
  fixModalPositioning(modalElement) {
    // Apply critical positioning fixes
    modalElement.style.position = 'fixed';
    modalElement.style.top = '0';
    modalElement.style.left = '0';
    modalElement.style.right = '0';
    modalElement.style.bottom = '0';
    modalElement.style.zIndex = '999999';
    modalElement.style.display = 'flex';
    modalElement.style.alignItems = 'center';
    modalElement.style.justifyContent = 'center';
    modalElement.style.background = 'rgba(0, 0, 0, 0.8)';
    modalElement.style.padding = '20px';
    modalElement.style.overflowY = 'auto';

    // Fix modal content
    const modalContent = modalElement.querySelector('.modal-content');
    if (modalContent) {
      modalContent.style.position = 'relative';
      modalContent.style.maxHeight = 'calc(100vh - 40px)';
      modalContent.style.overflowY = 'auto';
      modalContent.style.zIndex = '999999';
      modalContent.style.margin = 'auto';
    }

    // Fix modal body scrolling
    const modalBody = modalElement.querySelector('.modal-body');
    if (modalBody) {
      modalBody.style.maxHeight = 'calc(100vh - 200px)';
      modalBody.style.overflowY = 'auto';
    }

    // Prevent body scroll
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-open');

    return modalElement;
  }

  /**
   * Close all modals
   */
  closeAllModals() {
    const modalsToClose = [...this.activeModals];
    modalsToClose.forEach(modal => this.closeModal(modal));
  }
}

// Create global instance
const modalManager = new ModalManager();

// Export utilities
export { modalManager, ModalManager };

// Also make available globally for inline scripts
if (typeof window !== 'undefined') {
  window.modalManager = modalManager;
  window.ModalManager = ModalManager;
}

/**
 * Quick utility functions for common modal operations
 */
export const modalUtils = {
  /**
   * Fix any modal that's getting cut off
   */
  fixModal: (selector) => {
    const modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (modal) {
      return modalManager.fixModalPositioning(modal);
    }
    return null;
  },

  /**
   * Create and show a modal with content
   */
  showModal: (content, options = {}) => {
    const modal = modalManager.createModalOverlay(content, options);
    document.body.appendChild(modal);
    modalManager.openModal(modal);
    return modal;
  },

  /**
   * Close a modal by selector or element
   */
  closeModal: (selector) => {
    const modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (modal) {
      modalManager.closeModal(modal);
      if (modal.parentNode) {
        modal.parentNode.removeChild(modal);
      }
    }
  },

  /**
   * Fix all visible modals on the page
   */
  fixAllModals: () => {
    const modals = document.querySelectorAll('.modal-overlay, [class*="modal"]');
    modals.forEach(modal => {
      if (modal.style.display !== 'none' && modal.offsetParent !== null) {
        modalManager.fixModalPositioning(modal);
      }
    });
  }
};

// Auto-fix modals when DOM is ready
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    // Fix any existing modals
    modalUtils.fixAllModals();
    
    // Watch for new modals being added
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) { // Element node
            if (node.classList && (node.classList.contains('modal-overlay') || node.className.includes('modal'))) {
              modalManager.fixModalPositioning(node);
            }
            // Also check child elements
            const childModals = node.querySelectorAll && node.querySelectorAll('.modal-overlay, [class*="modal"]');
            if (childModals) {
              childModals.forEach(modal => modalManager.fixModalPositioning(modal));
            }
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  });
}