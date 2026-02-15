@php
    $rcPosConfig = [
        'enabled' => ns()->option->get('rencommissions_enabled', 'yes') === 'yes',
        'show_button' => ns()->option->get('rencommissions_show_pos_button', 'yes') === 'yes',
        'require_assignment' => ns()->option->get('rencommissions_require_assignment', 'no') === 'yes',
    ];
@endphp
<script>
(() => {
    if (window.__renCommissionsPosBooted || window.__renCommissionsPosBooting) {
        return;
    }

    const canBoot = () => {
        return Boolean(
            window.POS &&
            window.nsHooks &&
            window.nsHttpClient &&
            window.Popup &&
            window.nsSelectPopup &&
            window.nsSnackBar &&
            window.nsExtraComponents &&
            window.defineComponent
        );
    };

    const start = () => {
        if (window.__renCommissionsPosBooted) {
            return;
        }

        if (!canBoot()) {
            return;
        }

        window.__renCommissionsPosBooted = true;

        const config = @json($rcPosConfig);

        if (!config.enabled) {
            return;
        }

        const state = {
            sessionId: null,
            assignments: {},
            types: null,
            earners: null,
            syncTimer: null,
            syncing: false,
        };

        const ensureSessionId = () => {
            if (!state.sessionId) {
                state.sessionId = `rc-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
            }

            const order = POS.order.getValue() || {};
            order.uuid = state.sessionId;
            order.rencommissions_session_id = state.sessionId;
            POS.order.next({ ...order });
            return state.sessionId;
        };

        const resetState = () => {
            state.sessionId = null;
            state.assignments = {};
        };

        const assignmentCount = () => Object.keys(state.assignments).length;

        const hasMissingAssignments = () => {
            const products = POS.products.getValue() || [];
            if (!products.length) {
                return false;
            }

            let assigned = 0;

            products.forEach((product) => {
                const lineId = getLineId(product);
                if (lineId && state.assignments[lineId]) {
                    assigned += 1;
                }
            });

            return assigned < products.length;
        };

        const getLineId = (product) => {
            if (!product) {
                return null;
            }

            if (!product.__rcLineId) {
                product.__rcLineId = `rc-line-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
            }

            return product.__rcLineId;
        };

        const formatCurrency = (value) => {
            if (typeof window.nsCurrency === 'function') {
                return window.nsCurrency(Number(value || 0));
            }

            return String(Number(value || 0).toFixed(2));
        };

        const removeAssignment = async (lineId, currentProductIndex = null) => {
            const entry = state.assignments[lineId];
            if (!entry) {
                return;
            }

            if (!state.sessionId) {
                delete state.assignments[lineId];
                return;
            }

            const indices = [entry.payload.product_index, currentProductIndex]
                .filter((index) => index !== null && index !== undefined)
                .map((index) => Number(index))
                .filter((index, idx, arr) => Number.isInteger(index) && arr.indexOf(index) === idx);

            for (const productIndex of indices) {
                await new Promise((resolve, reject) => {
                    nsHttpClient.post('/api/rencommissions/pos/session/remove', {
                        session_id: state.sessionId,
                        product_index: Number(productIndex),
                    }).subscribe({
                        next: resolve,
                        error: reject,
                    });
                });
            }

            delete state.assignments[lineId];
        };

        const assignLine = async (productIndex = null) => {
            const products = POS.products.getValue() || [];
            if (products.length === 0) {
                nsSnackBar.error('Add a product before assigning commission.');
                return;
            }

            const selectedIndex = productIndex === null
                ? await selectOption({
                    label: 'Select Product',
                    options: products.map((product, index) => ({
                        label: `${product.name || 'Product'} x ${product.quantity ?? 1}`,
                        value: index,
                    })),
                })
                : productIndex;

            const selectedProduct = products[Number(selectedIndex)];
            if (!selectedProduct) {
                throw new Error('Invalid product selected.');
            }
            const lineId = getLineId(selectedProduct);

            const earners = await loadEarners();
            if (!earners.length) {
                nsSnackBar.error('No earners available for commission assignment.');
                return;
            }

            const currentUserId = getCurrentUserId();
            const selectedEarnerId = await selectOption({
                label: 'Select Earner',
                options: earners.map((earner) => ({
                    label: earner.username || earner.email || `User #${earner.id}`,
                    value: Number(earner.id),
                })),
                value: currentUserId || Number(earners[0].id),
            });

            const earnerId = Number(selectedEarnerId);
            if (!earnerId) {
                nsSnackBar.error('No earner selected.');
                return;
            }
            const selectedEarner = earners.find((earner) => Number(earner.id) === earnerId);
            const earnerLabel = selectedEarner?.username || selectedEarner?.email || `User #${earnerId}`;

            const types = await loadTypes();
            const selectedTypeId = await selectOption({
                label: 'Commission Type',
                options: [
                    { label: 'Default Product Commission', value: 0 },
                    ...types.map((type) => ({ label: type.name, value: Number(type.id) })),
                ],
                value: 0,
            });

            const selectedType = types.find((type) => Number(type.id) === Number(selectedTypeId));
            const method = selectedType?.calculation_method ?? 'fixed';
            const value = Number(selectedType?.default_value ?? selectedProduct?.commission_value ?? 0);

            if (value <= 0 && method !== 'on_the_house' && method !== 'fixed') {
                nsSnackBar.error('Commission value is 0. Set product/type commission first.');
                return;
            }

            const sessionId = ensureSessionId();

            const payload = {
                session_id: sessionId,
                product_index: Number(selectedIndex),
                product_id: Number(selectedProduct.product_id ?? selectedProduct.id ?? 0),
                earner_id: earnerId,
                type_id: selectedType ? Number(selectedType.id) : null,
                commission_method: method,
                commission_value: value,
                unit_price: Number(selectedProduct.unit_price ?? 0),
                quantity: Number(selectedProduct.quantity ?? 1),
            };

            if (payload.product_id <= 0) {
                nsSnackBar.error('This line cannot receive commission.');
                return;
            }

            const previous = state.assignments[lineId];
            if (previous && Number(previous.payload.product_index) !== Number(payload.product_index)) {
                await removeAssignment(lineId, payload.product_index);
            }

            const response = await new Promise((resolve, reject) => {
                nsHttpClient.post('/api/rencommissions/pos/session/assign', payload).subscribe({
                    next: resolve,
                    error: reject,
                });
            });

            const totalCommission = Number(
                response?.data?.total_commission
                ?? response?.total_commission
                ?? payload.commission_value
                ?? 0
            );

            const methodLabel = payload.commission_method === 'percentage'
                ? `${Number(payload.commission_value)}%`
                : '';

            state.assignments[lineId] = {
                lineId,
                label: methodLabel.length > 0
                    ? `${earnerLabel} ${methodLabel} : ${formatCurrency(totalCommission)}`
                    : `${earnerLabel} : ${formatCurrency(totalCommission)}`,
                assigned: true,
                payload,
            };
        };

        const syncAssignmentsToCurrentIndexes = async () => {
            if (state.syncing || !state.sessionId) {
                return;
            }

            state.syncing = true;

            try {
                const products = POS.products.getValue() || [];
                const lineIndexMap = new Map();

                products.forEach((product, index) => {
                    const lineId = getLineId(product);
                    if (lineId) {
                        lineIndexMap.set(lineId, index);
                    }
                });

                for (const lineId of Object.keys(state.assignments)) {
                    const entry = state.assignments[lineId];
                    const newIndex = lineIndexMap.get(lineId);

                    // Product removed from cart
                    if (newIndex === undefined) {
                        await removeAssignment(lineId, null);
                        continue;
                    }

                    if (Number(entry.payload.product_index) === Number(newIndex)) {
                        continue;
                    }

                    await removeAssignment(lineId, newIndex);

                    const nextPayload = {
                        ...entry.payload,
                        product_index: Number(newIndex),
                    };

                    await new Promise((resolve, reject) => {
                        nsHttpClient.post('/api/rencommissions/pos/session/assign', nextPayload).subscribe({
                            next: resolve,
                            error: reject,
                        });
                    });

                    state.assignments[lineId] = {
                        ...entry,
                        payload: nextPayload,
                    };
                }
            } finally {
                state.syncing = false;
            }
        };

        const scheduleSync = () => {
            if (state.syncTimer) {
                clearTimeout(state.syncTimer);
            }

            state.syncTimer = setTimeout(() => {
                syncAssignmentsToCurrentIndexes()
                    .catch(() => null)
                    .finally(() => renderInlineCommissions());
            }, 120);
        };

        const renderInlineCommissions = () => {
            const rows = document.querySelectorAll('#cart-products-table .product-item');
            if (!rows.length) {
                return;
            }

            rows.forEach((row) => {
                const controls = row.querySelector('.product-controls .-mx-1.flex');
                if (!controls) {
                    return;
                }

                controls.querySelectorAll('.ren-commission-chip').forEach((node) => node.remove());

                if (!config.show_button) {
                    return;
                }

                const productIndex = Number(row.getAttribute('product-index'));
                if (!Number.isInteger(productIndex)) {
                    return;
                }

                const products = POS.products.getValue() || [];
                const product = products[productIndex] || null;
                const lineId = getLineId(product);

                const detail = (lineId ? state.assignments[lineId] : null) ?? {
                    label: `Commission : ${formatCurrency(0)}`,
                    assigned: false,
                };

                const wrap = document.createElement('div');
                wrap.className = 'px-1 w-1/2 md:w-auto mb-1 ren-commission-chip';

                const link = document.createElement('a');
                link.className = 'cursor-pointer outline-hidden border-dashed py-1 border-b border-secondary text-sm';
                link.textContent = detail.label;
                link.onclick = async () => {
                    try {
                        const action = await selectOption({
                            label: 'Commission Action',
                            options: detail.assigned
                                ? [
                                    { label: 'Reassign', value: 'reassign' },
                                    { label: 'Remove', value: 'remove' },
                                ]
                                : [
                                    { label: 'Assign', value: 'reassign' },
                                ],
                            value: 'reassign',
                        });

                        if (action === 'remove') {
                            if (lineId) {
                                await removeAssignment(lineId, productIndex);
                            }
                            renderInlineCommissions();
                            nsSnackBar.success('Commission removed.');
                            return;
                        }

                        await assignLine(productIndex);
                        renderInlineCommissions();
                        nsSnackBar.success('Commission updated.');
                    } catch (error) {
                        if (error !== false) {
                            nsSnackBar.error(error?.message || 'Unable to update commission.');
                        }
                    }
                };

                wrap.appendChild(link);
                controls.appendChild(wrap);
            });
        };

        const getCurrentUserId = () => {
            return Number(
                window?.ns?.user?.id
                ?? window?.ns?.user?.user_id
                ?? window?.ns?.user?.attributes?.user_id
                ?? 0
            );
        };

        const selectOption = ({ label, options, value = null }) => {
            return new Promise((resolve, reject) => {
                Popup.show(nsSelectPopup, {
                    label,
                    options,
                    value,
                    type: 'select',
                    resolve,
                    reject,
                });
            });
        };

        const loadTypes = () => {
            if (Array.isArray(state.types)) {
                return Promise.resolve(state.types);
            }

            return new Promise((resolve, reject) => {
                nsHttpClient.get('/api/rencommissions/pos/types').subscribe({
                    next: (response) => {
                        const records = response?.data ?? response ?? [];
                        state.types = Array.isArray(records) ? records : [];
                        resolve(state.types);
                    },
                    error: reject,
                });
            });
        };

        const loadEarners = () => {
            if (Array.isArray(state.earners)) {
                return Promise.resolve(state.earners);
            }

            return new Promise((resolve, reject) => {
                nsHttpClient.get('/api/rencommissions/pos/earners').subscribe({
                    next: (response) => {
                        const records = response?.data ?? response ?? [];
                        state.earners = Array.isArray(records) ? records : [];
                        resolve(state.earners);
                    },
                    error: reject,
                });
            });
        };

        const registerButton = () => {
            if (!window.nsExtraComponents || !window.defineComponent || !window.POS) {
                return;
            }

            if (!config.show_button) {
                return;
            }

        window.nsExtraComponents['ns-rencommissions-assign-button'] = defineComponent({
            name: 'NsRenCommissionsAssignButton',
            data() {
                return {
                    assigning: false,
                };
            },
            computed: {
                label() {
                    const count = assignmentCount();
                    return count > 0
                        ? `Commission (${count})`
                        : 'Commission';
                },
            },
            methods: {
                async assign() {
                    if (this.assigning) {
                        return;
                    }

                    this.assigning = true;

                    try {
                        await assignLine();
                        renderInlineCommissions();
                        nsSnackBar.success('Commission assigned.');
                    } catch (error) {
                        if (error !== false) {
                            nsSnackBar.error(error?.message || 'Unable to assign commission.');
                        }
                    } finally {
                        this.assigning = false;
                    }
                },
            },
            template: `
                <button
                    class="w-full h-10 px-3 outline-hidden border-r border-box-edge text-sm"
                    @click="assign"
                    :disabled="assigning"
                >
                    <span class="las la-coins mr-1"></span>@{{ label }}
                </button>
            `,
        });

            const existingButtons = POS.cartHeaderButtons.getValue() || {};
            POS.cartHeaderButtons.next({
                ...existingButtons,
                nsRenCommissionsAssignButton: window.nsExtraComponents['ns-rencommissions-assign-button'],
            });
        };

        const clearServerSession = () => {
            if (!state.sessionId) {
                return;
            }

            nsHttpClient.post('/api/rencommissions/pos/session/clear', {
                session_id: state.sessionId,
            }).subscribe({
                next: () => resetState(),
                error: () => resetState(),
            });
        };

        nsHooks.addAction('ns-after-cart-reset', 'rencommissions-pos-rebind', () => {
            registerButton();
            renderInlineCommissions();
        });

        nsHooks.addAction('ns-order-before-submit', 'rencommissions-pos-bind-uuid', (order) => {
            if (!assignmentCount()) {
                if (config.require_assignment && (POS.products.getValue() || []).length > 0) {
                    nsSnackBar.error('Assign commissions to all cart items before checkout.');
                    throw new Error('Commission assignment is required for all items.');
                }

                return;
            }

            if (config.require_assignment && hasMissingAssignments()) {
                nsSnackBar.error('Assign commissions to all cart items before checkout.');
                throw new Error('Commission assignment is required for all items.');
            }

            order.uuid = ensureSessionId();
            order.rencommissions_session_id = state.sessionId;
        });

        nsHooks.addAction('ns-order-submit-successful', 'rencommissions-pos-success', () => {
            clearServerSession();
            renderInlineCommissions();
        });

        nsHooks.addAction('ns-order-submit-failed', 'rencommissions-pos-failed', () => {
            if (state.sessionId) {
                ensureSessionId();
            }
        });

        nsHooks.addAction('ns-after-cart-changed', 'rencommissions-inline-refresh', () => {
            setTimeout(renderInlineCommissions, 0);
            scheduleSync();
        });

        nsHooks.addAction('ns-cart-after-refreshed', 'rencommissions-inline-refresh2', () => {
            setTimeout(renderInlineCommissions, 0);
            scheduleSync();
        });

        registerButton();
        setTimeout(renderInlineCommissions, 0);
    };

    window.__renCommissionsPosBooting = true;

    const tryBoot = () => {
        start();
        if (window.__renCommissionsPosBooted) {
            window.__renCommissionsPosBooting = false;
            return;
        }

        setTimeout(tryBoot, 100);
    };

    tryBoot();
})();
</script>
