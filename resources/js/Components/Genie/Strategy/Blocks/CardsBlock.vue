<script setup>
import {computed} from "vue";
import useSchemaNode, {blockProps, labelOf} from "@/Composables/Genie/useSchemaNode.js";
import SchemaNode from "@/Components/Genie/Strategy/SchemaNode.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {childMeta} = useSchemaNode(props);

const properties = computed(() => props.schema?.properties ?? {});

const updateProperty = (key, value) => {
    emit('update:modelValue', {...(props.modelValue ?? {}), [key]: value});
};
</script>
<template>
    <div class="grid gap-sm sm:grid-cols-2">
        <div
            v-for="(child, key) in properties"
            :key="key"
            class="rounded-2xl border border-stone-600 bg-white p-md"
        >
            <p class="mb-xs text-[11px] font-bold uppercase tracking-wider text-gray-400">
                {{ labelOf(key, child) }}
            </p>

            <SchemaNode
                :schema="child"
                :model-value="modelValue?.[key] ?? null"
                :depth="depth + 1"
                :read-only="readOnly"
                :meta="childMeta(key)"
                :structure-only="structureOnly"
                @update:model-value="updateProperty(key, $event)"
            />
        </div>
    </div>
</template>
