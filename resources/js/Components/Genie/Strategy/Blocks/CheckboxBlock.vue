<script setup>
import useSchemaNode, {blockProps} from "@/Composables/Genie/useSchemaNode.js";
import Check from "@/Icons/Check.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {locked} = useSchemaNode(props);
</script>
<template>
    <button
        type="button"
        :disabled="locked"
        :aria-pressed="Boolean(modelValue)"
        class="inline-flex items-center gap-xs text-sm text-gray-700"
        @click="emit('update:modelValue', ! modelValue)"
    >
        <span
            :class="[
                'flex h-5 w-5 items-center justify-center rounded-md border-2 transition-colors',
                modelValue
                    ? 'border-(--strategy-accent) bg-(--strategy-accent) text-(--strategy-accent-context)'
                    : 'border-stone-600 bg-white'
            ]"
        >
            <Check v-if="modelValue" class="!h-4 !w-4"/>
        </span>
        {{ label || (modelValue ? 'true' : 'false') }}
    </button>
</template>
