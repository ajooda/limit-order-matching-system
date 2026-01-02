<template>
    <div
        class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
    >
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
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
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span>Wallet</span>
            </h2>
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
            <div v-else-if="profile">
                <div class="space-y-6">
                    <div
                        class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-5 border border-indigo-100"
                    >
                        <div class="text-sm font-medium text-gray-600 mb-1">
                            USD Balance
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            ${{ formatNumber(profile.balance_usd) }}
                        </div>
                    </div>

                    <div>
                        <div
                            class="text-sm font-semibold text-gray-700 mb-3 flex items-center space-x-2"
                        >
                            <svg
                                class="h-4 w-4 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                            <span>Assets</span>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="asset in assets"
                                :key="asset.symbol"
                                class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors border border-gray-100"
                            >
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ asset.symbol }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Available:
                                        <span
                                            class="font-medium text-gray-700"
                                            >{{
                                                formatNumber(asset.available)
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900">
                                        {{ formatNumber(asset.amount) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Locked:
                                        <span
                                            class="font-medium text-gray-700"
                                            >{{
                                                formatNumber(
                                                    asset.locked_amount,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="assets.length === 0"
                                class="text-sm text-gray-500 text-center py-4"
                            >
                                No assets
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-gray-500 text-center py-8">
                No wallet data
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useExchangeStore } from '../../stores/exchange.js'

const exchangeStore = useExchangeStore()

const profile = computed(() => exchangeStore.profile)
const loading = computed(() => exchangeStore.loading)

const assets = computed(() => {
    if (!profile.value?.assets) {
        return []
    }
    return profile.value.assets.map((asset) => ({
        ...asset,
        available: parseFloat(asset.amount) - parseFloat(asset.locked_amount),
    }))
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
