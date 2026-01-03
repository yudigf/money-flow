# MoneyFlow - Personal Finance Application

A Laravel-based personal finance management API for tracking income, expenses, and wallet balances.

## Tech Stack

- **Framework:** Laravel 12.x
- **Database:** MySQL
- **Language:** PHP 8.2+
- **Authentication:** Laravel Sanctum

## Features

- 💰 **Wallet Management** - Create and manage multiple wallets (Cash, Bank, E-Wallet)
- 📊 **Category System** - Organize transactions with income/expense categories
- 💸 **Transaction Tracking** - Record income and expenses with automatic balance updates
- 🔒 **Atomic Transactions** - Database transactions ensure data integrity
- 🔐 **API Authentication** - Secure API endpoints with Sanctum tokens

## Installation

### Prerequisites

- PHP 8.2+
- Composer
- MySQL
- Node.js (optional, for frontend assets)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yudigf/money-flow.git
   cd money-flow
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database** in `.env`
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=money_flow
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

## API Endpoints

All endpoints require authentication via `Authorization: Bearer {token}` header.

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login and get token |
| POST | `/api/logout` | Logout (revoke token) |

### Wallets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/wallets` | List all wallets |
| POST | `/api/wallets` | Create wallet |
| GET | `/api/wallets/{id}` | Get wallet details |
| PUT | `/api/wallets/{id}` | Update wallet |
| DELETE | `/api/wallets/{id}` | Delete wallet |

### Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/categories` | List all categories |
| POST | `/api/categories` | Create category |
| GET | `/api/categories/{id}` | Get category details |
| PUT | `/api/categories/{id}` | Update category |
| DELETE | `/api/categories/{id}` | Delete category |

### Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/transactions` | List all transactions |
| POST | `/api/transactions` | Create transaction |
| GET | `/api/transactions/{id}` | Get transaction details |
| DELETE | `/api/transactions/{id}` | Delete transaction |

## Transaction Example

### Create Income Transaction
```json
POST /api/transactions
{
    "wallet_id": 1,
    "category_id": 1,
    "amount": 5000000.00,
    "type": "INCOME",
    "date": "2026-01-03",
    "description": "Monthly salary"
}
```

### Create Expense Transaction
```json
POST /api/transactions
{
    "wallet_id": 1,
    "category_id": 6,
    "amount": 150000.00,
    "type": "EXPENSE",
    "date": "2026-01-03",
    "description": "Lunch with colleagues"
}
```

## Database Schema

### Tables

- `users` - User accounts
- `wallets` - User wallets with balance (DECIMAL 15,2)
- `categories` - Transaction categories (INCOME/EXPENSE)
- `transactions` - Financial transactions

### Key Features

- **DECIMAL(15,2)** for monetary values - prevents floating-point precision issues
- **Foreign keys** with cascade delete for data integrity
- **Database transactions** for atomic balance updates

## Default Seed Data

### Demo User
- Email: `demo@moneyflow.test`
- Password: `password`

### Default Wallets
- Cash
- Bank Account
- E-Wallet

### Default Categories
**Income:** Salary, Freelance, Investment, Gift, Other Income

**Expense:** Food & Dining, Transportation, Shopping, Bills & Utilities, Entertainment, Health, Education, Travel, Other Expense

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
