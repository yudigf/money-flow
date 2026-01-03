import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useBillStore = defineStore('bill', () => {
    const bills = ref([]);
    const summary = ref({});
    const loading = ref(false);
    const error = ref(null);

    const overdueBills = computed(() => 
        bills.value.filter(b => b.status === 'overdue')
    );

    const dueSoonBills = computed(() => 
        bills.value.filter(b => b.status === 'due_soon')
    );

    const upcomingBills = computed(() => 
        bills.value.filter(b => b.status === 'upcoming')
    );

    const activeBills = computed(() => 
        bills.value.filter(b => b.is_active)
    );

    async function fetchBills() {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/bills');
            bills.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch bills';
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary() {
        try {
            const response = await api.get('/bills/summary');
            summary.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch bill summary:', err);
        }
    }

    async function createBill(billData) {
        const response = await api.post('/bills', billData);
        bills.value.push(response.data.data);
        await fetchSummary();
        return response.data;
    }

    async function updateBill(id, billData) {
        const response = await api.put(`/bills/${id}`, billData);
        const index = bills.value.findIndex(b => b.id === id);
        if (index !== -1) {
            bills.value[index] = response.data.data;
        }
        await fetchSummary();
        return response.data;
    }

    async function deleteBill(id) {
        await api.delete(`/bills/${id}`);
        bills.value = bills.value.filter(b => b.id !== id);
        await fetchSummary();
    }

    async function markAsPaid(id, createTransaction = true) {
        const response = await api.post(`/bills/${id}/mark-paid`, { create_transaction: createTransaction });
        const index = bills.value.findIndex(b => b.id === id);
        if (index !== -1) {
            bills.value[index] = response.data.data;
        }
        await fetchSummary();
        return response.data;
    }

    return {
        bills,
        summary,
        loading,
        error,
        overdueBills,
        dueSoonBills,
        upcomingBills,
        activeBills,
        fetchBills,
        fetchSummary,
        createBill,
        updateBill,
        deleteBill,
        markAsPaid,
    };
});
