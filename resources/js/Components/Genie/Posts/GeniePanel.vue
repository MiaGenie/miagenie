<script setup>
import { computed, inject, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import Panel from "@/Components/Surface/Panel.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Alert from "@/Components/Util/Alert.vue";
import Flex from "@/Components/Layout/Flex.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Sparkles from "@/Icons/Sparkles.vue";
import Refresh from "@/Icons/Refresh.vue";
import ArrowTopRightOnSquare from "@/Icons/ArrowTopRightOnSquare.vue";
import GenieImageSlot from "@/Components/Genie/Posts/GenieImageSlot.vue";
import RegenerateTextModal from "@/Components/Genie/Posts/RegenerateTextModal.vue";
import RegenerateImageModal from "@/Components/Genie/Posts/RegenerateImageModal.vue";
import IdeaDetailsModal from "@/Components/Genie/Posts/IdeaDetailsModal.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import InformationCircle from "@/Icons/InformationCircle.vue";

const { t: $t } = useI18n();

const props = defineProps({
    geniePost: {
        type: Object,
        required: true,
    },
    postId: {
        type: [String, null],
        default: null,
    },
    busy: {
        type: Boolean,
        default: false,
    },
    /**
     * The media ids the post carries right now, from the editor's live form.
     */
    postMediaIds: {
        type: Array,
        default: () => [],
    },
    /**
     * Awaited before every action: the page saves what the user typed so a job never overwrites
     * unsaved work. Returns a promise.
     */
    beforeAction: {
        type: Function,
        default: () => Promise.resolve(),
    },
});

/**
 * `done` tells the page to re-seed its form once the server answered.
 */
const emit = defineEmits(["done"]);

const workspaceCtx = inject("workspaceCtx");

const showText = ref(false);
const showIdea = ref(false);
const imageAction = ref(null); // { image, mode }
const submitting = ref(false);

const status = computed(() => props.geniePost.status_title ?? "");

const statusBadge = computed(
    () =>
        ({ generating: "warning", ready: "success", failed: "error" })[
            status.value
        ] ?? "neutral",
);

const failed = computed(() => status.value === "failed");

/**
 * No fallback: an action is offered only when the rule carries a prompt written for it.
 */
const actions = computed(() => props.geniePost.actions ?? {});
const hasAnyAction = computed(() => Object.values(actions.value).some(Boolean));

const locked = computed(() => props.busy || submitting.value);

/**
 * Save first, then post; the page re-seeds its form on success.
 */
const act = async (url, data = {}) => {
    submitting.value = true;

    try {
        await props.beforeAction();
    } catch (error) {
        // The save failed and the page already reported it; do not start a job on unsaved content.
        submitting.value = false;
        return;
    }

    await new Promise((resolve) => {
        router.post(url, data, {
            preserveScroll: true,
            onSuccess: () => emit("done"),
            onFinish: () => {
                submitting.value = false;
                resolve();
            },
        });
    });
};

const postRoute = (name, params = {}) =>
    route(`genie.posts.${name}`, {
        workspace: workspaceCtx.id,
        post: props.geniePost.id,
        ...params,
    });

const regenerateText = (feedback) => {
    showText.value = false;
    return act(postRoute("regenerateText"), { feedback });
};

const regenerateImage = ({ mode, feedback, brief }) => {
    const image = imageAction.value?.image;
    imageAction.value = null;

    if (!image?.file) return;

    return act(postRoute("regenerateImage", { file: image.file.id }), {
        mode,
        feedback,
        brief,
    });
};

const reattach = (image) =>
    act(postRoute("reattachImage", { file: image.file.id }));

const retry = () => act(postRoute("retry"));
</script>

<template>
    <Panel>
        <template #title>
            <Flex :responsive="false" class="items-center gap-sm">
                <Sparkles class="w-5 h-5 text-primary-500" />
                <span>{{ $t("genie.genie") }}</span>
                <Badge :variant="statusBadge">{{ $t("genie." + status) }}</Badge>
            </Flex>
        </template>

        <template #action>
            <PureButton
                v-if="geniePost.idea"
                class="flex items-center gap-xs text-sm text-primary-500 hover:underline text-left"
                @click="showIdea = true"
            >
                {{ geniePost.idea.theme }}
                <InformationCircle class="w-4 h-4 shrink-0" />
            </PureButton>
        </template>

        <Alert v-if="failed && geniePost.error" :closeable="false" variant="error" class="mb-lg">
            {{ geniePost.error }}
        </Alert>

        <Alert v-if="busy" :closeable="false" variant="warning" class="mb-lg">
            {{ $t("genie.post_generating") }}
        </Alert>

        <Alert v-if="!hasAnyAction && !busy" :closeable="false" class="mb-lg">
            {{ $t("genie.post_actions_missing_steps") }}
        </Alert>

        <Flex :responsive="false" class="flex-wrap gap-sm">
            <WarningButton v-if="actions.revise_text" :disabled="locked" :isLoading="submitting" @click="showText = true">
                <template #icon>
                    <Refresh />
                </template>
                {{ $t("genie.post_regenerate_text") }}
            </WarningButton>

            <SecondaryButton v-if="failed" :disabled="locked" @click="retry">
                {{ $t("genie.post_retry") }}
            </SecondaryButton>
        </Flex>

        <div v-if="geniePost.images.length" class="mt-lg">
            <div class="font-medium mb-sm">{{ $t("genie.post_images") }}</div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <GenieImageSlot
                    v-for="image in geniePost.images"
                    :key="image.position"
                    :image="image"
                    :actions="actions"
                    :post-media-ids="postMediaIds"
                    :locked="locked"
                    @fix="imageAction = { image, mode: 'fix' }"
                    @rethink="imageAction = { image, mode: 'rethink' }"
                    @reattach="reattach(image)"
                />
            </div>
        </div>

        <RegenerateTextModal
            :show="showText"
            @close="showText = false"
            @submit="regenerateText"
        />

        <IdeaDetailsModal
            :show="showIdea"
            :idea="geniePost.idea"
            @close="showIdea = false"
        />

        <RegenerateImageModal
            :show="imageAction !== null"
            :mode="imageAction?.mode ?? 'fix'"
            :image="imageAction?.image ?? null"
            @close="imageAction = null"
            @submit="regenerateImage"
        />
    </Panel>
</template>
