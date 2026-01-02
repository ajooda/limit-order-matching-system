import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
})

let csrfCookieFetched = false

api.interceptors.request.use(
    async (config) => {
        if (!csrfCookieFetched) {
            try {
                await axios.get('/sanctum/csrf-cookie', {
                    withCredentials: true,
                })
                csrfCookieFetched = true
            } catch (error) {
                console.error('Failed to fetch CSRF cookie:', error)
            }
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    },
)

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 || error.response?.status === 403) {
            console.error('Unauthorized access')
        }
        return Promise.reject(error)
    },
)

export default api
