<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import Badge from "@/Components/DataDisplay/Badge.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import ChevronDown from "@/Icons/ChevronDown.vue";

const { t: $t } = useI18n();

const props = defineProps({
    image: {
        type: Object,
        required: true,
    },
    locked: {
        type: Boolean,
        default: false,
    },
    /**
     * Which regeneration actions the rule supports; a missing step means no button.
     */
    actions: {
        type: Object,
        default: () => ({}),
    },
    /**
     * The media ids the post carries right now (the editor's live form), so the state follows the
     * media strip without a reload.
     */
    postMediaIds: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["fix", "rethink", "reattach", "restore"]);

/**
 * Mixpost's version is the truth: in post, removed by the user, or deleted from the media library.
 */
const state = computed(() => {
    if (props.image.media_id !== null && props.postMediaIds.includes(String(props.image.media_id))) return "in_post";
    if (props.image.media_exists) return "removed";
    return "deleted";
});

const stateBadge = computed(
    () => ({ in_post: "success", removed: "warning", deleted: "error" })[state.value],
);

const showHistory = ref(false);

const disabled = computed(() => props.locked || props.image.revising || !props.image.file);
</script>

<template>
    <div
        class="border border-gray-100 rounded-lg p-md flex flex-col gap-sm"
        :class="{ 'opacity-60': state !== 'in_post' && !image.revising }"
    >
        <div class="flex items-center justify-between">
            <div class="font-medium">{{ $t("genie.idea_image", { n: image.position }) }}</div>
            <Badge :variant="stateBadge">{{ $t("genie.post_image_" + state) }}</Badge>
        </div>

        <div class="relative">
            <a v-if="image.file" :href="image.file.url" target="_blank" rel="noopener">
                <img
                    :src="image.file.url"
                    :alt="image.file.name"
                    class="rounded-lg w-full max-h-64 object-contain bg-gray-50"
                />
            </a>
            <div v-else class="text-sm text-gray-500">----------</div>

            <div
                v-if="image.revising"
                class="absolute inset-0 flex items-center justify-center bg-white/75 rounded-lg text-sm text-gray-600"
            >
                {{ $t("genie.run_step_image_revising") }}
            </div>
        </div>

        <div v-if="image.revision_error" class="text-sm text-red-600 break-words">
            {{ image.revision_error }}
        </div>

        <div v-if="image.brief" class="text-sm">
            <div class="text-gray-500">{{ $t("genie.post_image_brief") }}</div>
            <div class="whitespace-pre-wrap break-words">{{ image.brief }}</div>
        </div>

        <div class="flex flex-wrap gap-sm mt-auto">
            <SecondaryButton v-if="state === 'removed'" size="sm" :disabled="disabled" @click="$emit('reattach')">
                {{ $t("genie.post_image_reattach") }}
            </SecondaryButton>
            <WarningButton v-if="actions.fix" size="sm" :disabled="disabled" @click="$emit('fix')">
                {{ $t("genie.post_image_fix") }}
            </WarningButton>
            <SecondaryButton v-if="actions.rethink" size="sm" :disabled="disabled" @click="$emit('rethink')">
                {{ $t("genie.post_image_rethink") }}
            </SecondaryButton>
        </div>

        <div v-if="image.history.length" class="text-sm">
            <PureButton class="flex items-center gap-1 text-gray-500" @click="showHistory = !showHistory">
                <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showHistory }" />
                {{ $t("genie.run_step_image_history") }} ({{ image.history.length }})
            </PureButton>

            <div v-if="showHistory" class="mt-sm flex flex-col gap-sm">
                <div
                    v-for="version in image.history"
                    :key="version.id"
                    class="flex gap-sm items-start bg-gray-50 rounded-lg p-sm"
                >
                    <a v-if="version.file" :href="version.file.url" target="_blank" rel="noopener" class="shrink-0 w-16">
                        <img :src="version.file.url" :alt="version.file.name" class="rounded w-16 h-16 object-cover" />
                    </a>
                    <div class="min-w-0">
                        <div class="text-gray-500">
                            {{ version.created_at }}
                            <span v-if="version.mode">· {{ $t("genie.post_image_" + version.mode) }}</span>
                        </div>
                        <div v-if="version.feedback" class="whitespace-pre-wrap break-words">{{ version.feedback }}</div>
                        <div v-if="version.revision_error" class="text-red-600 break-words">{{ version.revision_error }}</div>
                        <SecondaryButton
                            v-if="version.file"
                            size="sm"
                            class="mt-xs"
                            :disabled="locked"
                            @click="$emit('restore', version)"
                        >
                            {{ $t("genie.post_image_restore") }}
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
