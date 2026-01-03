import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useInvestmentStore = defineStore('investment', () => {
    const investments = ref([]);
    const summary = ref({});
    const loading = ref(false);
    const error = ref(null);

    const groupedByType = computed(() => {
        const groups = {};
        investments.value.forEach(inv => {
            if (!groups[inv.type]) {
                groups[inv.type] = [];
            }
            groups[inv.type].push(inv);
        });
        return groups;
    });

    const totalValue = computed(() => 
        investments.value.reduce((sum, inv) => sum + inv.current_value, 0)
    );

    async function fetchInvestments() {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/investments');
            investments.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch investments';
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary() {
        try {
            const response = await api.get('/investments/summary');
            summary.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch investment summary:', err);
        }
    }

    async function createInvestment(data) {
        const response = await api.post('/investments', data);
        investments.value.push(response.data.data);
        await fetchSummary();
        return response.data;
    }

    async function updateInvestment(id, data) {
        const response = await api.put(`/investments/${id}`, data);
        const index = investments.value.findIndex(i => i.id === id);
        if (index !== -1) {
            investments.value[index] = response.data.data;
        }
        await fetchSummary();
        return response.data;
    }

    async function updatePrice(id, currentPrice) {
        const response = await api.patch(`/investments/${id}/price`, { current_price: currentPrice });
        const index = investments.value.findIndex(i => i.id === id);
        if (index !== -1) {
            investments.value[index] = response.data.data;
        }
        await fetchSummary();
        return response.data;
    }

    async function deleteInvestment(id) {
        await api.delete(`/investments/${id}`);
        investments.value = investments.value.filter(i => i.id !== id);
        await fetchSummary();
    }

    return {
        investments,
        summary,
        loading,
        error,
        groupedByType,
        totalValue,
        fetchInvestments,
        fetchSummary,
        createInvestment,
        updateInvestment,
        updatePrice,
        deleteInvestment,
    };
});
