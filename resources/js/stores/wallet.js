import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useWalletStore = defineStore('wallet', () => {
    const wallets = ref([]);
    const loading = ref(false);
    const error = ref(null);

    async function fetchWallets() {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/wallets');
            wallets.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch wallets';
        } finally {
            loading.value = false;
        }
    }

    async function createWallet(walletData) {
        const response = await api.post('/wallets', walletData);
        wallets.value.push(response.data.data);
        return response.data;
    }

    async function updateWallet(id, walletData) {
        const response = await api.put(`/wallets/${id}`, walletData);
        const index = wallets.value.findIndex(w => w.id === id);
        if (index !== -1) {
            wallets.value[index] = response.data.data;
        }
        return response.data;
    }

    async function deleteWallet(id) {
        await api.delete(`/wallets/${id}`);
        wallets.value = wallets.value.filter(w => w.id !== id);
    }

    function getTotalBalance() {
        return wallets.value.reduce((sum, w) => sum + parseFloat(w.balance), 0);
    }

    return {
        wallets,
        loading,
        error,
        fetchWallets,
        createWallet,
        updateWallet,
        deleteWallet,
        getTotalBalance,
    };
});
