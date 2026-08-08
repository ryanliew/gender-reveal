# Gender Reveal Party System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Single-event gender reveal app: guests scan a TV QR code, vote Boy/Girl with name + preset avatar; votes float onto the TV live; host reveals the gender from a PIN-protected control page and the TV bursts confetti and re-themes.

**Architecture:** Laravel 13 + Inertia v3 + Vue 3 SPA. Votes in a `guests` SQLite table keyed by a device-UUID cookie; reveal state in one cache key. Laravel Reverb broadcasts `VoteUpdated` / `GenderRevealed` / `PartyReset` on public channel `party`; all three screens hydrate from Inertia props and stay live via `@laravel/echo-vue`.

**Tech Stack:** laravel/reverb, laravel-echo + pusher-js + @laravel/echo-vue, canvas-confetti, qrcode (npm), Wayfinder route helpers, Pest 4, Tailwind v4.

**Spec:** `docs/superpowers/specs/2026-08-08-gender-reveal-design.md`

## Global Constraints

- Gender enum values: `boy` / `girl` (PHP enum keys `Boy` / `Girl`).
- Public broadcast channel name: `party`.
- Cache key for reveal state: `party.revealed_gender`.
- Device cookie name: `device_uuid`, lifetime 1 year.
- Host config: `config/party.php` reading `PARTY_HOST_SECRET`, `PARTY_HOST_PIN` from `.env`.
- Avatar preset keys: `fox, bear, owl, deer, bunny, chick, koala, frog`.
- Broadcast events use `ShouldBroadcastNow` (no queue latency; party is realtime).
- Never broadcast `device_uuid` (private to each device).
- Frontend uses Wayfinder-generated helpers, never hardcoded URLs.
- After every PHP change run `vendor/bin/pint --format agent` before committing.
- Tests: Pest, `RefreshDatabase` enabled in `tests/Pest.php`.
- Verification command: `composer test` (runs pint --test, phpstan, pest).

---

### Task 1: Project plumbing — git, broadcasting install, npm deps, test bootstrap

**Files:**
- Modify: `package.json`, `composer.json` (via installers), `.env`, `.env.example`
- Modify: `tests/Pest.php` (enable RefreshDatabase)
- Create: `config/party.php`

**Interfaces:**
- Produces: working Reverb/Echo scaffolding (`resources/js/echo.js` or equivalent env vars), `config('party.host_secret')`, `config('party.host_pin')`, npm packages `@laravel/echo-vue`, `canvas-confetti`, `qrcode` available.

- [ ] **Step 1: Initialize git** (repo is not yet a git repo; superpowers workflow needs commits)

```bash
git init && git add -A && git commit -m "chore: baseline blank starter kit + specs"
```

- [ ] **Step 2: Install broadcasting + Reverb**

```bash
php artisan install:broadcasting --reverb --no-interaction
```

Expected: composer gets `laravel/reverb`; `.env` gains `REVERB_*` + `VITE_REVERB_*`; `BROADCAST_CONNECTION=reverb`; echo bootstrap added to JS entry.

- [ ] **Step 3: Install frontend deps**

```bash
npm install laravel-echo pusher-js @laravel/echo-vue canvas-confetti qrcode
npm install -D @types/canvas-confetti @types/qrcode
```

(Skip any already added by the installer.)

- [ ] **Step 4: Create `config/party.php`**

```php
<?php

return [
    'host_secret' => env('PARTY_HOST_SECRET', 'change-me-secret'),
    'host_pin' => env('PARTY_HOST_PIN', '0000'),
];
```

Add to `.env` and `.env.example`:

```
PARTY_HOST_SECRET=let-the-party-begin
PARTY_HOST_PIN=1234
```

