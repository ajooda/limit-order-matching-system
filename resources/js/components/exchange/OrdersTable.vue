<template>
    <div
        class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
    >
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
            <div class="flex justify-between items-center">
                <h2
                    class="text-lg font-semibold text-white flex items-center space-x-2"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        />
                    </svg>
                    <span>My Orders</span>
                </h2>
                <button
                    @click="showFilters = !showFilters"
                    class="text-sm font-medium text-white/90 hover:text-white bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg transition-colors"
                >
                    {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                </button>
            </div>
        </div>
        <div class="p-6">
            <div v-if="showFilters" class="mb-4 p-4 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label
                            for="filter-symbol"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Symbol
                        </label>
                        <select
                            id="filter-symbol"
                            v-model="filters.symbol"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="filter-side"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Side
                        </label>
                        <select
                            id="filter-side"
                            v-model="filters.side"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="filter-status"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Status
                        </label>
                        <select
                            id="filter-status"
                            v-model="filters.status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="filled">Filled</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="filter-per-page"
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Per Page
                        </label>
                        <select
                            id="filter-per-page"
                            v-model="filters.per_page"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Apply Filters
                    </button>
                    <button
                        @click="clearFilters"
                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div v-if="loading" class="text-gray-500 text-center py-8">
                <svg
                    class="animate-spin h-8 w-8 mx-auto text-indigo-600"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>
            <div v-else>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Symbol
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Side
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Price
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Amount
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Created
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="order in myOrders" :key="order.id">
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ order.symbol }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    <span
                                        :class="[
                                            order.side === 1 ||
                                            order.side === 'buy' ||
                                            order.side === 'BUY'
                                                ? 'text-green-600'
                                                : 'text-red-600',
                                        ]"
                                    >
                                        {{
                                            order.side === 1 ||
                                            order.side === 'buy' ||
                                            order.side === 'BUY'
                                                ? 'Buy'
                                                : 'Sell'
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    ${{ formatNumber(order.price) }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ formatNumber(order.amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        :class="[
                                            getStatusClass(order.status),
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        ]"
                                    >
                                        {{ getStatusText(order.status) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{ formatDate(order.created_at) }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <button
                                        v-if="isOpenOrder(order.status)"
                                        @click="handleCancel(order.id)"
                                        :disabled="cancelling === order.id"
                                        class="text-red-600 hover:text-red-900 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{
                                            cancelling === order.id
                                                ? 'Cancelling...'
                                                : 'Cancel'
                                        }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="myOrders.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-4 text-center text-sm text-gray-500"
                                >
                                    No orders found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="pagination.last_page > 1"
                    class="mt-4 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
                >
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="
                                pagination.current_page === pagination.last_page
                            "
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                    <div
                        class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{
                                    (pagination.current_page - 1) *
                                        pagination.per_page +
                                    1
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    Math.min(
                                        pagination.current_page *
                                            pagination.per_page,
                                        pagination.total,
                                    )
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    pagination.total
                                }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav
                                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                                aria-label="Pagination"
                            >
                                <button
                                    @click="
                                        goToPage(pagination.current_page - 1)
                                    "
                                    :disabled="pagination.current_page === 1"
                                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Previous</span>
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                                <template
                                    v-for="page in getPageNumbers()"
                                    :key="page"
                                >
                                    <button
                                        v-if="page !== '...'"
                                        @click="goToPage(page)"
                                        :class="[
                                            page === pagination.current_page
                                                ? 'relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                                : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                    <span
                                        v-else
                                        class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0"
                                    >
                                        ...
                                    </span>
                                </template>
                                <button
                                    @click="
                                        goToPage(pagination.current_page + 1)
                                    "
                                    :disabled="
                                        pagination.current_page ===
                                        pagination.last_page
                                    "
                                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Next</span>
                                    <svg
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useExchangeStore } from '../../stores/exchange.js'

const exchangeStore = useExchangeStore()

const myOrders = computed(() => exchangeStore.myOrders)
const loading = computed(() => exchangeStore.loading)
const cancelling = ref(null)
const showFilters = ref(false)

const filters = ref({
    symbol: '',
    side: '',
    status: '',
    per_page: 20,
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
})

async function applyFilters() {
    const activeFilters = {}
    if (filters.value.symbol) activeFilters.symbol = filters.value.symbol
    if (filters.value.side) activeFilters.side = filters.value.side
    if (filters.value.status) activeFilters.status = filters.value.status
    if (filters.value.per_page) activeFilters.per_page = filters.value.per_page
    activeFilters.page = pagination.value.current_page

    try {
        const response = await exchangeStore.fetchMyOrders(activeFilters)
        if (response?.meta) {
            pagination.value = {
                current_page: response.meta.current_page || 1,
                last_page: response.meta.last_page || 1,
                per_page: response.meta.per_page || 20,
                total: response.meta.total || 0,
            }
        }
    } catch (error) {
        console.error('Failed to fetch filtered orders:', error)
    }
}

function clearFilters() {
    filters.value = {
        symbol: '',
        side: '',
        status: '',
        per_page: 20,
    }
    pagination.value.current_page = 1
    applyFilters()
}

function goToPage(page) {
    if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        applyFilters()
    }
}

function getPageNumbers() {
    const current = pagination.value.current_page
    const last = pagination.value.last_page
    const pages = []

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i)
        }
    } else {
        if (current <= 3) {
            for (let i = 1; i <= 4; i++) {
                pages.push(i)
            }
            pages.push('...')
            pages.push(last)
        } else if (current >= last - 2) {
            pages.push(1)
            pages.push('...')
            for (let i = last - 3; i <= last; i++) {
                pages.push(i)
            }
        } else {
            pages.push(1)
            pages.push('...')
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i)
            }
            pages.push('...')
            pages.push(last)
        }
    }

    return pages
}

watch(
    () => filters.value.per_page,
    () => {
        pagination.value.current_page = 1
        applyFilters()
    },
)

onMounted(async () => {
    await applyFilters()
})

function isOpenOrder(status) {
    return status === 1 || status === 'open' || status === 'OPEN'
}

function getStatusText(status) {
    if (status === 1 || status === 'open' || status === 'OPEN') return 'Open'
    if (status === 2 || status === 'filled' || status === 'FILLED')
        return 'Filled'
    if (status === 3 || status === 'cancelled' || status === 'CANCELLED')
        return 'Cancelled'
    return String(status)
}

function getStatusClass(status) {
    if (status === 1 || status === 'open' || status === 'OPEN')
        return 'bg-yellow-100 text-yellow-800'
    if (status === 2 || status === 'filled' || status === 'FILLED')
        return 'bg-green-100 text-green-800'
    if (status === 3 || status === 'cancelled' || status === 'CANCELLED')
        return 'bg-gray-100 text-gray-800'
    return 'bg-gray-100 text-gray-800'
}

function formatNumber(value) {
    if (!value) return '0.00'
    const num = parseFloat(value)
    if (isNaN(num)) return '0.00'
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    })
}

function formatDate(value) {
    if (!value) return '-'
    try {
        return new Date(value).toLocaleString()
    } catch {
        return value
    }
}

async function handleCancel(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        cancelling.value = orderId
        try {
            await exchangeStore.cancelOrder(orderId)
        } catch {
            alert('Failed to cancel order')
        } finally {
            cancelling.value = null
        }
    }
}
</script>
