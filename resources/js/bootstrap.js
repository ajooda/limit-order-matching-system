import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// TODO: Call GET /sanctum/csrf-cookie authenticated API requests
// to ensure CSRF protection is enabled
