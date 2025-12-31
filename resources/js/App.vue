<template>
    <div id="app">
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <router-link
                            to="/"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            Home
                        </router-link>
                        <router-link
                            to="/orders"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                        >
                            Orders
                        </router-link>
                    </div>
                </div>
            </div>
        </nav>
        <main>
            <router-view />
        </main>
    </div>
</template>

<script setup>
</script>


