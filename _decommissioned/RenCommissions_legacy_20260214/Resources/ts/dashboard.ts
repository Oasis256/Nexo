import { computed, defineComponent, onMounted, reactive, ref } from 'vue';

declare const nsSnackBar: any;
declare const nsExtraComponents: Record<string, any>;
declare const Popup: any;
declare const nsPromptPopup: any;
declare const nsConfirmPopup: any;

type Period = 'today' | 'this_week' | 'this_month' | 'last_month' | 'last_30_days' | 'this_year' | 'all_time';
type CommissionStatus = 'pending' | 'paid' | 'voided' | 'cancelled';
type CommissionMethod = 'percentage' | 'fixed' | 'on_the_house';
type RouteMap = Record<string, string>;
type RenCommissionsRoutes = { web?: RouteMap; api?: RouteMap };
const DASHBOARD_BUILD = 'rc-dashboard-2026-02-14-f';

interface SummaryData {
    total: { amount: number; count: number; formatted: string };
    pending: { amount: number; count: number; formatted: string };
    paid: { amount: number; count: number; formatted: string };
    average: { amount: number; formatted: string };
    period: string;
}

interface CommissionRow {
    id: number;
    order_code: string;
    product_name: string;
    earner_name: string;
    commission_type: CommissionMethod;
    total_commission: number;
    formatted_amount: string;
    status: CommissionStatus;
    created_at: string;
    created_at_human: string;
}

interface StaffEarningRow {
    earner_id: number;
    earner_name: string;
    earner_email: string;
    total_earned: number;
    pending: number;
    paid: number;
    formatted_total: string;
    formatted_pending: string;
    formatted_paid: string;
    commission_count: number;
}

interface LeaderboardRow {
    rank: number;
    earner_id: number;
    earner_name: string;
    total_earned: number;
    formatted_amount: string;
    commission_count: number;
}

interface TrendRow {
    date: string;
    total: number;
    count: number;
    paid: number;
    pending: number;
}

interface CommissionTypeRow {
    id: number;
    name: string;
    description: string;
    calculation_method: CommissionMethod;
    default_value: number;
    min_value: number | null;
    max_value: number | null;
    is_active: boolean;
    priority: number;
    is_system: boolean;
}

const PERIOD_OPTIONS: Array<{ label: string; value: Period }> = [
    { label: 'Today', value: 'today' },
    { label: 'This Week', value: 'this_week' },
    { label: 'This Month', value: 'this_month' },
    { label: 'Last Month', value: 'last_month' },
    { label: 'Last 30 Days', value: 'last_30_days' },
    { label: 'This Year', value: 'this_year' },
    { label: 'All Time', value: 'all_time' },
];

const STATUS_OPTIONS: Array<{ label: string; value: string }> = [
    { label: 'All Statuses', value: 'all' },
    { label: 'Pending', value: 'pending' },
    { label: 'Paid', value: 'paid' },
    { label: 'Voided', value: 'voided' },
    { label: 'Cancelled', value: 'cancelled' },
];

const METHOD_OPTIONS: Array<{ label: string; value: CommissionMethod }> = [
    { label: 'Percentage', value: 'percentage' },
    { label: 'Fixed Amount', value: 'fixed' },
    { label: 'On-The-House', value: 'on_the_house' },
];

function csrfToken(): string {
    const el = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return el?.content || '';
}

async function requestJson<T>(method: string, url: string, payload?: any): Promise<T> {
    const timeoutMs = 12000;
    const token = csrfToken();
    const normalizedUrl = normalizeUrl(url);
    return await new Promise<T>((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, normalizedUrl, true);
        xhr.withCredentials = true;
        xhr.timeout = timeoutMs;
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (token) {
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
        }
        if (payload !== undefined) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }

        xhr.onload = () => {
            const raw = xhr.responseText || '';
            let parsed: any = raw;
            try {
                parsed = raw ? JSON.parse(raw) : {};
            } catch {
                reject(new Error(`Non-JSON response for ${normalizedUrl}`));
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                const message =
                    parsed?.message ||
                    (typeof parsed === 'string' ? parsed.slice(0, 160) : null) ||
                    `Request failed (${xhr.status}) for ${normalizedUrl}`;
                reject(new Error(message));
                return;
            }

            resolve(parsed as T);
        };

        xhr.onerror = () => reject(new Error(`Network error for ${normalizedUrl}`));
        xhr.ontimeout = () => reject(new Error(`Request timeout for ${normalizedUrl}`));
        xhr.onabort = () => reject(new Error(`Request aborted for ${normalizedUrl}`));

        try {
            xhr.send(payload !== undefined ? JSON.stringify(payload) : null);
        } catch (error: any) {
            reject(new Error(error?.message || `Network error for ${normalizedUrl}`));
        }
    });
}

function get<T>(url: string): Promise<T> {
    return requestJson<T>('GET', url);
}

function post<T>(url: string, payload: any): Promise<T> {
    return requestJson<T>('POST', url, payload);
}

function put<T>(url: string, payload: any): Promise<T> {
    return requestJson<T>('PUT', url, payload);
}

function del<T>(url: string): Promise<T> {
    return requestJson<T>('DELETE', url);
}

function parseMaybeJson(input: any): any {
    if (typeof input !== 'string') {
        return input;
    }

    try {
        return JSON.parse(input);
    } catch {
        return input;
    }
}

function isTransportEnvelope(input: any): boolean {
    if (!input || typeof input !== 'object' || Array.isArray(input)) {
        return false;
    }

    if (typeof input.status === 'number') {
        return true;
    }

    return (
        'body' in input ||
        'response' in input ||
        'headers' in input ||
        'config' in input ||
        'statusCode' in input ||
        'statusText' in input ||
        'ok' in input
    );
}

