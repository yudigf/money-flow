<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'due_date' => ['required', 'date'],
            'frequency' => ['required', 'in:WEEKLY,MONTHLY,YEARLY,ONCE'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'wallet_id' => ['nullable', 'integer', 'exists:wallets,id'],
            'is_active' => ['boolean'],
            'auto_pay' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
