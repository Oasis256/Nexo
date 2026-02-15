<template>
    <div class="ns-commission-product-values">
        <div class="border-b border-box-edge pb-4 mb-4">
            <h3 class="font-semibold text-primary">{{ __m('Per-Product Commission Values', 'NsCommissions') }}</h3>
            <p class="text-secondary text-sm">{{ __m('Set individual commission values for specific products. Products not listed will use the default commission value.', 'NsCommissions') }}</p>
        </div>

        <!-- Search and Add Product -->
        <div class="flex gap-2 mb-4">
            <div class="flex-auto relative">
                <input 
                    type="text" 
                    v-model="searchQuery"
                    @input="searchProducts"
                    @focus="showSearchResults = true"
                    :placeholder="__m('Search products by name or SKU...', 'NsCommissions')"
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
                        <span class="font-medium">{{ product.name }}</span>
                        <span class="text-sm text-secondary ml-2">({{ product.sku }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-8">
            <ns-spinner></ns-spinner>
            <span class="ml-2 text-secondary">{{ __m('Loading...', 'NsCommissions') }}</span>
        </div>

        <!-- Product Values Table -->
        <div v-else-if="productValues.length > 0" class="border border-box-edge rounded-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-box-elevation-edge">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-primary">{{ __m('Product', 'NsCommissions') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-primary w-40">{{ __m('Commission Value', 'NsCommissions') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-primary w-20">{{ __m('Actions', 'NsCommissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="(item, index) in productValues" 
                        :key="item.product_id"
                        class="border-t border-box-edge hover:bg-box-elevation-hover"
                    >
                        <td class="px-4 py-3">
                            <span class="font-medium text-primary">{{ item.product_name }}</span>
                            <span v-if="item.product_sku" class="text-sm text-secondary ml-2">({{ item.product_sku }})</span>
                        </td>
                        <td class="px-4 py-3">
                            <input 
                                type="number" 
                                v-model.number="item.value"
                                step="0.01"
                                min="0"
                                @change="markDirty"
                                class="w-full border border-box-edge rounded px-3 py-1 text-primary bg-box-background"
                            />
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button 
                                @click="removeProduct(index)"
                                class="text-error-primary hover:text-error-secondary p-1"
                                :title="__m('Remove', 'NsCommissions')"
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
            <p class="text-secondary">{{ __m('No product-specific values configured.', 'NsCommissions') }}</p>
            <p class="text-sm text-secondary">{{ __m('Search and add products above to set individual commission values.', 'NsCommissions') }}</p>
        </div>

        <!-- Save Button -->
        <div v-if="productValues.length > 0" class="mt-4 flex justify-end">
            <button 
                @click="saveProductValues"
                :disabled="isSaving"
                class="px-4 py-2 bg-info-primary text-white rounded-md hover:bg-info-secondary disabled:opacity-50 flex items-center gap-2"
            >
                <ns-spinner v-if="isSaving" size="sm"></ns-spinner>
                <i v-else class="las la-save"></i>
                {{ isSaving ? __m('Saving...', 'NsCommissions') : __m('Save Product Values', 'NsCommissions') }}
            </button>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch } from 'vue';
import { __ } from '~/libraries/lang';

declare const nsHttpClient: any;
declare const nsSnackBar: any;

interface ProductValue {
    product_id: number;
    product_name: string;
    product_sku?: string;
    value: number;
}

interface Product {
    id: number;
    name: string;
    sku: string;
    category_id: number;
}

export default defineComponent({
    name: 'nsCommissionProductValues',
    props: {
        commissionId: {
            type: [Number, String],
            required: false,
            default: null
        },
        field: {
            type: Object,
            required: false,
            default: null
        }
    },
    setup(props) {
        const productValues = ref<ProductValue[]>([]);
        const searchQuery = ref('');
        const searchResults = ref<Product[]>([]);
        const showSearchResults = ref(false);
        const isLoading = ref(false);
        const isSaving = ref(false);
        const isDirty = ref(false);
        let searchTimeout: ReturnType<typeof setTimeout> | null = null;

        const __m = (text: string, domain: string) => {
            return (window as any).__m ? (window as any).__m(text, domain) : text;
        };

        const loadProductValues = async () => {
            if (!props.commissionId) return;
            
            isLoading.value = true;
            try {
                const response = await new Promise((resolve, reject) => {
                    nsHttpClient.get(`/api/commissions/${props.commissionId}/product-values`)
                        .subscribe({
                            next: (data: any) => resolve(data),
                            error: (err: any) => reject(err)
                        });
                });
                
                const data = (response as any).data || [];
                productValues.value = data.map((item: any) => ({
                    product_id: item.product_id,
                    product_name: item.product?.name || `Product #${item.product_id}`,
                    product_sku: item.product?.sku || '',
                    value: parseFloat(item.value) || 0
                }));
            } catch (error) {
                console.error('Failed to load product values:', error);
                nsSnackBar.error(__m('Failed to load product values.', 'NsCommissions'));
            } finally {
                isLoading.value = false;
            }
        };

        const searchProducts = () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (searchQuery.value.length < 2) {
                searchResults.value = [];
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const response = await new Promise((resolve, reject) => {
                        nsHttpClient.get(`/api/commissions/products/search?search=${encodeURIComponent(searchQuery.value)}`)
                            .subscribe({
                                next: (data: any) => resolve(data),
                                error: (err: any) => reject(err)
                            });
                    });

                    const products = (response as any).data || [];
                    // Filter out already added products
                    const existingIds = productValues.value.map(pv => pv.product_id);
                    searchResults.value = products.filter((p: Product) => !existingIds.includes(p.id));
                } catch (error) {
                    console.error('Search failed:', error);
                }
            }, 300);
        };

        const addProduct = (product: Product) => {
            productValues.value.push({
                product_id: product.id,
                product_name: product.name,
                product_sku: product.sku,
                value: 0
            });
            searchQuery.value = '';
            searchResults.value = [];
            showSearchResults.value = false;
            isDirty.value = true;
        };

        const removeProduct = (index: number) => {
            productValues.value.splice(index, 1);
            isDirty.value = true;
        };

        const markDirty = () => {
            isDirty.value = true;
        };

        const saveProductValues = async () => {
            if (!props.commissionId) {
                nsSnackBar.error(__m('Please save the commission first before adding product values.', 'NsCommissions'));
                return;
            }

            isSaving.value = true;
            try {
                const payload = {
                    product_values: productValues.value.map(pv => ({
                        product_id: pv.product_id,
                        value: pv.value
                    }))
                };

                await new Promise((resolve, reject) => {
                    nsHttpClient.post(`/api/commissions/${props.commissionId}/product-values`, payload)
                        .subscribe({
                            next: (data: any) => resolve(data),
                            error: (err: any) => reject(err)
                        });
                });

                nsSnackBar.success(__m('Product values saved successfully.', 'NsCommissions'));
                isDirty.value = false;
            } catch (error) {
                console.error('Save failed:', error);
                nsSnackBar.error(__m('Failed to save product values.', 'NsCommissions'));
            } finally {
                isSaving.value = false;
            }
        };

        // Close search results when clicking outside
        const handleClickOutside = (event: MouseEvent) => {
            const target = event.target as HTMLElement;
            if (!target.closest('.ns-commission-product-values')) {
                showSearchResults.value = false;
            }
        };

        onMounted(() => {
            loadProductValues();
            document.addEventListener('click', handleClickOutside);
        });

        // Watch for commissionId changes (when editing existing commission)
        watch(() => props.commissionId, (newId) => {
            if (newId) {
                loadProductValues();
            }
        });

        return {
            productValues,
            searchQuery,
            searchResults,
            showSearchResults,
            isLoading,
            isSaving,
            isDirty,
            __m,
            searchProducts,
            addProduct,
            removeProduct,
            markDirty,
            saveProductValues
        };
    }
});
</script>

<style scoped>
.ns-commission-product-values input[type="number"]::-webkit-inner-spin-button,
.ns-commission-product-values input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}
</style>
