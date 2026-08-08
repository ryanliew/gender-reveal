<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $device_uuid
 * @property string $name
 * @property ?string $avatar
 * @property ?string $photo_path
 * @property Gender $gender
 */
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    public const array AVATARS = [
        'fox', 'bear', 'owl', 'deer', 'bunny', 'chick', 'koala', 'frog',
        'lion', 'panda', 'pig', 'unicorn', 'tiger', 'octopus', 'duck', 'turtle',
    ];

    protected $fillable = ['device_uuid', 'name', 'avatar', 'photo_path', 'gender'];

    /**
     * @return array{gender: class-string<Gender>}
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
        ];
    }

    /**
     * The guest data safe to broadcast and render publicly (excludes device_uuid).
     *
     * @return array{id: int, name: string, avatar: ?string, photoUrl: ?string, gender: string}
     */
    public function publicPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'photoUrl' => $this->photo_path !== null
                ? Storage::disk('public')->url($this->photo_path)
                : null,
            'gender' => $this->gender->value,
        ];
    }
}
