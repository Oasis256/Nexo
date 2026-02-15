<template>
    <div class="ns-product-commission-user-select">
        <!-- Inline selector for POS cart items -->
        <div class="flex items-center gap-2">
            <div class="flex-auto relative">
                <select 
                    v-model="selectedUserId"
                    @change="onUserChange"
                    :disabled="isLoading || eligibleUsers.length === 0"
                    class="w-full border border-box-edge rounded px-3 py-1.5 text-sm text-primary bg-box-background disabled:opacity-50"
                >
                    <option :value="null">{{ __m('Default (Order Author)', 'NsCommissions') }}</option>
                    <option 
                        v-for="user in eligibleUsers" 
                        :key="user.id" 
                        :value="user.id"
                    >
                        {{ user.username }}
                    </option>
                </select>
            </div>
            
            <!-- Commission Preview -->
            <div v-if="commissionPreview" class="text-right min-w-[80px]">
                <span class="text-xs text-secondary block">{{ __m('Commission', 'NsCommissions') }}</span>
                <span class="text-sm font-medium text-success-primary">{{ commissionPreview.formatted_value }}</span>
            </div>

            <!-- Loading indicator -->
            <ns-spinner v-if="isLoading" size="sm"></ns-spinner>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch, computed } from 'vue';

declare const nsHttpClient: any;
declare const nsSnackBar: any;

interface User {
    id: number;
    username: string;
    email: string;
}

interface CommissionPreview {
    value: number;
    formatted_value: string;
    commission: {
        id: number;
        name: string;
        type: string;
    } | null;
}

export default defineComponent({
    name: 'nsProductCommissionUserSelect',
    props: {
        productId: {
            type: Number,
            required: true
        },
        categoryId: {
            type: Number,
            required: true
        },
        unitPrice: {
            type: Number,
            required: true
        },
        quantity: {
            type: Number,
            required: true,
            default: 1
        },
        orderId: {
            type: Number,
            required: false,
            default: null
        },
        orderProductId: {
            type: Number,
            required: false,
            default: null
        },
        initialUserId: {
            type: Number,
            required: false,
            default: null
        }
    },
    emits: ['change', 'preview'],
    setup(props, { emit }) {
        const selectedUserId = ref<number | null>(props.initialUserId);
        const eligibleUsers = ref<User[]>([]);
        const commissionPreview = ref<CommissionPreview | null>(null);
        const isLoading = ref(false);

        const __m = (text: string, domain: string) => {
            return (window as any).__m ? (window as any).__m(text, domain) : text;
        };

        const loadEligibleUsers = async () => {
            isLoading.value = true;
            try {
                const response = await new Promise((resolve, reject) => {
                    nsHttpClient.get(`/api/commissions/eligible-users?category_id=${props.categoryId}`)
                        .subscribe({
                            next: (data: any) => resolve(data),
                            error: (err: any) => reject(err)
                        });
                });

                eligibleUsers.value = (response as any).data || [];
            } catch (error) {
                console.error('Failed to load eligible users:', error);
            } finally {
                isLoading.value = false;
            }
        };

        const loadCommissionPreview = async (userId: number) => {
            if (!userId) {
                commissionPreview.value = null;
                return;
            }

            try {
                const payload = {
                    product_id: props.productId,
                    category_id: props.categoryId,
                    unit_price: props.unitPrice,
                    quantity: props.quantity,
                    user_id: userId
                };

                const response = await new Promise((resolve, reject) => {
                    nsHttpClient.post('/api/commissions/preview', payload)
                        .subscribe({
                            next: (data: any) => resolve(data),
                            error: (err: any) => reject(err)
                        });
                });

                commissionPreview.value = (response as any).data;
                emit('preview', commissionPreview.value);
            } catch (error) {
                console.error('Failed to load commission preview:', error);
                commissionPreview.value = null;
            }
        };

        const onUserChange = () => {
            emit('change', {
                userId: selectedUserId.value,
                productId: props.productId,
                orderProductId: props.orderProductId
            });

            if (selectedUserId.value) {
                loadCommissionPreview(selectedUserId.value);
            } else {
                commissionPreview.value = null;
            }
        };

        const saveAssignment = async () => {
            if (!props.orderId || !props.orderProductId || !selectedUserId.value) {
                return;
            }

            try {
                await new Promise((resolve, reject) => {
                    nsHttpClient.post(
                        `/api/orders/${props.orderId}/products/${props.orderProductId}/commission-assignment`,
                        { user_id: selectedUserId.value }
                    ).subscribe({
                        next: (data: any) => resolve(data),
                        error: (err: any) => reject(err)
                    });
                });

                nsSnackBar.success(__m('Commission assignment saved.', 'NsCommissions'));
            } catch (error) {
                console.error('Failed to save assignment:', error);
                nsSnackBar.error(__m('Failed to save commission assignment.', 'NsCommissions'));
            }
        };

        // Watch for quantity/price changes to update preview
        watch([() => props.quantity, () => props.unitPrice], () => {
            if (selectedUserId.value) {
                loadCommissionPreview(selectedUserId.value);
            }
        });

        // Watch for category changes to reload eligible users
        watch(() => props.categoryId, () => {
            loadEligibleUsers();
        });

        onMounted(() => {
            loadEligibleUsers();
            if (props.initialUserId) {
                loadCommissionPreview(props.initialUserId);
            }
        });

        return {
            selectedUserId,
            eligibleUsers,
            commissionPreview,
            isLoading,
            __m,
            onUserChange,
            saveAssignment
        };
    }
});
</script>