- [ ] **Step 5: Enable RefreshDatabase in `tests/Pest.php`** (uncomment/add `->use(RefreshDatabase::class)` for Feature tests per the file's existing commented hint).

- [ ] **Step 6: Verify baseline still green**

Run: `composer test`
Expected: PASS (existing example tests only).

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "chore: install reverb/echo, party config, test bootstrap"
```

---

### Task 2: Domain — Gender enum, Guest model + migration + factory

**Files:**
- Create: `app/Enums/Gender.php`
- Create: `app/Models/Guest.php`, `database/migrations/*_create_guests_table.php`, `database/factories/GuestFactory.php`
- Test: `tests/Unit/GenderTest.php` (enum), model exercised via feature tests later

**Interfaces:**
- Produces: `App\Enums\Gender` (`Gender::Boy->value === 'boy'`), `App\Models\Guest` with `device_uuid, name, avatar, gender` (gender cast to `Gender`), `Guest::AVATARS` (list of 8 preset keys), `Guest->publicPayload(): array{id:int,name:string,avatar:string,gender:string}`.

- [ ] **Step 1: Write failing enum/model test**

```php
<?php

use App\Enums\Gender;

test('gender enum exposes boy and girl values', function () {
    expect(Gender::Boy->value)->toBe('boy')
        ->and(Gender::Girl->value)->toBe('girl')
        ->and(Gender::values())->toBe(['boy', 'girl']);
});
```

- [ ] **Step 2: Run it — expect FAIL** (`class App\Enums\Gender not found`): `php artisan test --compact --filter=GenderTest`

- [ ] **Step 3: Implement enum**

```php
<?php

namespace App\Enums;

enum Gender: string
{
    case Boy = 'boy';
    case Girl = 'girl';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 4: Create model + migration + factory**

```bash
php artisan make:model Guest -mf --no-interaction
```

Migration:

```php
Schema::create('guests', function (Blueprint $table) {
    $table->id();
    $table->uuid('device_uuid')->unique();
    $table->string('name');
    $table->string('avatar');
    $table->string('gender');
    $table->timestamps();
});
```

Model:

```php
<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\GuestFactory> */
    use HasFactory;

    public const array AVATARS = ['fox', 'bear', 'owl', 'deer', 'bunny', 'chick', 'koala', 'frog'];

    protected $fillable = ['device_uuid', 'name', 'avatar', 'gender'];

    protected function casts(): array
    {
        return ['gender' => Gender::class];
    }

    /** @return array{id: int, name: string, avatar: string, gender: string} */
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
```

Factory: `device_uuid => fake()->uuid()`, `name => fake()->firstName()`, `avatar => fake()->randomElement(Guest::AVATARS)`, `gender => fake()->randomElement(Gender::cases())`.

- [ ] **Step 5: Run migration + tests**: `php artisan migrate --no-interaction && php artisan test --compact --filter=GenderTest` → PASS

- [ ] **Step 6: Pint + commit**: `vendor/bin/pint --format agent && git add -A && git commit -m "feat: gender enum and guest model"`

---

### Task 3: Party state + broadcast events

**Files:**
- Create: `app/PartyState.php` (via `php artisan make:class PartyState`)
- Create: `app/Events/VoteUpdated.php`, `app/Events/GenderRevealed.php`, `app/Events/PartyReset.php` (via `php artisan make:event`)
- Test: `tests/Feature/PartyStateTest.php`

**Interfaces:**
- Produces: `App\PartyState` with static `revealedGender(): ?Gender`, `reveal(Gender): void`, `reset(): void` (reset also deletes guests). Events all broadcast on public `Channel('party')`, implement `ShouldBroadcastNow`; `VoteUpdated` has `public array $guest` (publicPayload), `GenderRevealed` has `public string $gender`, `PartyReset` has no payload.

- [ ] **Step 1: Failing test**

```php
<?php

use App\Enums\Gender;
use App\Models\Guest;
use App\PartyState;

test('reveal stores gender and reset clears everything', function () {
    Guest::factory()->count(3)->create();

    expect(PartyState::revealedGender())->toBeNull();

    PartyState::reveal(Gender::Girl);
    expect(PartyState::revealedGender())->toBe(Gender::Girl);

    PartyState::reset();
    expect(PartyState::revealedGender())->toBeNull()
        ->and(Guest::count())->toBe(0);
});
```

- [ ] **Step 2: Run — FAIL** (`PartyState not found`): `php artisan test --compact --filter=PartyStateTest`

- [ ] **Step 3: Implement**

```php
<?php

namespace App;

use App\Enums\Gender;
use App\Models\Guest;
use Illuminate\Support\Facades\Cache;

class PartyState
{
    private const string REVEAL_KEY = 'party.revealed_gender';

    public static function revealedGender(): ?Gender
    {
        $value = Cache::get(self::REVEAL_KEY);

        return $value === null ? null : Gender::from($value);
    }

    public static function reveal(Gender $gender): void
    {
        Cache::forever(self::REVEAL_KEY, $gender->value);
    }

    public static function reset(): void
    {
        Cache::forget(self::REVEAL_KEY);
        Guest::query()->delete();
    }
}
```

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Create the three events** (`php artisan make:event VoteUpdated` etc.), each shaped like:

```php
<?php

namespace App\Events;

use App\Models\Guest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class VoteUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @var array{id: int, name: string, avatar: string, gender: string} */
    public array $guest;

    public function __construct(Guest $guest)
    {
        $this->guest = $guest->publicPayload();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('party');
    }
}
```

`GenderRevealed`: `public string $gender;` constructor `(Gender $gender)` sets `$this->gender = $gender->value;`. `PartyReset`: no constructor properties. Both broadcast on `new Channel('party')`.

- [ ] **Step 6: Pint, full `composer test`, commit** `feat: party state and broadcast events`

---

### Task 4: Vote flow — join page + vote endpoint

**Files:**
- Create: `app/Http/Controllers/VoteController.php`, `app/Http/Requests/StoreVoteRequest.php`
- Modify: `routes/web.php` (replace Welcome route)
- Delete: `resources/js/pages/Welcome.vue` usage (page replaced in Task 7; keep file until then)
- Test: `tests/Feature/VoteTest.php`

**Interfaces:**
- Consumes: `Guest`, `PartyState`, `VoteUpdated`.
- Produces: named routes `home` (GET `/`, Inertia page `Join`), `vote.store` (POST `/vote`). Join props: `guest: ?array` (publicPayload), `avatars: string[]`, `revealedGender: ?string`. POST sets/refreshes `device_uuid` cookie (1 year), upserts by cookie UUID, dispatches `VoteUpdated`, redirects back; 403 when revealed.

- [ ] **Step 1: Failing tests**

```php
<?php

