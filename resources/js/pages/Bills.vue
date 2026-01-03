<template>
    <div class="page bills">
        <header class="page-header">
            <div>
                <h1>Bill Reminder</h1>
                <p>Track recurring bills and never miss a payment</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">+ Add Bill</button>
        </header>

        <!-- Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card overdue-card" v-if="billStore.summary.overdue_count > 0">
                <span class="stat-icon">⚠️</span>
                <div class="stat-content">
                    <span class="stat-label">Overdue</span>
                    <span class="stat-value">{{ billStore.summary.overdue_count }} bills</span>
                </div>
            </div>
            <div class="stat-card warning-card" v-if="billStore.summary.due_soon_count > 0">
                <span class="stat-icon">⏰</span>
                <div class="stat-content">
                    <span class="stat-label">Due Soon</span>
                    <span class="stat-value">{{ billStore.summary.due_soon_count }} bills</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📋</span>
                <div class="stat-content">
                    <span class="stat-label">Active Bills</span>
                    <span class="stat-value">{{ billStore.summary.total_active || 0 }}</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">💸</span>
                <div class="stat-content">
                    <span class="stat-label">Monthly Total</span>
                    <span class="stat-value">{{ formatCurrency(billStore.summary.total_monthly) }}</span>
                </div>
            </div>
        </div>

        <!-- Bills List -->
        <div class="bills-container" v-if="billStore.bills.length">
            <!-- Overdue -->
            <div class="bills-section" v-if="billStore.overdueBills.length">
                <h3 class="section-title overdue">⚠️ Overdue</h3>
                <div class="bills-grid">
                    <BillCard 
                        v-for="bill in billStore.overdueBills" 
                        :key="bill.id" 
                        :bill="bill"
                        @edit="openModal(bill)"
                        @delete="handleDelete(bill.id)"
                        @pay="handlePay(bill)"
                    />
                </div>
            </div>

            <!-- Due Soon -->
            <div class="bills-section" v-if="billStore.dueSoonBills.length">
                <h3 class="section-title due-soon">⏰ Due Soon</h3>
                <div class="bills-grid">
                    <BillCard 
                        v-for="bill in billStore.dueSoonBills" 
                        :key="bill.id" 
                        :bill="bill"
                        @edit="openModal(bill)"
                        @delete="handleDelete(bill.id)"
                        @pay="handlePay(bill)"
                    />
                </div>
            </div>

            <!-- Upcoming -->
            <div class="bills-section" v-if="billStore.upcomingBills.length">
                <h3 class="section-title">📅 Upcoming</h3>
                <div class="bills-grid">
                    <BillCard 
                        v-for="bill in billStore.upcomingBills" 
                        :key="bill.id" 
                        :bill="bill"
                        @edit="openModal(bill)"
                        @delete="handleDelete(bill.id)"
                        @pay="handlePay(bill)"
                    />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" v-else-if="!billStore.loading">
            <div class="empty-icon">🔔</div>
            <h3>No bills yet</h3>
            <p>Add recurring bills to track and never miss a payment</p>
            <button class="btn btn-primary" @click="openModal()">Add First Bill</button>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingBill ? 'Edit Bill' : 'New Bill' }}</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-group">
                        <label for="name">Bill Name</label>
                        <input id="name" v-model="form.name" type="text" placeholder="e.g., Electricity" required />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount (Rp)</label>
                            <input id="amount" v-model="form.amount" type="number" min="1000" step="1000" required />
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input id="due_date" v-model="form.due_date" type="date" required />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="frequency">Frequency</label>
                            <select id="frequency" v-model="form.frequency">
                                <option value="WEEKLY">Weekly</option>
                                <option value="MONTHLY">Monthly</option>
                                <option value="YEARLY">Yearly</option>
                                <option value="ONCE">One-time</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" v-model="form.category_id">
                                <option value="">Select category</option>
                                <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                                    {{ cat.icon }} {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="wallet_id">Pay From Wallet</label>
                        <select id="wallet_id" v-model="form.wallet_id">
                            <option value="">Select wallet</option>
                            <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                                {{ wallet.name }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <input id="notes" v-model="form.notes" type="text" placeholder="Additional notes" />
                    </div>

                    <div class="error-message" v-if="error">{{ error }}</div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
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
import { ref, reactive, onMounted } from 'vue';
import { useBillStore } from '@/stores/bill';
import { useCategoryStore } from '@/stores/category';
import { useWalletStore } from '@/stores/wallet';
import BillCard from '@/components/BillCard.vue';

const billStore = useBillStore();
const categoryStore = useCategoryStore();
const walletStore = useWalletStore();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const editingBill = ref(null);

const form = reactive({
    name: '',
    amount: '',
    due_date: '',
    frequency: 'MONTHLY',
    category_id: '',
    wallet_id: '',
    notes: '',
});

const expenseCategories = ref([]);
const wallets = ref([]);

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
}

function openModal(bill = null) {
    editingBill.value = bill;
    if (bill) {
        form.name = bill.name;
        form.amount = bill.amount;
        form.due_date = bill.due_date;
        form.frequency = bill.frequency;
        form.category_id = bill.category_id || '';
        form.wallet_id = bill.wallet_id || '';
        form.notes = bill.notes || '';
    } else {
        form.name = '';
        form.amount = '';
        form.due_date = new Date().toISOString().split('T')[0];
        form.frequency = 'MONTHLY';
        form.category_id = '';
        form.wallet_id = '';
        form.notes = '';
    }
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingBill.value = null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        const data = { ...form };
        if (!data.category_id) delete data.category_id;
        if (!data.wallet_id) delete data.wallet_id;

        if (editingBill.value) {
            await billStore.updateBill(editingBill.value.id, data);
        } else {
            await billStore.createBill(data);
        }
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save bill';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this bill?')) return;
    try {
        await billStore.deleteBill(id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete bill');
    }
}

async function handlePay(bill) {
    const createTransaction = bill.wallet_id && bill.category_id;
    const msg = createTransaction 
        ? `Mark "${bill.name}" as paid and create expense transaction?`
        : `Mark "${bill.name}" as paid?`;
    
    if (!confirm(msg)) return;

    try {
        await billStore.markAsPaid(bill.id, createTransaction);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to mark as paid');
    }
}

onMounted(async () => {
    await Promise.all([
        billStore.fetchBills(),
        billStore.fetchSummary(),
        categoryStore.fetchCategories(),
        walletStore.fetchWallets(),
    ]);
    expenseCategories.value = categoryStore.expenseCategories;
    wallets.value = walletStore.wallets;
});
</script>
