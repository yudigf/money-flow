<template>
    <div class="page budgets">
        <header class="page-header">
            <div>
                <h1>Budget Planner</h1>
                <p>Set spending limits per category</p>
            </div>
            <div class="header-actions">
                <div class="month-selector">
                    <button class="btn btn-secondary" @click="prevMonth">←</button>
                    <span class="current-month">{{ monthName }} {{ budgetStore.currentYear }}</span>
                    <button class="btn btn-secondary" @click="nextMonth">→</button>
                </div>
                <button class="btn btn-primary" @click="openModal()" v-if="budgetStore.unbudgetedCategories.length">
                    + Add Budget
                </button>
            </div>
        </header>

        <!-- Budget Summary -->
        <div class="budget-summary" v-if="budgetStore.budgets.length">
            <div class="summary-card">
                <div class="summary-header">
                    <span class="summary-label">Total Budget</span>
                    <span class="summary-value">{{ formatCurrency(totalBudget) }}</span>
                </div>
                <div class="progress-bar-container">
                    <div 
                        class="progress-bar" 
                        :class="{ warning: overallPercentage >= 80, danger: overallPercentage >= 100 }"
                        :style="{ width: Math.min(overallPercentage, 100) + '%' }"
                    ></div>
                </div>
                <div class="summary-footer">
                    <span>Spent: {{ formatCurrency(totalSpent) }}</span>
                    <span>{{ overallPercentage }}%</span>
                </div>
            </div>
        </div>

        <!-- Budget List -->
        <div class="budgets-grid" v-if="budgetStore.budgets.length">
            <div 
                v-for="budget in budgetStore.budgets" 
                :key="budget.id" 
                class="budget-card"
                :class="{ over: budget.is_over, warning: budget.percentage >= 80 && !budget.is_over }"
            >
                <div class="budget-header">
                    <div class="budget-category">
                        <span class="category-icon">{{ budget.category?.icon || '📁' }}</span>
                        <span class="category-name">{{ budget.category?.name }}</span>
                    </div>
                    <div class="budget-actions">
                        <button class="icon-btn" @click="openModal(budget)">✏️</button>
                        <button class="icon-btn delete" @click="handleDelete(budget.id)">🗑️</button>
                    </div>
                </div>
                
                <div class="budget-progress">
                    <div class="progress-bar-container">
                        <div 
                            class="progress-bar"
                            :class="{ warning: budget.percentage >= 80, danger: budget.is_over }"
                            :style="{ width: Math.min(budget.percentage, 100) + '%' }"
                        ></div>
                    </div>
                    <div class="progress-labels">
                        <span class="spent">{{ formatCurrency(budget.spent) }}</span>
                        <span class="percentage" :class="{ over: budget.is_over }">
                            {{ budget.percentage }}%
                        </span>
                    </div>
                </div>

                <div class="budget-footer">
                    <div class="budget-limit">
                        <span class="label">Limit:</span>
                        <span class="value">{{ formatCurrency(budget.amount) }}</span>
                    </div>
                    <div class="budget-remaining" :class="{ negative: budget.remaining < 0 }">
                        <span class="label">{{ budget.remaining < 0 ? 'Over:' : 'Left:' }}</span>
                        <span class="value">{{ formatCurrency(Math.abs(budget.remaining)) }}</span>
                    </div>
                </div>

                <div class="over-badge" v-if="budget.is_over">⚠️ Over Budget!</div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" v-else>
            <div class="empty-icon">📊</div>
            <h3>No budgets set for {{ monthName }}</h3>
            <p>Set spending limits to keep your expenses in check</p>
            <button class="btn btn-primary" @click="openModal()" v-if="budgetStore.unbudgetedCategories.length">
                Create First Budget
            </button>
            <p v-else class="text-muted">Create expense categories first to set budgets</p>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingBudget ? 'Edit Budget' : 'New Budget' }}</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-group" v-if="!editingBudget">
                        <label for="category_id">Category</label>
                        <select id="category_id" v-model="form.category_id" required>
                            <option value="">Select category</option>
                            <option 
                                v-for="category in budgetStore.unbudgetedCategories" 
                                :key="category.id" 
                                :value="category.id"
                            >
                                {{ category.icon }} {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group" v-else>
                        <label>Category</label>
                        <div class="selected-category">
                            {{ editingBudget.category?.icon }} {{ editingBudget.category?.name }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="amount">Monthly Limit (Rp)</label>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="1000"
                            min="1000"
                            placeholder="e.g., 1500000"
                            required
                        />
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
import { ref, reactive, computed, onMounted } from 'vue';
import { useBudgetStore } from '@/stores/budget';

const budgetStore = useBudgetStore();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const editingBudget = ref(null);

const form = reactive({
    category_id: '',
    amount: '',
});

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const monthName = computed(() => monthNames[budgetStore.currentMonth - 1]);

const totalBudget = computed(() => 
    budgetStore.budgets.reduce((sum, b) => sum + parseFloat(b.amount), 0)
);

const totalSpent = computed(() => 
    budgetStore.budgets.reduce((sum, b) => sum + parseFloat(b.spent), 0)
);

const overallPercentage = computed(() => {
    if (totalBudget.value === 0) return 0;
    return Math.round((totalSpent.value / totalBudget.value) * 100);
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function prevMonth() {
    let m = budgetStore.currentMonth - 1;
    let y = budgetStore.currentYear;
    if (m < 1) {
        m = 12;
        y--;
    }
    budgetStore.fetchBudgets(m, y);
}

function nextMonth() {
    let m = budgetStore.currentMonth + 1;
    let y = budgetStore.currentYear;
    if (m > 12) {
        m = 1;
        y++;
    }
    budgetStore.fetchBudgets(m, y);
}

function openModal(budget = null) {
    editingBudget.value = budget;
    if (budget) {
        form.category_id = budget.category_id;
        form.amount = budget.amount;
    } else {
        form.category_id = '';
        form.amount = '';
    }
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingBudget.value = null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        if (editingBudget.value) {
            await budgetStore.updateBudget(editingBudget.value.id, form.amount);
        } else {
            await budgetStore.createBudget({
                category_id: form.category_id,
                amount: form.amount,
                month: budgetStore.currentMonth,
                year: budgetStore.currentYear,
            });
        }
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save budget';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this budget?')) return;

    try {
        await budgetStore.deleteBudget(id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete budget');
    }
}

onMounted(() => {
    budgetStore.fetchBudgets();
});
</script>
