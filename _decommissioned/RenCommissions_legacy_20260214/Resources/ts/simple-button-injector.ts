/**
 * RenCommissions - Cart Button Injector
 * 
 * Injects commission buttons into POS cart product rows using DOM manipulation.
 * This is the proper NexoPOS pattern since hook-based product actions don't exist.
 */

declare const Popup: any;
declare const nsHttpClient: any;
declare const nsSnackBar: any;
declare const nsConfirmPopup: any;
declare const nsSelectPopup: any;
declare const __: (key: string) => string;
declare const POS: any;

interface CommissionType {
    id: number;
    name: string;
    calculation_method: 'percentage' | 'fixed' | 'on_the_house';
    default_value: number;
}

interface Earner {
    id: number;
    username: string;
    display_name: string;
}

interface CartProduct {
    id?: number;
    product_id?: number;
    name?: string;
    product_name?: string;
    unit_price?: number;
    quantity?: number;
    barcode?: string;
}

interface AssignmentData {
    earner_id: number;
    earner_name: string;
    commission_type: string;
    amount: number;
}

/**
 * Track assigned commissions by product_id
 * Key: product_id, Value: assignment data
 */
const assignedProducts = new Map<number, AssignmentData>();

/**
 * Initialize commission button injection using DOM observation
 */
export function injectCommissionButton(): void {
    console.log('[RenCommissions] Initializing cart button injection...');
    
    // Wait for POS cart to be available
    waitForPosCart().then(() => {
        console.log('[RenCommissions] POS cart found, starting observation...');
        observeCartChanges();
    }).catch((error) => {
        console.warn('[RenCommissions] POS cart not found after waiting:', error);
    });
}

/**
 * Wait for the POS cart element to appear in DOM
 */
function waitForPosCart(): Promise<Element> {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const maxAttempts = 30; // 30 seconds max
        
        const checkInterval = setInterval(() => {
            attempts++;
            
            // Try multiple selectors for POS cart
            const cartEl = document.querySelector('#pos-cart') ||
                          document.querySelector('.ns-pos-cart') ||
                          document.querySelector('#cart-products-table') ||
                          document.querySelector('[id*="cart"]');
            
            if (cartEl) {
                clearInterval(checkInterval);
                resolve(cartEl);
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                reject(new Error('POS cart not found'));
            }
        }, 1000);
    });
}

/**
 * Observe cart DOM for changes and inject buttons
 */
function observeCartChanges(): void {
    const cartContainer = document.querySelector('#pos-cart') ||
                         document.querySelector('#cart-products-table') ||
                         document.body;
    
    if (!cartContainer) {
        console.warn('[RenCommissions] Cart container not found');
        return;
    }

    // Create mutation observer
    const observer = new MutationObserver((mutations) => {
        // Skip if currently processing a click
        if ((window as any).__rcProcessing) {
            return;
        }
        
        // Debounce - only run once per batch of mutations
        clearTimeout((observer as any)._debounceTimer);
        (observer as any)._debounceTimer = setTimeout(() => {
            injectButtonsToCartItems();
        }, 150);
    });

    // Start observing
    observer.observe(cartContainer, {
        childList: true,
        subtree: true,
    });

    // Initial injection
    setTimeout(() => injectButtonsToCartItems(), 500);
    
    console.log('[RenCommissions] MutationObserver started');
}

/**
 * Setup click delegation - NOT USED
 */
function setupClickDelegation(): void {
    // Removed - not needed
}

/**
 * Inject commission buttons to all cart product items
 */
function injectButtonsToCartItems(): void {
    // Find all product rows in cart - matching the ns-pos-cart.vue template structure
    const productItems = document.querySelectorAll('.product-item, [product-index]');
    
    if (productItems.length === 0) {
        return; // No products in cart yet
    }

    productItems.forEach((productEl, index) => {
        // Get product data to check assignment state
        const product = getProductFromPosState(index);
        const productId = product?.product_id || product?.id;
        const isAssigned = productId ? assignedProducts.has(productId) : false;
        
        // Check if button already exists
        const existingBtn = productEl.querySelector('.rencommissions-btn') as HTMLElement;
        if (existingBtn) {
            // Update existing button's state and click handler (index might change when cart reorders)
            updateButtonState(existingBtn, isAssigned, index);
            return;
        }

        // Find the product-options container (right side of product row header)
        const optionsContainer = productEl.querySelector('.product-options') ||
                                productEl.querySelector('.flex.product-options') ||
                                productEl.querySelector('.product-details > div:last-child');

        if (!optionsContainer) {
            // Fallback: insert after product controls
            const controlsContainer = productEl.querySelector('.product-controls .flex') ||
                                     productEl.querySelector('.product-controls');
            if (controlsContainer) {
                injectButtonToContainer(controlsContainer, index, isAssigned);
            }
            return;
        }

        injectButtonToContainer(optionsContainer, index, isAssigned);
    });
}

