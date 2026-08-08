<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import confetti from 'canvas-confetti';
import { computed, onMounted, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/VoteController';
import GenderPicker from '@/components/GenderPicker.vue';
import { avatarPreset } from '@/lib/avatars';
import type { Gender, GuestData } from '@/types/party';

const props = defineProps<{
    guest: GuestData | null;
    avatars: string[];
    revealedGender: Gender | null;
}>();

const revealed = ref<Gender | null>(props.revealedGender);
const saved = ref(false);

const doodles = [
    { emoji: '🍃', left: '8%', duration: '20s', delay: '-4s' },
    { emoji: '💕', left: '50%', duration: '26s', delay: '-14s' },
    { emoji: '⭐', left: '86%', duration: '23s', delay: '-9s' },
];

const FLAG_COLORS = ['#b8d4e8', '#f6c6d0', '#fbf0cc'];

// Triangle flags hung along the garland's quadratic curve M0 10 Q 300 70 600 10.
const flags = Array.from({ length: 7 }, (_, i) => {
    const t = (i + 0.5) / 7;

    return {
        x: 600 * t,
        y: 10 + 120 * t * (1 - t),
        color: FLAG_COLORS[i % FLAG_COLORS.length],
    };
});

const dotPattern = {
    backgroundImage:
        'radial-gradient(circle, rgba(74, 63, 58, 0.05) 6px, transparent 7px)',
    backgroundSize: '52px 52px',
};

onMounted(() => {
    if (revealed.value) {
        burst(revealed.value);
    }
});

useEchoPublic<{ gender: Gender }>('party', 'GenderRevealed', (e) => {
    // Hold back for the TV's 3…2…1 countdown so phones don't spoil the moment.
    setTimeout(() => {
        revealed.value = e.gender;
        burst(e.gender);
    }, 2700);
});

const form = useForm({
    name: props.guest?.name ?? '',
    avatar: props.guest?.avatar ?? '',
    photo: null as File | null,
    gender: (props.guest?.gender ?? '') as Gender | '',
});

const photoInput = ref<HTMLInputElement | null>(null);
const photoPreview = ref<string | null>(props.guest?.photoUrl ?? null);

const photoSelected = computed(
    () =>
        form.photo !== null ||
        (form.avatar === '' && photoPreview.value !== null),
);

function pickPreset(key: string) {
    form.avatar = key;
    form.photo = null;
}

async function onPhotoPicked(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    form.photo = await downscale(file);
    form.avatar = '';
    photoPreview.value = URL.createObjectURL(form.photo);
}

/**
 * Shrink the photo to ≤512px JPEG on-device so uploads stay small.
 * Falls back to the original file if the browser can't decode it.
 */
async function downscale(file: File): Promise<File> {
    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, 512 / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas
            .getContext('2d')
            ?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise<Blob | null>((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', 0.85),
        );

        return blob
            ? new File([blob], 'avatar.jpg', { type: 'image/jpeg' })
            : file;
    } catch {
        return file;
    }
}

function burst(gender: Gender | '') {
    confetti({
        particleCount: 50,
        spread: 75,
        startVelocity: 35,
        origin: { y: 0.75 },
        colors:
            gender === 'boy'
                ? ['#5b8db8', '#b8d4e8', '#ffffff']
                : ['#d4708b', '#f6c6d0', '#ffffff'],
    });
}

function submit() {
    form.submit(store(), {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            burst(form.gender);
        },
    });
}
</script>

