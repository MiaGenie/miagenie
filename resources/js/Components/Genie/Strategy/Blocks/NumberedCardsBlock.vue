<script setup>
import {computed} from "vue";
import useSchemaNode, {blockProps, labelOf, typeOf} from "@/Composables/Genie/useSchemaNode.js";
import SchemaNode from "@/Components/Genie/Strategy/SchemaNode.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {structureLocked, itemMeta} = useSchemaNode(props);

const items = computed(() => props.schema?.items ?? {});

const list = computed(() => Array.isArray(props.modelValue) ? props.modelValue : []);

/**
 * The first text property of an item stands in as its heading.
 */
const headingKey = computed(() => {
    return Object.keys(items.value.properties ?? {})
        .find((entry) => typeOf(items.value.properties[entry]) === 'string');
});

const itemHeading = (item) => {
    if (typeof item === 'string') {
        return item;
    }

    return headingKey.value ? item?.[headingKey.value] : '';
};

const itemBodyKeys = computed(() => {
    return Object.keys(items.value.properties ?? {}).filter((entry) => entry !== headingKey.value);
});

const updateItem = (index, value) => {
    const next = [...list.value];

    next[index] = value;

    emit('update:modelValue', next);
};

const removeItem = (index) => {
    emit('update:modelValue', list.value.filter((item, entry) => entry !== index));
};

const addItem = () => {
    emit('update:modelValue', [...list.value, {}]);
};
</script>
<template>
    <div class="flex flex-col gap-sm">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="group relative rounded-2xl border border-stone-600 bg-white p-md"
        >
            <div class="mb-sm flex items-start justify-between gap-xs">
                <p class="text-sm font-semibold text-black">
                    <span class="mr-1.5 text-xs font-bold text-(--strategy-accent)">{{ index + 1 }}</span>
                    {{ itemHeading(item) || '—' }}
                </p>

                <button
                    v-if="!structureLocked"
                    type="button"
                    class="shrink-0 text-gray-300 opacity-0 transition-opacity hover:text-red-600 group-hover:opacity-100"
                    @click="removeItem(index)"
                >
                    <X class="!h-4 !w-4"/>
                </button>
            </div>

            <div class="flex flex-col gap-sm">
                <div v-for="key in itemBodyKeys" :key="key">
                    <p class="mb-0.5 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        {{ labelOf(key, items.properties[key]) }}
                    </p>

                    <SchemaNode
                        :schema="items.properties[key]"
                        :model-value="item?.[key] ?? null"
                        :depth="depth + 1"
                        :read-only="readOnly"
                        :meta="itemMeta(key)"
                        :structure-only="structureOnly"
                        @update:model-value="updateItem(index, {...item, [key]: $event})"
                    />
                </div>
            </div>
        </div>

        <button
            v-if="!structureLocked"
            type="button"
            class="inline-flex w-fit items-center gap-1 rounded-full border border-dashed border-stone-600 px-3 py-1 text-xs text-gray-400 transition-colors hover:border-primary-200 hover:text-primary-500"
            @click="addItem"
        >
            <Plus class="!h-3 !w-3"/>
        </button>
    </div>
</template>
