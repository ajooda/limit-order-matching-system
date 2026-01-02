import './bootstrap'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useAuthStore } from './stores/auth.js'
import { initializeEcho } from './echo.js'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

const authStore = useAuthStore()

authStore.bootstrap().then((isAuthenticated) => {
    if (isAuthenticated) {
        initializeEcho()
    }
    app.mount('#app')
})
