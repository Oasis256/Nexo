{{-- Commission Module Vue Components --}}
<script>
    /**
     * Commission Module Vue Components
     * Registers components for commission management in CRUD forms and POS
     */
    
    console.log('[Commission] Vue components script loading...');
    
    // Wait for Vue globals to be available
    (function initCommissionComponents() {
        // Check if required globals are available
        if (typeof defineComponent === 'undefined' || typeof nsExtraComponents === 'undefined') {
            console.log('[Commission] Waiting for Vue globals...');
            setTimeout(initCommissionComponents, 100);
            return;
        }
        
        console.log('[Commission] Vue globals available, initializing components...');

    // ============================================================
    // POS Commission User Selection Popup
    // ============================================================
    // This popup appears when adding products to cart to select
    // which user should earn the commission for each product.
    // Supports three commission types:
    // - on_the_house: Fixed commission that ignores product discounts
    // - fixed: Per-product fixed values
    // - percentage: Flat percentage of product price
    // ============================================================
    
    const NsCommissionUserPopup = defineComponent({
        name: 'NsCommissionUserPopup',
        props: ['popup'],
        template: `
            <div class="ns-box shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
                <div class="p-2 border-b ns-box-header flex justify-between items-center">
                    <h3 class="font-semibold text-primary">{{ __('Commission Assignment') }}</h3>
                    <ns-close-button @click="close()"></ns-close-button>
                </div>
                <div class="p-4 ns-box-body">
                    <div class="mb-4">
                        <p class="text-secondary text-sm mb-2">{{ __('Product') }}: <strong class="text-primary">@{{ productName }}</strong></p>
                        <p class="text-secondary text-sm">{{ __('Select who should earn commission for this product:') }}</p>
                    </div>
                    
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <ns-spinner></ns-spinner>
                    </div>
                    
                    <div v-else-if="eligibleUsers.length === 0" class="text-center py-8">
                        <i class="las la-user-slash text-4xl text-secondary mb-2"></i>
                        <p class="text-secondary">{{ __('No eligible users found for this product category.') }}</p>
                        <p class="text-sm text-secondary">{{ __('The order author will earn the commission.') }}</p>
                    </div>
                    
                    <div v-else class="space-y-2 max-h-[40vh] overflow-y-auto">
                        <!-- Default Option: Order Author -->
                        <div 
                            @click="selectUser(null)"
                            :class="['p-3 rounded border cursor-pointer transition-all flex items-center justify-between', 
                                selectedUserId === null ? 'border-info-primary bg-info-primary/10' : 'border-box-edge hover:border-info-secondary']"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <i class="las la-user text-xl text-gray-600"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-primary">{{ __('Default (Order Author)') }}</span>
                                    <p class="text-xs text-secondary">{{ __('Commission goes to whoever creates the order') }}</p>
                                </div>
                            </div>
                            <i v-if="selectedUserId === null" class="las la-check-circle text-2xl text-info-primary"></i>
                        </div>
                        
                        <!-- User Options -->
                        <div 
                            v-for="user in eligibleUsers" 
                            :key="user.id"
                            @click="selectUser(user.id)"
                            :class="['p-3 rounded border cursor-pointer transition-all flex items-center justify-between', 
                                selectedUserId === user.id ? 'border-info-primary bg-info-primary/10' : 'border-box-edge hover:border-info-secondary']"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-info-primary/20 flex items-center justify-center">
                                    <i class="las la-user text-xl text-info-primary"></i>
                                </div>
                                <div>
                                    <span class="font-medium text-primary">@{{ user.username }}</span>
                                    <p class="text-xs text-secondary">@{{ user.email }}</p>
                                </div>
                            </div>
                            <i v-if="selectedUserId === user.id" class="las la-check-circle text-2xl text-info-primary"></i>
                        </div>
                    </div>
                    
                    <!-- Commission Preview -->
                    <div v-if="commissionPreview && selectedUserId" class="mt-4 p-3 rounded bg-success-primary/10 border border-success-primary/30">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-secondary">{{ __('Estimated Commission') }}:</span>
                            <span class="font-semibold text-success-primary">@{{ commissionPreview.formatted_value }}</span>
                        </div>
                        <p class="text-xs text-secondary mt-1">
                            @{{ commissionPreview.commission_name }} 
                            <span class="ml-1 px-1.5 py-0.5 rounded text-xs" :class="getTypeClass(commissionPreview.type)">
                                @{{ formatType(commissionPreview.type) }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="p-2 border-t ns-box-footer flex justify-end gap-2">
                    <ns-button @click="skip()" type="warning">{{ __('Skip') }}</ns-button>
                    <ns-button @click="confirm()" type="info">{{ __('Confirm') }}</ns-button>
                </div>
            </div>
        `,
        data() {
            return {
                selectedUserId: null,
                eligibleUsers: [],
                commissionPreview: null,
                isLoading: true
            };
        },
        computed: {
            productName() {
                return this.popup?.params?.product?.name || 'Unknown Product';
            },
            categoryId() {
                const product = this.popup?.params?.product;
                if (product?.$original) {
                    return product.$original().category_id;
                }
                return product?.category_id || null;
            },
            productId() {
                const product = this.popup?.params?.product;
                if (product?.$original) {
                    return product.$original().id;
                }
                return product?.id || product?.product_id || null;
            },
            unitPrice() {
                return this.popup?.params?.product?.unit_price || 0;
            }
        },
        mounted() {
            this.loadEligibleUsers();
        },
        methods: {
            getTypeClass(type) {
                const classes = {
                    'on_the_house': 'bg-purple-100 text-purple-800',
                    'fixed': 'bg-blue-100 text-blue-800',
                    'percentage': 'bg-green-100 text-green-800'
                };
                return classes[type] || 'bg-gray-100 text-gray-800';
            },
            formatType(type) {
                const labels = {
                    'on_the_house': '{{ __('On The House') }}',
                    'fixed': '{{ __('Fixed') }}',
                    'percentage': '{{ __('Percentage') }}'
                };
                return labels[type] || type;
            },
            async loadEligibleUsers() {
                if (!this.categoryId) {
                    this.isLoading = false;
                    return;
                }
                
                this.isLoading = true;
                nsHttpClient.get(`/api/commissions/eligible-users?category_id=${this.categoryId}`)
                    .subscribe({
                        next: (response) => {
                            this.eligibleUsers = response.data || [];
                            this.isLoading = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load eligible users:', err);
                            this.eligibleUsers = [];
                            this.isLoading = false;
                        }
                    });
            },
            
            selectUser(userId) {
                this.selectedUserId = userId;
                if (userId !== null) {
                    this.loadCommissionPreview(userId);
                } else {
                    this.commissionPreview = null;
                }
            },
            
            async loadCommissionPreview(userId) {
                nsHttpClient.post('/api/commissions/preview', {
                    product_id: this.productId,
                    category_id: this.categoryId,
                    unit_price: this.unitPrice,
                    quantity: 1,
                    user_id: userId
                }).subscribe({
                    next: (response) => {
                        this.commissionPreview = response.data;
                    },
                    error: (err) => {
                        console.error('[Commission] Failed to load preview:', err);
                        this.commissionPreview = null;
                    }
                });
            },
            
            skip() {
                // Skip without assigning - order author will get commission
                this.popup.params.resolve({});
                this.popup.close();
            },
            
            confirm() {
                this.popup.params.resolve({
                    commission_user_id: this.selectedUserId
                });
                this.popup.close();
            },
            
            close() {
                this.popup.params.reject(false);
                this.popup.close();
            }
        }
    });
    
    // Register the popup globally
    nsExtraComponents['NsCommissionUserPopup'] = NsCommissionUserPopup;
    
    // ============================================================
    // Commission User Queue Class for POS
    // ============================================================
    // This queue class integrates into POS.addToCartQueue to show
    // the commission user selection popup when adding products.
    // ============================================================
    
    class CommissionUserPromise {
        constructor(product) {
            this.product = product;
        }
        
        run(data) {
            return new Promise((resolve, reject) => {
                console.log('[Commission] CommissionUserPromise.run() called', this.product);
                
                // Skip for dynamic products (they don't have categories)
                if (this.product.product_type === 'dynamic') {
                    console.log('[Commission] Skipping dynamic product');
                    return resolve({});
                }
                
                // Check if commission user selection is enabled in settings
                const options = POS.options.getValue();
                console.log('[Commission] POS options:', options);
                console.log('[Commission] commission_show_in_pos:', options.commission_show_in_pos);
                
                if (options.commission_show_in_pos !== 'yes') {
                    console.log('[Commission] Commission selection disabled, skipping popup');
                    return resolve({});
                }
                
                console.log('[Commission] Showing commission user popup');
                // Show the commission user popup
                Popup.show(NsCommissionUserPopup, {
                    product: this.product,
                    resolve,
                    reject
                });
            });
        }
    }
    
    // Add to POS queue when POS is ready
    // Use multiple fallback methods to ensure queue is added
    function addCommissionQueueToPOS() {
        if (typeof POS !== 'undefined' && POS.addToCartQueue) {
            // Check if already added to prevent duplicates
            const alreadyAdded = POS.addToCartQueue.some(
                q => q.name === 'CommissionUserPromise' || q === CommissionUserPromise
            );
            if (!alreadyAdded) {
                POS.addToCartQueue.push(CommissionUserPromise);
                console.log('[Commission] Commission user queue added to POS');
            }
            return true;
        }
        return false;
    }
    
    // Try immediately
    if (!addCommissionQueueToPOS()) {
        // Try on DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            if (!addCommissionQueueToPOS()) {
                // Try with intervals as POS may load after DOM ready
                let attempts = 0;
                const maxAttempts = 20;
                const interval = setInterval(() => {
                    attempts++;
                    if (addCommissionQueueToPOS() || attempts >= maxAttempts) {
                        clearInterval(interval);
                        if (attempts >= maxAttempts) {
                            console.log('[Commission] POS not found after max attempts');
                        }
                    }
                }, 500);
            }
        });
    }
    
    // ============================================================
    // Hook into order submission to process commission assignments
    // ============================================================
    nsHooks.addAction('ns-order-before-submit', 'ns-commission', (order) => {
        // Add commission user assignments to each product
        if (order.products && Array.isArray(order.products)) {
            order.products.forEach(product => {
                if (product.commission_user_id !== undefined) {
                    // Ensure the commission_user_id is included in the product data
                    product.meta = product.meta || {};
                    product.meta.commission_user_id = product.commission_user_id;
                }
            });
        }
        console.log('[Commission] Order prepared with commission assignments');
    });
    
    // ============================================================
    // Commission Product Values Component - For CRUD Form Tab
    // ============================================================
    // This component allows setting individual commission values
    // for specific products when type is "fixed"
    // ============================================================
    
    nsExtraComponents['nsCommissionProductValues'] = defineComponent({
        name: 'nsCommissionProductValues',
        props: ['field', 'form'],
        template: `
            <div class="p-4">
                <div class="border-b border-box-edge pb-4 mb-4">
                    <h3 class="font-semibold text-primary">{{ __('Per-Product Commission Values') }}</h3>
                    <p class="text-secondary text-sm">{{ __('Set individual commission values for specific products. Products not listed will use the default commission value.') }}</p>
                </div>

                <!-- Search and Add Product -->
                <div class="flex gap-2 mb-4">
                    <div class="flex-auto relative">
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @input="searchProducts"
                            @focus="showSearchResults = true"
                            :placeholder="__('Search products by name or SKU...')"
                            class="w-full border border-box-edge rounded-md px-4 py-2 text-primary bg-box-background"
                        />
                        <!-- Search Results Dropdown -->
                        <div 
                            v-if="showSearchResults && searchResults.length > 0" 
                            class="absolute z-10 w-full mt-1 bg-box-background border border-box-edge rounded-md shadow-lg max-h-60 overflow-y-auto"
                        >
                            <div 
                                v-for="product in searchResults" 
                                :key="product.id"
                                @click="addProduct(product)"
                                class="px-4 py-2 hover:bg-info-primary hover:text-white cursor-pointer border-b border-box-edge last:border-b-0"
                            >
                                <span class="font-medium">@{{ product.name }}</span>
                                <span class="text-sm text-secondary ml-2">(@{{ product.sku }})</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <ns-spinner></ns-spinner>
                    <span class="ml-2 text-secondary">{{ __('Loading...') }}</span>
                </div>

                <!-- Product Values Table -->
                <div v-else-if="productValues.length > 0" class="border border-box-edge rounded-md overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-box-elevation-edge">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-primary">{{ __('Product') }}</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-primary w-40">{{ __('Commission Value') }}</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-primary w-20">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr 
                                v-for="(item, index) in productValues" 
                                :key="item.product_id"
                                class="border-t border-box-edge hover:bg-box-elevation-hover"
                            >
                                <td class="px-4 py-3">
                                    <span class="font-medium text-primary">@{{ item.product_name }}</span>
                                    <span v-if="item.product_sku" class="text-sm text-secondary ml-2">(@{{ item.product_sku }})</span>
                                </td>
                                <td class="px-4 py-3">
                                    <input 
                                        type="number" 
                                        v-model.number="item.value"
                                        step="0.01"
                                        min="0"
                                        class="w-full border border-box-edge rounded px-3 py-1 text-primary bg-box-background"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button 
                                        @click="removeProduct(index)"
                                        class="text-error-primary hover:text-error-secondary p-1"
                                        :title="__('Remove')"
                                    >
                                        <i class="las la-trash-alt text-xl"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="border border-dashed border-box-edge rounded-md p-8 text-center">
                    <i class="las la-box text-4xl text-secondary mb-2"></i>
                    <p class="text-secondary">{{ __('No product-specific values configured.') }}</p>
                    <p class="text-sm text-secondary">{{ __('Search and add products above to set individual commission values.') }}</p>
                </div>

                <!-- Save Button -->
                <div v-if="productValues.length > 0" class="mt-4 flex justify-end">
                    <ns-button @click="saveProductValues()" :disabled="isSaving" type="info">
                        <ns-spinner v-if="isSaving" size="sm" class="mr-2"></ns-spinner>
                        <i v-else class="las la-save mr-2"></i>
                        @{{ isSaving ? '{{ __('Saving...') }}' : '{{ __('Save Product Values') }}' }}
                    </ns-button>
                </div>
            </div>
        `,
        data() {
            return {
                productValues: [],
                searchQuery: '',
                searchResults: [],
                showSearchResults: false,
                isLoading: false,
                isSaving: false,
                searchTimeout: null
            };
        },
        computed: {
            commissionId() {
                // Try to get commission ID from form or URL
                if (this.form && this.form.id) {
                    return this.form.id;
                }
                // Extract from URL
                const match = window.location.pathname.match(/\/commissions\/edit\/(\d+)/);
                return match ? parseInt(match[1]) : null;
            }
        },
        mounted() {
            this.loadProductValues();
            document.addEventListener('click', this.handleClickOutside);
        },
        beforeUnmount() {
            document.removeEventListener('click', this.handleClickOutside);
        },
        methods: {
            async loadProductValues() {
                if (!this.commissionId) return;
                
                this.isLoading = true;
                try {
                    nsHttpClient.get(`/api/commissions/${this.commissionId}/product-values`)
                        .subscribe({
                            next: (response) => {
                                const data = response.data || [];
                                this.productValues = data.map(item => ({
                                    product_id: item.product_id,
                                    product_name: item.product?.name || `Product #${item.product_id}`,
                                    product_sku: item.product?.sku || '',
                                    value: parseFloat(item.value) || 0
                                }));
                                this.isLoading = false;
                            },
                            error: (err) => {
                                console.error('[Commission] Failed to load product values:', err);
                                this.isLoading = false;
                            }
                        });
                } catch (error) {
                    console.error('[Commission] Failed to load product values:', error);
                    this.isLoading = false;
                }
            },
            searchProducts() {
                if (this.searchTimeout) {
                    clearTimeout(this.searchTimeout);
                }

                if (this.searchQuery.length < 2) {
                    this.searchResults = [];
                    return;
                }

                this.searchTimeout = setTimeout(() => {
                    nsHttpClient.get(`/api/commissions/products/search?search=${encodeURIComponent(this.searchQuery)}`)
                        .subscribe({
                            next: (response) => {
                                const products = response.data || [];
                                const existingIds = this.productValues.map(pv => pv.product_id);
                                this.searchResults = products.filter(p => !existingIds.includes(p.id));
                            },
                            error: (err) => {
                                console.error('[Commission] Search failed:', err);
                            }
                        });
                }, 300);
            },
            addProduct(product) {
                this.productValues.push({
                    product_id: product.id,
                    product_name: product.name,
                    product_sku: product.sku,
                    value: 0
                });
                this.searchQuery = '';
                this.searchResults = [];
                this.showSearchResults = false;
            },
            removeProduct(index) {
                this.productValues.splice(index, 1);
            },
            saveProductValues() {
                if (!this.commissionId) {
                    nsSnackBar.error(__('Please save the commission first before adding product values.')).subscribe();
                    return;
                }

                this.isSaving = true;
                const payload = {
                    product_values: this.productValues.map(pv => ({
                        product_id: pv.product_id,
                        value: pv.value
                    }))
                };

                nsHttpClient.post(`/api/commissions/${this.commissionId}/product-values`, payload)
                    .subscribe({
                        next: (response) => {
                            nsSnackBar.success(__('Product values saved successfully.')).subscribe();
                            this.isSaving = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Save failed:', err);
                            nsSnackBar.error(__('Failed to save product values.')).subscribe();
                            this.isSaving = false;
                        }
                    });
            },
            handleClickOutside(event) {
                if (!this.$el.contains(event.target)) {
                    this.showSearchResults = false;
                }
            }
        }
    });

    // ============================================================
    // Product Commission User Select Component - For Order Details
    // ============================================================
    // Inline selector for changing commission user on order products
    // ============================================================
    
    nsExtraComponents['nsProductCommissionUserSelect'] = defineComponent({
        name: 'nsProductCommissionUserSelect',
        props: {
            productId: { type: Number, required: true },
            categoryId: { type: Number, required: true },
            unitPrice: { type: Number, required: true },
            quantity: { type: Number, default: 1 },
            orderId: { type: Number, default: null },
            orderProductId: { type: Number, default: null },
            initialUserId: { type: Number, default: null }
        },
        emits: ['change', 'preview'],
        template: `
            <div class="flex items-center gap-2">
                <div class="flex-auto">
                    <select 
                        v-model="selectedUserId"
                        @change="onUserChange"
                        :disabled="isLoading || eligibleUsers.length === 0"
                        class="w-full border border-box-edge rounded px-3 py-1.5 text-sm text-primary bg-box-background disabled:opacity-50"
                    >
                        <option :value="null">{{ __('Default (Order Author)') }}</option>
                        <option v-for="user in eligibleUsers" :key="user.id" :value="user.id">
                            @{{ user.username }}
                        </option>
                    </select>
                </div>
                <div v-if="commissionPreview" class="text-right min-w-[80px]">
                    <span class="text-xs text-secondary block">{{ __('Commission') }}</span>
                    <span class="text-sm font-medium text-success-primary">@{{ commissionPreview.formatted_value }}</span>
                </div>
                <ns-spinner v-if="isLoading" size="sm"></ns-spinner>
            </div>
        `,
        data() {
            return {
                selectedUserId: this.initialUserId,
                eligibleUsers: [],
                commissionPreview: null,
                isLoading: false
            };
        },
        mounted() {
            this.loadEligibleUsers();
            if (this.initialUserId) {
                this.loadCommissionPreview(this.initialUserId);
            }
        },
        watch: {
            quantity() { this.refreshPreview(); },
            unitPrice() { this.refreshPreview(); },
            categoryId() { this.loadEligibleUsers(); }
        },
        methods: {
            loadEligibleUsers() {
                this.isLoading = true;
                nsHttpClient.get(`/api/commissions/eligible-users?category_id=${this.categoryId}`)
                    .subscribe({
                        next: (response) => {
                            this.eligibleUsers = response.data || [];
                            this.isLoading = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load eligible users:', err);
                            this.isLoading = false;
                        }
                    });
            },
            loadCommissionPreview(userId) {
                if (!userId) {
                    this.commissionPreview = null;
                    return;
                }

                nsHttpClient.post('/api/commissions/preview', {
                    product_id: this.productId,
                    category_id: this.categoryId,
                    unit_price: this.unitPrice,
                    quantity: this.quantity,
                    user_id: userId
                }).subscribe({
                    next: (response) => {
                        this.commissionPreview = response.data;
                        this.$emit('preview', this.commissionPreview);
                    },
                    error: (err) => {
                        console.error('[Commission] Failed to load preview:', err);
                        this.commissionPreview = null;
                    }
                });
            },
            onUserChange() {
                this.$emit('change', {
                    userId: this.selectedUserId,
                    productId: this.productId,
                    orderProductId: this.orderProductId
                });

                if (this.selectedUserId) {
                    this.loadCommissionPreview(this.selectedUserId);
                } else {
                    this.commissionPreview = null;
                }
            },
            refreshPreview() {
                if (this.selectedUserId) {
                    this.loadCommissionPreview(this.selectedUserId);
                }
            }
        }
    });

    // ============================================================
    // Commission Total Earnings Widget Component
    // ============================================================
    
    nsExtraComponents['nsCommissionTotalEarnings'] = defineComponent({
        name: 'nsCommissionTotalEarnings',
        template: `
            <div class="ns-box rounded-lg shadow">
                <div class="p-4 ns-box-header border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-primary">{{ __('Total Commission Earnings') }}</h3>
                        <select v-model="period" @change="loadData" class="text-sm border border-box-edge rounded px-2 py-1 bg-box-background text-primary">
                            <option value="today">{{ __('Today') }}</option>
                            <option value="week">{{ __('This Week') }}</option>
                            <option value="month">{{ __('This Month') }}</option>
                            <option value="year">{{ __('This Year') }}</option>
                        </select>
                    </div>
                </div>
                <div class="p-6 ns-box-body">
                    <div v-if="isLoading" class="flex justify-center py-4">
                        <ns-spinner></ns-spinner>
                    </div>
                    <div v-else>
                        <div class="text-3xl font-bold text-success-primary mb-2">@{{ formattedTotal }}</div>
                        <div class="flex items-center gap-4 text-sm text-secondary">
                            <span><i class="las la-receipt mr-1"></i>@{{ commissionCount }} {{ __('commissions') }}</span>
                            <span v-if="percentChange !== null" :class="percentChange >= 0 ? 'text-success-primary' : 'text-error-primary'">
                                <i :class="percentChange >= 0 ? 'las la-arrow-up' : 'las la-arrow-down'"></i>
                                @{{ Math.abs(percentChange).toFixed(1) }}% {{ __('vs previous') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                period: 'month',
                totalAmount: 0,
                commissionCount: 0,
                percentChange: null,
                isLoading: true
            };
        },
        computed: {
            formattedTotal() {
                return ns.currency.format(this.totalAmount);
            }
        },
        mounted() {
            this.loadData();
        },
        methods: {
            loadData() {
                this.isLoading = true;
                nsHttpClient.get(`/api/commissions/statistics?period=${this.period}`)
                    .subscribe({
                        next: (response) => {
                            this.totalAmount = response.data.total_amount || 0;
                            this.commissionCount = response.data.count || 0;
                            this.percentChange = response.data.percent_change || null;
                            this.isLoading = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load statistics:', err);
                            this.isLoading = false;
                        }
                    });
            }
        }
    });

    // ============================================================
    // Commission Top Earners Widget Component
    // ============================================================
    
    nsExtraComponents['nsCommissionTopEarners'] = defineComponent({
        name: 'nsCommissionTopEarners',
        template: `
            <div class="ns-box rounded-lg shadow">
                <div class="p-4 ns-box-header border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-primary">{{ __('Top Commission Earners') }}</h3>
                        <select v-model="period" @change="loadData" class="text-sm border border-box-edge rounded px-2 py-1 bg-box-background text-primary">
                            <option value="week">{{ __('This Week') }}</option>
                            <option value="month">{{ __('This Month') }}</option>
                            <option value="year">{{ __('This Year') }}</option>
                        </select>
                    </div>
                </div>
                <div class="p-4 ns-box-body">
                    <div v-if="isLoading" class="flex justify-center py-4">
                        <ns-spinner></ns-spinner>
                    </div>
                    <div v-else-if="topEarners.length === 0" class="text-center py-4 text-secondary">
                        <i class="las la-user-slash text-3xl mb-2"></i>
                        <p>{{ __('No commission data available.') }}</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="(earner, index) in topEarners" :key="earner.user_id" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                                :class="index === 0 ? 'bg-yellow-100 text-yellow-800' : index === 1 ? 'bg-gray-100 text-gray-800' : index === 2 ? 'bg-orange-100 text-orange-800' : 'bg-box-elevation-edge text-secondary'">
                                @{{ index + 1 }}
                            </div>
                            <div class="flex-auto">
                                <div class="font-medium text-primary">@{{ earner.username }}</div>
                                <div class="text-xs text-secondary">@{{ earner.commission_count }} {{ __('commissions') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-success-primary">@{{ formatCurrency(earner.total_amount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                period: 'month',
                topEarners: [],
                isLoading: true
            };
        },
        mounted() {
            this.loadData();
        },
        methods: {
            formatCurrency(value) {
                return ns.currency.format(value);
            },
            loadData() {
                this.isLoading = true;
                nsHttpClient.get(`/api/commissions/top-earners?period=${this.period}&limit=5`)
                    .subscribe({
                        next: (response) => {
                            this.topEarners = response.data || [];
                            this.isLoading = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load top earners:', err);
                            this.isLoading = false;
                        }
                    });
            }
        }
    });

    // ============================================================
    // Recent Commissions Widget Component
    // ============================================================
    
    nsExtraComponents['nsCommissionRecent'] = defineComponent({
        name: 'nsCommissionRecent',
        template: `
            <div class="ns-box rounded-lg shadow">
                <div class="p-4 ns-box-header border-b flex justify-between items-center">
                    <h3 class="font-semibold text-primary">{{ __('Recent Commissions') }}</h3>
                    <a href="/dashboard/commissions/earned" class="text-sm text-info-primary hover:underline">{{ __('View All') }}</a>
                </div>
                <div class="ns-box-body">
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <ns-spinner></ns-spinner>
                    </div>
                    <div v-else-if="recentCommissions.length === 0" class="text-center py-8 text-secondary">
                        <i class="las la-receipt text-3xl mb-2"></i>
                        <p>{{ __('No recent commissions.') }}</p>
                    </div>
                    <div v-else>
                        <div v-for="commission in recentCommissions" :key="commission.id" 
                            class="flex items-center gap-3 p-3 border-b border-box-edge last:border-b-0 hover:bg-box-elevation-hover">
                            <div class="w-10 h-10 rounded-full bg-success-primary/20 flex items-center justify-center">
                                <i class="las la-coins text-xl text-success-primary"></i>
                            </div>
                            <div class="flex-auto">
                                <div class="font-medium text-primary">@{{ commission.username }}</div>
                                <div class="text-xs text-secondary">
                                    {{ __('Order') }} #@{{ commission.order_id }} • @{{ commission.product_name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-success-primary">@{{ formatCurrency(commission.amount) }}</div>
                                <div class="text-xs text-secondary">@{{ formatDate(commission.created_at) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                recentCommissions: [],
                isLoading: true
            };
        },
        mounted() {
            this.loadData();
        },
        methods: {
            formatCurrency(value) {
                return ns.currency.format(value);
            },
            formatDate(date) {
                return new Date(date).toLocaleDateString();
            },
            loadData() {
                this.isLoading = true;
                nsHttpClient.get('/api/commissions/recent?limit=5')
                    .subscribe({
                        next: (response) => {
                            this.recentCommissions = response.data || [];
                            this.isLoading = false;
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load recent commissions:', err);
                            this.isLoading = false;
                        }
                    });
            }
        }
    });

    // ============================================================
    // Commission Daily Chart Component
    // ============================================================
    
    nsExtraComponents['nsCommissionDailyChart'] = defineComponent({
        name: 'nsCommissionDailyChart',
        props: {
            startDate: { type: String, default: null },
            endDate: { type: String, default: null }
        },
        template: `
            <div class="ns-box rounded-lg shadow">
                <div class="p-4 ns-box-header border-b">
                    <h3 class="font-semibold text-primary">{{ __('Daily Commission Earnings') }}</h3>
                </div>
                <div class="p-4 ns-box-body">
                    <div v-if="isLoading" class="flex justify-center py-8">
                        <ns-spinner></ns-spinner>
                    </div>
                    <div v-else>
                        <canvas ref="chartCanvas" height="200"></canvas>
                    </div>
                </div>
            </div>
        `,
        data() {
            return {
                chart: null,
                isLoading: true,
                chartData: []
            };
        },
        mounted() {
            this.loadData();
        },
        beforeUnmount() {
            if (this.chart) {
                this.chart.destroy();
            }
        },
        watch: {
            startDate() { this.loadData(); },
            endDate() { this.loadData(); }
        },
        methods: {
            loadData() {
                this.isLoading = true;
                
                let url = '/api/commissions/daily-earnings';
                const params = [];
                if (this.startDate) params.push(`start_date=${this.startDate}`);
                if (this.endDate) params.push(`end_date=${this.endDate}`);
                if (params.length > 0) url += '?' + params.join('&');
                
                nsHttpClient.get(url)
                    .subscribe({
                        next: (response) => {
                            this.chartData = response.data || [];
                            this.isLoading = false;
                            this.$nextTick(() => this.renderChart());
                        },
                        error: (err) => {
                            console.error('[Commission] Failed to load daily earnings:', err);
                            this.isLoading = false;
                        }
                    });
            },
            renderChart() {
                if (!this.$refs.chartCanvas || typeof Chart === 'undefined') {
                    console.warn('[Commission] Chart.js not available');
                    return;
                }

                if (this.chart) {
                    this.chart.destroy();
                }

                const ctx = this.$refs.chartCanvas.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: this.chartData.map(d => d.date),
                        datasets: [{
                            label: '{{ __('Commission Earnings') }}',
                            data: this.chartData.map(d => d.amount),
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return ns.currency.format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return ns.currency.format(context.raw);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    });

    console.log('[Commission] Vue components registered successfully.');
    })(); // End of initCommissionComponents IIFE
</script>
