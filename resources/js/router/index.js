import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Layouts
import AuthLayout from '@/layouts/AuthLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';

// Pages
import Login from '@/pages/auth/Login.vue';
import Register from '@/pages/auth/Register.vue';
import Dashboard from '@/pages/Dashboard.vue';
import Wallets from '@/pages/Wallets.vue';
import Categories from '@/pages/Categories.vue';
import Transactions from '@/pages/Transactions.vue';
import Budgets from '@/pages/Budgets.vue';

const routes = [
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/',
        component: AuthLayout,
        meta: { guest: true },
        children: [
            { path: 'login', name: 'login', component: Login },
            { path: 'register', name: 'register', component: Register },
        ],
    },
    {
        path: '/',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            { path: 'dashboard', name: 'dashboard', component: Dashboard },
            { path: 'wallets', name: 'wallets', component: Wallets },
            { path: 'categories', name: 'categories', component: Categories },
            { path: 'transactions', name: 'transactions', component: Transactions },
            { path: 'budgets', name: 'budgets', component: Budgets },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guards
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
