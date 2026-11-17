<script setup>
import {computed} from "vue";
import useSchemaNode, {blockProps, labelOf} from "@/Composables/Genie/useSchemaNode.js";
import Check from "@/Icons/Check.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {locked, childMeta} = useSchemaNode(props);

const properties = computed(() => props.schema?.properties ?? {});

const isChoiceLocked = (key) => locked.value || (! props.structureOnly && childMeta(key)?.editable === false);

/**
 * The answer arrives in one of two shapes and both have to be read.
 *
 * The schema describes a map of key => bool, which is what a strategy stores. A field still typed
 * CHECKBOX is the exception: StrategyOutput::castForField() collapses it to a list of the selected
 * keys, which is how the older versions kept it.
 */
const isChosen = (key) => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(key);
    }

    return Boolean(props.modelValue?.[key]);
};

const toggleChoice = (key) => {
    if (isChoiceLocked(key)) {
        return;
    }

    // Answer in the shape it came in, so editing never rewrites how the value is stored.
    if (Array.isArray(props.modelValue)) {
        emit('update:modelValue', isChosen(key)
            ? props.modelValue.filter((entry) => entry !== key)
            : [...props.modelValue, key]);

        return;
    }

    emit('update:modelValue', {...(props.modelValue ?? {}), [key]: ! isChosen(key)});
};
</script>
<template>
    <div class="grid gap-xs sm:grid-cols-2">
        <button
            v-for="(child, key) in properties"
            :key="key"
            type="button"
            :disabled="isChoiceLocked(key)"
            :aria-pressed="isChosen(key)"
            :class="[
                'w-full rounded-xl border-2 px-md py-sm text-left text-sm font-medium transition-colors duration-200',
                isChoiceLocked(key) ? '' : 'cursor-pointer',
                isChosen(key)
                    ? 'border-(--strategy-accent) bg-(--strategy-accent-soft) text-black'
                    : 'border-stone-600 bg-white hover:border-primary-200 hover:bg-primary-50'
            ]"
            @click="toggleChoice(key)"
        >
            <span class="flex items-start justify-between gap-xs">
                <span>{{ labelOf(key, child) }}</span>
                <Check v-if="isChosen(key)" class="!h-5 !w-5 shrink-0 text-(--strategy-accent)"/>
            </span>
        </button>
    </div>
</template>
