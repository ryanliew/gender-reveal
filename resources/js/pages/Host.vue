<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { reveal, reset } from '@/actions/App/Http/Controllers/HostController';
import type { Gender } from '@/types/party';

const props = defineProps<{
    secret: string;
}>();

const pin = ref('');
const revealedNow = ref(false);
const confirmingReveal = ref(false);
const confirmingReset = ref(false);

const revealForm = useForm({ pin: '', gender: '' as Gender | '' });
const resetForm = useForm({ pin: '' });

function submitReveal() {
    if (!confirmingReveal.value) {
        confirmingReveal.value = true;

        return;
    }

    confirmingReveal.value = false;
    revealForm.pin = pin.value;
    revealForm.submit(reveal(props.secret), {
        preserveScroll: true,
        onSuccess: () => (revealedNow.value = true),
    });
}

function submitReset() {
    if (!confirmingReset.value) {
        confirmingReset.value = true;

        return;
    }

    confirmingReset.value = false;
    resetForm.pin = pin.value;
    resetForm.submit(reset(props.secret), {
        preserveScroll: true,
        onSuccess: () => {
            revealedNow.value = false;
            revealForm.gender = '';
        },
    });
}
</script>

<template>
    <Head title="Host Controls" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-cream px-5 py-10 text-ink"
    >
        <div class="flex w-full max-w-md flex-col gap-8">
            <header class="text-center">
                <p class="font-hand text-3xl text-girl-deep">
                    shhh… top secret
                </p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Host Controls 🎉
                </h1>
            </header>

            <div
                v-if="revealedNow"
                class="rounded-2xl bg-white px-5 py-4 text-center shadow-sm"
            >
                🎊 Revealed! Check the TV.
            </div>

            <!-- PIN -->
            <label class="flex flex-col gap-2">
                <span class="font-medium">Host PIN</span>
                <input
                    v-model="pin"
                    type="password"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="••••"
                    class="rounded-2xl border-2 border-blush bg-white px-5 py-4 text-center text-2xl tracking-[0.5em] outline-none focus:border-girl"
                />
                <span
                    v-if="revealForm.errors.pin || resetForm.errors.pin"
                    class="text-sm text-girl-deep"
                >
                    {{ revealForm.errors.pin || resetForm.errors.pin }}
                </span>
            </label>

            <!-- Gender pick -->
            <div class="flex flex-col gap-2">
                <span class="font-medium">The actual gender</span>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="flex min-h-20 items-center justify-center gap-2 rounded-2xl bg-boy text-xl font-semibold text-boy-deep transition-transform active:scale-95"
                        :class="
                            revealForm.gender === 'boy'
                                ? 'scale-105 ring-3 ring-boy-deep'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="revealForm.gender === 'boy'"
                        @click="
                            ((revealForm.gender = 'boy'),
                            (confirmingReveal = false))
                        "
                    >
                        💙 Boy
                    </button>
                    <button
                        type="button"
                        class="flex min-h-20 items-center justify-center gap-2 rounded-2xl bg-girl text-xl font-semibold text-girl-deep transition-transform active:scale-95"
                        :class="
                            revealForm.gender === 'girl'
                                ? 'scale-105 ring-3 ring-girl-deep'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="revealForm.gender === 'girl'"
                        @click="
                            ((revealForm.gender = 'girl'),
                            (confirmingReveal = false))
                        "
                    >
                        💖 Girl
                    </button>
                </div>
                <span
                    v-if="revealForm.errors.gender"
                    class="text-sm text-girl-deep"
                >
                    Pick the gender to reveal.
                </span>
            </div>

            <button
                type="button"
                class="min-h-14 rounded-2xl text-lg font-semibold text-white transition-all active:scale-95 disabled:opacity-40"
                :class="confirmingReveal ? 'bg-girl-deep' : 'bg-ink'"
                :disabled="revealForm.processing || !pin || !revealForm.gender"
                @click="submitReveal"
            >
                {{
                    confirmingReveal
                        ? `Tap again to reveal "${revealForm.gender}"!`
                        : 'Reveal 🎊'
                }}
            </button>

            <hr class="border-blush" />

            <button
                type="button"
                class="min-h-12 rounded-2xl border-2 text-base font-medium transition-all active:scale-95 disabled:opacity-40"
                :class="
                    confirmingReset
                        ? 'border-girl-deep bg-girl-deep text-white'
                        : 'border-blush text-ink/70'
                "
                :disabled="resetForm.processing || !pin"
                @click="submitReset"
            >
                {{
                    confirmingReset
                        ? 'Tap again to clear all votes and un-reveal'
                        : 'Reset party'
                }}
            </button>
        </div>
    </div>
</template>
