<template>
    <div
        id="app"
        class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100"
    >
        <nav
            class="bg-white/80 backdrop-blur-sm shadow-sm border-b border-gray-200/50 sticky top-0 z-40"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <router-link
                            v-if="authStore.isAuthenticated"
                            to="/exchange"
                            class="inline-flex items-center space-x-2 px-4 py-2 text-sm font-semibold text-gray-900 hover:text-indigo-600 transition-colors"
                        >
                            <svg
                                class="h-6 w-6 text-indigo-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <span
                                class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                            >
                            Limit-Order Exchange Mini Engine
                            </span>
                        </router-link>
                    </div>
                    <div
                        v-if="authStore.isAuthenticated"
                        class="flex items-center"
                    >
                        <div class="relative">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="inline-flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <div
                                    class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs"
                                >
                                    {{
                                        (authStore.user?.name ||
                                            'U')[0].toUpperCase()
                                    }}
                                </div>
                                <span class="font-medium">{{
                                    authStore.user?.name || 'User'
                                }}</span>
                                <svg
                                    class="ml-1 h-4 w-4 text-gray-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>
                            <div
                                v-if="showUserMenu"
                                class="absolute right-0 mt-2 w-48 rounded-lg bg-white shadow-xl ring-1 ring-black/5 focus:outline-none z-50 overflow-hidden"
                            >
                                <div class="py-1">
                                    <button
                                        @click="handleLogout"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors"
                                    >
                                        <span
                                            class="flex items-center space-x-2"
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
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                                />
                                            </svg>
                                            <span>Logout</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <main class="flex-1">
            <router-view />
        </main>
        <Toast />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth.js'
import Toast from './components/Toast.vue'

const router = useRouter()
const authStore = useAuthStore()
const showUserMenu = ref(false)

function handleClickOutside(event) {
    if (!event.target.closest('.relative')) {
        showUserMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

async function handleLogout() {
    showUserMenu.value = false
    await authStore.logout()
    router.push('/login')
}
</script>
