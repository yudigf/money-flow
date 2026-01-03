<template>
    <div class="page investments">
        <header class="page-header">
            <div>
                <h1>Investment Tracker</h1>
                <p>Track your portfolio performance</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">+ Add Investment</button>
        </header>

        <!-- Portfolio Summary -->
        <div class="portfolio-summary">
            <div class="portfolio-card">
                <div class="portfolio-main">
                    <span class="portfolio-label">Total Portfolio Value</span>
                    <span class="portfolio-value">{{ formatCurrency(investmentStore.summary.total_value) }}</span>
                    <div class="portfolio-change" :class="{ profit: isProfit, loss: !isProfit }">
                        <span class="change-icon">{{ isProfit ? '📈' : '📉' }}</span>
                        <span class="change-value">{{ formatCurrency(Math.abs(investmentStore.summary.profit_loss)) }}</span>
                        <span class="change-percent">({{ investmentStore.summary.profit_loss_percent }}%)</span>
                    </div>
                </div>
                <div class="portfolio-stats">
                    <div class="stat">
                        <span class="stat-label">Total Cost</span>
                        <span class="stat-value">{{ formatCurrency(investmentStore.summary.total_cost) }}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Assets</span>
                        <span class="stat-value">{{ investmentStore.summary.investments_count || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investments by Type -->
        <div class="investments-container" v-if="investmentStore.investments.length">
            <div 
                v-for="(invs, type) in investmentStore.groupedByType" 
                :key="type" 
                class="investment-section"
            >
                <h3 class="section-title">{{ getTypeIcon(type) }} {{ getTypeLabel(type) }}</h3>
                <div class="investments-grid">
                    <div 
                        v-for="inv in invs" 
                        :key="inv.id" 
                        class="investment-card"
                        :class="{ profit: inv.is_profitable, loss: !inv.is_profitable }"
                    >
                        <div class="inv-header">
                            <div class="inv-info">
                                <span class="inv-name">{{ inv.name }}</span>
                                <span class="inv-symbol" v-if="inv.symbol">{{ inv.symbol }}</span>
                            </div>
                            <div class="inv-actions">
                                <button class="icon-btn" @click="openModal(inv)">✏️</button>
                                <button class="icon-btn delete" @click="handleDelete(inv.id)">🗑️</button>
                            </div>
                        </div>

                        <div class="inv-value">{{ formatCurrency(inv.current_value) }}</div>

                        <div class="inv-details">
                            <div class="detail">
                                <span class="label">Quantity</span>
                                <span class="value">{{ formatQuantity(inv.quantity) }}</span>
                            </div>
                            <div class="detail">
                                <span class="label">Avg Price</span>
                                <span class="value">{{ formatCurrency(inv.buy_price) }}</span>
                            </div>
                            <div class="detail">
                                <span class="label">Current</span>
                                <span class="value price-editable" @click="openPriceModal(inv)">
                                    {{ formatCurrency(inv.current_price) }} ✏️
                                </span>
                            </div>
                        </div>

                        <div class="inv-profit" :class="{ profit: inv.is_profitable, loss: !inv.is_profitable }">
                            <span class="profit-value">{{ inv.is_profitable ? '+' : '' }}{{ formatCurrency(inv.profit_loss) }}</span>
                            <span class="profit-percent">({{ inv.profit_loss_percent }}%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" v-else-if="!investmentStore.loading">
            <div class="empty-icon">💼</div>
            <h3>No investments yet</h3>
            <p>Start tracking your portfolio by adding investments</p>
            <button class="btn btn-primary" @click="openModal()">Add First Investment</button>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingInv ? 'Edit Investment' : 'New Investment' }}</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input id="name" v-model="form.name" type="text" placeholder="e.g., Bitcoin" required />
                        </div>
                        <div class="form-group">
                            <label for="symbol">Symbol</label>
                            <input id="symbol" v-model="form.symbol" type="text" placeholder="e.g., BTC" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" v-model="form.type" required>
                            <option value="STOCK">📈 Stock</option>
                            <option value="CRYPTO">₿ Cryptocurrency</option>
                            <option value="MUTUAL_FUND">📊 Mutual Fund</option>
                            <option value="GOLD">🥇 Gold</option>
                            <option value="BOND">📜 Bond</option>
                            <option value="PROPERTY">🏠 Property</option>
                            <option value="OTHER">💼 Other</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input id="quantity" v-model="form.quantity" type="number" step="0.00000001" min="0" required />
                        </div>
                        <div class="form-group">
                            <label for="buy_price">Buy Price (Rp)</label>
                            <input id="buy_price" v-model="form.buy_price" type="number" step="0.01" min="0" required />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_price">Current Price (Rp)</label>
                            <input id="current_price" v-model="form.current_price" type="number" step="0.01" min="0" />
                        </div>
                        <div class="form-group">
                            <label for="buy_date">Buy Date</label>
                            <input id="buy_date" v-model="form.buy_date" type="date" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <input id="notes" v-model="form.notes" type="text" placeholder="Optional notes" />
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

        <!-- Price Update Modal -->
        <div class="modal-overlay" v-if="showPriceModal" @click.self="closePriceModal">
            <div class="modal small">
                <div class="modal-header">
                    <h2>Update Price</h2>
                    <button class="close-btn" @click="closePriceModal">×</button>
                </div>
                <form @submit.prevent="handlePriceUpdate" class="modal-form">
                    <div class="form-group">
                        <label>{{ priceInvestment?.name }}</label>
                        <input v-model="newPrice" type="number" step="0.01" min="0.01" required />
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="closePriceModal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="loading">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useInvestmentStore } from '@/stores/investment';

const investmentStore = useInvestmentStore();

const showModal = ref(false);
const showPriceModal = ref(false);
const loading = ref(false);
const error = ref('');
const editingInv = ref(null);
const priceInvestment = ref(null);
const newPrice = ref('');

const form = reactive({
    name: '',
    symbol: '',
    type: 'STOCK',
    quantity: '',
    buy_price: '',
    current_price: '',
    buy_date: '',
    notes: '',
});

const isProfit = computed(() => (investmentStore.summary.profit_loss || 0) >= 0);

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
}

