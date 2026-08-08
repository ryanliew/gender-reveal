<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreVoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30'],
            'avatar' => ['nullable', 'string', Rule::in(Guest::AVATARS)],
            'photo' => ['nullable', File::image()->max(5 * 1024)],
            'gender' => ['required', Rule::enum(Gender::class)],
        ];
    }

    /**
     * A vote needs a face: a preset, an uploaded photo, or a photo this
     * device uploaded previously (updates may leave the avatar untouched).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('avatar') || $this->hasFile('photo')) {
                    return;
                }

                $hasExistingPhoto = Guest::query()
                    ->where('device_uuid', $this->cookie('device_uuid'))
                    ->whereNotNull('photo_path')
                    ->exists();

                if (! $hasExistingPhoto) {
                    $validator->errors()->add('avatar', 'Pick a character or add a photo.');
                }
            },
        ];
    }
}
