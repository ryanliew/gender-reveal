<?php

namespace App\Models;

use App\Enums\Gender;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $device_uuid
 * @property string $name
 * @property string $avatar
 * @property Gender $gender
 */
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    public const array AVATARS = ['fox', 'bear', 'owl', 'deer', 'bunny', 'chick', 'koala', 'frog'];

    protected $fillable = ['device_uuid', 'name', 'avatar', 'gender'];

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
     * @return array{id: int, name: string, avatar: string, gender: string}
     */
    public function publicPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'gender' => $this->gender->value,
        ];
    }
}
