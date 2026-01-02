import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echo = null
let currentChannel = null
let currentListener = null
let orderbookChannel = null
let orderCreatedListener = null

export function initializeEcho() {
    if (echo) {
        return echo
    }

    window.Pusher = Pusher

    echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        withCredentials: true,
        enabledTransports: ['ws', 'wss'],
    })

    setupConnectionHandlers()

    return echo
}

function setupConnectionHandlers() {
    if (!echo) {
        return
    }

    const pusher = echo.connector.pusher

    pusher.connection.bind('error', (error) => {
        console.error('Pusher connection error', error)
    })
}

export function attachOrderMatchedListener(userId, callback) {
    if (!userId) {
        console.error('Cannot attach listener without userId')
        return null
    }

    if (currentListener) {
        detachOrderMatchedListener()
    }

    if (!echo) {
        initializeEcho()
    }

    try {
        currentChannel = echo.private(`user.${userId}`)

        currentChannel.error((error) => {
            console.error('Channel subscription error', error)
        })

        currentListener = currentChannel.listen('.order.matched', (payload) => {
            try {
                callback(payload)
            } catch (error) {
                console.error('Error processing order.matched event', error)
            }
        })

        return currentListener
    } catch (error) {
        console.error('Failed to attach order matched listener', error)
        currentChannel = null
        currentListener = null
        return null
    }
}

export function detachOrderMatchedListener() {
    if (currentChannel && currentListener) {
        currentChannel.stopListening('.order.matched', currentListener)
        currentChannel.unsubscribe()
        currentChannel = null
        currentListener = null
    }
}

export function attachOrderCreatedListener(callback) {
    if (!echo) {
        initializeEcho()
    }

    try {
        if (orderbookChannel && orderCreatedListener) {
            detachOrderCreatedListener()
        }

        orderbookChannel = echo.channel('orderbook')

        orderbookChannel.error((error) => {
            console.error('Orderbook channel subscription error', error)
        })

        orderCreatedListener = orderbookChannel.listen('.order.created', (payload) => {
            try {
                callback(payload)
            } catch (error) {
                console.error('Error processing order.created event', error)
            }
        })

        return orderCreatedListener
    } catch (error) {
        console.error('Failed to attach order created listener', error)
        orderbookChannel = null
        orderCreatedListener = null
        return null
    }
}

export function detachOrderCreatedListener() {
    if (orderbookChannel && orderCreatedListener) {
        orderbookChannel.stopListening('.order.created', orderCreatedListener)
        orderbookChannel.unsubscribe()
        orderbookChannel = null
        orderCreatedListener = null
    }
}

export function disconnectEcho() {
    if (echo) {
        detachOrderMatchedListener()
        detachOrderCreatedListener()
        echo.disconnect()
        echo = null
    }
}
