import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import Login from '../pages/Login.vue'
import Exchange from '../pages/Exchange.vue'

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { requiresGuest: true },
    },
    {
        path: '/exchange',
        name: 'exchange',
        component: Exchange,
        meta: { requiresAuth: true },
    },
    {
        path: '/',
        redirect: (_to) => {
            const authStore = useAuthStore()
            return authStore.isAuthenticated ? '/exchange' : '/login'
        },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        if (!authStore.user) {
            const isAuthenticated = await authStore.bootstrap()
            if (!isAuthenticated) {
                return next('/login')
            }
        }
    }

    if (to.meta.requiresGuest && authStore.isAuthenticated) {
        return next('/exchange')
    }

    next()
})

export default router
