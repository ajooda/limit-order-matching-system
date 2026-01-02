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
import {
    attachOrderMatchedListener,
    detachOrderMatchedListener,
} from '../echo.js'
import OrderForm from '../components/exchange/OrderForm.vue'
import WalletOverview from '../components/exchange/WalletOverview.vue'
import OrdersTable from '../components/exchange/OrdersTable.vue'
import Orderbook from '../components/exchange/Orderbook.vue'

const authStore = useAuthStore()
const exchangeStore = useExchangeStore()

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
})

onUnmounted(() => {
    detachOrderMatchedListener()
})
</script>
