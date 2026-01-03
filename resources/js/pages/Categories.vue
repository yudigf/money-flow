<template>
    <div class="page categories">
        <header class="page-header">
            <div>
                <h1>Categories</h1>
                <p>Organize your transactions</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">
                + Add Category
            </button>
        </header>

        <div class="categories-container">
            <!-- Income Categories -->
            <section class="category-section">
                <h2 class="section-title income">📈 Income Categories</h2>
                <div class="categories-grid" v-if="categoryStore.incomeCategories.length">
                    <div
                        v-for="category in categoryStore.incomeCategories"
                        :key="category.id"
                        class="category-card income"
                    >
                        <span class="category-icon">{{ category.icon || '📁' }}</span>
                        <span class="category-name">{{ category.name }}</span>
                        <div class="category-actions">
                            <button class="icon-btn" @click="openModal(category)">✏️</button>
                            <button class="icon-btn delete" @click="handleDelete(category.id)">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="empty-state small" v-else>
                    <p>No income categories yet</p>
                </div>
            </section>

            <!-- Expense Categories -->
            <section class="category-section">
                <h2 class="section-title expense">📉 Expense Categories</h2>
                <div class="categories-grid" v-if="categoryStore.expenseCategories.length">
                    <div
                        v-for="category in categoryStore.expenseCategories"
                        :key="category.id"
                        class="category-card expense"
                    >
                        <span class="category-icon">{{ category.icon || '📁' }}</span>
                        <span class="category-name">{{ category.name }}</span>
                        <div class="category-actions">
                            <button class="icon-btn" @click="openModal(category)">✏️</button>
                            <button class="icon-btn delete" @click="handleDelete(category.id)">🗑️</button>
                        </div>
                    </div>
                </div>
                <div class="empty-state small" v-else>
                    <p>No expense categories yet</p>
                </div>
            </section>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" v-if="showModal" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingCategory ? 'Edit Category' : 'New Category' }}</h2>
                    <button class="close-btn" @click="closeModal">×</button>
                </div>
                <form @submit.prevent="handleSubmit" class="modal-form">
                    <div class="form-group">
                        <label for="name">Category Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g., Groceries, Salary"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" v-model="form.type" required>
                            <option value="INCOME">Income</option>
                            <option value="EXPENSE">Expense</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="icon">Icon</label>
                        <div class="icon-picker">
                            <button
                                v-for="icon in icons"
                                :key="icon"
                                type="button"
                                class="icon-option"
                                :class="{ selected: form.icon === icon }"
                                @click="form.icon = icon"
                            >
                                {{ icon }}
                            </button>
                        </div>
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
import { ref, reactive } from 'vue';
import { useCategoryStore } from '@/stores/category';

const categoryStore = useCategoryStore();

const showModal = ref(false);
const loading = ref(false);
const error = ref('');
const editingCategory = ref(null);

const icons = ['💼', '💰', '🏠', '🚗', '🍔', '🛒', '💡', '📱', '🎮', '✈️', '🏥', '📚', '🎁', '💳', '📈', '📉'];

const form = reactive({
    name: '',
    type: 'EXPENSE',
    icon: '📁',
});

function openModal(category = null) {
    editingCategory.value = category;
    if (category) {
        form.name = category.name;
        form.type = category.type;
        form.icon = category.icon || '📁';
    } else {
        form.name = '';
        form.type = 'EXPENSE';
        form.icon = '📁';
    }
    error.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingCategory.value = null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        if (editingCategory.value) {
            await categoryStore.updateCategory(editingCategory.value.id, {
                name: form.name,
                type: form.type,
                icon: form.icon,
            });
        } else {
            await categoryStore.createCategory({
                name: form.name,
                type: form.type,
                icon: form.icon,
            });
        }
        closeModal();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to save category';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!confirm('Are you sure you want to delete this category?')) return;

    try {
        await categoryStore.deleteCategory(id);
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to delete category');
    }
}

// Fetch on mount
categoryStore.fetchCategories();
</script>
