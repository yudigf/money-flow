import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useCategoryStore = defineStore('category', () => {
    const categories = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const incomeCategories = computed(() =>
        categories.value.filter(c => c.type === 'INCOME')
    );

    const expenseCategories = computed(() =>
        categories.value.filter(c => c.type === 'EXPENSE')
    );

    async function fetchCategories() {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get('/categories');
            categories.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch categories';
        } finally {
            loading.value = false;
        }
    }

    async function createCategory(categoryData) {
        const response = await api.post('/categories', categoryData);
        categories.value.push(response.data.data);
        return response.data;
    }

    async function updateCategory(id, categoryData) {
        const response = await api.put(`/categories/${id}`, categoryData);
        const index = categories.value.findIndex(c => c.id === id);
        if (index !== -1) {
            categories.value[index] = response.data.data;
        }
        return response.data;
    }

    async function deleteCategory(id) {
        await api.delete(`/categories/${id}`);
        categories.value = categories.value.filter(c => c.id !== id);
    }

    return {
        categories,
        loading,
        error,
        incomeCategories,
        expenseCategories,
        fetchCategories,
        createCategory,
        updateCategory,
        deleteCategory,
    };
});
