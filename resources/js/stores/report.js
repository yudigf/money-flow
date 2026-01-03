import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useReportStore = defineStore('report', () => {
    const overview = ref({});
    const monthlyTrend = ref({ months: [], total_income: 0, total_expense: 0 });
    const categoryBreakdown = ref({ categories: [], total: 0 });
    const dailyBreakdown = ref({ days: [] });
    
    const currentMonth = ref(new Date().getMonth() + 1);
    const currentYear = ref(new Date().getFullYear());
    const loading = ref(false);

    async function fetchOverview(month = null, year = null) {
        const m = month || currentMonth.value;
        const y = year || currentYear.value;
        
        try {
            const response = await api.get(`/reports/overview?month=${m}&year=${y}`);
            overview.value = response.data.data;
            currentMonth.value = m;
            currentYear.value = y;
        } catch (err) {
            console.error('Failed to fetch overview:', err);
        }
    }

    async function fetchMonthlyTrend(year = null) {
        const y = year || currentYear.value;
        loading.value = true;
        
        try {
            const response = await api.get(`/reports/monthly-trend?year=${y}`);
            monthlyTrend.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch monthly trend:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchCategoryBreakdown(month = null, year = null, type = 'EXPENSE') {
        const m = month || currentMonth.value;
        const y = year || currentYear.value;
        
        try {
            const response = await api.get(`/reports/category-breakdown?month=${m}&year=${y}&type=${type}`);
            categoryBreakdown.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch category breakdown:', err);
        }
    }

    async function fetchDailyBreakdown(month = null, year = null) {
        const m = month || currentMonth.value;
        const y = year || currentYear.value;
        
        try {
            const response = await api.get(`/reports/daily-breakdown?month=${m}&year=${y}`);
            dailyBreakdown.value = response.data.data;
        } catch (err) {
            console.error('Failed to fetch daily breakdown:', err);
        }
    }

    async function fetchAll(month = null, year = null) {
        loading.value = true;
        await Promise.all([
            fetchOverview(month, year),
            fetchMonthlyTrend(year),
            fetchCategoryBreakdown(month, year),
            fetchDailyBreakdown(month, year),
        ]);
        loading.value = false;
    }

    return {
        overview,
        monthlyTrend,
        categoryBreakdown,
        dailyBreakdown,
        currentMonth,
        currentYear,
        loading,
        fetchOverview,
        fetchMonthlyTrend,
        fetchCategoryBreakdown,
        fetchDailyBreakdown,
        fetchAll,
    };
});
