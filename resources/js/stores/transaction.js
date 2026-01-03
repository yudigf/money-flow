import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';
import { useWalletStore } from './wallet';

export const useTransactionStore = defineStore('transaction', () => {
    const transactions = ref([]);
    const pagination = ref({});
    const loading = ref(false);
    const error = ref(null);

    async function fetchTransactions(page = 1) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get(`/transactions?page=${page}`);
            transactions.value = response.data.data.data;
            pagination.value = {
                currentPage: response.data.data.current_page,
                lastPage: response.data.data.last_page,
                total: response.data.data.total,
            };
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch transactions';
        } finally {
            loading.value = false;
        }
    }

    async function createTransaction(transactionData) {
        const response = await api.post('/transactions', transactionData);
        transactions.value.unshift(response.data.data);

        // Refresh wallets to get updated balance
        const walletStore = useWalletStore();
        await walletStore.fetchWallets();

        return response.data;
    }

    async function deleteTransaction(id) {
        await api.delete(`/transactions/${id}`);
        transactions.value = transactions.value.filter(t => t.id !== id);

        // Refresh wallets to get updated balance
        const walletStore = useWalletStore();
        await walletStore.fetchWallets();
    }

    return {
        transactions,
        pagination,
        loading,
        error,
        fetchTransactions,
        createTransaction,
        deleteTransaction,
    };
});
