<script>
/**
 * The most icons the grid draws at once: the set holds over 7,000, so it shows the first matches
 * and asks for a narrower search instead of building thousands of buttons.
 */
const MAX_RESULTS = 150;

/**
 * `mdiAlphaABox` → `alpha-a-box`, the icon's name on the Material Design Icons site.
 */
const toName = (key) => key.slice(3)
    .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
    .replace(/([A-Z])([A-Z][a-z])/g, "$1-$2")
    .replace(/([A-Za-z])([0-9])/g, "$1-$2")
    .toLowerCase();

let iconSet = null;

/**
 * The icon set, loaded on the first open and shared by every picker on the page.
 *
 * The dynamic import keeps `@mdi/js` in a chunk of its own, downloaded only when an admin opens a
 * picker; the pages that show an icon draw its stored path and never load the set.
 */
const loadIcons = () => {
    iconSet ??= import("@mdi/js")
        .then((mdi) => Object.entries(mdi)
            .filter(([key, path]) => key.startsWith("mdi") && typeof path === "string")
            .map(([key, path]) => ({name: `mdi:${toName(key)}`, label: toName(key), path})))
        .catch((error) => {
            iconSet = null;

            throw error;
        });

    return iconSet;
};
</script>

<script setup>
import {computed, ref, shallowRef} from "vue";
import {useI18n} from "vue-i18n";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import Input from "@/Components/Form/Input.vue";
import MdiIcon from "@/Components/DataDisplay/Genie/MdiIcon.vue";
import ChevronDown from "@/Icons/ChevronDown.vue";

/**
 * Picks a Material Design Icon. `v-model` is the icon's name (`mdi:rocket-launch`) and
 * `v-model:path` its SVG path, which is what gets drawn wherever the icon is shown.
 */
const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    path: {
        type: String,
        default: "",
    },
    id: {
        type: String,
        default: null,
    },
    error: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "update:path"]);

const {t: $t} = useI18n();

const icons = shallowRef([]);
const loading = ref(false);
const failed = ref(false);
const search = ref("");

const label = computed(() => (props.modelValue ?? "").replace(/^mdi:/, ""));

const matches = computed(() => {
    const term = search.value.trim().toLowerCase().replace(/\s+/g, "-");

    return term ? icons.value.filter((icon) => icon.label.includes(term)) : icons.value;
});

const visible = computed(() => matches.value.slice(0, MAX_RESULTS));

const open = async () => {
    if (icons.value.length || loading.value) {
        return;
    }

    loading.value = true;
    failed.value = false;

    try {
        icons.value = await loadIcons();
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
};

const select = (icon) => {
    emit("update:modelValue", icon.name);
    emit("update:path", icon.path);
};

const clear = () => {
    emit("update:modelValue", "");
    emit("update:path", "");
};
</script>

<template>
    <Dropdown
        placement="bottom-start"
        width-classes="w-80"
        :content-classes="['p-sm', 'bg-white']"
    >
        <template #trigger>
            <button
                :id="id"
                type="button"
                :class="error ? 'border-red-600' : 'border-stone-600'"
                class="flex w-full items-center gap-xs rounded-md border bg-white px-3 py-2 text-left outline-hidden transition-colors duration-200 ease-in-out focus:border-primary-200 focus:ring-3 focus:ring-primary-200/50"
                @click="open"
            >
                <MdiIcon v-if="path" :path="path" class="shrink-0 text-gray-700"/>
                <span :class="label ? 'text-black' : 'text-gray-400'" class="min-w-0 flex-1 truncate">
                    {{ label || $t("genie.icon_picker_none") }}
                </span>
                <ChevronDown class="!h-4 !w-4 shrink-0 text-gray-400"/>
            </button>
        </template>

        <template #header>
            <Input v-model="search" type="search" :placeholder="$t('genie.icon_picker_search')"/>
        </template>

        <template #content>
            <p v-if="loading" class="py-sm text-center text-sm text-gray-400">
                {{ $t("genie.icon_picker_loading") }}
            </p>

            <p v-else-if="failed" class="py-sm text-center text-sm text-red-600">
                {{ $t("genie.icon_picker_failed") }}
            </p>

            <template v-else>
                <div v-if="visible.length" class="grid max-h-64 grid-cols-8 gap-1 overflow-y-auto">
                    <button
                        v-for="icon in visible"
                        :key="icon.name"
                        type="button"
                        :title="icon.label"
                        :aria-label="icon.label"
                        :class="icon.name === modelValue
                            ? 'bg-primary-50 text-primary-500 ring-2 ring-primary-500'
                            : 'text-gray-700 hover:bg-primary-50 hover:text-primary-500'"
                        class="flex aspect-square items-center justify-center rounded-md transition-colors"
                        @click="select(icon)"
                    >
                        <MdiIcon :path="icon.path"/>
                    </button>
                </div>

                <p v-else class="py-sm text-center text-sm text-gray-400">
                    {{ $t("genie.icon_picker_empty") }}
                </p>

                <p v-if="matches.length > visible.length" class="mt-xs text-xs text-gray-400">
                    {{ $t("genie.icon_picker_narrow", {count: visible.length}) }}
                </p>

                <button
                    v-if="modelValue"
                    type="button"
                    class="mt-sm w-full rounded-md py-1 text-sm text-red-600 transition-colors hover:bg-red-50"
                    @click="clear"
                >
                    {{ $t("genie.icon_picker_clear") }}
                </button>
            </template>
        </template>
    </Dropdown>
</template>
