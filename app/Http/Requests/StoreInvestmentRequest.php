<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentRequest extends FormRequest
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
            'symbol' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:STOCK,CRYPTO,MUTUAL_FUND,GOLD,BOND,PROPERTY,OTHER'],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'buy_price' => ['required', 'numeric', 'min:0.01'],
            'current_price' => ['nullable', 'numeric', 'min:0.01'],
            'buy_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
