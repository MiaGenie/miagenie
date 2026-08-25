<script setup>
defineProps({
    title: {
        type: String,
        default: '',
    },
    text: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'primary',
    }
});

const tones = {
    primary: 'from-primary-500 to-primary-700',
    accent: 'from-miagenie-600 to-miagenie-900',
    alert: 'from-red-600 to-red-700',
};
</script>
<template>
    <div class="mx-auto w-full overflow-hidden rounded-2xl border border-stone-600 bg-white text-center shadow-mix">
        <div :class="['flex h-24 items-center justify-center bg-linear-to-r', tones[tone] ?? tones.primary]">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-white">
                <slot name="icon"/>
            </span>
        </div>

        <div class="px-lg py-lg">
            <h2 v-if="title" class="font-title text-xl font-bold text-black">{{ title }}</h2>

            <p v-if="text" class="mt-xs whitespace-pre-line text-sm leading-relaxed text-gray-500">{{ text }}</p>

            <div v-if="$slots.default" class="mt-lg flex flex-col items-center gap-sm">
                <slot/>
            </div>
        </div>
    </div>
</template>
