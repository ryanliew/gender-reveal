# Boy/Girl Tug-of-War Picker Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the voting screen's two-button Boy/Girl picker with a single tug-of-war bar (tap or drag) that visually echoes the TV scoreboard's existing tug-of-war bar.

**Architecture:** Extract the picker into a new `resources/js/components/GenderPicker.vue` single-file component using Vue 3's `defineModel` for two-way binding, driven by Pointer Events for drag support. `resources/js/pages/Join.vue` swaps its inline two-button block for this component.

**Tech Stack:** Vue 3.5 `<script setup lang="ts">`, Tailwind CSS v4 utility classes (no new CSS), no new dependencies.

## Global Constraints

- No backend/data model changes — `form.gender` on `Join.vue`'s `useForm` keeps its existing type `Gender | ''` (`resources/js/types/party.ts` defines `Gender = 'boy' | 'girl'`).
- No changes to `resources/js/pages/Tv.vue`, `VoteController`, `Guest` model, or broadcast events.
- No new frontend dependencies — use Vue core (`defineModel`, `ref`, `computed`) and native Pointer Events only.
- Follow existing code style: 4-space indent, single quotes, `<script setup lang="ts">`, blank line before/after `if`/`return` blocks (see `resources/js/pages/Join.vue`'s `downscale()` for the pattern) — this repo's ESLint config (`eslint.config.js`) enforces this padding rule.
- Reuse existing design tokens only: `--color-boy`, `--color-boy-soft`, `--color-boy-deep`, `--color-girl`, `--color-girl-soft`, `--color-girl-deep` (all defined in `resources/css/app.css`) — do not add new CSS variables.
- This project has no frontend test runner (no Vitest, no `npm test` script) — verification is `npm run types:check`, `npm run lint:check`, `npm run format:check`, and manual browser testing, not automated unit tests.

---

### Task 1: Build `GenderPicker.vue` and wire it into the voting screen

**Files:**
- Create: `resources/js/components/GenderPicker.vue`
- Modify: `resources/js/pages/Join.vue:1-8` (add import), `resources/js/pages/Join.vue:370-412` (replace inline picker block)

**Interfaces:**
- `GenderPicker.vue` exposes a `v-model` of type `Gender | ''` (via `defineModel<Gender | ''>({ required: true })`), imported as `import type { Gender } from '@/types/party';`. Consumers bind with `<GenderPicker v-model="form.gender" />`.

- [ ] **Step 1: Create the `GenderPicker.vue` component**

Create `resources/js/components/GenderPicker.vue`:

```vue
<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Gender } from '@/types/party';

const modelValue = defineModel<Gender | ''>({ required: true });

const BOY_PCT = 18;
const GIRL_PCT = 82;

const track = ref<HTMLElement | null>(null);
const dragging = ref(false);
const dragPct = ref<number | null>(null);

const markerPct = computed(() => {
    if (dragging.value && dragPct.value !== null) {
        return dragPct.value;
    }

    if (modelValue.value === 'boy') {
        return BOY_PCT;
    }

    if (modelValue.value === 'girl') {
        return GIRL_PCT;
    }

    return 50;
});

const boyFillPct = computed(() =>
    markerPct.value < 50 ? markerPct.value : 0,
);

const girlFillPct = computed(() =>
    markerPct.value > 50 ? 100 - markerPct.value : 0,
);

const markerEmoji = computed(() => {
    if (modelValue.value === 'boy') {
        return '💙';
    }

    if (modelValue.value === 'girl') {
        return '💖';
    }

    return '🍼';
});

function pick(gender: Gender) {
    modelValue.value = gender;
}

function pctFromPointer(event: PointerEvent): number {
    if (!track.value) {
        return 50;
    }

    const rect = track.value.getBoundingClientRect();
    const pct = ((event.clientX - rect.left) / rect.width) * 100;

    return Math.min(100, Math.max(0, pct));
}

function onPointerDown(event: PointerEvent) {
    dragging.value = true;
    dragPct.value = pctFromPointer(event);
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent) {
    if (!dragging.value) {
        return;
    }

    dragPct.value = pctFromPointer(event);
}

function onPointerUp(event: PointerEvent) {
    if (!dragging.value) {
        return;
    }

    const pct = dragPct.value ?? 50;
    dragging.value = false;
    dragPct.value = null;
    pick(pct < 50 ? 'boy' : 'girl');
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            ref="track"
            class="relative h-4 w-full touch-none rounded-full bg-white shadow-inner"
            aria-hidden="true"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
        >
            <div
                class="absolute inset-y-0 left-0 rounded-l-full bg-boy"
                :class="dragging ? '' : 'transition-all duration-500'"
                :style="{ width: `${boyFillPct}%` }"
            />
            <div
                class="absolute inset-y-0 right-0 rounded-r-full bg-girl"
                :class="dragging ? '' : 'transition-all duration-500'"
                :style="{ width: `${girlFillPct}%` }"
            />
            <span
                class="absolute top-1/2 flex h-12 w-12 -translate-x-1/2 -translate-y-1/2 cursor-grab items-center justify-center rounded-full bg-white text-2xl shadow-md active:cursor-grabbing"
                :class="[
                    dragging ? '' : 'transition-all duration-500',
                    modelValue === '' ? 'opacity-50 grayscale' : '',
                ]"
                :style="{ left: `${markerPct}%` }"
            >
                {{ markerEmoji }}
            </span>
        </div>
        <div class="flex items-center justify-between px-1">
            <button
                type="button"
                class="rounded-full px-4 py-2 text-lg font-semibold transition-colors"
                :class="
                    modelValue === 'boy'
                        ? 'bg-boy-soft text-boy-deep'
                        : 'text-ink/50'
                "
                :aria-pressed="modelValue === 'boy'"
                @click="pick('boy')"
            >
                💙 Boy
            </button>
            <button
                type="button"
                class="rounded-full px-4 py-2 text-lg font-semibold transition-colors"
                :class="
                    modelValue === 'girl'
                        ? 'bg-girl-soft text-girl-deep'
                        : 'text-ink/50'
                "
                :aria-pressed="modelValue === 'girl'"
                @click="pick('girl')"
            >
                💖 Girl
            </button>
        </div>
    </div>
</template>
```

Notes on the behavior this encodes (for review, not extra steps):
- `markerPct` is 50 (center) whenever `modelValue` is `''` and no drag is in progress — the idle state.
- `boyFillPct` / `girlFillPct` are both 0 at `markerPct === 50`, so the track shows no color wash until the marker leans to one side — matches the "no fill wash while idle" requirement.
- The marker is dimmed (`opacity-50 grayscale`) exactly when `modelValue === ''`, regardless of an in-progress drag, per the design spec.
- `onPointerDown`/`onPointerMove` are handled on the whole track div (not just the marker), so pressing anywhere on the bar starts a drag from that position — this also means a plain tap-and-release anywhere on the track picks a side, which is intentional (a tap on the bar itself, not just the labels below, is a natural extension of "tap or drag").
- Releasing a drag exactly at the 50% midpoint resolves to `'girl'` (the `pct < 50` check is false at exactly 50) — deterministic, never leaves the value unset once a drag occurred, per spec.

- [ ] **Step 2: Type-check the new component in isolation**

Run: `npm run types:check`
Expected: passes with no errors (the file isn't imported anywhere yet, but `vue-tsc` still checks it since it's part of the project).

- [ ] **Step 3: Wire `GenderPicker` into `Join.vue`**

In `resources/js/pages/Join.vue`, add the import alongside the existing imports (after the `avatarPreset` import on line 7):

```ts
import GenderPicker from '@/components/GenderPicker.vue';
```

Then replace the entire "Gender" block (currently lines 370–412, from `<!-- Gender -->` through its closing `</div>`) with:

```vue
<!-- Gender -->
<div class="flex flex-col gap-2">
    <span class="font-medium">Your guess</span>
    <GenderPicker v-model="form.gender" />
    <span v-if="form.errors.gender" class="text-sm text-girl-deep">
        Boy or girl — take a guess!
    </span>
</div>
```

This removes the old two-button-plus-"vs"-badge markup entirely. No script changes are needed elsewhere in `Join.vue` — `form.gender` already exists and drives the same validation/submit-disabled logic.

- [ ] **Step 4: Type-check and lint the full change**

Run: `npm run types:check`
Expected: passes with no errors.

Run: `npm run lint`
Expected: exits clean (auto-fixes any trivial issues; re-run and confirm no remaining errors if it modified files).

Run: `npm run format`
Expected: exits clean (auto-formats; confirm no unexpected diffs beyond formatting).

- [ ] **Step 5: Manually verify in the browser**

Run: `composer dev` (or `npm run dev` if the Laravel dev server is already running separately)

Open `http://localhost:8000/` (the `home` route serves `Join.vue`) and verify:
- On load, with no prior guess, the marker sits centered and dimmed, track shows no color fill, both "Boy"/"Girl" labels are neutral-colored.
- Tapping "💙 Boy" snaps the marker to the left with a pop animation, fills blue from the left up to the marker, swaps the marker to 💙, and colors the "Boy" label.
- Tapping "💖 Girl" does the mirror image on the right in pink with 💖.
- Pressing and dragging the marker (mouse or touch) follows the pointer smoothly along the bar; releasing left-of-center commits Boy, right-of-center commits Girl.
- Tab-focusing the "Boy"/"Girl" buttons and pressing Enter/Space selects them (keyboard operability unchanged from before).
- Submitting the form without a pick still shows "Boy or girl — take a guess!" and the submit button stays disabled until a pick + name + avatar are all set (existing validation untouched).

If any of these don't match, fix `GenderPicker.vue` before proceeding — do not commit broken interaction.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/GenderPicker.vue resources/js/pages/Join.vue
git commit -m "$(cat <<'EOF'
feat: redesign Boy/Girl picker as a tug-of-war bar

Replaces the two flat buttons with a draggable/tappable bar that
echoes the TV scoreboard's tug-of-war visual, extracted into a
reusable GenderPicker component.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
EOF
)"
```