use App\Enums\Gender;
use App\Events\VoteUpdated;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

test('join page renders with no vote', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Join')
            ->where('guest', null)
            ->where('avatars', \App\Models\Guest::AVATARS)
            ->where('revealedGender', null));
});

test('posting a vote creates a guest and broadcasts', function () {
    Event::fake([VoteUpdated::class]);

    $this->post('/vote', ['name' => 'Aunty May', 'avatar' => 'fox', 'gender' => 'girl'])
        ->assertRedirect()
        ->assertCookie('device_uuid');

    expect(Guest::count())->toBe(1)
        ->and(Guest::first()->gender)->toBe(Gender::Girl);

    Event::assertDispatched(VoteUpdated::class);
});

test('same device updates its vote instead of duplicating', function () {
    Event::fake([VoteUpdated::class]);
    $uuid = (string) Str::uuid();
    Guest::factory()->create(['device_uuid' => $uuid, 'gender' => 'boy']);

    $this->withUnencryptedCookie('device_uuid', $uuid)
        ->post('/vote', ['name' => 'Uncle Tan', 'avatar' => 'bear', 'gender' => 'girl'])
        ->assertRedirect();

    expect(Guest::count())->toBe(1)
        ->and(Guest::first()->name)->toBe('Uncle Tan');
});

