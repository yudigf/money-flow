<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'due_date' => ['sometimes', 'date'],
            'frequency' => ['sometimes', 'in:WEEKLY,MONTHLY,YEARLY,ONCE'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'is_active' => ['sometimes', 'boolean'],
            'auto_pay' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
