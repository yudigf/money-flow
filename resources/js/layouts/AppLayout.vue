<template>
    <div class="app-layout">
        <aside class="sidebar" :class="{ collapsed: sidebarCollapsed }">
            <div class="sidebar-header">
                <div class="brand">
                    <span class="brand-icon">💰</span>
                    <span class="brand-text" v-if="!sidebarCollapsed">MoneyFlow</span>
                </div>
                <button class="toggle-btn" @click="sidebarCollapsed = !sidebarCollapsed">
                    <span>{{ sidebarCollapsed ? '→' : '←' }}</span>
                </button>
            </div>

            <nav class="sidebar-nav">
                <router-link to="/dashboard" class="nav-item" active-class="active">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Dashboard</span>
                </router-link>
                <router-link to="/wallets" class="nav-item" active-class="active">
                    <span class="nav-icon">👛</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Wallets</span>
                </router-link>
                <router-link to="/categories" class="nav-item" active-class="active">
                    <span class="nav-icon">🏷️</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Categories</span>
                </router-link>
                <router-link to="/transactions" class="nav-item" active-class="active">
                    <span class="nav-icon">💸</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Transactions</span>
                </router-link>
                <router-link to="/budgets" class="nav-item" active-class="active">
                    <span class="nav-icon">📋</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Budgets</span>
                </router-link>
                <router-link to="/reports" class="nav-item" active-class="active">
                    <span class="nav-icon">📈</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Reports</span>
                </router-link>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info" v-if="!sidebarCollapsed">
                    <span class="user-name">{{ authStore.user?.name }}</span>
                    <span class="user-email">{{ authStore.user?.email }}</span>
                </div>
                <button class="logout-btn" @click="handleLogout">
                    <span class="nav-icon">🚪</span>
                    <span class="nav-text" v-if="!sidebarCollapsed">Logout</span>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const sidebarCollapsed = ref(false);

async function handleLogout() {
    await authStore.logout();
    router.push({ name: 'login' });
}
</script>
