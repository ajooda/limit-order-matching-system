<template>
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <OrderForm />
                </div>
                <div class="lg:col-span-2 space-y-6">
                    <WalletOverview />
                    <OrdersTable />
                    <Orderbook />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import { useExchangeStore } from '../stores/exchange.js'
import { useToastStore } from '../stores/toast.js'
import {
    attachOrderMatchedListener,
    detachOrderMatchedListener,
    attachOrderCreatedListener,
    detachOrderCreatedListener,
} from '../echo.js'
import { formatNumber } from '../utils/format.js'
import OrderForm from '../components/exchange/OrderForm.vue'
import WalletOverview from '../components/exchange/WalletOverview.vue'
import OrdersTable from '../components/exchange/OrdersTable.vue'
import Orderbook from '../components/exchange/Orderbook.vue'

const authStore = useAuthStore()
const exchangeStore = useExchangeStore()
const toastStore = useToastStore()

onMounted(async () => {
    try {
        await Promise.all([
            exchangeStore.fetchProfile(),
            exchangeStore.fetchMyOrders(),
        ])
    } catch (error) {
        console.error('Failed to load initial exchange data:', error)
    }

    if (authStore.user?.id) {
        const listener = attachOrderMatchedListener(
            authStore.user.id,
            (payload) => {
                exchangeStore.applyOrderMatchedEvent(payload)
            },
        )

        if (!listener) {
            console.error('Failed to attach order matched listener')
        }
    } else {
        console.warn('Cannot attach Echo listener: user not authenticated')
    }

    // Attach order created listener (public channel, works for all users)
    const orderCreatedListener = attachOrderCreatedListener((payload) => {
        if (payload?.order) {
            exchangeStore.appendOrderToOrderbook(payload.order)

            const side = payload.order.side === 1 ? 'BUY' : 'SELL'
            const symbol = payload.order.symbol
            const amount = formatNumber(payload.order.amount)
            const price = formatNumber(payload.order.price)

            toastStore.info(
                `New ${side} order: ${amount} ${symbol} @ $${price}`,
            )
        }
    })

    if (!orderCreatedListener) {
        console.error('Failed to attach order created listener')
    }
})

onUnmounted(() => {
    detachOrderMatchedListener()
    detachOrderCreatedListener()
})
</script>
