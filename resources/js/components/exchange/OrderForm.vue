<template>
    <div
        class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden"
    >
        <div
            :class="[
                'px-6 py-4',
                form.side === 'buy'
                    ? 'bg-gradient-to-r from-green-600 to-emerald-600'
                    : form.side === 'sell'
                      ? 'bg-gradient-to-r from-red-600 to-rose-600'
                      : 'bg-gradient-to-r from-indigo-600 to-purple-600',
            ]"
        >
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
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                <span>Place Order</span>
            </h2>
        </div>
        <div class="p-6">
            <form @submit.prevent="handleSubmit" class="space-y-5">
                <div>
                    <label
                        for="symbol"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        Symbol
                    </label>
                    <select
                        id="symbol"
                        v-model="form.symbol"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm py-2.5 px-3"
                    >
                        <option value="">Select symbol</option>
                        <option value="BTC">BTC</option>
                        <option value="ETH">ETH</option>
                    </select>
                </div>

                <div>
                    <label
                        for="side"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        Side
                    </label>
                    <select
                        id="side"
                        v-model="form.side"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm py-2.5 px-3"
                    >
                        <option value="">Select side</option>
                        <option value="buy">Buy</option>
                        <option value="sell">Sell</option>
                    </select>
                </div>

                <div>
                    <label
                        for="price"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        Price
                    </label>
                    <input
                        id="price"
                        v-model.number="form.price"
                        type="number"
                        step="0.00000001"
                        min="0.00000001"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm py-2.5 px-3"
                        placeholder="0.00"
                    />
                </div>

                <div>
                    <label
                        for="amount"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        Amount
                    </label>
                    <input
                        id="amount"
                        v-model.number="form.amount"
                        type="number"
                        step="0.000000000000000001"
                        min="0.000000000000000001"
                        required
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm py-2.5 px-3"
                        placeholder="0.00"
                    />
                </div>

                <div
                    v-if="
                        previewData &&
                        form.price &&
                        form.amount &&
                        form.price > 0 &&
                        form.amount > 0
                    "
                    class="rounded-lg bg-gradient-to-br from-indigo-50 to-purple-50 p-4 border border-indigo-100"
                >
                    <h3
                        class="text-sm font-semibold text-gray-700 mb-3 flex items-center space-x-2"
                    >
                        <svg
                            class="h-4 w-4 text-indigo-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>Order Preview</span>
                    </h3>
                    <div v-if="previewLoading" class="text-sm text-gray-500">
                        <div class="flex items-center space-x-2">
                            <svg
                                class="animate-spin h-4 w-4 text-indigo-600"
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
                            <span>Calculating...</span>
                        </div>
                    </div>
                    <div v-else class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Volume:</span>
                            <span class="font-semibold text-gray-900"
                                >${{ formatNumber(previewData.volume) }}</span
                            >
                        </div>
                        <div
                            v-if="
                                form.side === 'buy' &&
                                parseFloat(previewData.fee) > 0
                            "
                            class="flex justify-between"
                        >
                            <span class="text-gray-600"
                                >Fee ({{
                                    (previewData.fee_rate * 100).toFixed(2)
                                }}%):</span
                            >
                            <span class="font-semibold text-gray-900"
                                >${{ formatNumber(previewData.fee) }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between pt-2.5 border-t border-indigo-200"
                        >
                            <span class="font-semibold text-gray-700">
                                {{
                                    form.side === 'buy'
                                        ? 'Total Cost'
                                        : 'Total Value'
                                }}:
                            </span>
                            <span class="font-bold text-lg text-gray-900"
                                >${{ formatNumber(previewData.total) }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-if="errors.length > 0"
                    class="rounded-lg bg-red-50 border border-red-200 p-4"
                >
                    <div class="text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li v-for="(error, index) in errors" :key="index">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="loading"
                        :class="[
                            'w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all',
                            form.side === 'buy'
                                ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:ring-green-500'
                                : form.side === 'sell'
                                  ? 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 focus:ring-red-500'
                                  : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:ring-indigo-500',
                        ]"
                    >
                        <span
                            v-if="loading"
                            class="flex items-center space-x-2"
                        >
                            <svg
                                class="animate-spin h-4 w-4 text-white"
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
                            <span>Placing Order...</span>
                        </span>
                        <span v-else>Place Order</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import { useExchangeStore } from '../../stores/exchange.js'
import * as exchangeApi from '../../api/exchange.js'

const exchangeStore = useExchangeStore()

const form = reactive({
    symbol: '',
    side: '',
    price: '',
    amount: '',
})

const previewData = ref(null)
const previewLoading = ref(false)
let previewTimeout = null

function formatNumber(value) {
    if (!value) return '0.00'
    return parseFloat(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    })
}

async function fetchPreview() {
    if (
        !form.symbol ||
        !form.side ||
        !form.price ||
        !form.amount ||
        form.price <= 0 ||
        form.amount <= 0
    ) {
        previewData.value = null
        return
    }

    clearTimeout(previewTimeout)
    previewTimeout = setTimeout(async () => {
        previewLoading.value = true
        try {
            previewData.value = await exchangeApi.previewOrder({
                symbol: form.symbol,
                side: form.side,
                price: form.price,
                amount: form.amount,
            })
        } catch {
            previewData.value = null
        } finally {
            previewLoading.value = false
        }
    }, 300)
}

watch(() => [form.symbol, form.side, form.price, form.amount], fetchPreview)

const errors = ref([])
const loading = ref(false)

function validateForm() {
    errors.value = []
    if (
        !form.symbol ||
        !form.side ||
        !form.price ||
        !form.amount ||
        form.price <= 0 ||
        form.amount <= 0
    ) {
        errors.value.push('Please fill all fields with valid values')
    }
    return errors.value.length === 0
}

async function handleSubmit() {
    if (!validateForm()) {
        return
    }

    loading.value = true
    errors.value = []

    try {
        await exchangeStore.placeOrder({
            symbol: form.symbol,
            side: form.side,
            price: parseFloat(form.price),
            amount: parseFloat(form.amount),
        })

        form.symbol = ''
        form.side = ''
        form.price = ''
        form.amount = ''
    } catch (error) {
        if (error.response?.status === 422) {
            const validationErrors = error.response.data.errors || {}
            errors.value = Object.values(validationErrors).flat()
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Failed to place order']
        }
    } finally {
        loading.value = false
    }
}
</script>