function unwrapData(input: any): any {
    let current = parseMaybeJson(input);

    for (let i = 0; i < 8; i++) {
        if (current === null || current === undefined) {
            return current;
        }

        if (isTransportEnvelope(current)) {
            if ((current as any).body !== undefined) {
                const next = parseMaybeJson((current as any).body);
                if (next !== current) {
                    current = next;
                    continue;
                }
            }
            if ((current as any).response !== undefined) {
                const next = parseMaybeJson((current as any).response);
                if (next !== current) {
                    current = next;
                    continue;
                }
            }
            if ((current as any).data !== undefined) {
                const next = parseMaybeJson((current as any).data);
                if (next !== current) {
                    current = next;
                    continue;
                }
            }
        }

        if (typeof current === 'object' && 'data' in current && (current as any).data !== undefined) {
            const next = parseMaybeJson((current as any).data);
            if (next === current) {
                break;
            }
            current = next;
            continue;
        }

        break;
    }

    return current;
}

function dataOf<T>(response: any, fallback: T): T {
    const candidates = [
        response,
        response?.data,
        response?.body,
        response?.response,
        response?.response?.data,
        response?.response?.body,
    ];

    for (const candidate of candidates) {
        const payload = unwrapData(candidate);
        if (payload === undefined || payload === null) {
            continue;
        }

        if (typeof payload === 'object' && !Array.isArray(payload) && 'status' in payload && 'data' in payload) {
            const next = unwrapData((payload as any).data);
            if (next !== undefined && next !== null) {
                return next as T;
            }
        }

        return payload as T;
    }

    return fallback;
}

function metaOf<T>(response: any, key: string, fallback: T): T {
    if (response?.[key] !== undefined) {
        return response[key] as T;
    }
    if (response?.data?.[key] !== undefined) {
        return response.data[key] as T;
    }
    return fallback;
}

function ensureSuccess(response: any): void {
    const parsed = parseMaybeJson(response);
    const status =
        parsed?.status ??
        parsed?.data?.status ??
        parsed?.body?.status ??
        parsed?.body?.data?.status ??
        parsed?.response?.status ??
        parsed?.response?.data?.status ??
        parsed?.response?.body?.status ??
        parsed?.response?.body?.data?.status;
    if (status === 'error') {
        throw new Error(toMessage(parsed, 'Request failed.'));
    }
}

function toMessage(error: any, fallback: string): string {
    return error?.message || fallback;
}

function shapeOf(input: any): any {
    const payload = parseMaybeJson(input);
    if (payload === null || payload === undefined) return payload;
    if (Array.isArray(payload)) return { type: 'array', length: payload.length };
    if (typeof payload !== 'object') return { type: typeof payload, value: String(payload).slice(0, 120) };

    const out: any = { type: 'object', keys: Object.keys(payload).slice(0, 12) };
    if ((payload as any).status !== undefined) out.status = (payload as any).status;
    if ((payload as any).data !== undefined) {
        const data = (payload as any).data;
        out.dataType = Array.isArray(data) ? 'array' : typeof data;
        if (Array.isArray(data)) out.dataLength = data.length;
        if (data && typeof data === 'object' && !Array.isArray(data)) {
            out.dataKeys = Object.keys(data).slice(0, 12);
        }
    }
    return out;
}

function statusClass(status: string): string {
    if (status === 'paid') return 'bg-green-100 text-green-700';
    if (status === 'pending') return 'bg-amber-100 text-amber-700';
    if (status === 'voided') return 'bg-red-100 text-red-700';
    if (status === 'cancelled') return 'bg-slate-100 text-slate-700';
    return 'bg-slate-100 text-slate-700';
}

function methodLabel(method: CommissionMethod): string {
    const item = METHOD_OPTIONS.find(option => option.value === method);
    return item ? item.label : method;
}

function routeBag(): RenCommissionsRoutes {
    return ((window as any).renCommissionsRoutes || {}) as RenCommissionsRoutes;
}

