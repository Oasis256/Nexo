/**
 * NsCommissions Module - Main TypeScript Entry Point
 * 
 * Registers Vue components for commission management:
 * - nsCommissionProductValues: Per-product commission value assignment
 * - nsProductCommissionUserSelect: POS component for selecting commission earner
 */

import { defineAsyncComponent } from 'vue';

declare const nsExtraComponents: any;

// Register components on nsExtraComponents for CRUD form integration
nsExtraComponents.nsCommissionProductValues = defineAsyncComponent(
    () => import('./components/ns-commission-product-values.vue')
);

nsExtraComponents.nsProductCommissionUserSelect = defineAsyncComponent(
    () => import('./components/ns-product-commission-user-select.vue')
);

console.log('[NsCommissions] Vue components registered successfully.');
