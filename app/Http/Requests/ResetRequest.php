<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hash_equals((string) config('party.host_secret'), (string) $this->route('secret'));
    }

    protected function failedAuthorization(): never
    {
        throw new NotFoundHttpException;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'string'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! hash_equals((string) config('party.host_pin'), (string) $this->input('pin'))) {
                    $validator->errors()->add('pin', 'Incorrect PIN.');
                }
            },
        ];
    }
}
