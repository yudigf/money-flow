<template>
    <div class="page dashboard">
        <header class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, {{ authStore.user?.name }}!</p>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card total-balance">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <span class="stat-label">Total Balance</span>
                    <span class="stat-value">{{ formatCurrency(walletStore.getTotalBalance()) }}</span>
                </div>
            </div>
            <div class="stat-card income">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <span class="stat-label">Wallets</span>
                    <span class="stat-value">{{ walletStore.wallets.length }}</span>
                </div>
            </div>
            <div class="stat-card expense">
                <div class="stat-icon">📁</div>
                <div class="stat-content">
                    <span class="stat-label">Categories</span>
                    <span class="stat-value">{{ categoryStore.categories.length }}</span>
                </div>
            </div>
            <div class="stat-card transactions">
                <div class="stat-icon">📝</div>
                <div class="stat-content">
                    <span class="stat-label">Transactions</span>
                    <span class="stat-value">{{ transactionStore.pagination.total || 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Wallets Overview -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Your Wallets</h2>
                <router-link to="/wallets" class="link">View All →</router-link>
            </div>
            <div class="wallets-grid" v-if="walletStore.wallets.length">
                <div
                    v-for="wallet in walletStore.wallets"
                    :key="wallet.id"
                    class="wallet-card"
                    :style="{ '--wallet-color': wallet.color || '#6366f1' }"
                >
                    <div class="wallet-name">{{ wallet.name }}</div>
                    <div class="wallet-balance">{{ formatCurrency(wallet.balance) }}</div>
                </div>
            </div>
            <div class="empty-state" v-else>
                <p>No wallets yet. <router-link to="/wallets">Create one</router-link></p>
            </div>
        </section>

        <!-- Recent Transactions -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Recent Transactions</h2>
                <router-link to="/transactions" class="link">View All →</router-link>
            </div>
            <div class="transactions-list" v-if="transactionStore.transactions.length">
                <div
                    v-for="transaction in transactionStore.transactions.slice(0, 5)"
                    :key="transaction.id"
                    class="transaction-item"
                    :class="transaction.type.toLowerCase()"
                >
                    <div class="transaction-info">
                        <span class="transaction-category">
                            {{ transaction.category?.name || 'Uncategorized' }}
                        </span>
                        <span class="transaction-description">{{ transaction.description }}</span>
                    </div>
                    <div class="transaction-amount" :class="transaction.type.toLowerCase()">
                        {{ transaction.type === 'INCOME' ? '+' : '-' }}
                        {{ formatCurrency(transaction.amount) }}
                    </div>
                </div>
            </div>
            <div class="empty-state" v-else>
                <p>No transactions yet. <router-link to="/transactions">Add one</router-link></p>
            </div>
        </section>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useWalletStore } from '@/stores/wallet';
import { useCategoryStore } from '@/stores/category';
import { useTransactionStore } from '@/stores/transaction';

const authStore = useAuthStore();
const walletStore = useWalletStore();
const categoryStore = useCategoryStore();
const transactionStore = useTransactionStore();

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

onMounted(async () => {
    await Promise.all([
        walletStore.fetchWallets(),
        categoryStore.fetchCategories(),
        transactionStore.fetchTransactions(),
    ]);
});
</script>