test('join page shows existing vote for known device', function () {
    $guest = Guest::factory()->create();

    $this->withUnencryptedCookie('device_uuid', $guest->device_uuid)
        ->get('/')
        ->assertInertia(fn ($page) => $page->where('guest.id', $guest->id));
});

test('invalid payloads are rejected', function (array $payload) {
    $this->post('/vote', $payload)->assertSessionHasErrors();
})->with([
    'missing name' => [['avatar' => 'fox', 'gender' => 'boy']],
    'unknown avatar' => [['name' => 'A', 'avatar' => 'dragon', 'gender' => 'boy']],
    'unknown gender' => [['name' => 'A', 'avatar' => 'fox', 'gender' => 'unsure']],
]);

test('voting after reveal is forbidden', function () {
    PartyState::reveal(Gender::Boy);

    $this->post('/vote', ['name' => 'Late Guy', 'avatar' => 'owl', 'gender' => 'boy'])
        ->assertForbidden();
});
```

Note: cookie encryption — device cookie must be excluded from encryption (`withUnencryptedCookie` in tests, and controller reads raw). Exclude it in `bootstrap/app.php` via `$middleware->encryptCookies(except: ['device_uuid'])`.

- [ ] **Step 2: Run — FAIL** (route missing): `php artisan test --compact --filter=VoteTest`

- [ ] **Step 3: Implement**

`StoreVoteRequest` rules:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:30'],
        'avatar' => ['required', 'string', Rule::in(Guest::AVATARS)],
        'gender' => ['required', Rule::enum(Gender::class)],
    ];
}
```

`VoteController`:

```php
<?php

namespace App\Http\Controllers;

use App\Events\VoteUpdated;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    public function create(Request $request): Response
    {
        $guest = Guest::query()->where('device_uuid', $request->cookie('device_uuid'))->first();

        return Inertia::render('Join', [
            'guest' => $guest?->publicPayload(),
            'avatars' => Guest::AVATARS,
            'revealedGender' => PartyState::revealedGender()?->value,
        ]);
    }

    public function store(StoreVoteRequest $request): RedirectResponse
    {
        abort_if(PartyState::revealedGender() !== null, 403, 'The gender has already been revealed!');

        $deviceUuid = $request->cookie('device_uuid') ?? (string) Str::uuid();

        $guest = Guest::query()->updateOrCreate(
            ['device_uuid' => $deviceUuid],
            $request->validated(),
        );

        VoteUpdated::dispatch($guest);

        return back()->withCookie(cookie('device_uuid', $deviceUuid, 60 * 24 * 365));
    }
}
```

Routes (`routes/web.php`) — replace the Welcome route:

```php
use App\Http\Controllers\VoteController;

Route::get('/', [VoteController::class, 'create'])->name('home');
Route::post('/vote', [VoteController::class, 'store'])->name('vote.store');
```

`bootstrap/app.php` middleware callback: `$middleware->encryptCookies(except: ['device_uuid']);`

Temporary: keep a minimal `resources/js/pages/Join.vue` stub (`<template><div>join</div></template>`) so the Inertia response resolves; the real page comes in Task 7.

- [ ] **Step 4: Run — PASS**, then **Step 5: Pint + `composer test` + commit** `feat: vote endpoint and join page backend`

---

### Task 5: TV + host backend

**Files:**
- Create: `app/Http/Controllers/TvController.php`, `app/Http/Controllers/HostController.php`, `app/Http/Requests/RevealRequest.php`, `app/Http/Requests/ResetRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TvTest.php`, `tests/Feature/HostTest.php`

**Interfaces:**
- Consumes: `PartyState`, events, `Guest`.
- Produces: routes `tv` (GET `/tv` → page `Tv`, props `guests: array[]` of publicPayload, `revealedGender: ?string`, `joinUrl: string` absolute), `host.show` (GET `/host/{secret}` → page `Host`, prop `secret: string`), `host.reveal` (POST `/host/{secret}/reveal`, body `{pin, gender}`), `host.reset` (POST `/host/{secret}/reset`, body `{pin}`).

