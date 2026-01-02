<template>
    <div
        class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="max-w-md w-full">
            <div
                class="bg-white rounded-2xl shadow-xl p-8 space-y-8 border border-gray-100"
            >
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <div
                            class="h-16 w-16 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg"
                        >
                            <svg
                                class="h-8 w-8 text-white"
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
                        </div>
                    </div>
                    <h2
                        class="text-3xl font-bold text-gray-900 bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                    >
                        Login
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Sign in to your account to continue
                    </p>
                </div>
                <form class="space-y-6" @submit.prevent="handleSubmit">
                    <div class="space-y-4">
                        <div>
                            <label
                                for="email"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Email address
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all sm:text-sm"
                                placeholder="you@example.com"
                            />
                        </div>
                        <div>
                            <label
                                for="password"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all sm:text-sm"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <div
                        v-if="errors.length > 0"
                        class="rounded-lg bg-red-50 border border-red-200 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg
                                    class="h-5 w-5 text-red-400"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    There were errors with your submission
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li
                                            v-for="(error, index) in errors"
                                            :key="index"
                                        >
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="group relative w-full flex justify-center items-center py-3 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg hover:shadow-xl"
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
                                <span>Signing in...</span>
                            </span>
                            <span v-else>Sign in</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    email: '',
    password: '',
})

const errors = ref([])
const loading = ref(false)

async function handleSubmit() {
    errors.value = []
    loading.value = true

    try {
        await authStore.login(form.email, form.password)
        router.push('/exchange')
    } catch (error) {
        if (error.response?.status === 422) {
            const validationErrors = error.response.data.errors || {}
            errors.value = Object.values(validationErrors).flat()
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message]
        } else {
            errors.value = ['Invalid email or password']
        }
    } finally {
        loading.value = false
    }
}
</script>
