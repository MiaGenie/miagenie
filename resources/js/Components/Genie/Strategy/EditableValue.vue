<script setup>
import { computed, nextTick, ref } from "vue";

const props = defineProps({
    modelValue: {
        default: "",
    },
    multiline: {
        type: Boolean,
        default: true,
    },
    placeholder: {
        type: String,
        default: "",
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    /**
     * A fixed list of allowed values renders as a segmented control instead of free text.
     */
    options: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:modelValue"]);

const editing = ref(false);
const draft = ref("");
const input = ref(null);

const value = computed(() => props.modelValue ?? "");

const rows = computed(() => {
    return Math.min(
        12,
        Math.max(2, String(draft.value).split("\n").length + 1),
    );
});

const adjustTextarea = () => {
    input.value.style.height = "auto";
    input.value.style.height = `${input.value.scrollHeight}px`;
};

const open = async () => {
    if (props.readOnly || props.options.length) {
        return;
    }

    draft.value = String(value.value);
    editing.value = true;

    await nextTick();

    input.value?.focus();
    input.value?.setSelectionRange(draft.value.length, draft.value.length);
};

const save = () => {
    editing.value = false;

    if (draft.value !== String(value.value)) {
        emit("update:modelValue", draft.value);
    }
};

const cancel = () => {
    draft.value = String(value.value);
    editing.value = false;
};

const onKeydown = (event) => {
    if (event.key === "Escape") {
        cancel();
        return;
    }

    if (event.key === "Enter" && (!props.multiline || !event.shiftKey)) {
        event.preventDefault();
        save();
    }
};

const choose = (option) => {
    if (props.readOnly) {
        return;
    }

    emit("update:modelValue", option);
};
</script>
<style scoped>
.autoresize {
    resize: none;
    overflow: hidden;
    min-height: 40px;
    max-height: 120px;
}
</style>
<template>
    <div>
        <div v-if="options.length" class="flex flex-wrap gap-1">
            <button
                v-for="option in options"
                :key="option"
                type="button"
                :disabled="readOnly"
                :class="[
                    'rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors',
                    option === value
                        ? 'border-primary-500 bg-primary-500 text-primary-context'
                        : 'border-stone-600 bg-white text-gray-500 hover:border-primary-200 disabled:hover:border-stone-600',
                ]"
                @click="choose(option)"
            >
                {{ option }}
            </button>
        </div>

        <textarea
            v-else-if="editing && multiline"
            ref="input"
            v-model="draft"
            :placeholder="placeholder"
            class="w-full autoresize text-sm resize-none rounded-lg border-0 border-primary-200 bg-primary-50 px-0 py-0 text-inherit outline-none focus:ring-2 focus:ring-primary-200"
            @input="adjustTextarea"
            @focus="adjustTextarea"
            @blur="save"
            @keydown="onKeydown"
        ></textarea>

        <input
            v-else-if="editing"
            ref="input"
            v-model="draft"
            type="text"
            :placeholder="placeholder"
            class="w-full text-sm rounded-lg border-0 border-primary-200 bg-primary-50 px-0 py-0 text-inherit outline-none focus:ring-2 focus:ring-primary-200"
            @blur="save"
            @keydown="onKeydown"
        />

        <p
            v-else
            :class="[
                'group relative whitespace-pre-line rounded-lg px-1 -mx-1 transition-colors',
                readOnly ? '' : 'cursor-text hover:bg-stone-400',
            ]"
            @click="open"
        >
            <span v-if="String(value).length">{{ value }}</span>
            <span v-else class="text-xs italic text-gray-400">{{
                placeholder || "—"
            }}</span>
        </p>
    </div>
</template>
