<template>
    <div class="bill-card" :class="[bill.status]">
        <div class="bill-header">
            <div class="bill-info">
                <span class="bill-icon">{{ bill.category?.icon || '📄' }}</span>
                <div>
                    <span class="bill-name">{{ bill.name }}</span>
                    <span class="bill-frequency">{{ frequencyLabel }}</span>
                </div>
            </div>
            <div class="bill-actions">
                <button class="icon-btn" @click="$emit('edit')" title="Edit">✏️</button>
                <button class="icon-btn delete" @click="$emit('delete')" title="Delete">🗑️</button>
            </div>
        </div>

        <div class="bill-amount">{{ formatCurrency(bill.amount) }}</div>

        <div class="bill-footer">
            <div class="due-info">
                <span class="due-label">Due:</span>
                <span class="due-date" :class="{ overdue: bill.is_overdue }">
                    {{ formatDate(bill.due_date) }}
                </span>
                <span class="days-badge" :class="bill.status">
                    {{ daysLabel }}
                </span>
            </div>
            <button 
                class="btn btn-sm pay-btn" 
                :class="{ 'btn-primary': bill.is_overdue || bill.is_due_soon }"
                @click="$emit('pay')"
            >
                ✓ Pay
            </button>
        </div>

        <div class="bill-wallet" v-if="bill.wallet">
            <span>💳 {{ bill.wallet.name }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    bill: { type: Object, required: true }
});

defineEmits(['edit', 'delete', 'pay']);

const frequencyLabel = computed(() => ({
    'WEEKLY': 'Weekly',
    'MONTHLY': 'Monthly', 
    'YEARLY': 'Yearly',
    'ONCE': 'One-time'
})[props.bill.frequency] || props.bill.frequency);

const daysLabel = computed(() => {
    const days = props.bill.days_until_due;
    if (days < 0) return `${Math.abs(days)}d overdue`;
    if (days === 0) return 'Today';
    if (days === 1) return 'Tomorrow';
    return `in ${days}d`;
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
    });
}
</script>
