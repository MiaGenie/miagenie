<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { groupBy, sortBy } from "lodash";
import ChevronDown from "@/Icons/ChevronDown.vue";

const { t: $t } = useI18n();

const props = defineProps({
    images: {
        type: Array,
        required: true,
    },
});

const blank = "----------";

/**
 * One block per slot: the file currently filling it, and the versions it replaced. A slot whose
 * regeneration failed before anything was stored still has a current file, plus the error.
 */
const slots = computed(() => {
    const byPosition = groupBy(props.images, "position");

    return sortBy(Object.keys(byPosition), Number).map((position) => {
        const versions = sortBy(byPosition[position], "id");
        const current =
            versions.find((image) => !image.superseded_at) ??
            versions[versions.length - 1];

        return {
            position: Number(position),
            current,
            history: versions.filter((image) => image !== current).reverse(),
        };
    });
});

const openHistory = ref({});

const toggleHistory = (position) => {
    openHistory.value[position] = !openHistory.value[position];
};
</script>

<template>
    <div>
        <div v-for="slot in slots" :key="slot.position" class="mb-5">
            <div class="pb-2 pl-2 font-medium">
                {{ $t("genie.idea_image", { n: slot.position }) }}
            </div>

            <div
                class="bg-primary-50 rounded-lg p-3 w-full flex flex-col md:flex-row gap-4"
            >
                <div class="md:w-1/3 shrink-0">
                    <a
                        v-if="slot.current.file"
                        :href="slot.current.file.url"
                        target="_blank"
                        rel="noopener"
                    >
                        <img
                            :src="slot.current.file.url"
                            :alt="slot.current.file.name"
                            class="rounded-lg w-full object-contain bg-white"
                        />
                    </a>
                    <div v-else>{{ blank }}</div>

                    <div
                        v-if="slot.current.revising"
                        class="text-sm text-gray-500 mt-1"
                    >
                        {{ $t("genie.run_step_image_revising") }}
                    </div>
                    <div
                        v-if="slot.current.revision_error"
                        class="text-sm text-red-600 mt-1 break-words"
                    >
                        {{ slot.current.revision_error }}
                    </div>
                </div>

                <div class="md:w-2/3 min-w-0">
                    <div class="text-sm text-gray-500">
                        {{ $t("genie.run_step_image_brief") }}
                    </div>
                    <pre class="wrap-anywhere text-wrap mb-3">{{
                        slot.current.brief ?? blank
                    }}</pre>

                    <div class="text-sm text-gray-500">
                        {{ $t("genie.run_step_image_prompt") }}
                    </div>
                    <pre class="wrap-anywhere text-wrap">{{
                        slot.current.prompt ?? blank
                    }}</pre>

                    <template v-if="slot.current.feedback">
                        <div class="text-sm text-gray-500 mt-3">
                            {{ $t("genie.step_review_message_user") }}
                            <span v-if="slot.current.mode"
                                >({{ slot.current.mode }})</span
                            >
                        </div>
                        <pre class="wrap-anywhere text-wrap">{{
                            slot.current.feedback
                        }}</pre>
                    </template>
                </div>
            </div>

            <div v-if="slot.history.length" class="mt-2 pl-2">
                <button
                    type="button"
                    class="flex items-center gap-1 text-sm text-gray-600"
                    @click="toggleHistory(slot.position)"
                >
                    <ChevronDown
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': openHistory[slot.position] }"
                    />
                    {{ $t("genie.run_step_image_history") }} ({{
                        slot.history.length
                    }})
                </button>

                <div v-if="openHistory[slot.position]" class="mt-2 flex flex-col gap-3">
                    <div
                        v-for="version in slot.history"
                        :key="version.id"
                        class="bg-primary-50 rounded-lg p-3 flex flex-col md:flex-row gap-4 opacity-80"
                    >
                        <div class="md:w-1/4 shrink-0">
                            <a
                                v-if="version.file"
                                :href="version.file.url"
                                target="_blank"
                                rel="noopener"
                            >
                                <img
                                    :src="version.file.url"
                                    :alt="version.file.name"
                                    class="rounded-lg w-full object-contain bg-white"
                                />
                            </a>
                        </div>
                        <div class="md:w-3/4 min-w-0 text-sm">
                            <div class="text-gray-500">
                                {{ version.created_at }}
                                <span v-if="version.mode">· {{ version.mode }}</span>
                            </div>
                            <template v-if="version.feedback">
                                <div class="text-gray-500 mt-2">
                                    {{ $t("genie.step_review_message_user") }}
                                </div>
                                <pre class="wrap-anywhere text-wrap">{{
                                    version.feedback
                                }}</pre>
                            </template>
                            <div class="text-gray-500 mt-2">
                                {{ $t("genie.run_step_image_prompt") }}
                            </div>
                            <pre class="wrap-anywhere text-wrap">{{
                                version.prompt ?? blank
                            }}</pre>
                            <div
                                v-if="version.revision_error"
                                class="text-red-600 mt-2 break-words"
                            >
                                {{ version.revision_error }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
