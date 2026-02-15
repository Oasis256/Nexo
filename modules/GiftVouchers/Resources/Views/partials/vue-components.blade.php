{{-- GiftVouchers Vue Components --}}
{{-- Load html5-qrcode library for camera-based QR scanning --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
    /**
     * GiftVouchers Module Vue Components
     * Registers components for voucher redemption in POS
     */
    
    console.log('[GiftVouchers] Vue components script loading...');
    
    // Wait for Vue globals to be available
    (function initGiftVouchers() {
        // Check if required globals are available
        if (typeof defineComponent === 'undefined' || typeof nsExtraComponents === 'undefined' || typeof Popup === 'undefined') {
            console.log('[GiftVouchers] Waiting for Vue globals... (defineComponent:', typeof defineComponent, ', nsExtraComponents:', typeof nsExtraComponents, ', Popup:', typeof Popup, ')');
            setTimeout(initGiftVouchers, 100);
            return;
        }
        
        console.log('[GiftVouchers] Vue globals available, initializing components...');

        // ============================================================
        // POS Voucher Redemption Popup with QR Camera Scanner
        // ============================================================
        
        const NsVoucherRedemptionPopup = defineComponent({
            name: 'NsVoucherRedemptionPopup',
            props: ['popup'],
            template: `
                <div class="ns-box shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
                    <div class="p-2 border-b ns-box-header flex justify-between items-center">
                        <h3 class="font-semibold text-primary">
                            <i class="las la-gift mr-2"></i>
                            {{ __('Redeem Gift Voucher') }}
                        </h3>
                        <ns-close-button @click="close()"></ns-close-button>
                    </div>
                    <div class="p-4 ns-box-body">
                        <!-- Mode Toggle: Manual Entry vs Camera Scan -->
                        <div class="mb-4 flex gap-2">
                            <button 
                                @click="setMode('manual')"
                                :class="['flex-1 py-2 px-4 rounded border transition-colors', 
                                    mode === 'manual' ? 'bg-info-primary text-white border-info-primary' : 'border-box-edge hover:border-info-secondary']"
                            >
                                <i class="las la-keyboard mr-2"></i>
                                {{ __('Manual Entry') }}
                            </button>
                            <button 
                                @click="setMode('camera')"
                                :class="['flex-1 py-2 px-4 rounded border transition-colors', 
                                    mode === 'camera' ? 'bg-info-primary text-white border-info-primary' : 'border-box-edge hover:border-info-secondary']"
                            >
                                <i class="las la-camera mr-2"></i>
                                {{ __('Scan QR Code') }}
                            </button>
                        </div>

                        <!-- Manual Code Entry -->
                        <div v-if="mode === 'manual'" class="mb-4">
                            <label class="block text-sm font-medium text-secondary mb-2">
                                {{ __('Voucher Code') }}
                            </label>
                            <div class="flex gap-2">
                                <input 
                                    ref="codeInput"
                                    type="text" 
                                    v-model="voucherCode"
                                    @keyup.enter="lookupVoucher"
                                    :disabled="isLoading || voucherLoaded"
                                    placeholder="{{ __('Enter voucher code...') }}"
                                    class="flex-auto border input-field"
                                />
                                <ns-button 
                                    @click="lookupVoucher" 
                                    :disabled="isLoading || !voucherCode || voucherLoaded" 
                                    type="info"
                                >
                                    <ns-spinner v-if="isLoading" size="sm"></ns-spinner>
                                    <i v-else class="las la-search"></i>
                                </ns-button>
                            </div>
                        </div>

                        <!-- Camera QR Scanner -->
                        <div v-if="mode === 'camera'" class="mb-4">
                            <!-- Camera Selection -->
                            <div v-if="!cameraStarted" class="mb-3">
                                <label class="block text-sm font-medium text-secondary mb-2">
                                    {{ __('Select Camera') }}
                                </label>
                                <div class="flex gap-2">
                                    <select 
                                        v-model="selectedCameraId" 
                                        class="flex-auto border input-field rounded px-3 py-2"
                                        :disabled="isLoadingCameras"
                                    >
                                        <option value="" disabled>{{ __('-- Select a camera --') }}</option>
                                        <option v-for="camera in availableCameras" :key="camera.id" :value="camera.id">
                                            @{{ camera.label || camera.id }}
                                        </option>
                                    </select>
                                    <ns-button @click="loadCameras" type="info" :disabled="isLoadingCameras">
                                        <ns-spinner v-if="isLoadingCameras" size="sm"></ns-spinner>
                                        <i v-else class="las la-sync"></i>
                                    </ns-button>
                                </div>
                                <p v-if="availableCameras.length === 0 && !isLoadingCameras && camerasLoaded" class="text-sm text-warning-primary mt-1">
                                    <i class="las la-exclamation-triangle mr-1"></i>
                                    {{ __('No cameras detected. Please connect a camera and click refresh.') }}
                                </p>
                            </div>

                            <div v-if="!cameraStarted && !cameraError" class="text-center py-4">
                                <ns-button 
                                    @click="startCamera" 
                                    type="info" 
                                    :disabled="isLoading || !selectedCameraId"
                                >
                                    <i class="las la-camera mr-2"></i>
                                    {{ __('Start Camera') }}
                                </ns-button>
                                <p v-if="!selectedCameraId && availableCameras.length > 0" class="text-sm text-secondary mt-2">
                                    {{ __('Please select a camera first') }}
                                </p>
                                <p v-else-if="selectedCameraId" class="text-sm text-secondary mt-2">
                                    {{ __('Click to activate camera for QR scanning') }}
                                </p>
                            </div>
                            
                            <div v-if="cameraError" class="text-center py-4">
                                <i class="las la-exclamation-triangle text-4xl text-error-primary mb-2"></i>
                                <p class="text-error-primary mb-2">@{{ cameraError }}</p>
                                <ns-button @click="retryCamera" type="info">
                                    <i class="las la-redo mr-2"></i>
                                    {{ __('Try Again') }}
                                </ns-button>
                            </div>

                            <div v-show="cameraStarted && !cameraError">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs text-secondary">
                                        <i class="las la-video mr-1"></i>
                                        @{{ currentCameraLabel }}
                                    </span>
                                    <div class="flex items-center gap-3">
                                        <button 
                                            @click="toggleMirror"
                                            :class="['text-xs hover:underline', isMirrored ? 'text-success-primary' : 'text-secondary']"
                                            :title="isMirrored ? '{{ __('Mirrored') }}' : '{{ __('Normal') }}'"
                                        >
                                            <i class="las la-arrows-alt-h mr-1"></i>
                                            {{ __('Mirror') }}
                                        </button>
                                        <button 
                                            @click="switchCamera" 
                                            v-if="availableCameras.length > 1"
                                            class="text-xs text-info-primary hover:underline"
                                        >
                                            <i class="las la-exchange-alt mr-1"></i>
                                            {{ __('Switch') }}
                                        </button>
                                    </div>
                                </div>
                                <div 
                                    id="qr-reader" 
                                    :style="{ 
                                        width: '100%', 
                                        maxWidth: '400px', 
                                        margin: '0 auto',
                                        transform: isMirrored ? 'scaleX(-1)' : 'none'
                                    }"
                                ></div>
                                <p class="text-sm text-secondary text-center mt-2">
                                    <i class="las la-info-circle mr-1"></i>
                                    {{ __('Point your camera at the voucher QR code') }}
                                </p>
                                <div class="text-center mt-2">
                                    <ns-button @click="stopCamera" type="warning" size="sm">
                                        <i class="las la-stop mr-1"></i>
                                        {{ __('Stop Camera') }}
                                    </ns-button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Error Message -->
                        <div v-if="errorMessage" class="mb-4 p-3 rounded border border-error-primary text-error-primary">
                            <p class="text-sm">
                                <i class="las la-exclamation-circle mr-1"></i>
                                @{{ errorMessage }}
                            </p>
                        </div>
                        
                        <!-- Voucher Details -->
                        <div v-if="voucher" class="mb-4 p-3 rounded border border-success-primary">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold text-primary">@{{ voucher.name }}</h4>
                                    <p class="text-sm text-secondary">{{ __('Code') }}: @{{ voucher.code }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded bg-success-tertiary text-white">
                                    @{{ voucher.status_label }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <span class="text-secondary">{{ __('Purchaser') }}:</span>
                                    <span class="text-primary font-medium">@{{ voucher.purchaser_name }}</span>
                                </div>
                                <div>
                                    <span class="text-secondary">{{ __('Expires') }}:</span>
                                    <span class="text-primary">@{{ voucher.expires_at || 'Never' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Voucher Items -->
                        <div v-if="voucherItems.length > 0" class="mb-4">
                            <h4 class="font-semibold text-primary mb-2">
                                {{ __('Available Items') }}
                            </h4>
                            <div class="border rounded-md overflow-hidden" style="max-height: 30vh; overflow-y: auto;">
                                <table class="w-full">
                                    <thead class="bg-input-background sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-secondary">
                                                <input 
                                                    type="checkbox" 
                                                    :checked="allSelected" 
                                                    @change="toggleSelectAll"
                                                    class="rounded"
                                                />
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-secondary">{{ __('Product') }}</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-secondary" style="width: 80px;">{{ __('Qty') }}</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-secondary" style="width: 96px;">{{ __('Price') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr 
                                            v-for="item in voucherItems" 
                                            :key="item.id"
                                            class="border-t"
                                        >
                                            <td class="px-3 py-2">
                                                <input 
                                                    type="checkbox" 
                                                    v-model="item.selected"
                                                    :disabled="item.remaining_quantity <= 0"
                                                    class="rounded"
                                                />
                                            </td>
                                            <td class="px-3 py-2">
                                                <span class="font-medium text-primary">@{{ item.product_name }}</span>
                                                <span v-if="item.unit_name" class="text-xs text-secondary ml-1">(@{{ item.unit_name }})</span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input 
                                                    type="number" 
                                                    v-model.number="item.redeem_quantity"
                                                    :min="1"
                                                    :max="item.remaining_quantity"
                                                    :disabled="!item.selected || item.remaining_quantity <= 0"
                                                    class="w-16 border rounded px-2 py-1 text-center text-sm input-field"
                                                    style="width: 64px;"
                                                />
                                                <div class="text-xs text-secondary mt-1">
                                                    {{ __('of') }} @{{ item.remaining_quantity }} {{ __('left') }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <span class="text-primary font-medium">@{{ formatCurrency(item.unit_price) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Empty State -->
                        <div v-if="voucherLoaded && voucherItems.length === 0" class="text-center py-8">
                            <i class="las la-box-open text-4xl text-secondary mb-2"></i>
                            <p class="text-secondary">{{ __('This voucher has no remaining items to redeem.') }}</p>
                        </div>
                    </div>
                    <div class="p-2 border-t ns-box-footer flex justify-between items-center">
                        <div v-if="selectedCount > 0" class="text-sm text-secondary">
                            @{{ selectedCount }} {{ __('item(s) selected') }}
                        </div>
                        <div v-else></div>
                        <div class="flex gap-2">
                            <ns-button @click="close()" type="warning">{{ __('Cancel') }}</ns-button>
                            <ns-button 
                                @click="addToCart()" 
                                :disabled="selectedCount === 0 || isProcessing" 
                                type="info"
                            >
                                <ns-spinner v-if="isProcessing" size="sm" class="mr-2"></ns-spinner>
                                <i v-else class="las la-cart-plus mr-2"></i>
                                {{ __('Add to Cart') }}
                            </ns-button>
                        </div>
                    </div>
                </div>
            `,
            data() {
                return {
                    mode: 'manual', // 'manual' or 'camera'
                    voucherCode: '',
                    voucher: null,
                    voucherItems: [],
                    voucherLoaded: false,
                    isLoading: false,
                    isProcessing: false,
                    errorMessage: '',
                    // Camera/QR state
                    cameraStarted: false,
                    cameraError: null,
                    html5QrCode: null,
                    // Camera selection
                    availableCameras: [],
                    selectedCameraId: '',
                    isLoadingCameras: false,
                    camerasLoaded: false,
                    isMirrored: true // Mirror by default for front cameras
                };
            },
            computed: {
                allSelected() {
                    const selectableItems = this.voucherItems.filter(i => i.remaining_quantity > 0);
                    return selectableItems.length > 0 && selectableItems.every(i => i.selected);
                },
                selectedCount() {
                    return this.voucherItems.filter(i => i.selected && i.redeem_quantity > 0).length;
                },
                currentCameraLabel() {
                    const camera = this.availableCameras.find(c => c.id === this.selectedCameraId);
                    return camera ? (camera.label || camera.id) : '{{ __('Unknown Camera') }}';
                }
            },
            mounted() {
                console.log('[GiftVouchers] NsVoucherRedemptionPopup MOUNTED', {
                    popup: this.popup,
                    hasCloseMethod: this.popup && typeof this.popup.close === 'function'
                });
                // Focus the input field
                this.$nextTick(() => {
                    if (this.$refs.codeInput) {
                        this.$refs.codeInput.focus();
                    }
                });
            },
            beforeUnmount() {
                // Clean up camera when component is destroyed
                this.stopCamera();
            },
            methods: {
                setMode(newMode) {
                    if (this.mode === 'camera' && newMode !== 'camera') {
                        this.stopCamera();
                    }
                    this.mode = newMode;
                    this.cameraError = null;
                    
                    if (newMode === 'manual') {
                        this.$nextTick(() => {
                            if (this.$refs.codeInput) {
                                this.$refs.codeInput.focus();
                            }
                        });
                    } else if (newMode === 'camera' && !this.camerasLoaded) {
                        // Auto-load cameras when switching to camera mode
                        this.loadCameras();
                    }
                },

                async loadCameras() {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.cameraError = '{{ __('QR scanner library not loaded. Please refresh the page.') }}';
                        return;
                    }

                    this.isLoadingCameras = true;
                    this.cameraError = null;

                    try {
                        const devices = await Html5Qrcode.getCameras();
                        this.availableCameras = devices.map(device => ({
                            id: device.id,
                            label: device.label || this.getCameraLabel(device.id, devices.indexOf(device))
                        }));
                        this.camerasLoaded = true;

                        // Auto-select back camera if available, otherwise first camera
                        if (this.availableCameras.length > 0 && !this.selectedCameraId) {
                            const backCamera = this.availableCameras.find(c => 
                                c.label.toLowerCase().includes('back') || 
                                c.label.toLowerCase().includes('rear') ||
                                c.label.toLowerCase().includes('environment')
                            );
                            this.selectedCameraId = backCamera ? backCamera.id : this.availableCameras[0].id;
                            
                            // Set initial mirror state based on selected camera
                            this.updateMirrorForCamera();
                        }

                        console.log('[GiftVouchers] Cameras loaded:', this.availableCameras);
                    } catch (err) {
                        console.error('[GiftVouchers] Failed to load cameras:', err);
                        if (err.name === 'NotAllowedError') {
                            this.cameraError = '{{ __('Camera access denied. Please allow camera access in your browser settings.') }}';
                        } else {
                            this.cameraError = '{{ __('Failed to detect cameras:') }} ' + (err.message || err);
                        }
                    } finally {
                        this.isLoadingCameras = false;
                    }
                },

                getCameraLabel(id, index) {
                    // Generate a user-friendly label if the browser doesn't provide one
                    return '{{ __('Camera') }} ' + (index + 1);
                },

                async startCamera() {
                    if (typeof Html5Qrcode === 'undefined') {
                        this.cameraError = '{{ __('QR scanner library not loaded. Please refresh the page.') }}';
                        return;
                    }

                    if (!this.selectedCameraId) {
                        this.cameraError = '{{ __('Please select a camera first.') }}';
                        return;
                    }

                    this.cameraError = null;
                    this.cameraStarted = true;

                    try {
                        // Small delay to ensure DOM element exists
                        await this.$nextTick();
                        
                        this.html5QrCode = new Html5Qrcode('qr-reader');
                        
                        const config = {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            aspectRatio: 1.0
                        };

                        // Use selected camera ID
                        await this.html5QrCode.start(
                            this.selectedCameraId,
                            config,
                            (decodedText, decodedResult) => {
                                console.log('[GiftVouchers] QR Code scanned:', decodedText);
                                this.onQrCodeScanned(decodedText);
                            },
                            (errorMessage) => {
                                // QR code parse error - ignore these as they happen constantly
                            }
                        );
                        
                        console.log('[GiftVouchers] Camera started successfully:', this.selectedCameraId);
                    } catch (err) {
                        console.error('[GiftVouchers] Camera error:', err);
                        this.cameraStarted = false;
                        
                        if (err.name === 'NotAllowedError') {
                            this.cameraError = '{{ __('Camera access denied. Please allow camera access and try again.') }}';
                        } else if (err.name === 'NotFoundError') {
                            this.cameraError = '{{ __('Selected camera not found. Please choose another camera.') }}';
                        } else if (err.name === 'NotReadableError') {
                            this.cameraError = '{{ __('Camera is already in use by another application.') }}';
                        } else {
                            this.cameraError = '{{ __('Failed to start camera:') }} ' + (err.message || err);
                        }
                    }
                },

                retryCamera() {
                    this.cameraError = null;
                    this.loadCameras();
                },

                async switchCamera() {
                    // Stop current camera
                    await this.stopCamera();
                    
                    // Find next camera in the list
                    const currentIndex = this.availableCameras.findIndex(c => c.id === this.selectedCameraId);
                    const nextIndex = (currentIndex + 1) % this.availableCameras.length;
                    this.selectedCameraId = this.availableCameras[nextIndex].id;
                    
                    // Auto-set mirror based on camera type
                    this.updateMirrorForCamera();
                    
                    // Start with new camera
                    await this.startCamera();
                },

                toggleMirror() {
                    this.isMirrored = !this.isMirrored;
                },

                updateMirrorForCamera() {
                    // Auto-enable mirror for front/user-facing cameras
                    const camera = this.availableCameras.find(c => c.id === this.selectedCameraId);
                    if (camera) {
                        const label = camera.label.toLowerCase();
                        const isFrontCamera = label.includes('front') || 
                                              label.includes('user') || 
                                              label.includes('facetime') ||
                                              label.includes('selfie');
                        const isBackCamera = label.includes('back') || 
                                             label.includes('rear') || 
                                             label.includes('environment');
                        
                        // Mirror front cameras, don't mirror back cameras
                        // For unknown cameras, keep current setting
                        if (isFrontCamera) {
                            this.isMirrored = true;
                        } else if (isBackCamera) {
                            this.isMirrored = false;
                        }
                    }
                },

                async stopCamera() {
                    if (this.html5QrCode) {
                        try {
                            const state = this.html5QrCode.getState();
                            if (state === Html5QrcodeScannerState.SCANNING) {
                                await this.html5QrCode.stop();
                            }
                        } catch (err) {
                            console.warn('[GiftVouchers] Error stopping camera:', err);
                        }
                        this.html5QrCode = null;
                    }
                    this.cameraStarted = false;
                },

                onQrCodeScanned(code) {
                    // Stop camera after successful scan
                    this.stopCamera();
                    
                    // Set the code and lookup
                    this.voucherCode = code;
                    this.mode = 'manual'; // Switch to manual mode to show the code
                    
                    // Auto-lookup the voucher
                    this.lookupVoucher();
                },

                formatCurrency(value) {
                    if (typeof nsCurrency !== 'undefined') {
                        return nsCurrency(value);
                    }
                    return '$' + parseFloat(value || 0).toFixed(2);
                },

                toggleSelectAll() {
                    const newState = !this.allSelected;
                    this.voucherItems.forEach(item => {
                        if (item.remaining_quantity > 0) {
                            item.selected = newState;
                        }
                    });
                },

                lookupVoucher() {
                    if (!this.voucherCode || this.isLoading) return;

                    this.isLoading = true;
                    this.errorMessage = '';
                    this.voucher = null;
                    this.voucherItems = [];
                    this.voucherLoaded = false;

                    nsHttpClient.post('/api/gift-vouchers/pos/lookup', { code: this.voucherCode })
                        .subscribe({
                            next: (response) => {
                                this.voucher = response.voucher;
                                this.voucherItems = (response.items || []).map(item => ({
                                    ...item,
                                    selected: item.remaining_quantity > 0,
                                    redeem_quantity: Math.min(1, item.remaining_quantity)
                                }));
                                this.voucherLoaded = true;
                                this.isLoading = false;
                                
                                // Play success sound if available
                                if (typeof ns !== 'undefined' && ns.playSound) {
                                    ns.playSound('success');
                                }
                            },
                            error: (err) => {
                                console.error('[GiftVouchers] Lookup failed:', err);
                                this.errorMessage = err.message || err.error || '{{ __('Failed to find voucher. Please check the code and try again.') }}';
                                this.isLoading = false;
                                
                                // Play error sound if available
                                if (typeof ns !== 'undefined' && ns.playSound) {
                                    ns.playSound('error');
                                }
                            }
                        });
                },

                async addToCart() {
                    const selectedItems = this.voucherItems.filter(i => i.selected && i.redeem_quantity > 0);

                    if (selectedItems.length === 0) {
                        nsSnackBar.error('{{ __('Please select at least one item to redeem.') }}').subscribe();
                        return;
                    }

                    this.isProcessing = true;

                    try {
                        for (const item of selectedItems) {
                            const product = {
                                product_id: item.product_id,
                                unit_id: item.unit_id,
                                unit_quantity_id: item.unit_quantity_id,
                                quantity: item.redeem_quantity,
                                unit_price: 0,
                                name: item.product_name,
                                $quantities: () => [{
                                    id: item.unit_quantity_id,
                                    quantity: item.redeem_quantity,
                                    sale_price: 0,
                                    sale_price_edit: 0
                                }],
                                $original: () => ({
                                    id: item.product_id,
                                    name: item.product_name,
                                    unit_id: item.unit_id
                                }),
                                voucher_id: this.voucher.id,
                                voucher_item_id: item.id,
                                voucher_code: this.voucher.code,
                                is_voucher_redemption: true
                            };

                            if (typeof POS !== 'undefined' && POS.addProductToCart) {
                                await new Promise((resolve, reject) => {
                                    POS.addProductToCart(product)
                                        .then(resolve)
                                        .catch(reject);
                                });
                            } else {
                                console.warn('[GiftVouchers] POS.addProductToCart not available');
                            }
                        }

                        nsSnackBar.success('{{ __('Voucher items added to cart successfully.') }}').subscribe();
                        this.close();

                    } catch (error) {
                        console.error('[GiftVouchers] Failed to add items to cart:', error);
                        nsSnackBar.error(error.message || '{{ __('Failed to add items to cart.') }}').subscribe();
                    } finally {
                        this.isProcessing = false;
                    }
                },

                close() {
                    this.stopCamera();
                    if (this.popup && typeof this.popup.close === 'function') {
                        this.popup.close();
                    }
                }
            }
        });
        
        // Register the popup globally
        nsExtraComponents['NsVoucherRedemptionPopup'] = NsVoucherRedemptionPopup;
        
        // ============================================================
        // POS Cart Header Button Component for Voucher Redemption
        // This is a Vue component that gets rendered in the cart header
        // ============================================================
        
        const NsPosCartVoucherButton = defineComponent({
            name: 'ns-pos-cart-voucher-button',
            props: {
                order: {
                    type: Object,
                    required: true
                },
                options: {
                    type: Object,
                    required: true
                },
                settings: {
                    type: Object,
                    required: false,
                    default: () => ({})
                }
            },
            template: `
                <div class="ns-button">
                    <button @click="openVoucherPopup()" class="w-full h-10 px-3 outline-hidden flex items-center">
                        <i class="las la-gift"></i>
                        <span class="ml-1 hidden md:inline-block">{{ __('Voucher') }}</span>
                    </button>
                </div>
            `,
            methods: {
                openVoucherPopup() {
                    console.log('[GiftVouchers] Opening voucher redemption popup');
                    console.log('[GiftVouchers] NsVoucherRedemptionPopup:', NsVoucherRedemptionPopup);
                    console.log('[GiftVouchers] NsVoucherRedemptionPopup has template:', !!NsVoucherRedemptionPopup.template);
                    try {
                        const popup = Popup.show(NsVoucherRedemptionPopup);
                        console.log('[GiftVouchers] Popup.show returned:', popup);
                    } catch (error) {
                        console.error('[GiftVouchers] Error showing popup:', error);
                    }
                }
            }
        });
        
        // Register the button component globally
        nsExtraComponents['NsPosCartVoucherButton'] = NsPosCartVoucherButton;
        
        // ============================================================
        // Add button to POS cart header when POS is ready
        // ============================================================
        
        function addVoucherButtonToPOS() {
            if (typeof POS === 'undefined' || !POS.cartHeaderButtons) {
                return false;
            }
            
            // Get current buttons
            const currentButtons = POS.cartHeaderButtons.getValue() || {};
            
            // Check if button already exists
            if (currentButtons.nsPosCartVoucherButton) {
                console.log('[GiftVouchers] Voucher button already exists');
                return true;
            }
            
            // Add the voucher button component (use markRaw if available)
            const buttonComponent = typeof markRaw !== 'undefined' 
                ? markRaw(NsPosCartVoucherButton) 
                : NsPosCartVoucherButton;
            
            POS.cartHeaderButtons.next({
                ...currentButtons,
                nsPosCartVoucherButton: buttonComponent
            });
            
            console.log('[GiftVouchers] Voucher button added to POS cart header');
            return true;
        }
        
        // Try to add button immediately, then retry if POS isn't ready
        if (!addVoucherButtonToPOS()) {
            document.addEventListener('DOMContentLoaded', () => {
                if (!addVoucherButtonToPOS()) {
                    let attempts = 0;
                    const maxAttempts = 30;
                    const interval = setInterval(() => {
                        attempts++;
                        if (addVoucherButtonToPOS() || attempts >= maxAttempts) {
                            clearInterval(interval);
                            if (attempts >= maxAttempts) {
                                console.log('[GiftVouchers] POS not found after max attempts - not on POS page');
                            }
                        }
                    }, 500);
                }
            });
        }
        
        // ============================================================
        // Hook into cart reset to preserve our button
        // ============================================================
        nsHooks.addAction('ns-before-cart-reset', 'gift-vouchers-preserve-button', () => {
            // After cart reset, re-add our button
            setTimeout(() => {
                addVoucherButtonToPOS();
            }, 100);
        });
        
        // ============================================================
        // Hook into order submission to process voucher redemptions
        // ============================================================
        nsHooks.addAction('ns-order-before-submit', 'gift-vouchers', (order) => {
            if (order.products && Array.isArray(order.products)) {
                order.products.forEach(product => {
                    if (product.is_voucher_redemption) {
                        // Ensure voucher metadata is included
                        product.meta = product.meta || {};
                        product.meta.voucher_id = product.voucher_id;
                        product.meta.voucher_item_id = product.voucher_item_id;
                        product.meta.voucher_code = product.voucher_code;
                        product.meta.is_voucher_redemption = true;
                    }
                });
            }
            console.log('[GiftVouchers] Order prepared with voucher redemptions');
        });
        
        console.log('[GiftVouchers] Vue components registered.');
    })(); // End of initGiftVouchers IIFE
</script>