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

const boyFillPct = computed(() => (markerPct.value < 50 ? markerPct.value : 0));

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

// eslint-disable-next-line @typescript-eslint/no-unused-vars
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
