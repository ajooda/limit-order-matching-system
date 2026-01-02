import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as exchangeApi from '../api/exchange.js'
import { useToastStore } from './toast.js'
import { formatNumber } from '../utils/format.js'

export const useExchangeStore = defineStore('exchange', () => {
    const profile = ref(null)
    const myOrders = ref([])
    const orderbook = ref(null)
    const loading = ref(false)
    const error = ref(null)

    async function fetchProfile() {
        try {
            loading.value = true
            error.value = null
            profile.value = await exchangeApi.getProfile()
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    async function fetchMyOrders(filters = {}) {
        try {
            loading.value = true
            error.value = null
            const response = await exchangeApi.getMyOrders(filters)
            myOrders.value = response.data || []
            return response
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    async function fetchOrderbook(symbol) {
        try {
            loading.value = true
            error.value = null
            orderbook.value = await exchangeApi.getOrderbook(symbol)
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    async function placeOrder(data) {
        const toastStore = useToastStore()
        try {
            loading.value = true
            error.value = null
            const order = await exchangeApi.placeOrder(data)
            await Promise.all([
                fetchProfile(),
                fetchMyOrders(),
                fetchOrderbook(data.symbol),
            ])
            toastStore.success(
                `Order placed: ${data.side.toUpperCase()} ${formatNumber(data.amount)} ${data.symbol} @ $${formatNumber(data.price)}`,
            )
            return order
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    async function cancelOrder(orderId) {
        const toastStore = useToastStore()
        try {
            loading.value = true
            error.value = null
            const order = await exchangeApi.cancelOrder(orderId)
            const orderIndex = myOrders.value.findIndex((o) => o.id == orderId)
            if (orderIndex !== -1) {
                myOrders.value[orderIndex] = order
            }
            await Promise.all([fetchProfile(), fetchOrderbook(order.symbol)])
            toastStore.success('Order cancelled')
            return order
        } catch (err) {
            error.value = err
            throw err
        } finally {
            loading.value = false
        }
    }

    let orderbookRefreshTimeout = null

    function applyOrderMatchedEvent(payload) {
        if (!payload) return

        const toastStore = useToastStore()

        if (payload.user) {
            if (profile.value) {
                profile.value = {
                    ...profile.value,
                    balance_usd: payload.user.balance_usd,
                    assets: payload.user.assets,
                }
            }
        }

        if (payload.orders) {
            let needsRefresh = false
            const filledOrders = []

            Object.entries(payload.orders).forEach(([orderId, status]) => {
                const orderIndex = myOrders.value.findIndex(
                    (o) => o.id == orderId,
                )
                const statusNum = Number(status)

                if (orderIndex !== -1) {
                    const order = myOrders.value[orderIndex]
                    if (order.status !== statusNum) {
                        if (statusNum === 2) {
                            filledOrders.push(order)
                            myOrders.value.splice(orderIndex, 1)
                        } else if (statusNum === 3) {
                            myOrders.value.splice(orderIndex, 1)
                        } else {
                            myOrders.value[orderIndex] = {
                                ...order,
                                status: statusNum,
                            }
                        }
                    }
                } else {
                    needsRefresh = true
                }
            })

            filledOrders.forEach((order) => {
                const side = order.side === 1 ? 'BUY' : 'SELL'
                toastStore.success(
                    `Order filled: ${side} ${formatNumber(order.amount)} ${order.symbol} @ $${formatNumber(order.price)}`,
                )
            })

            if (needsRefresh) {
                fetchMyOrders().catch(() => {})
            }
        }

        if (payload.trade?.symbol) {
            clearTimeout(orderbookRefreshTimeout)
            orderbookRefreshTimeout = setTimeout(() => {
                fetchOrderbook(payload.trade.symbol).catch(() => {})
            }, 500)
        }
    }

    function appendOrderToOrderbook(order) {
        if (!order || !orderbook.value || order.status !== 1) {
            return
        }

        if (orderbook.value.symbol && order.symbol !== orderbook.value.symbol) {
            return
        }

        const orders = orderbook.value[order.side === 1 ? 'buy' : 'sell']
        if (!orders) {
            return
        }

        if (orders.some((o) => o.id === order.id)) {
            return
        }

        orders.push({
            id: order.id,
            symbol: order.symbol,
            side: order.side,
            status: order.status,
            price: String(order.price),
            amount: String(order.amount),
            created_at: order.created_at,
        })

        if (order.side === 1) {
            orders.sort((a, b) => parseFloat(b.price) - parseFloat(a.price))
        } else {
            orders.sort((a, b) => parseFloat(a.price) - parseFloat(b.price))
        }
    }

    return {
        profile,
        myOrders,
        orderbook,
        loading,
        error,
        fetchProfile,
        fetchMyOrders,
        fetchOrderbook,
        placeOrder,
        cancelOrder,
        applyOrderMatchedEvent,
        appendOrderToOrderbook,
    }
})
