import axios from 'axios'

const authApi = axios.create({
    baseURL: '/',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
})

export async function login(email, password) {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })

    await authApi.post('/login', {
        email,
        password,
    })
}

export async function logout() {
    await authApi.post('/logout')
}
