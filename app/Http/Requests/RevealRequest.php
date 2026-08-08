<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Validation\Rule;

class RevealRequest extends ResetRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'gender' => ['required', Rule::enum(Gender::class)],
        ];
    }

    public function gender(): Gender
    {
        return Gender::from($this->string('gender')->value());
    }
}