- [ ] **Step 1: Failing tests**

```php
<?php
// TvTest.php

use App\Models\Guest;

test('tv page lists guests and join url', function () {
    Guest::factory()->count(2)->create();

    $this->get('/tv')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Tv')
            ->has('guests', 2)
            ->where('revealedGender', null)
            ->where('joinUrl', route('home')));
});

test('tv guest payload does not leak device uuid', function () {
    Guest::factory()->create();

    $this->get('/tv')->assertInertia(fn ($page) => $page->missing('guests.0.device_uuid'));
});
```

```php
<?php
// HostTest.php

use App\Enums\Gender;
use App\Events\GenderRevealed;
use App\Events\PartyReset;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['party.host_secret' => 'sekret', 'party.host_pin' => '1234']);
});

test('host page 404s on wrong secret', function () {
    $this->get('/host/nope')->assertNotFound();
    $this->get('/host/sekret')->assertOk();
});

test('reveal requires correct pin', function () {
    Event::fake([GenderRevealed::class]);

    $this->post('/host/sekret/reveal', ['pin' => '9999', 'gender' => 'boy'])
        ->assertSessionHasErrors('pin');

    $this->post('/host/sekret/reveal', ['pin' => '1234', 'gender' => 'boy'])
        ->assertRedirect();

    expect(PartyState::revealedGender())->toBe(Gender::Boy);
    Event::assertDispatched(GenderRevealed::class);
});

test('reset clears party and broadcasts', function () {
    Event::fake([PartyReset::class]);
    Guest::factory()->count(2)->create();
    PartyState::reveal(Gender::Girl);

    $this->post('/host/sekret/reset', ['pin' => '1234'])->assertRedirect();

    expect(Guest::count())->toBe(0)
        ->and(PartyState::revealedGender())->toBeNull();
    Event::assertDispatched(PartyReset::class);
});
```

- [ ] **Step 2: Run — FAIL**, then **Step 3: Implement**

`TvController` (invokable):

```php
public function __invoke(): Response
{
    return Inertia::render('Tv', [
        'guests' => Guest::query()->latest()->get()->map->publicPayload()->all(),
        'revealedGender' => PartyState::revealedGender()?->value,
        'joinUrl' => route('home'),
    ]);
}
```

`RevealRequest` rules: `'pin' => ['required', 'string'], 'gender' => ['required', Rule::enum(Gender::class)]` plus an `after` hook adding error `pin => 'Incorrect PIN.'` when `! hash_equals(config('party.host_pin'), $this->string('pin')->value())`.

`HostController`:

```php
class HostController extends Controller
{
    public function show(string $secret): Response
    {
        $this->authorizeSecret($secret);

        return Inertia::render('Host', ['secret' => $secret]);
    }

    public function reveal(RevealRequest $request, string $secret): RedirectResponse
    {
        $this->authorizeSecret($secret);
        PartyState::reveal($request->gender());
        GenderRevealed::dispatch($request->gender());

        return back();
    }

    public function reset(ResetRequest $request, string $secret): RedirectResponse
    {
        $this->authorizeSecret($secret);
        PartyState::reset();
        PartyReset::dispatch();

        return back();
    }

    private function authorizeSecret(string $secret): void
    {
        abort_unless(hash_equals((string) config('party.host_secret'), $secret), 404);
    }
}
```

`ResetRequest`: pin rule + same after-hook (extract a tiny shared trait or duplicate the 3 lines — duplication is fine here). `RevealRequest::gender(): Gender` returns `Gender::from($this->validated('gender'))`.

Routes:

