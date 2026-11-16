<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import Panel from "@/Components/Surface/Panel.vue";

const props = defineProps({
    usage: {
        type: Object,
        required: true,
    },
});

const { t: $t, locale } = useI18n();

const numberFormat = computed(() => new Intl.NumberFormat(locale.value));

const counters = computed(() => [
    { key: "weekly", label: $t("genie.token_usage_weekly") },
    { key: "monthly", label: $t("genie.token_usage_monthly") },
    { key: "total", label: $t("genie.token_usage_total") },
]);
</script>
<template>
    <Panel>
        <template #title>{{ $t("genie.run_token_usage") }}</template>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-sm">
            <div v-for="counter in counters" :key="counter.key">
                <div class="text-gray-500">{{ counter.label }}</div>
                <div class="font-bold text-primary-500 text-2xl">
                    {{ numberFormat.format(props.usage[counter.key] ?? 0) }}
                </div>
            </div>
        </div>
    </Panel>
</template>
