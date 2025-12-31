import { createRouter, createWebHistory } from 'vue-router';
import Home from '../pages/Home.vue';
import Orders from '../pages/Orders.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
    },
    {
        path: '/orders',
        name: 'orders',
        component: Orders,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;