/**
 * Update button visual state based on assignment status
 */
function updateButtonState(button: HTMLElement, isAssigned: boolean, productIndex: number): void {
    // Update product index data attribute
    button.dataset.productIndex = String(productIndex);
    
    // Ensure pointer events work
    (button as HTMLElement).style.pointerEvents = 'auto';
    
    // Update icon using innerHTML (icon has pointer-events:none so clicks go to button)
    button.innerHTML = isAssigned 
        ? '<i class="las la-check-circle text-xl" style="pointer-events:none"></i>'
        : '<i class="las la-hand-holding-usd text-xl" style="pointer-events:none"></i>';
    
    // Update button classes (matching NexoPOS pattern with outline-hidden)
    if (isAssigned) {
        button.className = 'rencommissions-btn cursor-pointer outline-hidden border-dashed py-1 border-b border-success-secondary text-success-secondary hover:text-success-tertiary text-sm';
        button.title = __('Commission Assigned');
    } else {
        button.className = 'rencommissions-btn cursor-pointer outline-hidden border-dashed py-1 border-b border-info-secondary text-info-secondary hover:text-info-tertiary text-sm';
        button.title = __('Assign Commission');
    }
    
    // Re-attach event listener only if not already attached
    const btn = button as HTMLElement & { _rcHandler?: boolean };
    if (!btn._rcHandler) {
        btn._rcHandler = true;
        btn.addEventListener('mousedown', handleButtonClick, false);
    }
}

/**
 * Inject a commission button into a container element
 */
function injectButtonToContainer(container: Element, productIndex: number, isAssigned: boolean = false): void {
    // Skip if button already exists
    if (container.querySelector('.rencommissions-btn')) {
        return;
    }

    // Create button wrapper to match NexoPOS style
    const buttonWrapper = document.createElement('div');
    buttonWrapper.className = 'px-1';
    
    // Create the commission button matching NexoPOS pattern (no href)
    const button = document.createElement('a') as HTMLAnchorElement & { _rcHandler?: boolean };
    button.className = isAssigned
        ? 'rencommissions-btn cursor-pointer outline-hidden border-dashed py-1 border-b border-success-secondary text-success-secondary hover:text-success-tertiary text-sm'
        : 'rencommissions-btn cursor-pointer outline-hidden border-dashed py-1 border-b border-info-secondary text-info-secondary hover:text-info-tertiary text-sm';
    button.style.pointerEvents = 'auto';
    button.title = isAssigned ? __('Commission Assigned') : __('Assign Commission');
    button.innerHTML = isAssigned 
        ? '<i class="las la-check-circle text-xl" style="pointer-events:none"></i>'
        : '<i class="las la-hand-holding-usd text-xl" style="pointer-events:none"></i>';
    
    // Store product index as data attribute
    button.dataset.productIndex = String(productIndex);
    
    // Use mousedown (fires immediately before any DOM changes can interfere)
    button._rcHandler = true;
    button.addEventListener('mousedown', handleButtonClick, false);

    buttonWrapper.appendChild(button);
    container.appendChild(buttonWrapper);
}

/**
 * Global click handler for commission buttons
 */
function handleButtonClick(e: Event): void {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    
    // Prevent double-processing
    if ((window as any).__rcProcessing) {
        return;
    }
    (window as any).__rcProcessing = true;
    
    const button = e.currentTarget as HTMLElement;
    const productIndex = parseInt(button.dataset.productIndex || '0', 10);
    
    console.log('[RenCommissions] Button clicked, product index:', productIndex);
    
    // Clear flag after short delay
    setTimeout(() => {
        (window as any).__rcProcessing = false;
    }, 100);
    
    handleCommissionClick(productIndex);
}

/**
 * Handle commission button click
 */
async function handleCommissionClick(productIndex: number): Promise<void> {
    console.log('[RenCommissions] Commission button clicked for product index:', productIndex);
    
    // Get product data from POS state
    const product = getProductFromPosState(productIndex);
    
    if (!product) {
        nsSnackBar.error(__('Could not retrieve product information'));
        return;
    }

    showCommissionPopup(product);
}

/**
 * Get product data from POS reactive state
 */