function formatQuantity(qty) {
    return qty < 1 ? qty.toFixed(8) : qty.toLocaleString();
}

function getTypeIcon(type) {
    const icons = { STOCK: '📈', CRYPTO: '₿', MUTUAL_FUND: '📊', GOLD: '🥇', BOND: '📜', PROPERTY: '🏠', OTHER: '💼' };
    return icons[type] || '💰';
}

function getTypeLabel(type) {
    const labels = { STOCK: 'Stocks', CRYPTO: 'Crypto', MUTUAL_FUND: 'Mutual Funds', GOLD: 'Gold', BOND: 'Bonds', PROPERTY: 'Property', OTHER: 'Other' };
    return labels[type] || type;
}

function openModal(inv = null) {
    editingInv.value = inv;
    if (inv) {
        Object.assign(form, {
            name: inv.name,
            symbol: inv.symbol || '',
            type: inv.type,
            quantity: inv.quantity,
            buy_price: inv.buy_price,
            current_price: inv.current_price,
            buy_date: inv.buy_date || '',
            notes: inv.notes || '',
        });
    } else {
        Object.assign(form, { name: '', symbol: '', type: 'STOCK', quantity: '', buy_price: '', current_price: '', buy_date: '', notes: '' });
    }
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingInv.value = null;
}

function openPriceModal(inv) {
    priceInvestment.value = inv;
    newPrice.value = inv.current_price;
    showPriceModal.value = true;
}

function closePriceModal() {
    showPriceModal.value = false;
    priceInvestment.value = null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        const data = { ...form };
        if (!data.current_price) data.current_price = data.buy_price;

        if (editingInv.value) {
            await investmentStore.updateInvestment(editingInv.value.id, data);
        } else {
            await investmentStore.createInvestment(data);
        }
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save investment';
    } finally {
        loading.value = false;
    }
}

async function handlePriceUpdate() {
    loading.value = true;
    try {
        await investmentStore.updatePrice(priceInvestment.value.id, newPrice.value);
        closePriceModal();
    } catch (err) {
        alert('Failed to update price');
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Delete this investment?')) return;
    try {
        await investmentStore.deleteInvestment(id);
    } catch (err) {
        alert('Failed to delete');
    }
}

onMounted(async () => {
    await Promise.all([
        investmentStore.fetchInvestments(),
        investmentStore.fetchSummary(),
    ]);
});
</script>
