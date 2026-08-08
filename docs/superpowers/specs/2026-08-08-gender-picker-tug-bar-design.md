# Boy/Girl Picker Redesign: Tug-of-War Bar

## Context

The voting screen (`resources/js/pages/Join.vue`) lets guests guess Boy or Girl via two flat pastel buttons with a "vs" badge between them. The rest of the app (name field, avatar grid, reveal card, TV screen) carries a papercraft party aesthetic — washi tape, dashed craft-pane borders, doodles, and bouncy "sticker pop" selection animations. The gender picker is currently the plainest element on the page relative to its siblings.

Separately, `Tv.vue` already renders a tug-of-war scoreboard bar showing the live boy/girl vote split. This redesign reuses that same visual language on the phone, so a guest's own pick visually rhymes with the bar they'll later see on the TV.

## Goal

Replace the two-button picker with a single sliding tug-of-war bar: a pill-shaped track with a draggable/tappable marker that snaps to the Boy or Girl side.

## Visual & Interaction Spec

- **Track**: pill-shaped bar, `boy-soft` → `girl-soft` gradient background, roughly 56px tall (comfortable thumb target).
- **Marker**: circular, ~48px, shows 🍼 while unselected, swaps to 💙 (boy) or 💖 (girl) once a side is picked.
- **Idle state** (`form.gender === ''`): marker sits centered, dimmed/grayscale (`opacity` + `grayscale` filter or similar), track shows no fill wash, both labels render in a neutral (non-deep) tone.
- **Labels**: "💙 Boy" and "💖 Girl" rendered as real `<button type="button">` elements below or flanking the track — these are the actual accessible controls (see Accessibility below), not purely decorative captions.
- **Tap a label** → marker snaps to that side's fixed position (18% / 82% along the track) using the existing `animate-pop` overshoot keyframe, track fills solid from that side, chosen label text switches to its `-deep` color with a subtle background tint.
- **Drag the marker**: pressing down on the marker and moving follows the pointer's x position along the track (clamped to track bounds) using Pointer Events (`pointerdown` / `pointermove` / `pointerup`) — works for both touch and mouse. The fill wash follows the marker's live position during the drag for tactile "tug" feedback.
- **Release while dragging**: snaps to whichever half the marker is closer to (left half → boy, right half → girl) and commits the value — releasing at/near center still resolves to a side (based on which half of the track the release x falls in), it never leaves the value unset once a drag has occurred.
- **Colors/tokens**: reuse existing `--color-boy`, `--color-boy-soft`, `--color-boy-deep`, `--color-girl`, `--color-girl-soft`, `--color-girl-deep` tokens from `resources/css/app.css`. No new tokens needed.

## Component Structure

- New component: `resources/js/components/GenderPicker.vue`
  - Props: `modelValue: Gender | ''`
  - Emits: `update:modelValue`
  - Encapsulates track/marker rendering, pointer-drag handling, snap-position math, and the two label buttons.
- `Join.vue` changes:
  - Remove the inline two-button block (current lines ~371–412).
  - Replace with `<GenderPicker v-model="form.gender" />` inside the existing `<div class="flex flex-col gap-2">` wrapper, keeping the `"Your guess"` label and the `form.errors.gender` message beneath it unchanged.

## Accessibility

- The two label buttons remain the primary interactive controls: `type="button"`, `:aria-pressed="modelValue === 'boy' | 'girl'"`, clickable/keyboard-operable exactly as today's buttons are.
- The track + marker are a decorative/tactile layer on top, marked `aria-hidden="true"` — dragging is a bonus interaction, not a required path, since the labels alone fully drive the value.

## Edge Cases

- Drag released outside the track bounds → clamp marker x to track bounds before computing nearest-half, same snap logic applies.
- Rapid tap-drag-tap sequences → pointer handlers use pointer capture (`setPointerCapture`) so a drag started on the marker doesn't get interrupted by intervening pointer events.
- No change to existing form validation (`form.errors.gender`, "Boy or girl — take a guess!" message) or submit-disabled logic (`!form.gender` in `Join.vue`'s submit button `:disabled`).
- No backend/data model changes — `form.gender` continues to hold `'boy' | 'girl' | ''`, same `Gender` type from `@/types/party`.

## Testing

- No Pest coverage needed — this is a pure frontend presentation/interaction change with no server-side behavior change.
- Manual verification via `npm run dev`: confirm tap-to-select, drag-to-select, idle/selected visual states, keyboard operability of the label buttons, and that the existing validation/error/submit-disabled behavior still works.

## Out of Scope

- No changes to `Tv.vue`'s existing scoreboard bar, `VoteController`, `Guest` model, or any broadcast events.
- No changes to the avatar picker, name field, or reveal screen.
