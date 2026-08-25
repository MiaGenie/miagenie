<script setup>
import {computed} from "vue";
import EditableValue from "@/Components/Genie/Strategy/EditableValue.vue";
import Check from "@/Icons/Check.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";

const props = defineProps({
    schema: {
        type: Object,
        default: () => ({}),
    },
    modelValue: {
        default: null,
    },
    label: {
        type: String,
        default: '',
    },
    depth: {
        type: Number,
        default: 0,
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    /**
     * What the sub-field tree says about this node, shaped like the schema:
     * `{editable, properties?, items?}`. Absent means nothing is locked.
     */
    meta: {
        type: Object,
        default: null,
    },
    /**
     * Apply `editable` to structure only — no adding or removing items — and leave values alone.
     * The step review runs this way: it is where the customer corrects what the model wrote.
     */
    structureOnly: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['update:modelValue']);

/**
 * Whether this node's value may be changed, and whether its shape may be.
 *
 * An unlocked page is the first condition of both: `readOnly` is the strategy being approved, or
 * some ancestor page-level lock, and nothing below it is editable regardless of the tree.
 */
const locked = computed(() => props.readOnly || (! props.structureOnly && props.meta?.editable === false));

const structureLocked = computed(() => props.readOnly || props.meta?.editable === false);

const childMeta = (key) => props.meta?.properties?.[key] ?? null;

const itemMeta = (key) => props.meta?.items?.properties?.[key] ?? null;

const isChoiceLocked = (key) => locked.value || (! props.structureOnly && childMeta(key)?.editable === false);

const HEX = /^#[0-9a-f]{3,8}$/i;

/**
 * Optional properties are emitted as a union with null, so the meaningful type is the one that
 * is not "null".
 */
const typeOf = (schema) => {
    const type = schema?.type;

    return Array.isArray(type) ? type.find((entry) => entry !== 'null') : type;
};

const type = computed(() => typeOf(props.schema));
const properties = computed(() => props.schema?.properties ?? {});
const items = computed(() => props.schema?.items ?? {});
const itemType = computed(() => typeOf(items.value));

const list = computed(() => Array.isArray(props.modelValue) ? props.modelValue : []);

/**
 * An object whose children are all objects reads best as a grid of cards rather than a stack.
 */
const isCardGrid = computed(() => {
    const children = Object.values(properties.value);

    return children.length > 1 && children.every((child) => typeOf(child) === 'object');
});

/**
 * An object whose children are all booleans is a multiple choice — the shape a CHECKBOX field
 * compiles to now that its options live in the sub-field tree.
 */
const isChoiceGroup = computed(() => {
    const children = Object.values(properties.value);

    return children.length > 0 && children.every((child) => typeOf(child) === 'boolean');
});

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

const labelOf = (key, schema) => schema?.title || key.replace(/_/g, ' ');

/**
 * The first text property of an item stands in as its heading.
 */
const itemHeading = (item) => {
    if (typeof item === 'string') {
        return item;
    }

    const key = Object.keys(items.value.properties ?? {})
        .find((entry) => typeOf(items.value.properties[entry]) === 'string');

    return key ? item?.[key] : '';
};

const itemBodyKeys = computed(() => {
    const keys = Object.keys(items.value.properties ?? {});
    const heading = keys.find((entry) => typeOf(items.value.properties[entry]) === 'string');

    return keys.filter((entry) => entry !== heading);
});

const updateProperty = (key, value) => {
    emit('update:modelValue', {...(props.modelValue ?? {}), [key]: value});
};

const updateItem = (index, value) => {
    const next = [...list.value];

    next[index] = value;

    emit('update:modelValue', next);
};

const removeItem = (index) => {
    emit('update:modelValue', list.value.filter((item, entry) => entry !== index));
};

const addItem = () => {
    emit('update:modelValue', [...list.value, itemType.value === 'object' ? {} : '']);
};

const isHex = (value) => typeof value === 'string' && HEX.test(value.trim());
</script>
<template>
    <!-- multiple choice: an object of booleans -->
    <div v-if="type === 'object' && isChoiceGroup" class="grid gap-xs sm:grid-cols-2">
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
                    ? 'border-primary-500 bg-primary-50 text-black'
                    : 'border-stone-600 bg-white hover:border-primary-200 hover:bg-primary-50'
            ]"
            @click="toggleChoice(key)"
        >
            <span class="flex items-start justify-between gap-xs">
                <span>{{ labelOf(key, child) }}</span>
                <Check v-if="isChosen(key)" class="!h-5 !w-5 shrink-0 text-primary-500"/>
            </span>
        </button>
    </div>

    <!-- object -->
    <div v-else-if="type === 'object'" :class="isCardGrid ? 'grid gap-sm sm:grid-cols-2' : 'flex flex-col gap-md'">
        <div
            v-for="(child, key) in properties"
            :key="key"
            :class="isCardGrid ? 'rounded-2xl border border-stone-600 bg-white p-md' : ''"
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

    <!-- array of strings -->
    <div v-else-if="type === 'array' && itemType !== 'object'" class="flex flex-col gap-xs">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="group flex items-start gap-xs"
        >
            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"></span>

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

    <!-- array of objects -->
    <div v-else-if="type === 'array'" class="flex flex-col gap-sm">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="group relative rounded-2xl border border-stone-600 bg-white p-md"
        >
            <div class="mb-sm flex items-start justify-between gap-xs">
                <p class="text-sm font-semibold text-black">
                    <span class="mr-1.5 text-xs font-bold text-primary-500">{{ index + 1 }}</span>
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

    <!-- a boolean on its own -->
    <button
        v-else-if="type === 'boolean'"
        type="button"
        :disabled="locked"
        :aria-pressed="Boolean(modelValue)"
        class="inline-flex items-center gap-xs text-sm text-gray-700"
        @click="emit('update:modelValue', ! modelValue)"
    >
        <span
            :class="[
                'flex h-5 w-5 items-center justify-center rounded-md border-2 transition-colors',
                modelValue ? 'border-primary-500 bg-primary-500 text-primary-context' : 'border-stone-600 bg-white'
            ]"
        >
            <Check v-if="modelValue" class="!h-4 !w-4"/>
        </span>
        {{ label || (modelValue ? 'true' : 'false') }}
    </button>

    <!-- string -->
    <div v-else class="flex items-start gap-xs">
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