function normalizeUrl(url: string): string {
    try {
        const parsed = new URL(url, window.location.origin);
        if (parsed.origin === window.location.origin || /^https?:\/\//i.test(url)) {
            return `${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
    } catch {
        // Keep original URL if parsing fails.
    }
    return url;
}

function webRoute(key: string, fallback: string): string {
    return normalizeUrl(routeBag().web?.[key] || fallback);
}

function apiRoute(key: string, fallback: string): string {
    return normalizeUrl(routeBag().api?.[key] || fallback);
}

function fillRoute(url: string, params: Record<string, string | number>): string {
    return Object.entries(params).reduce((output, [key, value]) => {
        const token = `__${key.toUpperCase()}__`;
        return output.split(token).join(encodeURIComponent(String(value)));
    }, url);
}

function withQuery(url: string, query: Record<string, string | number | undefined | null>): string {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            params.set(key, String(value));
        }
    });
    const queryString = params.toString();
    if (!queryString) {
        return url;
    }
    return `${url}${url.includes('?') ? '&' : '?'}${queryString}`;
}

async function withHardTimeout<T>(promise: Promise<T>, timeoutMs: number, label: string): Promise<T> {
    return await Promise.race([
        promise,
        new Promise<T>((_, reject) => {
            window.setTimeout(() => reject(new Error(`Timeout: ${label}`)), timeoutMs);
        }),
    ]);
}

async function promptReason(title: string, message: string): Promise<string | null> {
    try {
        const value = await new Promise<string>((resolve, reject) => {
            Popup.show(nsPromptPopup, {
                title,
                message,
                type: 'textarea',
                input: '',
                resolve,
                reject,
            });
        });

        const reason = (value || '').trim();
        if (reason === '') {
            nsSnackBar.info('Reason is required.');
            return null;
        }

        return reason;
    } catch {
        return null;
    }
}

async function confirmAction(title: string, message: string): Promise<boolean> {
    return await new Promise((resolve) => {
        Popup.show(nsConfirmPopup, {
            title,
            message,
            onAction: (confirmed: boolean) => resolve(!!confirmed),
        });
    });
}

async function downloadCsv(entries: Array<{ id: number }>): Promise<void> {
    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || '';
    const response = await fetch(apiRoute('commission_export', '/api/rencommissions/commissions/export'), {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ entries }),
    });

    if (!response.ok) {
        throw new Error('Export failed.');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'commissions-export.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

const SummaryCards = defineComponent({
    name: 'NsRcSummaryCards',
    props: {
        summary: { type: Object, required: true },
        loading: { type: Boolean, required: true },
    },
    template: `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Total</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold">{{ summary.total?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.total?.count || 0 }} records</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Pending</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-warning-tertiary">{{ summary.pending?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.pending?.count || 0 }} pending</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Paid</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-success-tertiary">{{ summary.paid?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">{{ summary.paid?.count || 0 }} paid</div>
            </div>
            <div class="ns-box rounded-lg p-4">
                <div class="text-xs uppercase tracking-wider text-secondary mb-1">Average</div>
                <div v-if="loading" class="animate-pulse h-7 bg-input-background rounded"></div>
                <div v-else class="text-2xl font-bold text-info-tertiary">{{ summary.average?.formatted || '0' }}</div>
                <div class="text-xs text-secondary mt-1">per line item</div>
            </div>
        </div>
    `,
});

const NsRencommissionsDashboard = defineComponent({
    name: 'NsRencommissionsDashboard',
    components: { SummaryCards },
    setup() {
        const loading = ref(true);
        const debug = ref<any>({});
        const period = ref<Period>('this_month');
        const summary = ref<SummaryData>({
            total: { amount: 0, count: 0, formatted: '0' },
            pending: { amount: 0, count: 0, formatted: '0' },
            paid: { amount: 0, count: 0, formatted: '0' },
            average: { amount: 0, formatted: '0' },
            period: 'this_month',
        });
        const recent = ref<CommissionRow[]>([]);
        const leaderboard = ref<LeaderboardRow[]>([]);
        const trends = ref<TrendRow[]>([]);
        const maxTrend = computed(() => Math.max(1, ...trends.value.map(row => row.total)));
        const selectedPeriodLabel = computed(() => {
            return PERIOD_OPTIONS.find(option => option.value === period.value)?.label || 'This Month';
        });
        const pendingPreview = computed(() => recent.value.filter(row => row.status === 'pending').slice(0, 5));
        const paymentPreview = computed(() => recent.value.filter(row => row.status === 'paid').slice(0, 6));
        const hasBootstrapped = ref(false);

        const load = async () => {
            loading.value = true;
            debug.value = {
                phase: 'load_started',
                at: new Date().toISOString(),
                period: period.value,
                loading: loading.value,
                build: DASHBOARD_BUILD,
            };
            window.setTimeout(() => {
                debug.value.watchdog12s = {
                    at: new Date().toISOString(),
                    loading: loading.value,
                    phase: debug.value.phase,
                    build: DASHBOARD_BUILD,
                };
            }, 12000);
            try {
                try {
                    const sumResponse = await withHardTimeout(
                        get<any>(withQuery(apiRoute('dashboard_summary', '/api/rencommissions/dashboard/summary'), { period: period.value })),
                        12000,
                        'summary'
                    );
                    debug.value.summaryResponse = shapeOf(sumResponse);
                    ensureSuccess(sumResponse);
                    summary.value = dataOf(sumResponse, summary.value);
                    debug.value.summaryData = shapeOf(summary.value);
                } catch (error: any) {
                    debug.value.summaryError = toMessage(error, 'Unable to load summary.');
                    nsSnackBar.error(toMessage(error, 'Unable to load summary.'));
                }

                try {
                    const recentResponse = await withHardTimeout(
                        get<any>(withQuery(apiRoute('dashboard_recent', '/api/rencommissions/dashboard/recent'), { limit: 8 })),
                        12000,
                        'recent'
                    );
                    debug.value.recentResponse = shapeOf(recentResponse);
                    ensureSuccess(recentResponse);
                    recent.value = dataOf(recentResponse, []);
                    debug.value.recentData = shapeOf(recent.value);
                } catch {
                    // Keep previous recent rows when this request fails.
                }

                try {
                    const boardResponse = await withHardTimeout(
                        get<any>(withQuery(apiRoute('dashboard_leaderboard', '/api/rencommissions/dashboard/leaderboard'), { period: period.value, limit: 6 })),
                        12000,
                        'leaderboard'
                    );
                    debug.value.leaderboardResponse = shapeOf(boardResponse);
                    ensureSuccess(boardResponse);
                    leaderboard.value = dataOf(boardResponse, []);
                    debug.value.leaderboardData = shapeOf(leaderboard.value);
                } catch {
                    // Keep previous leaderboard rows when this request fails.
                }

                try {
                    const trendsResponse = await withHardTimeout(
                        get<any>(withQuery(apiRoute('dashboard_trends', '/api/rencommissions/dashboard/trends'), { period: period.value, group_by: 'day' })),
                        12000,
                        'trends'
                    );
                    debug.value.trendsResponse = shapeOf(trendsResponse);
                    ensureSuccess(trendsResponse);
                    trends.value = dataOf(trendsResponse, []);
                    debug.value.trendsData = shapeOf(trends.value);
                } catch {
                    // Keep previous trend rows when this request fails.
                }
            } catch (error: any) {
                debug.value.loadError = toMessage(error, 'Dashboard load crashed.');
                nsSnackBar.error(toMessage(error, 'Dashboard load crashed.'));
            } finally {
                loading.value = false;
                debug.value.phase = 'load_settled';
                debug.value.loading = loading.value;
                debug.value.at = new Date().toISOString();
            }
        };

        const markPaid = async (id: number) => {
            try {
                await post(fillRoute(apiRoute('commission_mark_paid', '/api/rencommissions/commissions/__ID__/mark-paid'), { id }), {});
                nsSnackBar.success('Commission marked as paid.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to mark paid.'));
            }
        };

        const voidWithReason = async (id: number) => {
            const reason = await promptReason('Void Commission', 'Why are you voiding this commission?');
            if (!reason) return;
            try {
                await post(fillRoute(apiRoute('commission_void', '/api/rencommissions/commissions/__ID__/void'), { id }), { reason });
                nsSnackBar.success('Commission voided.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to void commission.'));
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();

        return {
            loading,
            period,
            summary,
            recent,
            leaderboard,
            trends,
            maxTrend,
            selectedPeriodLabel,
            pendingPreview,
            paymentPreview,
            webRoute,
            PERIOD_OPTIONS,
            statusClass,
            markPaid,
            voidWithReason,
            load,
            debug,
        };
    },
    template: `
        <div style="display:flex;flex-direction:column;row-gap:1rem;">
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a class="ns-button" :href="webRoute('commissions', '/dashboard/rencommissions/commissions')">View All</a>
                <a class="ns-button" :href="webRoute('types', '/dashboard/rencommissions/types')">Commission Types</a>
                <select class="ns-select px-2 py-1 text-xs" v-model="period" @change="load()">
                    <option v-for="option in PERIOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <button class="ns-button" @click="load()">Refresh</button>
            </div>

            <summary-cards :summary="summary" :loading="loading"></summary-cards>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));column-gap:1rem;row-gap:1rem;align-items:start;">
                <div class="ns-box rounded-lg" style="grid-column: span 2 / span 2;">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Recent Commissions</h3>
                        <a :href="webRoute('payment_history', '/dashboard/rencommissions/payment-history')" class="text-sm text-info-tertiary hover:underline">View All</a>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'recent-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="recent.length === 0" class="p-8 text-center text-secondary">No commissions available.</div>
                        <div v-else v-for="row in recent" :key="row.id" class="rounded border border-box-edge p-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="font-medium">{{ row.product_name }}</div>
                                <div class="text-xs text-secondary">{{ row.order_code }} | {{ row.created_at_human }}</div>
                            </div>
                            <div class="text-sm text-secondary">{{ row.earner_name }}</div>
                            <div class="font-semibold">{{ row.formatted_amount }}</div>
                            <div>
                                <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', statusClass(row.status)]">{{ row.status }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button v-if="row.status === 'pending'" class="ns-button success px-2 py-1 text-xs" @click="markPaid(row.id)">Mark Paid</button>
                                <button v-if="row.status === 'pending'" class="ns-button error px-2 py-1 text-xs" @click="voidWithReason(row.id)">Void</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ns-box rounded-lg">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Top Earners</h3>
                        <span class="text-xs text-info-tertiary">{{ selectedPeriodLabel }}</span>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'leaders-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="leaderboard.length === 0" class="p-6 text-center text-secondary">No ranking data.</div>
                        <div v-else v-for="row in leaderboard" :key="row.earner_id" class="rounded border border-box-edge p-3 flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full bg-input-background flex items-center justify-center text-xs font-semibold">{{ row.rank }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary">{{ row.commission_count }} commissions</div>
                            </div>
                            <div class="font-semibold text-success-tertiary shrink-0">{{ row.formatted_amount }}</div>
                        </div>
                    </div>
                </div>

                <div class="ns-box rounded-lg">
                    <div class="p-4 border-b border-box-edge flex items-center justify-between">
                        <h3 class="font-semibold text-lg">Pending Payouts</h3>
                        <a :href="webRoute('pending_payouts', '/dashboard/rencommissions/pending-payouts')" class="text-xs text-info-tertiary hover:underline">View All</a>
                    </div>
                    <div class="p-3 space-y-2">
                        <div v-if="loading" v-for="n in 5" :key="'payout-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                        <div v-else-if="pendingPreview.length === 0" class="p-6 text-center text-secondary">No pending payouts.</div>
                        <div v-else v-for="row in pendingPreview" :key="'pending-' + row.id" class="rounded border border-box-edge p-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary truncate">{{ row.product_name }}</div>
                            </div>
                            <div class="font-semibold text-warning-tertiary shrink-0">{{ row.formatted_amount }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Payment History</h3>
                    <a :href="webRoute('payment_history', '/dashboard/rencommissions/payment-history')" class="text-xs text-info-tertiary hover:underline">View All</a>
                </div>
                <div class="p-3 space-y-2">
                    <div v-if="loading" v-for="n in 5" :key="'history-loading-' + n" class="h-10 rounded bg-input-background animate-pulse"></div>
                    <div v-else-if="paymentPreview.length === 0" class="p-6 text-center text-secondary">No payment history.</div>
                    <div v-else v-for="row in paymentPreview" :key="'history-' + row.id" class="rounded border border-box-edge p-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-medium truncate">{{ row.product_name }}</div>
                            <div class="text-xs text-secondary">{{ row.order_code }} | {{ row.created_at_human }}</div>
                        </div>
                        <div class="text-sm text-secondary shrink-0">{{ row.earner_name }}</div>
                        <div class="font-semibold text-success-tertiary shrink-0">{{ row.formatted_amount }}</div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Commission Trends</h3>
                    <span class="text-xs text-info-tertiary">{{ selectedPeriodLabel }}</span>
                </div>
                <div class="p-4">
                    <div v-if="!loading && trends.length === 0" class="h-44 flex items-center justify-center text-secondary">No trend data.</div>
                    <div v-else class="h-44 flex items-end gap-1 overflow-x-auto">
                        <div v-for="row in trends" :key="row.date" class="w-4 shrink-0">
                            <div class="bg-info-tertiary rounded-t transition-all duration-300"
                                :style="{ height: ((row.total / maxTrend) * 100) + '%', minHeight: row.total > 0 ? '3px' : '0' }"
                                :title="row.date + ' => ' + row.total"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge">
                    <h3 class="font-semibold text-sm text-info-tertiary">Dashboard Debug</h3>
                </div>
                <pre class="p-4 text-xs overflow-auto">{{ JSON.stringify(debug, null, 2) }}</pre>
            </div>

        </div>
    `,
});

const NsRencommissionsAllCommissionsComponent = defineComponent({
    name: 'NsRencommissionsAllCommissionsComponent',
    setup() {
        const loading = ref(false);
        const page = ref(1);
        const lastPage = ref(1);
        const total = ref(0);
        const rows = ref<CommissionRow[]>([]);
        const hasBootstrapped = ref(false);
        const selected = ref<number[]>([]);
        const filters = reactive({
            status: 'all',
            period: 'this_month' as Period,
            search: '',
        });

        const load = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams({
                    page: String(page.value),
                    limit: '20',
                    status: filters.status,
                    period: filters.period,
                    search: filters.search,
                });
                const response = await get<any>(withQuery(apiRoute('dashboard_commissions', '/api/rencommissions/dashboard/commissions'), Object.fromEntries(params.entries())));
                ensureSuccess(response);
                rows.value = dataOf(response, []);
                const pagination = metaOf(response, 'pagination', {});
                lastPage.value = pagination?.last_page || 1;
                total.value = pagination?.total || 0;
                selected.value = [];
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load commissions.'));
            } finally {
                loading.value = false;
            }
        };

        const toggleAll = () => {
            if (selected.value.length === rows.value.length) {
                selected.value = [];
                return;
            }
            selected.value = rows.value.map(row => row.id);
        };

        const markPaid = async (id: number) => {
            try {
                await post(fillRoute(apiRoute('commission_mark_paid', '/api/rencommissions/commissions/__ID__/mark-paid'), { id }), {});
                nsSnackBar.success('Commission marked as paid.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to mark paid.'));
            }
        };

        const voidOne = async (id: number) => {
            const reason = await promptReason('Void Commission', 'Please provide a void reason.');
            if (!reason) return;
            try {
                await post(fillRoute(apiRoute('commission_void', '/api/rencommissions/commissions/__ID__/void'), { id }), { reason });
                nsSnackBar.success('Commission voided.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to void commission.'));
            }
        };

        const bulkMarkPaid = async () => {
            if (selected.value.length === 0) {
                nsSnackBar.info('Select at least one commission.');
                return;
            }
            try {
                await post(apiRoute('commission_bulk_action', '/api/rencommissions/commissions/bulk-action'), {
                    action: 'bulk_mark_paid',
                    entries: selected.value.map(id => ({ id })),
                });
                nsSnackBar.success('Bulk mark paid completed.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Bulk action failed.'));
            }
        };

        const exportSelected = async () => {
            try {
                const ids = selected.value.length > 0 ? selected.value : rows.value.map(row => row.id);
                await downloadCsv(ids.map(id => ({ id })));
                nsSnackBar.success('CSV exported.');
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Export failed.'));
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();

        return {
            loading,
            page,
            lastPage,
            total,
            rows,
            selected,
            filters,
            STATUS_OPTIONS,
            PERIOD_OPTIONS,
            statusClass,
            methodLabel,
            load,
            toggleAll,
            markPaid,
            voidOne,
            bulkMarkPaid,
            exportSelected,
        };
    },
    template: `
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-lg">All Commissions</h3>
                <div class="flex items-center gap-2 flex-wrap">
                    <input class="ns-input px-2 py-1" placeholder="Search order/product/earner" v-model="filters.search" />
                    <select class="ns-select px-2 py-1" v-model="filters.status">
                        <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <select class="ns-select px-2 py-1" v-model="filters.period">
                        <option v-for="option in PERIOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <button class="ns-button" @click="page = 1; load()">Apply</button>
                    <button class="ns-button success" @click="bulkMarkPaid">Bulk Mark Paid</button>
                    <button class="ns-button info" @click="exportSelected">Export CSV</button>
                </div>
            </div>

            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="p-3 text-left"><input type="checkbox" @change="toggleAll" :checked="rows.length > 0 && selected.length === rows.length" /></th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Order</th>
                            <th class="p-3 text-left">Product</th>
                            <th class="p-3 text-left">Earner</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-right">Amount</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="9" class="p-8 text-center text-secondary">No commissions found.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                            <td class="p-3"><input type="checkbox" :value="row.id" v-model="selected" /></td>
                            <td class="p-3">{{ row.created_at_human }}</td>
                            <td class="p-3">{{ row.order_code }}</td>
                            <td class="p-3">{{ row.product_name }}</td>
                            <td class="p-3">{{ row.earner_name }}</td>
                            <td class="p-3">{{ methodLabel(row.commission_type) }}</td>
                            <td class="p-3 text-right font-semibold">{{ row.formatted_amount }}</td>
                            <td class="p-3 text-center">
                                <span :class="['px-2 py-1 rounded text-xs capitalize', statusClass(row.status)]">{{ row.status }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button v-if="row.status === 'pending'" class="ns-button success px-2 py-1 text-xs" @click="markPaid(row.id)">Mark Paid</button>
                                    <button v-if="row.status === 'pending'" class="ns-button error px-2 py-1 text-xs" @click="voidOne(row.id)">Void</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-box-edge flex items-center justify-between">
                <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }} ({{ total }} total)</div>
                <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
            </div>
        </div>
    `,
});

const NsRencommissionsTypesManagementComponent = defineComponent({
    name: 'NsRencommissionsTypesManagementComponent',
    setup() {
        const loading = ref(false);
        const rows = ref<CommissionTypeRow[]>([]);
        const editingId = ref<number | null>(null);
        const hasBootstrapped = ref(false);
        const form = reactive({
            name: '',
            description: '',
            calculation_method: 'percentage' as CommissionMethod,
            default_value: 0,
            min_value: null as number | null,
            max_value: null as number | null,
            is_active: true,
            priority: 0,
        });

        const resetForm = () => {
            editingId.value = null;
            form.name = '';
            form.description = '';
            form.calculation_method = 'percentage';
            form.default_value = 0;
            form.min_value = null;
            form.max_value = null;
            form.is_active = true;
            form.priority = 0;
        };

        const load = async () => {
            loading.value = true;
            try {
                const response = await get<any>(apiRoute('dashboard_types', '/api/rencommissions/dashboard/types'));
                ensureSuccess(response);
                rows.value = dataOf(response, []);
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load commission types.'));
            } finally {
                loading.value = false;
            }
        };

        const edit = (row: CommissionTypeRow) => {
            editingId.value = row.id;
            form.name = row.name;
            form.description = row.description || '';
            form.calculation_method = row.calculation_method;
            form.default_value = row.default_value || 0;
            form.min_value = row.min_value;
            form.max_value = row.max_value;
            form.is_active = !!row.is_active;
            form.priority = row.priority || 0;
        };

        const save = async () => {
            if (!form.name.trim()) {
                nsSnackBar.info('Type name is required.');
                return;
            }

            try {
                if (editingId.value) {
                    await put(fillRoute(apiRoute('dashboard_type_update', '/api/rencommissions/dashboard/types/__ID__'), { id: editingId.value }), form);
                    nsSnackBar.success('Commission type updated.');
                } else {
                    await post(apiRoute('dashboard_types', '/api/rencommissions/dashboard/types'), form);
                    nsSnackBar.success('Commission type created.');
                }
                resetForm();
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to save commission type.'));
            }
        };

        const remove = async (row: CommissionTypeRow) => {
            const confirmed = await confirmAction(
                'Delete Commission Type',
                `Delete "${row.name}"? This action cannot be undone.`
            );
            if (!confirmed) return;

            try {
                await del(fillRoute(apiRoute('dashboard_type_delete', '/api/rencommissions/dashboard/types/__ID__'), { id: row.id }));
                nsSnackBar.success('Commission type deleted.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Unable to delete commission type.'));
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();
        return { loading, rows, form, editingId, METHOD_OPTIONS, methodLabel, load, edit, save, remove, resetForm };
    },
    template: `
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">Commission Types</h3>
                    <button class="ns-button" @click="load">Refresh</button>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-input-background">
                            <tr>
                                <th class="p-3 text-left">Name</th>
                                <th class="p-3 text-left">Method</th>
                                <th class="p-3 text-right">Default</th>
                                <th class="p-3 text-center">Active</th>
                                <th class="p-3 text-right">Priority</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="6" class="p-8 text-center text-secondary">No commission types found.</td>
                            </tr>
                            <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                                <td class="p-3">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-xs text-secondary">{{ row.description }}</div>
                                </td>
                                <td class="p-3">{{ methodLabel(row.calculation_method) }}</td>
                                <td class="p-3 text-right">{{ row.default_value }}</td>
                                <td class="p-3 text-center">
                                    <span :class="row.is_active ? 'text-success-tertiary' : 'text-secondary'">{{ row.is_active ? 'Yes' : 'No' }}</span>
                                </td>
                                <td class="p-3 text-right">{{ row.priority }}</td>
                                <td class="p-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="ns-button px-2 py-1 text-xs" @click="edit(row)">Edit</button>
                                        <button v-if="!row.is_system" class="ns-button error px-2 py-1 text-xs" @click="remove(row)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge">
                    <h3 class="font-semibold text-lg">{{ editingId ? 'Edit Type' : 'Create Type' }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="text-xs text-secondary">Name</label>
                        <input class="ns-input w-full mt-1" v-model="form.name" />
                    </div>
                    <div>
                        <label class="text-xs text-secondary">Description</label>
                        <textarea class="ns-input w-full mt-1" rows="2" v-model="form.description"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-secondary">Method</label>
                        <select class="ns-select w-full mt-1" v-model="form.calculation_method">
                            <option v-for="option in METHOD_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-secondary">Default</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.default_value" />
                        </div>
                        <div>
                            <label class="text-xs text-secondary">Priority</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.priority" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-secondary">Min</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.min_value" />
                        </div>
                        <div>
                            <label class="text-xs text-secondary">Max</label>
                            <input type="number" class="ns-input w-full mt-1" v-model.number="form.max_value" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" />
                        <span class="text-sm">Active</span>
                    </label>
                    <div class="flex items-center gap-2 pt-2">
                        <button class="ns-button info" @click="save">{{ editingId ? 'Update' : 'Create' }}</button>
                        <button class="ns-button" @click="resetForm">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    `,
});

const NsRencommissionsStaffEarningsComponent = defineComponent({
    name: 'NsRencommissionsStaffEarningsComponent',
    setup() {
        const loading = ref(false);
        const period = ref<Period>('this_month');
        const rows = ref<StaffEarningRow[]>([]);
        const hasBootstrapped = ref(false);

        const load = async () => {
            loading.value = true;
            try {
                const response = await get<any>(withQuery(apiRoute('dashboard_staff_earnings', '/api/rencommissions/dashboard/staff-earnings'), { period: period.value }));
                ensureSuccess(response);
                rows.value = dataOf(response, []);
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load earnings.'));
            } finally {
                loading.value = false;
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();

        return { loading, period, rows, load, PERIOD_OPTIONS };
    },
    template: `
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-lg">Staff Earnings</h3>
                <div class="flex items-center gap-2">
                    <button v-for="option in PERIOD_OPTIONS"
                        :key="option.value"
                        class="ns-button px-3 py-1 text-xs"
                        :class="{ info: period === option.value }"
                        @click="period = option.value; load()">
                        {{ option.label }}
                    </button>
                </div>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="text-left p-3">Staff</th>
                            <th class="text-right p-3">Total</th>
                            <th class="text-right p-3">Pending</th>
                            <th class="text-right p-3">Paid</th>
                            <th class="text-right p-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="5" class="p-6 text-center text-secondary">No data for this period.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.earner_id" class="border-b border-box-edge">
                            <td class="p-3">
                                <div class="font-medium">{{ row.earner_name }}</div>
                                <div class="text-xs text-secondary">{{ row.earner_email }}</div>
                            </td>
                            <td class="p-3 text-right font-semibold">{{ row.formatted_total }}</td>
                            <td class="p-3 text-right text-warning-tertiary">{{ row.formatted_pending }}</td>
                            <td class="p-3 text-right text-success-tertiary">{{ row.formatted_paid }}</td>
                            <td class="p-3 text-right">{{ row.commission_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `,
});

const NsRencommissionsPendingPayoutsComponent = defineComponent({
    name: 'NsRencommissionsPendingPayoutsComponent',
    setup() {
        const loading = ref(false);
        const rows = ref<StaffEarningRow[]>([]);
        const selectedIds = ref<number[]>([]);
        const hasBootstrapped = ref(false);

        const load = async () => {
            loading.value = true;
            try {
                const response = await get<any>(withQuery(apiRoute('dashboard_staff_earnings', '/api/rencommissions/dashboard/staff-earnings'), { period: 'all_time' }));
                ensureSuccess(response);
                const data = dataOf<StaffEarningRow[]>(response, []);
                rows.value = data.filter(row => row.pending > 0);
                selectedIds.value = [];
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load pending payouts.'));
            } finally {
                loading.value = false;
            }
        };

        const markSelectedPaid = async () => {
            const entries = rows.value
                .filter(row => selectedIds.value.includes(row.earner_id))
                .map(row => ({ earner_id: row.earner_id }));

            if (entries.length === 0) {
                nsSnackBar.info('Select at least one earner first.');
                return;
            }

            const confirmed = await confirmAction('Bulk Mark Paid', 'Mark all pending commissions for selected earners as paid?');
            if (!confirmed) return;

            try {
                await post(apiRoute('commission_bulk_action', '/api/rencommissions/commissions/bulk-action'), {
                    action: 'bulk_mark_paid_by_earner',
                    entries,
                });
                nsSnackBar.success('Bulk payout processed.');
                await load();
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Bulk payout failed.'));
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();
        return { loading, rows, selectedIds, load, markSelectedPaid };
    },
    template: `
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex items-center justify-between">
                <h3 class="font-semibold text-lg">Pending Payouts</h3>
                <div class="flex items-center gap-2">
                    <button class="ns-button" @click="load">Refresh</button>
                    <button class="ns-button success" @click="markSelectedPaid">Bulk Mark Paid</button>
                </div>
            </div>
            <div class="divide-y divide-box-edge">
                <div v-if="!loading && rows.length === 0" class="p-8 text-center text-secondary">No pending payouts.</div>
                <label v-for="row in rows" :key="row.earner_id" class="p-4 flex items-center justify-between gap-3 cursor-pointer hover:bg-input-background">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" :value="row.earner_id" v-model="selectedIds" />
                        <div>
                            <div class="font-medium">{{ row.earner_name }}</div>
                            <div class="text-xs text-secondary">{{ row.commission_count }} commissions</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-semibold text-warning-tertiary">{{ row.formatted_pending }}</div>
                    </div>
                </label>
            </div>
        </div>
    `,
});

const NsRencommissionsPaymentHistoryComponent = defineComponent({
    name: 'NsRencommissionsPaymentHistoryComponent',
    setup() {
        const loading = ref(false);
        const page = ref(1);
        const lastPage = ref(1);
        const total = ref(0);
        const rows = ref<CommissionRow[]>([]);
        const hasBootstrapped = ref(false);

        const load = async () => {
            loading.value = true;
            try {
                const response = await get<any>(withQuery(apiRoute('dashboard_recent', '/api/rencommissions/dashboard/recent'), { limit: 20, page: page.value, status: 'paid' }));
                ensureSuccess(response);
                rows.value = dataOf(response, []);
                const pagination = metaOf(response, 'pagination', {});
                lastPage.value = pagination?.last_page || 1;
                total.value = pagination?.total || 0;
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load payment history.'));
            } finally {
                loading.value = false;
            }
        };

        const exportCurrent = async () => {
            try {
                await downloadCsv(rows.value.map(row => ({ id: row.id })));
                nsSnackBar.success('CSV exported.');
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to export CSV.'));
            }
        };

        const bootstrapLoad = () => {
            if (hasBootstrapped.value) {
                return;
            }
            hasBootstrapped.value = true;
            void load();
        };

        onMounted(() => {
            bootstrapLoad();
        });
        bootstrapLoad();
        return { loading, page, lastPage, total, rows, load, exportCurrent };
    },
    template: `
        <div class="ns-box rounded-lg">
            <div class="p-4 border-b border-box-edge flex items-center justify-between">
                <h3 class="font-semibold text-lg">Payment History</h3>
                <div class="flex items-center gap-2">
                    <button class="ns-button" @click="load">Refresh</button>
                    <button class="ns-button info" @click="exportCurrent">Export CSV</button>
                </div>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead class="bg-input-background">
                        <tr>
                            <th class="text-left p-3">Date</th>
                            <th class="text-left p-3">Earner</th>
                            <th class="text-left p-3">Order</th>
                            <th class="text-left p-3">Product</th>
                            <th class="text-right p-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!loading && rows.length === 0">
                            <td colspan="5" class="p-6 text-center text-secondary">No payment history yet.</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                            <td class="p-3">{{ row.created_at_human }}</td>
                            <td class="p-3">{{ row.earner_name }}</td>
                            <td class="p-3">{{ row.order_code }}</td>
                            <td class="p-3">{{ row.product_name }}</td>
                            <td class="p-3 text-right font-semibold text-success-tertiary">{{ row.formatted_amount }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-box-edge flex items-center justify-between">
                <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }} ({{ total }} total)</div>
                <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
            </div>
        </div>
    `,
});

const NsRencommissionsMyCommissionsComponent = defineComponent({
    name: 'NsRencommissionsMyCommissionsComponent',
    setup() {
        const loading = ref(false);
        const page = ref(1);
        const lastPage = ref(1);
        const rows = ref<any[]>([]);
        const summary = ref<any>(null);

        const load = async () => {
            loading.value = true;
            try {
                const [listRes, summaryRes] = await Promise.all([
                    get<any>(withQuery(apiRoute('my_commissions', '/api/rencommissions/my-commissions'), { page: page.value, per_page: 20 })),
                    get<any>(withQuery(apiRoute('my_summary', '/api/rencommissions/my-summary'), { period: 'this_month' })),
                ]);
                ensureSuccess(listRes);
                ensureSuccess(summaryRes);
                rows.value = dataOf(listRes, []);
                const pagination = metaOf(listRes, 'pagination', {});
                lastPage.value = pagination?.last_page || 1;
                summary.value = dataOf(summaryRes, null);
            } catch (error: any) {
                nsSnackBar.error(toMessage(error, 'Failed to load your commissions.'));
            } finally {
                loading.value = false;
            }
        };

        onMounted(load);
        return { loading, page, lastPage, rows, summary, load, statusClass };
    },
    template: `
        <div class="space-y-6">
            <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Total</div>
                    <div class="text-2xl font-semibold mt-1">{{ summary.total?.formatted || '0' }}</div>
                </div>
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Pending</div>
                    <div class="text-2xl font-semibold mt-1 text-warning-tertiary">{{ summary.pending?.formatted || '0' }}</div>
                </div>
                <div class="ns-box rounded-lg p-4">
                    <div class="text-secondary text-xs uppercase">Paid</div>
                    <div class="text-2xl font-semibold mt-1 text-success-tertiary">{{ summary.paid?.formatted || '0' }}</div>
                </div>
            </div>

            <div class="ns-box rounded-lg">
                <div class="p-4 border-b border-box-edge flex items-center justify-between">
                    <h3 class="font-semibold text-lg">My Commissions</h3>
                    <button class="ns-button" @click="load">Refresh</button>
                </div>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-input-background">
                            <tr>
                                <th class="text-left p-3">Date</th>
                                <th class="text-left p-3">Order</th>
                                <th class="text-left p-3">Product</th>
                                <th class="text-right p-3">Amount</th>
                                <th class="text-center p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!loading && rows.length === 0">
                                <td colspan="5" class="p-6 text-center text-secondary">No commissions found.</td>
                            </tr>
                            <tr v-for="row in rows" :key="row.id" class="border-b border-box-edge">
                                <td class="p-3">{{ new Date(row.created_at).toLocaleDateString() }}</td>
                                <td class="p-3">{{ row.order?.code || 'N/A' }}</td>
                                <td class="p-3">{{ row.product?.name || 'Unknown' }}</td>
                                <td class="p-3 text-right font-semibold">{{ row.total_commission }}</td>
                                <td class="p-3 text-center">
                                    <span :class="['px-2 py-1 rounded text-xs capitalize', statusClass(row.status)]">{{ row.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-box-edge flex items-center justify-between">
                    <button class="ns-button" :disabled="page <= 1" @click="page = Math.max(1, page - 1); load()">Previous</button>
                    <div class="text-sm text-secondary">Page {{ page }} of {{ lastPage }}</div>
                    <button class="ns-button" :disabled="page >= lastPage" @click="page = Math.min(lastPage, page + 1); load()">Next</button>
                </div>
            </div>
        </div>
    `,
});

if (typeof nsExtraComponents !== 'undefined') {
    nsExtraComponents['ns-rencommissions-dashboard'] = NsRencommissionsDashboard;
    nsExtraComponents['ns-rencommissions-all-commissions-component'] = NsRencommissionsAllCommissionsComponent;
    nsExtraComponents['ns-rencommissions-types-management-component'] = NsRencommissionsTypesManagementComponent;
    nsExtraComponents['ns-rencommissions-staff-earnings-component'] = NsRencommissionsStaffEarningsComponent;
    nsExtraComponents['ns-rencommissions-pending-payouts-component'] = NsRencommissionsPendingPayoutsComponent;
    nsExtraComponents['ns-rencommissions-payment-history-component'] = NsRencommissionsPaymentHistoryComponent;
    nsExtraComponents['ns-rencommissions-my-commissions-component'] = NsRencommissionsMyCommissionsComponent;
}
