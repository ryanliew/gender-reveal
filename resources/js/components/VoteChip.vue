<script setup lang="ts">
import { avatarPreset } from '@/lib/avatars';
import type { GuestData } from '@/types/party';

const props = defineProps<{
    guest: GuestData;
    celebrating: boolean;
}>();

const PETALS = [6, 8, 10, 12];

/**
 * Material 3 Expressive scalloped "cookie" shape: a polar curve
 * r = R(1 + a·cos(kθ)) sampled into an SVG path, k petals per cookie.
 */
function cookiePath(petals: number): string {
    const segments: string[] = [];

    for (let deg = 0; deg < 360; deg += 3) {
        const rad = (deg * Math.PI) / 180;
        const r = 34 * (1 + 0.11 * Math.cos(petals * rad));
        const x = 40 + r * Math.cos(rad);
        const y = 40 + r * Math.sin(rad);
        segments.push(
            `${deg === 0 ? 'M' : 'L'}${x.toFixed(1)} ${y.toFixed(1)}`,
        );
    }

    return `${segments.join(' ')} Z`;
}

const petals = PETALS[props.guest.id % PETALS.length];
const cookie = cookiePath(petals);

// Mirrored asymmetric (leaf) corners on alternating guests for variety.
const cornerClass =
    props.guest.id % 2 === 0
        ? 'rounded-[26px_12px_26px_12px]'
        : 'rounded-[12px_26px_12px_26px]';

const seed = ((props.guest.id * 9301 + 49297) % 233280) / 233280;
const seed2 = ((props.guest.id * 233280 + 9301) % 49297) / 49297;

const floatTiming = {
    animationDuration: `${6 + seed * 6}s`,
    animationDelay: `${-seed2 * 6}s`,
};
</script>

<template>
    <div
        class="flex items-center gap-3 border-2 bg-white py-2 pr-6 pl-2 shadow-md"
        :class="[
            cornerClass,
            guest.gender === 'boy' ? 'border-boy' : 'border-girl',
            celebrating ? 'animate-celebrate' : 'animate-float-drift',
        ]"
        :style="celebrating ? undefined : floatTiming"
    >
        <span class="relative h-14 w-14">
            <!-- Photo avatars: clipped into a static cookie -->
            <svg
                v-if="guest.photoUrl"
                viewBox="0 0 80 80"
                class="absolute inset-0 h-full w-full"
                aria-hidden="true"
            >
                <defs>
                    <clipPath :id="`cookie-${guest.id}`">
                        <path :d="cookie" />
                    </clipPath>
                </defs>
                <image
                    :href="guest.photoUrl"
                    width="80"
                    height="80"
                    preserveAspectRatio="xMidYMid slice"
                    :clip-path="`url(#cookie-${guest.id})`"
                />
            </svg>
            <!-- Preset avatars: emoji on a slowly spinning cookie -->
            <template v-else>
                <svg
                    viewBox="0 0 80 80"
                    class="absolute inset-0 h-full w-full animate-spin"
                    :style="{
                        animationDuration: '36s',
                        animationDirection:
                            guest.id % 2 === 0 ? 'normal' : 'reverse',
                    }"
                    aria-hidden="true"
                >
                    <path :d="cookie" :fill="avatarPreset(guest.avatar).bg" />
                </svg>
                <span
                    class="absolute inset-0 flex items-center justify-center text-3xl"
                >
                    {{ avatarPreset(guest.avatar).emoji }}
                </span>
            </template>
        </span>
        <span class="pt-1 font-hand text-3xl leading-none font-semibold">
            {{ guest.name }}
        </span>
    </div>
</template>
