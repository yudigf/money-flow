import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user')) || null);
    const token = ref(localStorage.getItem('token') || null);

    const isAuthenticated = computed(() => !!token.value);

    async function login(credentials) {
        const response = await api.post('/auth/login', credentials);
        const data = response.data.data;

        token.value = data.token;
        user.value = data.user;

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        return response.data;
    }

    async function register(userData) {
        const response = await api.post('/auth/register', userData);
        const data = response.data.data;

        token.value = data.token;
        user.value = data.user;

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));

        return response.data;
    }

    async function logout() {
        try {
            await api.post('/auth/logout');
        } catch (error) {
            // Ignore error on logout
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
    }

    async function fetchUser() {
        if (!token.value) return;

        try {
            const response = await api.get('/auth/user');
            user.value = response.data.data;
            localStorage.setItem('user', JSON.stringify(response.data.data));
        } catch (error) {
            logout();
        }
    }

    return {
        user,
        token,
        isAuthenticated,
        login,
        register,
        logout,
        fetchUser,
    };
});
