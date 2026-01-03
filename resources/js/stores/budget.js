import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useBudgetStore = defineStore('budget', () => {
    const budgets = ref([]);
    const unbudgetedCategories = ref([]);
    const summary = ref({});
    const currentMonth = ref(new Date().getMonth() + 1);
    const currentYear = ref(new Date().getFullYear());
    const loading = ref(false);
    const error = ref(null);

    async function fetchBudgets(month = null, year = null) {
        loading.value = true;
        error.value = null;

        const m = month || currentMonth.value;
        const y = year || currentYear.value;

        try {
            const response = await api.get(`/budgets?month=${m}&year=${y}`);
            budgets.value = response.data.data.budgets;
            unbudgetedCategories.value = response.data.data.unbudgeted_categories;
            currentMonth.value = response.data.data.month;
            currentYear.value = response.data.data.year;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch budgets';
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary() {
        try {
            const response = await api.get('/budgets/summary');
            summary.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch budget summary:', err);
        }
    }

    async function createBudget(budgetData) {
        const response = await api.post('/budgets', budgetData);
        budgets.value.push(response.data.data);
        // Remove category from unbudgeted list
        unbudgetedCategories.value = unbudgetedCategories.value.filter(
            c => c.id !== budgetData.category_id
        );
        await fetchSummary();
        return response.data;
    }

    async function updateBudget(id, amount) {
        const response = await api.put(`/budgets/${id}`, { amount });
        const index = budgets.value.findIndex(b => b.id === id);
        if (index !== -1) {
            budgets.value[index] = response.data.data;
        }
        await fetchSummary();
        return response.data;
    }

    async function deleteBudget(id) {
        const budget = budgets.value.find(b => b.id === id);
        await api.delete(`/budgets/${id}`);
        budgets.value = budgets.value.filter(b => b.id !== id);
        // Add category back to unbudgeted list
        if (budget?.category) {
            unbudgetedCategories.value.push(budget.category);
        }
        await fetchSummary();
    }

    return {
        budgets,
        unbudgetedCategories,
        summary,
        currentMonth,
        currentYear,
        loading,
        error,
        fetchBudgets,
        fetchSummary,
        createBudget,
        updateBudget,
        deleteBudget,
    };
});
