# Gender Reveal Party System — Design Spec

**Date:** 2026-08-08
**Status:** Approved by user (conversation, 2026-08-08)

## Purpose

A single-event, home-LAN gender reveal app. Guests scan a QR code on the TV,
vote Boy or Girl with their name and a preset avatar, and watch their vote
float onto the TV in real time. The host reveals the actual gender from a
control screen; the TV bursts confetti and re-themes to the winning gender.

## Decisions (settled with user)

- **Realtime driver:** Laravel Reverb (self-hosted, first-party) + Laravel Echo.
- **Avatars:** preset hand-drawn-style avatars only; no uploads.
- **Host protection:** unguessable secret URL segment + PIN, both from `.env`. No user accounts.
- **Scope:** single global event; host has a Reset action (clears votes, un-reveals) for rehearsal.
- **Vote rules:** one vote per device via long-lived cookie; guests can change
  name/avatar/gender until the reveal; votes are rejected after reveal.

## Stack additions

- Composer: `laravel/reverb`
- npm: `laravel-echo`, `pusher-js`, `canvas-confetti`, `qrcode`

## Data model

`guests` table:

| column        | type                          |
| ------------- | ----------------------------- |
| `id`          | pk                            |
| `device_uuid` | uuid, unique                  |
| `name`        | string                        |
| `avatar`      | string (key into preset list) |
| `gender`      | string enum: `boy` / `girl`   |
| timestamps    |                               |

Reveal state: single cache key `party.revealed_gender` (`null` until revealed;
database cache driver so it survives restarts). Reset deletes all guests and
forgets the key.

PHP enum `App\Enums\Gender` with TitleCase keys `Boy`, `Girl` and lowercase
string values.

## Routes

| Method | URI                     | Purpose                                                              |
| ------ | ----------------------- | -------------------------------------------------------------------- |
| GET    | `/`                     | Join page (QR target). Preloads this device's existing vote, if any. |
| POST   | `/vote`                 | Upsert guest by device UUID cookie. 403 once revealed.               |
| GET    | `/tv`                   | TV display: guests, reveal state, absolute join URL for QR.          |
| GET    | `/host/{secret}`        | Host control page. 404 on wrong secret.                              |
| POST   | `/host/{secret}/reveal` | Requires PIN in payload; takes gender; sets cache key; broadcasts.   |
| POST   | `/host/{secret}/reset`  | Requires PIN; truncates guests, clears reveal; broadcasts.           |

`{secret}` and the PIN come from `.env` (`PARTY_HOST_SECRET`, `PARTY_HOST_PIN`)
via `config/party.php`.

## Broadcasting

Public channel `party`, events implementing `ShouldBroadcast`:

- `VoteUpdated` — full guest payload (id, name, avatar, gender).
- `GenderRevealed` — the revealed gender.
- `PartyReset` — no payload.

Reverb runs during development alongside `composer dev`.

## Screens

Aesthetic follows `docs/inspirations/Hand drawn art`: cream/blush background,
soft pastels, hand-drawn animal motifs, script accent typography.

- **Join (`/`)**: name field → preset avatar grid (~8 cute animal presets) →
  large Boy/Girl choice → submit. Revisiting shows the existing vote, editable
  until reveal. After reveal, shows the announced gender instead of the form.
- **TV (`/tv`)**: split layout — Boy section (soft blue) and Girl section
  (soft pink); QR code + heading centered. Each vote renders as a floating
  chip (avatar + name) drifting gently within its gender's section, appearing
  live via Echo. On `GenderRevealed`: canvas-confetti burst, then the whole
  screen transitions to the winning gender's theme with a large
  "It's a Boy!/It's a Girl!" announcement, keeping the chips.
- **Host (`/host/{secret}`)**: PIN field, "Reveal" → pick Boy/Girl → confirm →
  fires reveal. Separate confirmed "Reset party" button.

## Error handling

- Vote validation: name required/max length, avatar must be a known preset key,
  gender must be a valid enum value.
- Voting after reveal → 403 with a friendly message; Join page also swaps to
  the revealed state via Echo without a refresh.
- Wrong host secret → 404; wrong PIN → 422 validation error.
- Echo disconnect: screens still render from server props on refresh.

## Testing (Pest, `RefreshDatabase` enabled)

- Vote: creates guest, second post from same device updates instead of
  duplicating, invalid payloads rejected, vote after reveal rejected.
- Host: wrong secret 404s, wrong PIN 422s, reveal sets state + broadcasts
  `GenderRevealed`, reset clears guests/state + broadcasts `PartyReset`
  (`Event::fake()`).
- Pages: `/`, `/tv`, host page render the right Inertia components with the
  right props.

## Out of scope (YAGNI)

- Multiple events/rooms, photo uploads, vote tallies/percentages on TV,
  guessed-right leaderboard, sound effects, SSR.
