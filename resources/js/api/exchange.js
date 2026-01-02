import api from './index.js'

export async function getProfile() {
    const response = await api.get('/profile')
    return response.data.data
}

export async function getMyOrders(filters = {}) {
    const params = new URLSearchParams()
    if (filters.symbol) params.append('symbol', filters.symbol)
    if (filters.side) params.append('side', filters.side)
    if (filters.status) params.append('status', filters.status)
    if (filters.per_page) params.append('per_page', filters.per_page.toString())
    if (filters.page) params.append('page', filters.page.toString())

    const response = await api.get(`/profile/my-orders?${params.toString()}`)
    return response.data
}

export async function getOrderbook(symbol) {
    const params = new URLSearchParams()
    if (symbol) {
        params.append('symbol', symbol)
    }
    const queryString = params.toString()
    const url = queryString ? `/orders?${queryString}` : '/orders'
    const response = await api.get(url)
    return response.data
}

export async function placeOrder(data) {
    const response = await api.post('/orders', {
        symbol: data.symbol,
        side: data.side,
        price: data.price,
        amount: data.amount,
    })
    return response.data.data
}

export async function cancelOrder(orderId) {
    const response = await api.post(`/orders/${orderId}/cancel`)
    return response.data.data
}

export async function previewOrder(data) {
    const response = await api.post('/orders/preview', {
        symbol: data.symbol,
        side: data.side,
        price: data.price,
        amount: data.amount,
    })
    return response.data
}
