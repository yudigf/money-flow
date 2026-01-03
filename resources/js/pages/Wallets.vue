<template>
    <div class="page wallets">
        <header class="page-header">
            <div>
                <h1>Wallets</h1>
                <p>Manage your wallets and balances</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">
                + Add Wallet
            </button>
        </header>

        <div class="wallets-grid" v-if="walletStore.wallets.length">
            <div
                v-for="wallet in walletStore.wallets"
                :key="wallet.id"
                class="wallet-card large"
                :style="{ '--wallet-color': wallet.color || '#6366f1' }"
            >
                <div class="wallet-header">
                    <span class="wallet-icon">👛</span>
                    <div class="wallet-actions">
                        <button class="icon-btn" @click="openModal(wallet)">✏️</button>
                        <button class="icon-btn delete" @click="handleDelete(wallet.id)">🗑️</button>
                    </div>
                </div>
                <div class="wallet-name">{{ wallet.name }}</div>
                <div class="wallet-balance">{{ formatCurrency(wallet.balance) }}</div>
            </div>
        </div>

        <div class="empty-state" v-else>
            <div class="empty-icon">👛</div>
            <h3>No wallets yet</h3>
            <p>Create your first wallet to start tracking your finances</p>
            <button class="btn btn-primary" @click="openModal()">Create Wallet</button>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingWallet ? 'Edit Wallet' : 'New Wallet' }}</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-group">
                        <label for="name">Wallet Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Cash, Bank Account"
                            required
                        />
                    </div>

                    <div class="form-group" v-if="!editingWallet">
                        <label for="balance">Initial Balance</label>
                        <input
                            id="balance"
                            v-model="form.balance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="form-group">
                        <label for="color">Color</label>
                        <div class="color-picker">
                            <button
                                v-for="color in colors"
                                :key="color"
                                type="button"
                                class="color-option"
                                :class="{ selected: form.color === color }"
                                :style="{ backgroundColor: color }"
                                @click="form.color = color"
                            ></button>
                        </div>
                    </div>

                    <div class="error-message" v-if="error">{{ error }}</div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="closeModal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            {{ loading ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useWalletStore } from '@/stores/wallet';

const walletStore = useWalletStore();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const editingWallet = ref(null);

const colors = ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899', '#06B6D4', '#84CC16'];

const form = reactive({
    name: '',
    balance: '',
    color: '#6366f1',
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function openModal(wallet = null) {
    editingWallet.value = wallet;
    if (wallet) {
        form.name = wallet.name;
        form.balance = wallet.balance;
        form.color = wallet.color || '#6366f1';
    } else {
        form.name = '';
        form.balance = '';
        form.color = '#6366f1';
    }
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingWallet.value = null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        if (editingWallet.value) {
            await walletStore.updateWallet(editingWallet.value.id, {
                name: form.name,
                color: form.color,
            });
        } else {
            await walletStore.createWallet({
                name: form.name,
                balance: form.balance || 0,
                color: form.color,
            });
        }
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save wallet';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this wallet?')) return;

    try {
        await walletStore.deleteWallet(id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete wallet');
    }
}

// Fetch on mount
walletStore.fetchWallets();
</script>
