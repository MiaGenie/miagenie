<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * What a call, a run step or a run consumed, as "input / output". Null means unknown — the call
 * never answered, or it was made before calls were counted — and shows the blank instead.
 */
const props = defineProps({
    input: {
        type: Number,
        default: null,
    },
    output: {
        type: Number,
        default: null,
    },
    blank: {
        type: String,
        default: "—",
    },
});

const { locale } = useI18n();

const numberFormat = computed(() => new Intl.NumberFormat(locale.value));

const text = computed(() => {
    if (props.input === null && props.output === null) {
        return props.blank;
    }

    return `${numberFormat.value.format(props.input ?? 0)} / ${numberFormat.value.format(props.output ?? 0)}`;
});
</script>
<template>
    <span class="whitespace-nowrap">{{ text }}</span>
</template>
