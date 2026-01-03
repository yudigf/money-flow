<template>
    <div class="page transactions">
        <header class="page-header">
            <div>
                <h1>Transactions</h1>
                <p>Track your income and expenses</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">
                + Add Transaction
            </button>
        </header>

        <div class="transactions-container" v-if="transactionStore.transactions.length">
            <div class="transactions-table">
                <div class="table-header">
                    <span>Date</span>
                    <span>Description</span>
                    <span>Category</span>
                    <span>Wallet</span>
                    <span>Amount</span>
                    <span>Actions</span>
                </div>
                <div
                    v-for="transaction in transactionStore.transactions"
                    :key="transaction.id"
                    class="table-row"
                    :class="transaction.type.toLowerCase()"
                >
                    <span class="date">{{ formatDate(transaction.date) }}</span>
                    <span class="description">{{ transaction.description }}</span>
                    <span class="category">
                        <span class="category-badge">
                            {{ transaction.category?.icon || '📁' }}
                            {{ transaction.category?.name || 'Uncategorized' }}
                        </span>
                    </span>
                    <span class="wallet">{{ transaction.wallet?.name }}</span>
                    <span class="amount" :class="transaction.type.toLowerCase()">
                        {{ transaction.type === 'INCOME' ? '+' : '-' }}
                        {{ formatCurrency(transaction.amount) }}
                    </span>
                    <span class="actions">
                        <button class="icon-btn delete" @click="handleDelete(transaction.id)">🗑️</button>
                    </span>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination" v-if="transactionStore.pagination.lastPage > 1">
                <button
                    class="btn btn-secondary"
                    :disabled="transactionStore.pagination.currentPage === 1"
                    @click="transactionStore.fetchTransactions(transactionStore.pagination.currentPage - 1)"
                >
                    Previous
                </button>
                <span class="page-info">
                    Page {{ transactionStore.pagination.currentPage }} of {{ transactionStore.pagination.lastPage }}
                </span>
                <button
                    class="btn btn-secondary"
                    :disabled="transactionStore.pagination.currentPage === transactionStore.pagination.lastPage"
                    @click="transactionStore.fetchTransactions(transactionStore.pagination.currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <div class="empty-state" v-else>
            <div class="empty-icon">💸</div>
            <h3>No transactions yet</h3>
            <p>Start tracking your finances by adding your first transaction</p>
            <button class="btn btn-primary" @click="openModal()">Add Transaction</button>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>New Transaction</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Type</label>
                            <div class="type-toggle">
                                <button
                                    type="button"
                                    class="toggle-btn"
                                    :class="{ active: form.type === 'INCOME' }"
                                    @click="form.type = 'INCOME'; form.category_id = ''"
                                >
                                    📈 Income
                                </button>
                                <button
                                    type="button"
                                    class="toggle-btn"
                                    :class="{ active: form.type === 'EXPENSE' }"
                                    @click="form.type = 'EXPENSE'; form.category_id = ''"
                                >
                                    📉 Expense
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            required
                        />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="wallet_id">Wallet</label>
                            <select id="wallet_id" v-model="form.wallet_id" required>
                                <option value="">Select wallet</option>
                                <option
                                    v-for="wallet in walletStore.wallets"
                                    :key="wallet.id"
                                    :value="wallet.id"
                                >
                                    {{ wallet.name }} ({{ formatCurrency(wallet.balance) }})
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" v-model="form.category_id">
                                <option value="">Select category</option>
                                <option
                                    v-for="category in filteredCategories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.icon }} {{ category.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <input
                            id="description"
                            v-model="form.description"
                            type="text"
                            placeholder="What was this transaction for?"
                            required
                        />
                    </div>

                    <div class="error-message" v-if="error">{{ error }}</div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="closeModal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            {{ loading ? 'Saving...' : 'Save Transaction' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useTransactionStore } from '@/stores/transaction';
import { useWalletStore } from '@/stores/wallet';
import { useCategoryStore } from '@/stores/category';

const transactionStore = useTransactionStore();
const walletStore = useWalletStore();
const categoryStore = useCategoryStore();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');

const form = reactive({
    type: 'EXPENSE',
    amount: '',
    wallet_id: '',
    category_id: '',
    date: new Date().toISOString().split('T')[0],
    description: '',
});

const filteredCategories = computed(() => {
    return form.type === 'INCOME'
        ? categoryStore.incomeCategories
        : categoryStore.expenseCategories;
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function openModal() {
    form.type = 'EXPENSE';
    form.amount = '';
    form.wallet_id = '';
    form.category_id = '';
    form.date = new Date().toISOString().split('T')[0];
    form.description = '';
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        await transactionStore.createTransaction({
            type: form.type,
            amount: form.amount,
            wallet_id: form.wallet_id,
            category_id: form.category_id || null,
            date: form.date,
            description: form.description,
        });
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save transaction';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this transaction? This will reverse the wallet balance change.')) return;

    try {
        await transactionStore.deleteTransaction(id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete transaction');
    }
}

onMounted(async () => {
    await Promise.all([
        transactionStore.fetchTransactions(),
        walletStore.fetchWallets(),
        categoryStore.fetchCategories(),
    ]);
});
</script>