function getProductFromPosState(productIndex: number): CartProduct | null {
    try {
        // Try to access POS global state
        if (typeof POS !== 'undefined' && POS.products) {
            let products: CartProduct[] = [];
            
            // POS.products is a BehaviorSubject - get current value
            if (typeof POS.products.getValue === 'function') {
                products = POS.products.getValue();
            } else if (Array.isArray(POS.products)) {
                products = POS.products;
            }
            
            // Debug: Log all products in cart
            console.log('[RenCommissions] POS products:', products.map((p, i) => ({
                index: i,
                id: p.id,
                product_id: p.product_id,
                name: p.name,
            })));
            
            if (products && products[productIndex]) {
                const p = products[productIndex];
                console.log('[RenCommissions] Selected product at index', productIndex, ':', {
                    id: p.id,
                    product_id: p.product_id,
                    name: p.name,
                });
                return {
                    id: p.id,
                    product_id: p.product_id,
                    name: p.name,
                    product_name: p.name,
                    unit_price: p.unit_price,
                    quantity: p.quantity,
                    barcode: p.barcode,
                };
            }
        }
        
        // Fallback: extract from DOM
        return extractProductFromDom(productIndex);
        
    } catch (error) {
        console.error('[RenCommissions] Error getting product from state:', error);
        return extractProductFromDom(productIndex);
    }
}

/**
 * Extract product data from DOM as fallback
 */
function extractProductFromDom(productIndex: number): CartProduct | null {
    const productItems = document.querySelectorAll('.product-item, [product-index]');
    const productEl = productItems[productIndex];
    
    if (!productEl) {
        return null;
    }

    // Try to extract name
    const nameEl = productEl.querySelector('h3, .font-semibold, .product-name');
    const name = nameEl?.textContent?.trim().split('—')[0]?.trim() || `Product ${productIndex + 1}`;
    
    // Try to extract price
    const priceEl = productEl.querySelector('[class*="price"], a[class*="cursor-pointer"]');
    let unitPrice = 0;
    if (priceEl) {
        const priceText = priceEl.textContent || '';
        const priceMatch = priceText.match(/[\d,.]+/);
        if (priceMatch) {
            unitPrice = parseFloat(priceMatch[0].replace(/,/g, '')) || 0;
        }
    }

    // Try to extract quantity
    const quantityEl = productEl.querySelector('.border-dashed.border-secondary.p-2, [class*="quantity"]');
    let quantity = 1;
    if (quantityEl) {
        quantity = parseFloat(quantityEl.textContent?.trim() || '1') || 1;
    }

    return {
        id: productIndex,
        product_id: undefined,
        name: name,
        product_name: name,
        unit_price: unitPrice,
        quantity: quantity,
    };
}

/**
 * Show commission assignment popup
 */
async function showCommissionPopup(product: CartProduct): Promise<void> {
    try {
        // Load eligible earners and commission types in parallel
        const [earnersResult, typesResult] = await Promise.all([
            fetchData('/api/rencommissions/earners'),
            fetchData('/api/rencommissions/types'),
        ]);

        const earners: Earner[] = earnersResult.data || earnersResult || [];
        const types: CommissionType[] = typesResult.data || typesResult || [];

        if (earners.length === 0) {
            nsSnackBar.error(__('No eligible earners found'));
            return;
        }

        if (types.length === 0) {
            nsSnackBar.error(__('No commission types configured'));
            return;
        }

        // Step 1: Select earner
        const selectedEarner = await selectEarner(earners);
        if (!selectedEarner) return;

        // Step 2: Select commission type
        const selectedType = await selectCommissionType(types);
        if (!selectedType) return;

        // Step 3: Preview commission
        const preview = await previewCommission(product, selectedEarner, selectedType);
        if (!preview) return;

        // Step 4: Confirm and assign
        const confirmed = await confirmCommission(product, selectedEarner, selectedType, preview);
        if (!confirmed) return;

        // Step 5: Save assignment
        await assignCommission(product, selectedEarner, selectedType, preview);

    } catch (error: any) {
        console.error('[RenCommissions] Error in popup:', error);
        nsSnackBar.error(error.message || __('Failed to assign commission'));
    }
}

/**
 * Fetch data from API
 */
function fetchData(url: string): Promise<any> {
    return new Promise((resolve, reject) => {
        nsHttpClient.get(url).subscribe({
            next: (response: any) => resolve(response),
            error: (error: any) => reject(error),
        });
    });
}

/**
 * Select earner from popup
 */
function selectEarner(earners: Earner[]): Promise<Earner | null> {
    return new Promise((resolve) => {
        Popup.show(nsSelectPopup, {
            label: __('Select Earner'),
            description: __('Choose who will receive the commission'),
            options: earners.map(e => ({
                label: e.display_name || e.username,
                value: e.id,
            })),
            resolve: (selectedId: number) => {
                const earner = earners.find(e => e.id === selectedId);
                resolve(earner || null);
            },
            reject: () => resolve(null),
        });
    });
}