```php
Route::get('/tv', TvController::class)->name('tv');
Route::get('/host/{secret}', [HostController::class, 'show'])->name('host.show');
Route::post('/host/{secret}/reveal', [HostController::class, 'reveal'])->name('host.reveal');
Route::post('/host/{secret}/reset', [HostController::class, 'reset'])->name('host.reset');
```

Add `Tv.vue` and `Host.vue` stubs like Join's.

- [ ] **Step 4: Run — PASS**, **Step 5: Pint + `composer test` + commit** `feat: tv and host endpoints`

---

### Task 6: Frontend foundation — Echo config, types, avatar catalog, theme

**Files:**
- Modify: `resources/js/app.ts` (configureEcho), `resources/css/app.css` (palette, fonts, float keyframes)
- Create: `resources/js/types/party.ts`, `resources/js/lib/avatars.ts`
- Test: `npm run types:check` + `npm run build`

**Interfaces:**
- Produces: `GuestData {id,name,avatar,gender}`, `Gender = 'boy' | 'girl'` types; `AVATARS: Record<string, {emoji: string, bg: string}>` for the 8 preset keys; CSS custom properties/theme utilities `--color-cream #FDF2F0`-family palette; `@keyframes float-drift` used by TV chips; Echo configured with `configureEcho({broadcaster: 'reverb'})` before `createInertiaApp`.

- [ ] **Step 1: Types**

```ts
// resources/js/types/party.ts
export type Gender = 'boy' | 'girl';

export interface GuestData {
    id: number;
    name: string;
    avatar: string;
    gender: Gender;
}
```

- [ ] **Step 2: Avatar catalog**

```ts
// resources/js/lib/avatars.ts
export const AVATARS: Record<string, { emoji: string; bg: string }> = {
    fox: { emoji: '🦊', bg: '#FDE4D3' },
    bear: { emoji: '🐻', bg: '#EAD9C9' },
    owl: { emoji: '🦉', bg: '#DCE6F2' },
    deer: { emoji: '🦌', bg: '#F5E3CF' },
    bunny: { emoji: '🐰', bg: '#F9E0E6' },
    chick: { emoji: '🐥', bg: '#FBF0CC' },
    koala: { emoji: '🐨', bg: '#E3E3E9' },
    frog: { emoji: '🐸', bg: '#DDEEDC' },
};
```

- [ ] **Step 3: app.ts** — add near top:

```ts
import { configureEcho } from '@laravel/echo-vue';

configureEcho({ broadcaster: 'reverb' });
```

(Remove/keep installer-generated `echo.js` import consistently — one source of Echo config.)

- [ ] **Step 4: Theme in `resources/css/app.css`** (Tailwind v4 `@theme`):

```css
@theme {
    --color-cream: #fdf6f3;
    --color-blush: #fbe9e7;
    --color-boy: #b8d4e8;
    --color-boy-deep: #5b8db8;
    --color-girl: #f6c6d0;
    --color-girl-deep: #d4708b;
    --color-ink: #4a3f3a;
    --font-hand: 'Caveat', cursive;
}
```

Import a script font (Caveat or similar) via `@fontsource` npm or Google Fonts `<link>` in `app.blade.php`; hand-drawn aesthetic per spec. Add:

```css
@keyframes float-drift {
    0%, 100% { transform: translate(0, 0) rotate(-2deg); }
    25% { transform: translate(12px, -14px) rotate(2deg); }
    50% { transform: translate(-8px, 10px) rotate(-1deg); }
    75% { transform: translate(10px, 6px) rotate(3deg); }
}
```

- [ ] **Step 5: Verify** `npm run types:check && npm run build` → PASS. Commit `feat: frontend foundation for party screens`.

---

### Task 7: Join page (`resources/js/pages/Join.vue`)

**Files:**
- Modify: `resources/js/pages/Join.vue` (replace stub), delete `resources/js/pages/Welcome.vue`

**Interfaces:**
- Consumes: props `{guest: GuestData | null, avatars: string[], revealedGender: Gender | null}`, `useForm`, Wayfinder `store` from `@/actions/App/Http/Controllers/VoteController`, `useEchoPublic` on `party` for `GenderRevealed`, `AVATARS`.

