import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echo = null
let currentChannel = null
let currentListener = null

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

export function disconnectEcho() {
    if (echo) {
        detachOrderMatchedListener()
        echo.disconnect()
        echo = null
    }
}