<template>
    <Head title="Join the Party" />
    <div
        class="relative flex min-h-screen flex-col items-center bg-cream px-5 pt-14 pb-10 text-ink"
        :style="dotPattern"
    >
        <!-- Bunting garland -->
        <svg
            viewBox="0 0 600 100"
            preserveAspectRatio="none"
            class="pointer-events-none absolute inset-x-0 top-0 z-10 h-14 w-full"
            aria-hidden="true"
        >
            <path
                d="M0 10 Q 300 70 600 10"
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

        <!-- Ambient drifting doodles -->
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <span
                v-for="(doodle, i) in doodles"
                :key="i"
                class="absolute animate-doodle-fall text-2xl opacity-30"
                :style="{
                    left: doodle.left,
                    animationDuration: doodle.duration,
                    animationDelay: doodle.delay,
                }"
            >
                {{ doodle.emoji }}
            </span>
        </div>

        <!-- Revealed: celebrate instead of voting -->
        <div
            v-if="revealed"
            class="flex w-full max-w-md grow flex-col items-center justify-center gap-6 text-center"
        >
            <div
                class="relative flex w-full animate-reveal-zoom flex-col items-center gap-4 rounded-3xl p-10 shadow-sm"
                :class="revealed === 'boy' ? 'bg-boy-soft' : 'bg-girl-soft'"
            >
                <span
                    class="absolute -top-3 left-8 h-7 w-20 -rotate-6 rounded-sm bg-boy/70"
                    aria-hidden="true"
                />
                <span
                    class="absolute -top-3 right-8 h-7 w-20 rotate-6 rounded-sm bg-girl/70"
                    aria-hidden="true"
                />
                <span class="text-7xl">{{
                    revealed === 'boy' ? '👶💙' : '👶💖'
                }}</span>
                <h1
                    class="font-hand text-6xl font-bold"
                    :class="
                        revealed === 'boy' ? 'text-boy-deep' : 'text-girl-deep'
                    "
                >
                    It's a {{ revealed === 'boy' ? 'Boy' : 'Girl' }}!
                </h1>
                <p class="text-lg opacity-70">
                    The secret is out — thanks for celebrating with us!
                </p>
            </div>
        </div>

        <!-- Voting form -->
        <form
            v-else
            class="relative flex w-full max-w-md flex-col gap-8 rounded-3xl bg-white/80 p-6 pt-8 shadow-md"
            @submit.prevent="submit"
        >
            <!-- Washi tape + stitched border + corner motifs -->
            <span
                class="absolute -top-3 left-8 z-10 h-7 w-20 -rotate-6 rounded-sm bg-boy/70"
                aria-hidden="true"
            />
            <span
                class="absolute -top-3 right-8 z-10 h-7 w-20 rotate-6 rounded-sm bg-girl/70"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute inset-2 rounded-[20px] border-2 border-dashed border-blush"
                aria-hidden="true"
            />
            <span
                class="pointer-events-none absolute top-6 left-4 rotate-12 text-2xl opacity-40"
                aria-hidden="true"
                >🧸</span
            >
            <span
                class="pointer-events-none absolute right-4 bottom-6 -rotate-12 text-2xl opacity-40"
                aria-hidden="true"
                >🎀</span
            >

            <header class="text-center">
                <p class="font-hand text-3xl text-girl-deep">
                    welcome to the party!
                </p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Cast Your Guess
                </h1>
                <svg
                    viewBox="0 0 200 12"
                    class="mx-auto mt-1 h-3 w-48 text-girl-deep"
                    aria-hidden="true"
                >
                    <path
                        class="squiggle-path"
                        d="M2 8 Q 20 2, 40 7 T 80 7 T 120 7 T 160 7 T 198 6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                    />
                </svg>
            </header>

            <Transition enter-active-class="animate-slide-down">
                <div
                    v-if="saved"
                    class="rounded-2xl bg-white px-5 py-4 text-center text-sm shadow-sm"
                >
                    🎉 Your vote is in, <strong>{{ form.name }}</strong> — you
                    can still change it until the big reveal!
                </div>
            </Transition>

            <!-- Name -->
            <label class="flex flex-col gap-2">
                <span class="font-medium">Your name</span>
                <input
                    v-model="form.name"
                    type="text"
                    maxlength="30"
                    placeholder="e.g. Aunty May"
                    class="rounded-2xl border-2 border-blush bg-white px-5 py-4 text-lg outline-none focus:border-girl"
                />
                <span v-if="form.errors.name" class="text-sm text-girl-deep">{{
                    form.errors.name
                }}</span>
            </label>

            <!-- Avatar -->
            <div class="flex flex-col gap-2">
                <span class="font-medium">
                    Pick your character — or use your own photo
                </span>
                <input
                    ref="photoInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onPhotoPicked"
                />
                <div class="grid grid-cols-4 gap-3">
                    <button
                        type="button"
                        class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-white transition-transform active:scale-95"
                        :class="
                            photoSelected
                                ? 'scale-105 animate-wiggle ring-3 ring-ink/60'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="photoSelected"
                        @click="photoInput?.click()"
                    >
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt="Your photo"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex flex-col items-center gap-1 text-ink/70"
                        >
                            <span class="text-3xl">📸</span>
                            <span class="text-xs font-medium">Selfie</span>
                        </span>
                    </button>
                    <button
                        v-for="key in avatars"
                        :key="key"
                        type="button"
                        class="aspect-square overflow-hidden rounded-2xl bg-white transition-transform active:scale-95"
                        :class="
                            form.avatar === key
                                ? 'scale-105 animate-wiggle ring-3 ring-ink/60'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="form.avatar === key"
                        @click="pickPreset(key)"
                    >
                        <img
                            :src="avatarPreset(key).src"
                            :alt="avatarPreset(key).label"
                            class="h-full w-full object-cover"
                            loading="lazy"
                        />
                    </button>
                </div>
                <span
                    v-if="form.errors.avatar || form.errors.photo"
                    class="text-sm text-girl-deep"
                >
                    {{
                        form.errors.photo || 'Pick a character or add a photo.'
                    }}
                </span>
            </div>

            <!-- Gender -->
            <div class="flex flex-col gap-2">
                <span class="font-medium">Your guess</span>
                <GenderPicker v-model="form.gender" />
                <span v-if="form.errors.gender" class="text-sm text-girl-deep">
                    Boy or girl — take a guess!
                </span>
            </div>

            <button
                type="submit"
                class="min-h-14 rounded-2xl bg-ink text-lg font-semibold text-cream transition-transform active:scale-95 disabled:opacity-40"
                :disabled="
                    form.processing ||
                    !form.name ||
                    !form.gender ||
                    (!form.avatar && !form.photo && !photoPreview)
                "
            >
                {{ guest ? 'Update my vote' : 'Join the party 🎈' }}
            </button>
        </form>
    </div>
</template>

<style scoped>
.squiggle-path {
    stroke-dasharray: 220;
    stroke-dashoffset: 220;
    animation: squiggle-draw 1.2s ease-out 0.4s forwards;
}

@keyframes squiggle-draw {
    to {
        stroke-dashoffset: 0;
    }
}
</style>