- [ ] **Step 1: Implement the page.** Structure (single root div, cream background, hand-drawn styling):

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/VoteController';
import { AVATARS } from '@/lib/avatars';
import type { Gender, GuestData } from '@/types/party';

const props = defineProps<{
    guest: GuestData | null;
    avatars: string[];
    revealedGender: Gender | null;
}>();

const revealed = ref<Gender | null>(props.revealedGender);
const saved = ref(false);

useEchoPublic<{ gender: Gender }>('party', 'GenderRevealed', (e) => (revealed.value = e.gender));

const form = useForm({
    name: props.guest?.name ?? '',
    avatar: props.guest?.avatar ?? 'fox',
    gender: props.guest?.gender ?? ('' as Gender | ''),
});

function submit() {
    form.submit(store(), { preserveScroll: true, onSuccess: () => (saved.value = true) });
}
</script>
```

Template states:
1. `revealed` non-null → celebratory card: big "It's a Boy! 💙" / "It's a Girl! 💖" themed to gender; no form.
2. Otherwise the form card: heading ("Cast your guess!" + script subheading), name `<input>`, avatar grid (buttons showing `AVATARS[key].emoji` on `bg`, ring on selected), two large gender buttons (Boy: `--color-boy` w/ 👶💙 style, Girl: `--color-girl`), submit button disabled while `form.processing` or fields empty; validation errors under fields; `saved` → "Your vote is in — you can still change it!" banner. If `props.guest` existed, button label "Update my vote" instead of "Join the party".

Mobile-first: max-w-md centered column, generous tap targets (min-h-14 buttons).

- [ ] **Step 2: Verify** `npm run types:check && npm run lint && npm run build` → PASS. Manual smoke: `composer test` still green (Inertia page exists).

- [ ] **Step 3: Commit** `feat: join screen`

---

### Task 8: TV page (`resources/js/pages/Tv.vue`)

**Files:**
- Modify: `resources/js/pages/Tv.vue` (replace stub)

**Interfaces:**
- Consumes: props `{guests: GuestData[], revealedGender: Gender | null, joinUrl: string}`; `useEchoPublic` for `VoteUpdated {guest: GuestData}`, `GenderRevealed {gender}`, `PartyReset {}`; `qrcode`'s `toDataURL`; `canvas-confetti`.

- [ ] **Step 1: Implement.** Key logic:

```ts
const guests = ref<GuestData[]>([...props.guests]);
const revealed = ref<Gender | null>(props.revealedGender);
const qr = ref('');

onMounted(async () => {
    qr.value = await QRCode.toDataURL(props.joinUrl, { margin: 1, width: 480 });
    if (revealed.value) fireConfetti(revealed.value); // rejoining an already-revealed party still celebrates
});

