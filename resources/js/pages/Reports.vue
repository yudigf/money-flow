<template>
    <div class="page reports">
        <header class="page-header">
            <div>
                <h1>Financial Reports</h1>
                <p>Track your income and expenses</p>
            </div>
            <div class="header-actions">
                <div class="month-selector">
                    <button class="btn btn-secondary" @click="prevMonth">←</button>
                    <span class="current-month">{{ monthName }} {{ reportStore.currentYear }}</span>
                    <button class="btn btn-secondary" @click="nextMonth">→</button>
                </div>
            </div>
        </header>

        <!-- Overview Cards -->
        <div class="stats-grid">
            <div class="stat-card income-card">
                <span class="stat-icon">📈</span>
                <div class="stat-content">
                    <span class="stat-label">Income</span>
                    <span class="stat-value">{{ formatCurrency(reportStore.overview.income || 0) }}</span>
                </div>
            </div>
            <div class="stat-card expense-card">
                <span class="stat-icon">📉</span>
                <div class="stat-content">
                    <span class="stat-label">Expense</span>
                    <span class="stat-value">{{ formatCurrency(reportStore.overview.expense || 0) }}</span>
                </div>
            </div>
            <div class="stat-card" :class="{ 'positive': netIncome >= 0, 'negative': netIncome < 0 }">
                <span class="stat-icon">{{ netIncome >= 0 ? '💰' : '⚠️' }}</span>
                <div class="stat-content">
                    <span class="stat-label">Net Income</span>
                    <span class="stat-value">{{ formatCurrency(netIncome) }}</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📊</span>
                <div class="stat-content">
                    <span class="stat-label">Transactions</span>
                    <span class="stat-value">{{ reportStore.overview.transaction_count || 0 }}</span>
                </div>
            </div>
        </div>

        <div class="reports-grid">
            <!-- Monthly Trend Chart -->
            <div class="report-card chart-card">
                <div class="card-header">
                    <h2>📊 Monthly Trend {{ reportStore.currentYear }}</h2>
                    <div class="year-nav">
                        <button class="btn btn-secondary btn-sm" @click="prevYear">←</button>
                        <button class="btn btn-secondary btn-sm" @click="nextYear">→</button>
                    </div>
                </div>
                <div class="chart-container">
                    <div class="bar-chart">
                        <div 
                            v-for="month in reportStore.monthlyTrend.months" 
                            :key="month.month" 
                            class="bar-group"
                        >
                            <div class="bars">
                                <div 
                                    class="bar income" 
                                    :style="{ height: getBarHeight(month.income, maxMonthlyValue) + '%' }"
                                    :title="'Income: ' + formatCurrency(month.income)"
                                ></div>
                                <div 
                                    class="bar expense" 
                                    :style="{ height: getBarHeight(month.expense, maxMonthlyValue) + '%' }"
                                    :title="'Expense: ' + formatCurrency(month.expense)"
                                ></div>
                            </div>
                            <span class="bar-label">{{ month.label }}</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="dot income"></span> Income</span>
                        <span class="legend-item"><span class="dot expense"></span> Expense</span>
                    </div>
                </div>
                <div class="chart-summary">
                    <div class="summary-item">
                        <span class="label">Total Income:</span>
                        <span class="value income">{{ formatCurrency(reportStore.monthlyTrend.total_income) }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="label">Total Expense:</span>
                        <span class="value expense">{{ formatCurrency(reportStore.monthlyTrend.total_expense) }}</span>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="report-card">
                <div class="card-header">
                    <h2>🏷️ Expense by Category</h2>
                </div>
                <div class="category-breakdown" v-if="reportStore.categoryBreakdown.categories?.length">
                    <div 
                        v-for="cat in reportStore.categoryBreakdown.categories" 
                        :key="cat.category_id" 
                        class="category-row"
                    >
                        <div class="category-info">
                            <span class="category-icon">{{ cat.category?.icon || '📁' }}</span>
                            <span class="category-name">{{ cat.category?.name }}</span>
                        </div>
                        <div class="category-bar-container">
                            <div 
                                class="category-bar" 
                                :style="{ width: cat.percentage + '%' }"
                            ></div>
                        </div>
                        <div class="category-value">
                            <span class="amount">{{ formatCurrency(cat.total) }}</span>
                            <span class="percentage">{{ cat.percentage }}%</span>
                        </div>
                    </div>
                </div>
                <div class="empty-state small" v-else>
                    <p>No expense data for this month</p>
                </div>
            </div>
        </div>

        <!-- Daily Activity -->
        <div class="report-card daily-card">
            <div class="card-header">
                <h2>📅 Daily Activity</h2>
            </div>
            <div class="daily-chart">
                <div 
                    v-for="day in reportStore.dailyBreakdown.days" 
                    :key="day.day" 
                    class="day-bar"
                    :class="{ 
                        'has-income': day.income > 0, 
                        'has-expense': day.expense > 0,
                        'has-both': day.income > 0 && day.expense > 0
                    }"
                    :title="getDayTooltip(day)"
                >
                    <div class="day-indicator">
                        <div 
                            class="indicator income" 
                            v-if="day.income > 0"
                            :style="{ height: getBarHeight(day.income, maxDailyValue) + '%' }"
                        ></div>
                        <div 
                            class="indicator expense" 
                            v-if="day.expense > 0"
                            :style="{ height: getBarHeight(day.expense, maxDailyValue) + '%' }"
                        ></div>
                    </div>
                    <span class="day-label">{{ day.day }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useReportStore } from '@/stores/report';

const reportStore = useReportStore();

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const monthName = computed(() => monthNames[reportStore.currentMonth - 1]);

const netIncome = computed(() => reportStore.overview.net || 0);

const maxMonthlyValue = computed(() => {
    const months = reportStore.monthlyTrend.months || [];
    let max = 0;
    months.forEach(m => {
        if (m.income > max) max = m.income;
        if (m.expense > max) max = m.expense;
    });
    return max || 1;
});

const maxDailyValue = computed(() => {
    const days = reportStore.dailyBreakdown.days || [];
    let max = 0;
    days.forEach(d => {
        if (d.income > max) max = d.income;
        if (d.expense > max) max = d.expense;
    });
    return max || 1;
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
}

function getBarHeight(value, max) {
    if (!max || !value) return 0;
    return Math.min((value / max) * 100, 100);
}

function getDayTooltip(day) {
    let tip = `Day ${day.day}\n`;
    if (day.income > 0) tip += `Income: ${formatCurrency(day.income)}\n`;
    if (day.expense > 0) tip += `Expense: ${formatCurrency(day.expense)}`;
    return tip;
}

function prevMonth() {
    let m = reportStore.currentMonth - 1;
    let y = reportStore.currentYear;
    if (m < 1) {
        m = 12;
        y--;
    }
    reportStore.fetchAll(m, y);
}

function nextMonth() {
    let m = reportStore.currentMonth + 1;
    let y = reportStore.currentYear;
    if (m > 12) {
        m = 1;
        y++;
    }
    reportStore.fetchAll(m, y);
}

function prevYear() {
    reportStore.fetchMonthlyTrend(reportStore.currentYear - 1);
}

function nextYear() {
    reportStore.fetchMonthlyTrend(reportStore.currentYear + 1);
}

onMounted(() => {
    reportStore.fetchAll();
});
</script>
