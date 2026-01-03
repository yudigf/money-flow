<template>
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to your account</p>

        <form @submit.prevent="handleLogin" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="Enter your email"
                    required
                />
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    placeholder="Enter your password"
                    required
                />
            </div>

            <div class="error-message" v-if="error">
                {{ error }}
            </div>

            <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Signing in...' : 'Sign In' }}
            </button>
        </form>

        <p class="auth-footer">
            Don't have an account?
            <router-link to="/register">Create one</router-link>
        </p>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const error = ref('');

const form = reactive({
    email: '',
    password: '',
});

async function handleLogin() {
    loading.value = true;
    error.value = '';

    try {
        await authStore.login(form);
        router.push({ name: 'dashboard' });
    } catch (err) {
        error.value = err.response?.data?.message || 'Login failed. Please try again.';
    } finally {
        loading.value = false;
    }
}
</script>