useEchoPublic<{ guest: GuestData }>('party', 'VoteUpdated', (e) => {
    const i = guests.value.findIndex((g) => g.id === e.guest.id);
    if (i === -1) guests.value.push(e.guest);
    else guests.value[i] = e.guest;
});
useEchoPublic<{ gender: Gender }>('party', 'GenderRevealed', (e) => {
    revealed.value = e.gender;
    fireConfetti(e.gender);
});
useEchoPublic('party', 'PartyReset', () => {
    guests.value = [];
    revealed.value = null;
});
```

`fireConfetti(gender)`: over ~3.5s, interval bursts of `confetti({particleCount: 80, spread: 100, origin: {x: Math.random(), y: Math.random() * 0.4}, colors: gender === 'boy' ? ['#5b8db8', '#b8d4e8', '#ffffff'] : ['#d4708b', '#f6c6d0', '#ffffff']})`.

Chip float: each chip gets deterministic pseudo-random inline style from its id:

```ts
function chipStyle(g: GuestData) {
    const seed = (g.id * 9301 + 49297) % 233280 / 233280;
    const seed2 = (g.id * 233280 + 9301) % 49297 / 49297;
    return {
        top: `${10 + seed * 70}%`,
        left: `${8 + seed2 * 64}%`,
        animation: `float-drift ${6 + seed * 6}s ease-in-out ${-seed2 * 6}s infinite`,
    };
}
```

Layout, pre-reveal: full-screen grid `grid-cols-[1fr_auto_1fr]` on cream; left pane soft-boy-blue rounded panel titled "Team Boy 💙", right pane girl-pink "Team Girl 💖"; chips (`absolute`, avatar emoji circle + name label, white pill, soft shadow) positioned within their pane (`relative`). Center column: script-font site title, "Scan to cast your vote" and the QR `<img>` in a white rounded card, plus live tally hearts (count per side).
Post-reveal: whole background transitions (`transition-colors duration-1000`) to winning palette; huge centered script "It's a Boy!"/"It's a Girl!"; QR card hides; chips remain floating; both panes tint to the winning color.

- [ ] **Step 2: Verify** `npm run types:check && npm run lint && npm run build` → PASS.

- [ ] **Step 3: Commit** `feat: tv display screen`

---

### Task 9: Host page (`resources/js/pages/Host.vue`)

**Files:**
- Modify: `resources/js/pages/Host.vue` (replace stub)

**Interfaces:**
- Consumes: prop `{secret: string}`; Wayfinder `reveal`, `reset` from `@/actions/App/Http/Controllers/HostController` (both take the secret param); `useForm`.

- [ ] **Step 1: Implement.** Two `useForm`s:

```ts
const revealForm = useForm({ pin: '', gender: '' as Gender | '' });
const resetForm = useForm({ pin: '' });
const done = ref(false);

function submitReveal() {
    if (!confirm(`Reveal "${revealForm.gender}" on the TV now?`)) return;
    revealForm.submit(reveal(props.secret), { onSuccess: () => (done.value = true) });
}
function submitReset() {
    if (!confirm('Clear all votes and un-reveal?')) return;
    resetForm.submit(reset(props.secret), { onSuccess: () => { done.value = false; revealForm.reset(); } });
}
```

Template: centered card "Host Controls 🎉"; PIN input (`inputmode="numeric"`, shared v-model? No — each form has own pin field; simpler: one pin `ref`, copied into both forms on submit via `transform` — implement with a single `pin` input bound to `revealForm.pin` and copy `resetForm.pin = revealForm.pin` before reset submit); Boy/Girl selector (same big buttons as Join); "Reveal 🎊" primary button (disabled until pin + gender); pin errors shown; after success a "Revealed! 🎉" state; divider; danger-styled "Reset party" with its own confirm. Keep it plain and functional — host screen doesn't need the fancy aesthetic, just matching palette.

Note: `confirm()` is fine here (host's own phone, not automated).

- [ ] **Step 2: Verify** `npm run types:check && npm run lint && npm run build` → PASS.

- [ ] **Step 3: Commit** `feat: host control screen`

---

### Task 10: Full verification + dev ergonomics

**Files:**
- Modify: `composer.json` (add `reverb:start` to the `dev` concurrently script if the installer didn't)

**Interfaces:** none new.

- [ ] **Step 1:** Ensure `composer dev` runs Reverb: the `dev` script's `concurrently` list should include `php artisan reverb:start`. Add if missing.

- [ ] **Step 2:** Run everything:

```bash
composer test          # pint --test, phpstan, pest — all PASS
npm run lint:check && npm run format:check && npm run types:check && npm run build
```

Fix anything red (run `npm run format` / `npm run lint` / pint to auto-fix).

- [ ] **Step 3:** Manual smoke via browser (dev server + reverb running): join → vote appears on `/tv` without refresh → host reveal → confetti + theme on TV.

- [ ] **Step 4: Commit** `chore: dev script + final verification`
