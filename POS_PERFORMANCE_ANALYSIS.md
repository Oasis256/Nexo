# NexoPOS POS Performance Analysis

## Executive Summary

**Critical Issue**: POS system shows **66,936ms Interaction to Next Paint (INP)** on pointer interactions
- **Target**: <200ms (Good), <500ms (Acceptable)
- **Current**: 66,936ms (335x worse than target)
- **Impact**: 67-second delay from click to UI response - catastrophic UX
- **Root Cause**: Synchronous cart recalculation on every interaction, no debouncing, blocking tax computations

---

## Performance Metrics

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| INP | <200ms | 66,936ms | ❌ CRITICAL |
| Interaction Type | - | Pointer | - |
| User Impact | - | 67s UI freeze | ❌ BLOCKING |

---

## Root Cause Analysis

### 1. **Synchronous Cart Refresh on Every Change** ⚠️ CRITICAL

**File**: `resources/ts/pos-init.ts`  
**Lines**: 337, 1312-1395

**Problem**:
```typescript
// Line 337: Hook triggers on EVERY cart change - NO DEBOUNCING
nsHooks.addAction('ns-after-cart-changed', 'listen-add-to-cart', () => this.refreshCart());

// Line 1312: Synchronous refresh method
async refreshCart() {
    this.checkCart();
    let order = this.sumProductsTotals(this.order.getValue());  // SYNC
    
    // ... coupon calculations (SYNC)
    // ... discount calculations (SYNC)
    
    this.order.next(order);  // Triggers ALL subscribers
    
    // BLOCKING: Async tax computation
    const response = await this.computeTaxes();  // BLOCKS UI
    order = response['data'].order;
    
    // Multiple math.chain operations (SYNC)
    order.total = math.chain(op1).subtract(order.discount).subtract(order.total_coupons).done();
    
    this.order.next(order);  // Triggers ALL subscribers AGAIN
}
```

**Impact**:
- Every product click triggers full cart recalculation
- No debouncing = 10 clicks = 10 full recalculations
- Each recalculation triggers ALL BehaviorSubject subscribers
- UI freezes waiting for async tax computation

**Frequency**: Every cart change (add product, change quantity, apply discount, etc.)

---

### 2. **Expensive Product Tax Calculations** ⚠️ HIGH

**File**: `resources/ts/pos-init.ts`  
**Lines**: 1654-1784

**Problem**:
```typescript
// Line 1654: Called for EVERY product on EVERY cart change
recomputeProducts(products = null) {
    products.forEach(product => {
        this.computeProduct(product);  // No async batching
    });
}

// Lines 1689-1733: Synchronous tax computation per product
private proceedProductTaxComputation(product, price) {
    // Multiple math.chain operations PER product
    const lineSubtotal = math.chain(price).multiply(product.quantity).done();
    const lineAfterDiscount = math.chain(lineSubtotal).subtract(product.discount).done();
    
    // Calls computeTaxForGroup for EACH product
    let result = this.computeTaxForGroup(lineAfterDiscount, taxGroup, originalProduct.tax_type);
    
    // More math.chain operations
    tax_value = math.chain(total_tax_value).divide(product.quantity).done();
    price_with_tax = math.chain(result.price_with_tax).divide(product.quantity).done();
    // ... etc
}
```

**Impact**:
- 10 products in cart = 10 synchronous tax calculations
- Each calculation involves 5-10 math.chain operations
- No batching or web worker offloading
- Blocks main thread during computation

**Complexity**: O(n) where n = number of products in cart

---

## Recommended Fixes (Priority Order)

### 🔴 **CRITICAL - Immediate Action Required**

#### 1. **Debounce Cart Refresh** (Lines 337, 1312)
```typescript
// BEFORE (pos-init.ts line 337)
nsHooks.addAction('ns-after-cart-changed', 'listen-add-to-cart', () => this.refreshCart());

// AFTER
private cartRefreshDebounce: any = null;

nsHooks.addAction('ns-after-cart-changed', 'listen-add-to-cart', () => {
    clearTimeout(this.cartRefreshDebounce);
    this.cartRefreshDebounce = setTimeout(() => this.refreshCart(), 150);
});
```

**Impact**: Reduces refreshCart calls from N to 1 per user action burst  
**Effort**: 5 minutes  
**Expected Improvement**: 80-90% reduction in computation

---

#### 2. **Cache Tax Groups** (Lines 592-660)
```typescript
// BEFORE
nsHttpClient.get(/api/taxes/groups/-Force{order.tax_group_id})
    .subscribe({ next: (tax) => { /* ... */ } });

// AFTER
private taxGroupCache = new Map<number, any>();

computeTaxes() {
    return new Promise((resolve, reject) => {
        let order = this.order.getValue();
        
        if (!order.tax_group_id) {
            this.computeOrderTaxes(order);
            return resolve({ data: { order }, status: 'success' });
        }
        
        // Check cache first
        if (this.taxGroupCache.has(order.tax_group_id)) {
            const taxGroup = this.taxGroupCache.get(order.tax_group_id);
            order = <Order>this.computeOrderTaxGroup(order, taxGroup);
            return resolve({ status: 'success', data: { tax: taxGroup, order } });
        }
        
        // Fetch and cache
        nsHttpClient.get(/api/taxes/groups/-Force{order.tax_group_id})
            .subscribe({
                next: (tax: any) => {
                    this.taxGroupCache.set(order.tax_group_id, tax);
                    order = <Order>this.computeOrderTaxGroup(order, tax);
                    return resolve({ status: 'success', data: { tax, order } });
                },
                error: (error) => reject(error)
            });
    });
}
```

**Impact**: Eliminates network latency on repeat calculations  
**Effort**: 15 minutes  
**Expected Improvement**: 50-70% reduction in computeTaxes() time

---

## Implementation Roadmap

### Phase 1: Emergency Fixes (1-2 hours)
1. ✅ Add debouncing to cart refresh (5 min)
2. ✅ Implement tax group caching (15 min)
3. ✅ Batch product computations (10 min)

**Expected Result**: INP reduced from 66,936ms to ~5,000-10,000ms (85-90% improvement)

---

## Testing Strategy

### Success Criteria
- INP < 200ms (Excellent)
- INP < 500ms (Good) ← Minimum acceptable
- No UI freezes > 100ms
- Cart updates feel instant (<50ms perceived)

---

**Document Version**: 1.0  
**Date**: November 23, 2025  
**Status**: CRITICAL - IMMEDIATE ACTION REQUIRED
