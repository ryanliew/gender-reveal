<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/VoteController';
import { avatarPreset } from '@/lib/avatars';
import type { Gender, GuestData } from '@/types/party';

const props = defineProps<{
    guest: GuestData | null;
    avatars: string[];
    revealedGender: Gender | null;
}>();

const revealed = ref<Gender | null>(props.revealedGender);
const saved = ref(false);

useEchoPublic<{ gender: Gender }>('party', 'GenderRevealed', (e) => {
    revealed.value = e.gender;
});

const form = useForm({
    name: props.guest?.name ?? '',
    avatar: props.guest?.avatar ?? '',
    gender: (props.guest?.gender ?? '') as Gender | '',
});

function submit() {
    form.submit(store(), {
        preserveScroll: true,
        onSuccess: () => (saved.value = true),
    });
}
</script>

<template>
    <Head title="Join the Party" />
    <div
        class="flex min-h-screen flex-col items-center bg-cream px-5 py-10 text-ink"
    >
        <!-- Revealed: celebrate instead of voting -->
        <div
            v-if="revealed"
            class="flex w-full max-w-md grow flex-col items-center justify-center gap-6 text-center"
        >
            <div
                class="flex w-full flex-col items-center gap-4 rounded-3xl p-10 shadow-sm"
                :class="revealed === 'boy' ? 'bg-boy-soft' : 'bg-girl-soft'"
            >
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
            class="flex w-full max-w-md flex-col gap-8"
            @submit.prevent="submit"
        >
            <header class="text-center">
                <p class="font-hand text-3xl text-girl-deep">
                    welcome to the party!
                </p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Cast Your Guess
                </h1>
            </header>

            <div
                v-if="saved"
                class="rounded-2xl bg-white px-5 py-4 text-center text-sm shadow-sm"
            >
                🎉 Your vote is in, <strong>{{ form.name }}</strong> — you can
                still change it until the big reveal!
            </div>

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
                <span class="font-medium">Pick your character</span>
                <div class="grid grid-cols-4 gap-3">
                    <button
                        v-for="key in avatars"
                        :key="key"
                        type="button"
                        class="flex aspect-square items-center justify-center rounded-2xl text-4xl transition-transform active:scale-95"
                        :style="{ backgroundColor: avatarPreset(key).bg }"
                        :class="
                            form.avatar === key
                                ? 'scale-105 ring-3 ring-ink/60'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="form.avatar === key"
                        @click="form.avatar = key"
                    >
                        {{ avatarPreset(key).emoji }}
                    </button>
                </div>
                <span v-if="form.errors.avatar" class="text-sm text-girl-deep">
                    Please pick a character.
                </span>
            </div>

            <!-- Gender -->
            <div class="flex flex-col gap-2">
                <span class="font-medium">Your guess</span>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="flex min-h-24 flex-col items-center justify-center gap-1 rounded-2xl bg-boy text-boy-deep transition-transform active:scale-95"
                        :class="
                            form.gender === 'boy'
                                ? 'scale-105 ring-3 ring-boy-deep'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="form.gender === 'boy'"
                        @click="form.gender = 'boy'"
                    >
                        <span class="text-3xl">💙</span>
                        <span class="text-xl font-semibold">Boy</span>
                    </button>
                    <button
                        type="button"
                        class="flex min-h-24 flex-col items-center justify-center gap-1 rounded-2xl bg-girl text-girl-deep transition-transform active:scale-95"
                        :class="
                            form.gender === 'girl'
                                ? 'scale-105 ring-3 ring-girl-deep'
                                : 'ring-1 ring-black/5'
                        "
                        :aria-pressed="form.gender === 'girl'"
                        @click="form.gender = 'girl'"
                    >
                        <span class="text-3xl">💖</span>
                        <span class="text-xl font-semibold">Girl</span>
                    </button>
                </div>
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
                    !form.avatar ||
                    !form.gender
                "
            >
                {{ guest ? 'Update my vote' : 'Join the party 🎈' }}
            </button>
        </form>
    </div>
</template>
