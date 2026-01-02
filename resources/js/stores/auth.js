import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as authApi from '../api/auth.js'
import * as exchangeApi from '../api/exchange.js'
import { disconnectEcho, initializeEcho } from '../echo.js'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const loading = ref(false)

    const isAuthenticated = computed(() => user.value !== null)

    async function bootstrap() {
        try {
            loading.value = true
            const profile = await exchangeApi.getProfile()
            user.value = profile
            return true
        } catch {
            user.value = null
            return false
        } finally {
            loading.value = false
        }
    }

    async function login(email, password) {
        try {
            loading.value = true
            await authApi.login(email, password)
            const profile = await exchangeApi.getProfile()
            user.value = profile
            initializeEcho()
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        try {
            loading.value = true
            await authApi.logout()
            disconnectEcho()
        } finally {
            user.value = null
            loading.value = false
        }
    }

    return {
        user,
        loading,
        isAuthenticated,
        bootstrap,
        login,
        logout,
    }
})
