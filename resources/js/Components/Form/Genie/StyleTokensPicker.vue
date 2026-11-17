<script setup>
import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";
import Select from "@/Components/Form/Select.vue";

/**
 * Picks a strategy field's or sub-field's style tokens (app/Enums/StrategyStyle.php): one select
 * per category, written back as the space separated value the `class` column stores.
 *
 * The categories and their tokens come from the page's `strategyStyles` prop.
 */
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: '',
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const groups = usePage().props.strategyStyles ?? [];

const tokens = computed(() => (props.modelValue ?? '').split(/\s+/).filter(Boolean));

/**
 * The token picked for a category. A token the vocabulary does not know is not offered, so it
 * drops out on the next change.
 */
const selected = (group) => {
    return tokens.value.find((token) => group.options.some((option) => option.value === token)) ?? '';
};

const choose = (group, value) => {
    const next = groups
        .map((entry) => entry.category === group.category ? value : selected(entry))
        .filter(Boolean);

    emit('update:modelValue', next.join(' '));
};
</script>
<template>
    <div class="flex w-full flex-col gap-1">
        <Select
            v-for="(group, index) in groups"
            :key="group.category"
            :id="index === 0 ? id : `${id}_${group.category}`"
            :model-value="selected(group)"
            :error="error"
            class="text-sm"
            @update:model-value="choose(group, $event)"
        >
            <option value="">{{ group.title }}: {{ $t('genie.strategy_style_none') }}</option>
            <option
                v-for="option in group.options"
                :key="option.value"
                :value="option.value"
            >
                {{ group.title }}: {{ option.title }}
            </option>
        </Select>
    </div>
</template>
