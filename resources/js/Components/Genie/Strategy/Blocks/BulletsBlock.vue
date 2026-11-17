<script setup>
import {computed} from "vue";
import useSchemaNode, {blockProps} from "@/Composables/Genie/useSchemaNode.js";
import EditableValue from "@/Components/Genie/Strategy/EditableValue.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

const {locked, structureLocked} = useSchemaNode(props);

const list = computed(() => Array.isArray(props.modelValue) ? props.modelValue : []);

const updateItem = (index, value) => {
    const next = [...list.value];

    next[index] = value;

    emit('update:modelValue', next);
};

const removeItem = (index) => {
    emit('update:modelValue', list.value.filter((item, entry) => entry !== index));
};

const addItem = () => {
    emit('update:modelValue', [...list.value, '']);
};
</script>
<template>
    <div class="flex flex-col gap-xs">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="group flex items-start gap-xs"
        >
            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-(--strategy-accent)"></span>

            <EditableValue
                :model-value="item"
                :multiline="false"
                :read-only="locked"
                class="min-w-0 flex-1 text-sm text-gray-700"
                @update:model-value="updateItem(index, $event)"
            />

            <button
                v-if="!structureLocked"
                type="button"
                class="mt-0.5 shrink-0 text-gray-300 opacity-0 transition-opacity hover:text-red-600 group-hover:opacity-100"
                @click="removeItem(index)"
            >
                <X class="!h-4 !w-4"/>
            </button>
        </div>

        <button
            v-if="!structureLocked"
            type="button"
            class="inline-flex w-fit items-center gap-1 rounded-full border border-dashed border-stone-600 px-2 py-0.5 text-xs text-gray-400 transition-colors hover:border-primary-200 hover:text-primary-500"
            @click="addItem"
        >
            <Plus class="!h-3 !w-3"/>
        </button>
    </div>
</template>
