<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'avatar' => ['required', 'string', Rule::in(Guest::AVATARS)],
            'gender' => ['required', Rule::enum(Gender::class)],
        ];
    }
}
