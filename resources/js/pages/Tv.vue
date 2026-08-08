<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import confetti from 'canvas-confetti';
import QRCode from 'qrcode';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import VoteChip from '@/components/VoteChip.vue';
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

const FLAG_COLORS = ['#b8d4e8', '#f6c6d0', '#fbf0cc'];

// Triangle flags hung along the garland's quadratic curve M0 10 Q 600 70 1200 10.
const flags = Array.from({ length: 14 }, (_, i) => {
    const t = (i + 0.5) / 14;

    return {
        x: 1200 * t,
        y: 10 + 120 * t * (1 - t),
        color: FLAG_COLORS[i % FLAG_COLORS.length],
    };
});

const boyPct = computed(() => {
    const total = boys.value.length + girls.value.length;

    return total === 0 ? 50 : (boys.value.length / total) * 100;
});

function dotPattern(team: Gender) {
    const color =
        team === 'boy' ? 'rgba(91, 141, 184, 0.1)' : 'rgba(212, 112, 139, 0.1)';

    return {
        backgroundImage: `radial-gradient(circle, ${color} 7px, transparent 8px)`,
        backgroundSize: '58px 58px',
    };
}

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

        <!-- Bunting garland -->
        <svg
            viewBox="0 0 1200 100"
            preserveAspectRatio="none"
            class="pointer-events-none absolute inset-x-0 top-0 z-10 h-20 w-full"
            aria-hidden="true"
        >
            <path
                d="M0 10 Q 600 70 1200 10"
                fill="none"
                stroke="#4a3f3a"
                stroke-opacity="0.25"
                stroke-width="3"
            />
            <polygon
                v-for="(flag, i) in flags"
                :key="i"
                :points="`${flag.x - 15},${flag.y} ${flag.x + 15},${flag.y} ${flag.x},${flag.y + 34}`"
                :fill="flag.color"
            />
        </svg>

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
                class="relative overflow-hidden rounded-3xl transition-colors duration-1000"
                :class="revealed === 'girl' ? 'bg-white/30' : 'bg-boy/40'"
            >
                <div
                    class="pointer-events-none absolute inset-0"
                    :style="dotPattern('boy')"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-3 rounded-[20px] border-2 border-dashed border-boy-deep/25"
                    aria-hidden="true"
                />
                <span
                    class="pointer-events-none absolute top-5 right-6 -rotate-12 text-4xl opacity-50"
                    aria-hidden="true"
                    >⛅</span
                >
                <span
                    class="pointer-events-none absolute bottom-6 left-6 rotate-6 text-5xl opacity-50"
                    aria-hidden="true"
                    >🧸</span
                >
                <span
                    class="pointer-events-none absolute right-8 bottom-8 -rotate-6 text-4xl opacity-50"
                    aria-hidden="true"
                    >🚂</span
                >
                <p
                    v-if="boys.length === 0"
                    class="absolute inset-0 flex items-center justify-center font-hand text-4xl text-boy-deep/40"
                >
                    no guesses yet…
                </p>
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
                        <VoteChip
                            :guest="guest"
                            :celebrating="revealed === 'boy'"
                        />
                    </div>
                </TransitionGroup>
            </section>

            <!-- Center: title + QR + scoreboard -->
            <section
                class="flex w-80 flex-col items-center justify-center gap-6 pt-6 text-center"
            >
                <template v-if="!revealed">
                    <div>
                        <p class="font-hand text-4xl text-girl-deep">
                            he or she… what will it be?
                        </p>
                        <h1 class="text-4xl font-semibold tracking-tight">
                            Gender Reveal
                        </h1>
                    </div>

                    <!-- Polaroid QR card held up by washi tape -->
                    <div
                        class="relative flex animate-nudge flex-col items-center gap-2 rounded-3xl bg-white p-5 pt-7 shadow-md"
                    >
                        <span
                            class="absolute -top-3 left-8 h-7 w-24 -rotate-6 rounded-sm bg-boy/70"
                            aria-hidden="true"
                        />
                        <span
                            class="absolute -top-3 right-8 h-7 w-24 rotate-6 rounded-sm bg-girl/70"
                            aria-hidden="true"
                        />
                        <div
                            class="rounded-xl border-2 border-dashed border-blush p-2"
                        >
                            <img
                                v-if="qr"
                                :src="qr"
                                alt="QR code to join and vote"
                                class="h-44 w-44"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="-rotate-12 text-3xl"
                                aria-hidden="true"
                            >
                                📲
                            </span>
                            <p
                                class="font-hand text-3xl leading-none text-ink/80"
                            >
                                scan to join the fun!
                            </p>
                        </div>
                    </div>

                    <!-- Scoreboard -->
                    <div class="flex w-full flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-1 rounded-2xl border-2 border-boy bg-white/70 py-2 text-center"
                            >
                                <p
                                    :key="`boys-${boys.length}`"
                                    class="animate-pop text-4xl leading-none font-bold text-boy-deep"
                                >
                                    {{ boys.length }}
                                </p>
                                <p
                                    class="font-hand text-2xl leading-tight text-boy-deep"
                                >
                                    Team Boy
                                </p>
                            </div>
                            <span class="font-hand text-3xl text-ink/50"
                                >vs</span
                            >
                            <div
                                class="flex-1 rounded-2xl border-2 border-girl bg-white/70 py-2 text-center"
                            >
                                <p
                                    :key="`girls-${girls.length}`"
                                    class="animate-pop text-4xl leading-none font-bold text-girl-deep"
                                >
                                    {{ girls.length }}
                                </p>
                                <p
                                    class="font-hand text-2xl leading-tight text-girl-deep"
                                >
                                    Team Girl
                                </p>
                            </div>
                        </div>
                        <!-- Tug-of-war bar -->
                        <div
                            class="relative h-4 w-full rounded-full bg-white shadow-inner"
                        >
                            <div
                                class="absolute inset-y-0 left-0 rounded-l-full bg-boy transition-all duration-700"
                                :style="{ width: `${boyPct}%` }"
                            />
                            <div
                                class="absolute inset-y-0 right-0 rounded-r-full bg-girl transition-all duration-700"
                                :style="{ width: `${100 - boyPct}%` }"
                            />
                            <span
                                class="absolute -top-3 -translate-x-1/2 text-2xl transition-all duration-700"
                                :style="{ left: `${boyPct}%` }"
                                aria-hidden="true"
                            >
                                🍼
                            </span>
                        </div>
                    </div>
                </template>
            </section>

            <!-- Team Girl -->
            <section
                class="relative overflow-hidden rounded-3xl transition-colors duration-1000"
                :class="revealed === 'boy' ? 'bg-white/30' : 'bg-girl/40'"
            >
                <div
                    class="pointer-events-none absolute inset-0"
                    :style="dotPattern('girl')"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-3 rounded-[20px] border-2 border-dashed border-girl-deep/25"
                    aria-hidden="true"
                />
                <span
                    class="pointer-events-none absolute top-5 left-6 rotate-12 text-4xl opacity-50"
                    aria-hidden="true"
                    >🎀</span
                >
                <span
                    class="pointer-events-none absolute right-6 bottom-6 -rotate-6 text-5xl opacity-50"
                    aria-hidden="true"
                    >🦋</span
                >
                <span
                    class="pointer-events-none absolute bottom-8 left-8 rotate-6 text-4xl opacity-50"
                    aria-hidden="true"
                    >🌷</span
                >
                <p
                    v-if="girls.length === 0"
                    class="absolute inset-0 flex items-center justify-center font-hand text-4xl text-girl-deep/40"
                >
                    no guesses yet…
                </p>
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
                        <VoteChip
                            :guest="guest"
                            :celebrating="revealed === 'girl'"
                        />
                    </div>
                </TransitionGroup>
            </section>
        </div>
    </div>
</template>
