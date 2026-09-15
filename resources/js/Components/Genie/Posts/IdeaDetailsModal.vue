<script setup>
import { useI18n } from "vue-i18n";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ProviderIcon from "@/Components/Account/ProviderIcon.vue";
import ArrowTopRightOnSquare from "@/Icons/ArrowTopRightOnSquare.vue";

const { t: $t } = useI18n();

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    idea: {
        type: [Object, null],
        default: null,
    },
});

defineEmits(["close"]);

/**
 * The idea's texts, in the order they read; each has a `genie.idea_*` label.
 */
const texts = ["objective", "suggested_content", "multimedia_suggestion", "production_instructions"];
</script>

<template>
    <DialogModal :show="show" max-width="2xl" scrollable-body @close="$emit('close')">
        <template #header>
            {{ idea?.theme }}
        </template>

        <template #body>
            <template v-if="idea">
                <div class="flex flex-wrap gap-xs text-xs mb-lg">
                    <Badge v-if="idea.format_label">{{ idea.format_label }}</Badge>
                    <Badge v-if="idea.stage_label" variant="info">{{ idea.stage_label }}</Badge>
                    <Badge v-if="idea.content_pillar" variant="dark">{{ idea.content_pillar }}</Badge>
                </div>

                <div v-for="name in texts" :key="name" class="mb-lg">
                    <template v-if="idea[name]">
                        <div class="font-medium">{{ $t("genie.idea_" + name) }}</div>
                        <div class="whitespace-pre-wrap break-words text-gray-700">{{ idea[name] }}</div>
                    </template>
                </div>

                <div v-if="idea.images?.length" class="mb-lg">
                    <div class="font-medium">{{ $t("genie.idea_images") }}</div>
                    <ol class="list-decimal ml-lg text-gray-700">
                        <li v-for="(brief, index) in idea.images" :key="index" class="whitespace-pre-wrap break-words mb-xs">
                            {{ brief }}
                        </li>
                    </ol>
                </div>

                <div v-if="idea.publish_to?.length">
                    <div class="font-medium">{{ $t("genie.idea_publish_to") }}</div>
                    <div class="flex flex-wrap items-center gap-sm mt-xs">
                        <span
                            v-for="target in idea.publish_to"
                            :key="target.provider"
                            class="flex items-center gap-xs rounded-full border border-gray-200 px-md py-xs text-sm"
                        >
                            <span class="w-5 h-5 flex items-center justify-center">
                                <ProviderIcon :provider="target.provider" />
                            </span>
                            {{ target.name }}
                        </span>
                    </div>
                </div>
            </template>
        </template>

        <template #footer>
            <SecondaryButton class="mr-xs rtl:mr-0 rtl:ml-xs" @click="$emit('close')">
                {{ $t("general.close") }}
            </SecondaryButton>
            <a v-if="idea?.url" :href="idea.url">
                <PrimaryButton>
                    <template #icon>
                        <ArrowTopRightOnSquare />
                    </template>
                    {{ $t("genie.open_idea") }}
                </PrimaryButton>
            </a>
        </template>
    </DialogModal>
</template>
