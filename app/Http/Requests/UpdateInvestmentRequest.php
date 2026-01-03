<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentRequest extends FormRequest
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
            'symbol' => ['nullable', 'string', 'max:20'],
            'type' => ['sometimes', 'in:STOCK,CRYPTO,MUTUAL_FUND,GOLD,BOND,PROPERTY,OTHER'],
            'quantity' => ['sometimes', 'numeric', 'min:0.00000001'],
            'buy_price' => ['sometimes', 'numeric', 'min:0.01'],
            'current_price' => ['sometimes', 'numeric', 'min:0.01'],
            'buy_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
