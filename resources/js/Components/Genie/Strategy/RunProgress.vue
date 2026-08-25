<script setup>
import {computed} from "vue";

const props = defineProps({
    done: {
        type: Number,
        default: 0,
    },
    total: {
        type: Number,
        default: 0,
    },
    step: {
        type: String,
        default: '',
    }
});

const percent = computed(() => {
    return props.total ? Math.round((props.done / props.total) * 100) : 0;
});
</script>
<template>
    <div class="w-full">
        <div class="mb-xs flex items-center justify-between text-xs text-gray-400">
            <span class="font-mono">{{ done }} / {{ total }}</span>
            <span class="font-mono">{{ percent }}%</span>
        </div>

        <div class="h-2 w-full overflow-hidden rounded-full bg-stone-500">
            <div
                class="h-full rounded-full bg-linear-to-r from-primary-500 to-miagenie-700 transition-[width] duration-500 ease-out"
                :style="{width: `${percent}%`}"
            ></div>
        </div>

        <p v-if="step" class="mt-sm flex items-center justify-center gap-xs text-sm font-medium text-primary-500">
            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-primary-500"></span>
            {{ step }}
        </p>
    </div>
</template>
