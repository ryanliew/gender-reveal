<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import confetti from 'canvas-confetti';
import QRCode from 'qrcode';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { avatarPreset } from '@/lib/avatars';
import type { Gender, GuestData } from '@/types/party';

const props = defineProps<{
    guests: GuestData[];
    revealedGender: Gender | null;
    joinUrl: string;
}>();

const guests = ref<GuestData[]>([...props.guests]);
const revealed = ref<Gender | null>(props.revealedGender);
const countdown = ref<number | null>(null);
const qr = ref('');

const boys = computed(() => guests.value.filter((g) => g.gender === 'boy'));
const girls = computed(() => guests.value.filter((g) => g.gender === 'girl'));

const doodles = [
    { emoji: '🍃', left: '4%', duration: '18s', delay: '0s', size: 'text-3xl' },
    {
        emoji: '💕',
        left: '14%',
        duration: '22s',
        delay: '-7s',
        size: 'text-2xl',
    },
    {
        emoji: '⭐',
        left: '28%',
        duration: '20s',
        delay: '-13s',
        size: 'text-xl',
    },
    {
        emoji: '🎈',
        left: '44%',
        duration: '26s',
        delay: '-3s',
        size: 'text-3xl',
    },
    {
        emoji: '🍃',
        left: '58%',
        duration: '19s',
        delay: '-16s',
        size: 'text-2xl',
    },
    {
        emoji: '⭐',
        left: '70%',
        duration: '24s',
        delay: '-9s',
        size: 'text-2xl',
    },
    {
        emoji: '💕',
        left: '84%',
        duration: '21s',
        delay: '-5s',
        size: 'text-3xl',
    },
    {
        emoji: '🎈',
        left: '94%',
        duration: '25s',
        delay: '-19s',
        size: 'text-xl',
    },
];

let countdownTimer: ReturnType<typeof setInterval> | undefined;

onMounted(async () => {
    qr.value = await QRCode.toDataURL(props.joinUrl, { margin: 1, width: 480 });
});

onUnmounted(() => clearInterval(countdownTimer));

useEchoPublic<{ guest: GuestData }>('party', 'VoteUpdated', (e) => {
    const index = guests.value.findIndex((g) => g.id === e.guest.id);

    if (index === -1) {
        guests.value.push(e.guest);
    } else {
        guests.value[index] = e.guest;
    }
});

useEchoPublic<{ gender: Gender }>('party', 'GenderRevealed', (e) => {
    beginReveal(e.gender);
});

useEchoPublic('party', 'PartyReset', () => {
    clearInterval(countdownTimer);
    countdown.value = null;
    guests.value = [];
    revealed.value = null;
});

/**
 * Build suspense with a 3…2…1 countdown before the confetti storm.
 * Skipped for viewers who prefer reduced motion.
 */
function beginReveal(gender: Gender) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        revealed.value = gender;
        fireConfetti(gender);

        return;
    }

    countdown.value = 3;
    countdownTimer = setInterval(() => {
        if (countdown.value !== null && countdown.value > 1) {
            countdown.value -= 1;
        } else {
            clearInterval(countdownTimer);
            countdown.value = null;
            revealed.value = gender;
            fireConfetti(gender);
        }
    }, 900);
}

function fireConfetti(gender: Gender) {
    const colors =
        gender === 'boy'
            ? ['#5b8db8', '#b8d4e8', '#ffffff']
            : ['#d4708b', '#f6c6d0', '#ffffff'];
    const end = Date.now() + 3500;

    const frame = () => {
        confetti({
            particleCount: 60,
            spread: 100,
            startVelocity: 45,
            origin: { x: Math.random(), y: Math.random() * 0.4 },
            colors,
        });

        if (Date.now() < end) {
            setTimeout(frame, 250);
        }
    };
    frame();
}

/**
 * Deterministic pseudo-random placement per guest so chips keep their spot
 * across re-renders instead of jumping around.
 */
function chipPosition(guest: GuestData) {
    const seed = ((guest.id * 9301 + 49297) % 233280) / 233280;
    const seed2 = ((guest.id * 233280 + 9301) % 49297) / 49297;

    return {
        top: `${8 + seed * 72}%`,
        left: `${5 + seed2 * 55}%`,
    };
}

function chipFloatTiming(guest: GuestData) {
    const seed = ((guest.id * 9301 + 49297) % 233280) / 233280;
    const seed2 = ((guest.id * 233280 + 9301) % 49297) / 49297;

    return {
        animationDuration: `${6 + seed * 6}s`,
        animationDelay: `${-seed2 * 6}s`,
    };
}
</script>

