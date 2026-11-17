<script setup>
import useSchemaNode, {blockProps, isHex} from "@/Composables/Genie/useSchemaNode.js";
import EditableValue from "@/Components/Genie/Strategy/EditableValue.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {locked} = useSchemaNode(props);
</script>
<template>
    <div class="flex items-start gap-xs">
        <span
            v-if="isHex(modelValue)"
            class="mt-0.5 h-5 w-5 shrink-0 rounded-md border border-stone-600"
            :style="{backgroundColor: modelValue}"
        ></span>

        <EditableValue
            :model-value="modelValue"
            :multiline="!schema.enum && (schema.maxLength ?? 400) > 120"
            :options="schema.enum ?? []"
            :read-only="locked"
            :placeholder="schema.description ?? ''"
            class="min-w-0 flex-1 text-sm leading-relaxed text-gray-700"
            @update:model-value="emit('update:modelValue', $event)"
        />
    </div>
</template>
