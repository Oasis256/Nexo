/**
 * RenCommissions - Main TypeScript Entry Point
 * 
 * This module adds commission functionality to NexoPOS POS screen.
 */

import { injectCommissionButton } from './simple-button-injector';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Start button injection when POS is available
    injectCommissionButton();
});

// Export for potential external use
export { injectCommissionButton };
