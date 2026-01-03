<template>
    <div class="auth-card">
        <h2>Create Account</h2>
        <p class="subtitle">Start managing your finances</p>

        <form @submit.prevent="handleRegister" class="auth-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Enter your name"
                    required
                />
            </div>

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
                    placeholder="Min. 8 characters"
                    required
                />
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="Confirm your password"
                    required
                />
            </div>

            <div class="error-message" v-if="error">
                {{ error }}
            </div>

            <button type="submit" class="btn btn-primary" :disabled="loading">
                {{ loading ? 'Creating account...' : 'Create Account' }}
            </button>
        </form>

        <p class="auth-footer">
            Already have an account?
            <router-link to="/login">Sign in</router-link>
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
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

async function handleRegister() {
    loading.value = true;
    error.value = '';

    try {
        await authStore.register(form);
        router.push({ name: 'dashboard' });
    } catch (err) {
        error.value = err.response?.data?.message || 'Registration failed. Please try again.';
    } finally {
        loading.value = false;
    }
}
</script>