<template>
    <Head title="Gender Reveal" />
    <div
        class="relative flex h-screen flex-col overflow-hidden p-6 text-ink transition-colors duration-1000"
        :class="{
            'bg-cream': !revealed,
            'bg-boy-soft': revealed === 'boy',
            'bg-girl-soft': revealed === 'girl',
        }"
    >
        <!-- Ambient drifting doodles -->
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <span
                v-for="(doodle, i) in doodles"
                :key="i"
                class="absolute animate-doodle-fall opacity-40"
                :class="doodle.size"
                :style="{
                    left: doodle.left,
                    animationDuration: doodle.duration,
                    animationDelay: doodle.delay,
                }"
            >
                {{ doodle.emoji }}
            </span>
        </div>

        <!-- Countdown -->
        <div
            v-if="countdown !== null"
            class="absolute inset-0 z-30 flex items-center justify-center bg-ink/20"
        >
            <span
                :key="countdown"
                class="animate-count-pop font-hand text-[16rem] leading-none font-bold text-white drop-shadow-lg"
            >
                {{ countdown }}
            </span>
        </div>

        <!-- Reveal announcement -->
        <div
            v-if="revealed"
            class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center"
        >
            <div class="flex animate-reveal-zoom flex-col items-center gap-4">
                <span class="text-9xl">{{
                    revealed === 'boy' ? '💙' : '💖'
                }}</span>
                <h1
                    class="font-hand text-[10rem] leading-none font-bold drop-shadow-sm"
                    :class="
                        revealed === 'boy' ? 'text-boy-deep' : 'text-girl-deep'
                    "
                >
                    It's a {{ revealed === 'boy' ? 'Boy' : 'Girl' }}!
                </h1>
            </div>
        </div>

        <div class="grid grow grid-cols-[1fr_auto_1fr] gap-6">
            <!-- Team Boy -->
            <section
                class="relative rounded-3xl transition-colors duration-1000"
                :class="revealed === 'girl' ? 'bg-white/30' : 'bg-boy/40'"
            >
                <h2
                    class="absolute top-6 left-1/2 -translate-x-1/2 font-hand text-5xl font-semibold text-boy-deep"
                >
                    <span class="inline-block animate-sway">Team Boy 💙</span>
                </h2>
                <TransitionGroup appear enter-active-class="animate-chip-pop">
                    <div
                        v-for="guest in boys"
                        :key="guest.id"
                        class="absolute z-10"
                        :style="chipPosition(guest)"
                    >
                        <div
                            class="flex items-center gap-3 rounded-full bg-white py-2 pr-5 pl-2 shadow-md"
                            :class="
                                revealed === 'boy'
                                    ? 'animate-celebrate'
                                    : 'animate-float-drift'
                            "
                            :style="
                                revealed === 'boy'
                                    ? undefined
                                    : chipFloatTiming(guest)
                            "
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full text-2xl"
                                :style="{
                                    backgroundColor: avatarPreset(guest.avatar)
                                        .bg,
                                }"
                            >
                                {{ avatarPreset(guest.avatar).emoji }}
                            </span>
                            <span class="text-xl font-medium">{{
                                guest.name
                            }}</span>
                        </div>
                    </div>
                </TransitionGroup>
            </section>

            <!-- Center: title + QR -->
            <section
                class="flex w-80 flex-col items-center justify-center gap-6 text-center"
            >
                <template v-if="!revealed">
                    <p class="font-hand text-4xl text-girl-deep">
                        he or she… what will it be?
                    </p>
                    <h1 class="text-4xl font-semibold tracking-tight">
                        Gender Reveal
                    </h1>
                    <div
                        class="flex animate-nudge flex-col items-center gap-3 rounded-3xl bg-white p-6 shadow-md"
                    >
                        <img
                            v-if="qr"
                            :src="qr"
                            alt="QR code to join and vote"
                            class="h-52 w-52"
                        />
                        <p class="text-lg font-medium">
                            📱 Scan to cast your vote!
                        </p>
                    </div>
                    <p class="text-2xl font-medium opacity-70">
                        <span
                            :key="`boys-${boys.length}`"
                            class="inline-block animate-pop"
                        >
                            💙 {{ boys.length }}
                        </span>
                        ·
                        <span
                            :key="`girls-${girls.length}`"
                            class="inline-block animate-pop"
                        >
                            {{ girls.length }} 💖
                        </span>
                    </p>
                </template>
            </section>

            <!-- Team Girl -->
            <section
                class="relative rounded-3xl transition-colors duration-1000"
                :class="revealed === 'boy' ? 'bg-white/30' : 'bg-girl/40'"
            >
                <h2
                    class="absolute top-6 left-1/2 -translate-x-1/2 font-hand text-5xl font-semibold text-girl-deep"
                >
                    <span class="inline-block animate-sway">Team Girl 💖</span>
                </h2>
                <TransitionGroup appear enter-active-class="animate-chip-pop">
                    <div
                        v-for="guest in girls"
                        :key="guest.id"
                        class="absolute z-10"
                        :style="chipPosition(guest)"
                    >
                        <div
                            class="flex items-center gap-3 rounded-full bg-white py-2 pr-5 pl-2 shadow-md"
                            :class="
                                revealed === 'girl'
                                    ? 'animate-celebrate'
                                    : 'animate-float-drift'
                            "
                            :style="
                                revealed === 'girl'
                                    ? undefined
                                    : chipFloatTiming(guest)
                            "
                        >
                            <span
                                class="flex h-12 w-12 items-center justify-center rounded-full text-2xl"
                                :style="{
                                    backgroundColor: avatarPreset(guest.avatar)
                                        .bg,
                                }"
                            >
                                {{ avatarPreset(guest.avatar).emoji }}
                            </span>
                            <span class="text-xl font-medium">{{
                                guest.name
                            }}</span>
                        </div>
                    </div>
                </TransitionGroup>
            </section>
        </div>
    </div>
</template>