/**
 * Select commission type from popup
 */
function selectCommissionType(types: CommissionType[]): Promise<CommissionType | null> {
    return new Promise((resolve) => {
        Popup.show(nsSelectPopup, {
            label: __('Select Commission Type'),
            description: __('Choose the commission calculation method'),
            options: types.map(t => ({
                label: `${t.name} (${formatTypeValue(t)})`,
                value: t.id,
            })),
            resolve: (selectedId: number) => {
                const type = types.find(t => t.id === selectedId);
                resolve(type || null);
            },
            reject: () => resolve(null),
        });
    });
}

/**
 * Format commission type value for display
 */
function formatTypeValue(type: CommissionType): string {
    switch (type.calculation_method) {
        case 'percentage':
            return `${type.default_value}%`;
        case 'fixed':
            return __('Fixed');
        case 'on_the_house':
            return __('On The House');
        default:
            return String(type.default_value);
    }
}

/**
 * Preview commission calculation
 */
async function previewCommission(product: CartProduct, earner: Earner, type: CommissionType): Promise<any> {
    return new Promise((resolve) => {
        nsHttpClient.post('/api/rencommissions/preview', {
            product_id: product.product_id || product.id,
            commission_type: type.calculation_method,
            unit_price: product.unit_price,
            quantity: product.quantity || 1,
            commission_value: type.default_value,
        }).subscribe({
            next: (response: any) => resolve(response.data || response),
            error: (error: any) => {
                console.error('[RenCommissions] Preview error:', error);
                nsSnackBar.error(error.message || __('Failed to calculate commission'));
                resolve(null);
            },
        });
    });
}

/**
 * Confirm commission assignment
 */
function confirmCommission(product: CartProduct, earner: Earner, type: CommissionType, preview: any): Promise<boolean> {
    return new Promise((resolve) => {
        const productName = product.name || product.product_name || 'Product';
        const earnerName = earner.display_name || earner.username;
        const amount = preview.total_commission || preview.commission_per_unit || '0';
        
        const message = `${__('Assign commission of')} ${amount} ${__('to')} ${earnerName} ${__('for')} ${productName}?`;

        Popup.show(nsConfirmPopup, {
            title: __('Confirm Commission'),
            message: message,
            onAction: (confirmed: boolean) => resolve(confirmed),
        });
    });
}

/**
 * Assign the commission
 */
async function assignCommission(product: CartProduct, earner: Earner, type: CommissionType, preview: any): Promise<void> {
    return new Promise((resolve, reject) => {
        const productId = product.product_id || product.id || 0;
        
        // Debug: Log the product data being sent
        console.log('[RenCommissions] Assigning commission:', {
            product_raw: product,
            product_product_id: product.product_id,
            product_id_field: product.id,
            resolved_productId: productId,
        });
        
        nsHttpClient.post('/api/rencommissions/session/assign', {
            product_index: productId, // Use product_id as index for uniqueness
            product_id: productId,
            staff_id: earner.id,
            commission_type: type.calculation_method,
            unit_price: product.unit_price,
            quantity: product.quantity || 1,
            commission_value: preview.rate || type.default_value,
        }).subscribe({
            next: (response: any) => {
                nsSnackBar.success(response.message || __('Commission assigned successfully'));
                
                // Store assignment by product_id for state persistence
                assignedProducts.set(productId, {
                    earner_id: earner.id,
                    earner_name: earner.display_name || earner.username,
                    commission_type: type.calculation_method,
                    amount: preview.total_commission || 0,
                });
                
                // Update all buttons to reflect current state
                refreshButtonStates();
                resolve();
            },
            error: (error: any) => {
                nsSnackBar.error(error.message || __('Failed to assign commission'));
                reject(error);
            },
        });
    });
}

/**
 * Refresh all button states based on assignedProducts map
 */
function refreshButtonStates(): void {
    const productItems = document.querySelectorAll('.product-item, [product-index]');
    
    productItems.forEach((productEl, index) => {
        const product = getProductFromPosState(index);
        const productId = product?.product_id || product?.id;
        const isAssigned = productId ? assignedProducts.has(productId) : false;
        
        const button = productEl.querySelector('.rencommissions-btn') as HTMLElement;
        if (button) {
            updateButtonState(button, isAssigned, index);
        }
    });
}

/**
 * Mark product button as assigned (visual feedback) - DEPRECATED, use refreshButtonStates
 */
function markProductAsAssigned(productIndex: number): void {
    // This function is kept for backwards compatibility but does nothing
    // State is now managed by assignedProducts map and refreshButtonStates()
}
