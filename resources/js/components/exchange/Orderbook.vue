<template>
    <div
        class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
    >
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
            <div class="flex items-center justify-between">
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
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                    <span>Orderbook - {{ selectedSymbol || 'All' }}</span>
                </h2>
                <div class="flex items-center space-x-2">
                    <label
                        for="symbol-filter"
                        class="text-sm font-medium text-white/90"
                    >
                        Filter:
                    </label>
                    <select
                        id="symbol-filter"
                        :value="selectedSymbol || ''"
                        @change="handleSymbolChange"
                        class="rounded-md border-white/20 bg-white/10 text-white shadow-sm focus:border-white/40 focus:ring-2 focus:ring-white/50 transition-all sm:text-sm py-1.5 px-3 cursor-pointer"
                    >
                        <option value="" class="text-gray-900">All</option>
                        <option value="BTC" class="text-gray-900">BTC</option>
                        <option value="ETH" class="text-gray-900">ETH</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="p-6">
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
            <div v-else-if="orderbook">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        class="border border-gray-200 rounded-lg overflow-hidden"
                    >
                        <div
                            class="bg-green-50 px-4 py-3 border-b border-green-200"
                        >
                            <h3
                                class="text-sm font-semibold text-green-700 flex items-center space-x-2"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                    />
                                </svg>
                                <span>Buy Orders</span>
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Symbol
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Price
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-100"
                                >
                                    <tr
                                        v-for="order in buyOrders"
                                        :key="order.id"
                                        class="hover:bg-green-50/50 transition-colors"
                                    >
                                        <td
                                            class="px-4 py-2.5 text-sm font-medium text-gray-900"
                                        >
                                            {{ order.symbol }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-sm font-medium text-green-600"
                                        >
                                            ${{ formatNumber(order.price) }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-sm text-gray-900"
                                        >
                                            {{ formatNumber(order.amount) }}
                                        </td>
                                    </tr>
                                    <tr v-if="buyOrders.length === 0">
                                        <td
                                            colspan="3"
                                            class="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No buy orders
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div
                        class="border border-gray-200 rounded-lg overflow-hidden"
                    >
                        <div
                            class="bg-red-50 px-4 py-3 border-b border-red-200"
                        >
                            <h3
                                class="text-sm font-semibold text-red-700 flex items-center space-x-2"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
                                    />
                                </svg>
                                <span>Sell Orders</span>
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Symbol
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Price
                                        </th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"
                                        >
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-100"
                                >
                                    <tr
                                        v-for="order in sellOrders"
                                        :key="order.id"
                                        class="hover:bg-red-50/50 transition-colors"
                                    >
                                        <td
                                            class="px-4 py-2.5 text-sm font-medium text-gray-900"
                                        >
                                            {{ order.symbol }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-sm font-medium text-red-600"
                                        >
                                            ${{ formatNumber(order.price) }}
                                        </td>
                                        <td
                                            class="px-4 py-2.5 text-sm text-gray-900"
                                        >
                                            {{ formatNumber(order.amount) }}
                                        </td>
                                    </tr>
                                    <tr v-if="sellOrders.length === 0">
                                        <td
                                            colspan="3"
                                            class="px-4 py-8 text-center text-sm text-gray-500"
                                        >
                                            No sell orders
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-gray-500 text-center py-8">
                No orderbook data
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch, ref, onMounted } from 'vue'
import { useExchangeStore } from '../../stores/exchange.js'

const exchangeStore = useExchangeStore()

const selectedSymbol = ref(null)
const orderbook = computed(() => exchangeStore.orderbook)
const loading = computed(() => exchangeStore.loading)

const buyOrders = computed(() => {
    if (!orderbook.value?.buy) return []
    return Array.isArray(orderbook.value.buy) ? orderbook.value.buy : []
})

const sellOrders = computed(() => {
    if (!orderbook.value?.sell) return []
    return Array.isArray(orderbook.value.sell) ? orderbook.value.sell : []
})

function handleSymbolChange(event) {
    const value = event.target.value
    selectedSymbol.value = value || null
}

watch(selectedSymbol, (newSymbol) => {
    exchangeStore.fetchOrderbook(newSymbol)
})

onMounted(() => {
    exchangeStore.fetchOrderbook(selectedSymbol.value)
})

function formatNumber(value) {
    if (!value) return '0.00'
    const num = parseFloat(value)
    if (isNaN(num)) return '0.00'
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    })
}
</script>
