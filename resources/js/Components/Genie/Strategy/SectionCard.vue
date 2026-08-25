<script setup>
import {computed} from "vue";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    /**
     * Cycles the header accent so neighbouring sections stay visually distinct without the
     * component needing to know which field it is showing.
     */
    accent: {
        type: Number,
        default: 0,
    },
    index: {
        type: Number,
        default: 0,
    },
    edited: {
        type: Boolean,
        default: false,
    }
});

const accents = [
    'from-primary-500 to-primary-700',
    'from-miagenie-600 to-miagenie-900',
    'from-cyan-700 to-cyan-900',
    'from-orange-600 to-orange-700',
];

const accentClass = computed(() => accents[props.accent % accents.length]);
</script>
<template>
    <section class="scroll-mt-24 overflow-hidden rounded-2xl border border-stone-600 bg-white shadow-mix">
        <header :class="['flex items-center justify-between gap-sm bg-linear-to-r px-lg py-md', accentClass]">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-white/70">
                    {{ String(index + 1).padStart(2, '0') }}
                </p>
                <h2 class="truncate font-title text-lg font-bold text-white">{{ title }}</h2>
            </div>

            <span
                v-if="edited"
                class="shrink-0 rounded-full bg-white/20 px-2.5 py-0.5 text-[11px] font-semibold text-white"
            >
                <slot name="badge">edited</slot>
            </span>
        </header>

        <div class="px-lg py-lg">
            <p v-if="description" class="mb-md text-sm text-gray-500">{{ description }}</p>

            <slot/>
        </div>
    </section>
</template>
