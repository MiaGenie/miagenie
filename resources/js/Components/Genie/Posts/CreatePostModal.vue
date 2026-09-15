<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import Switch from "@/Components/Form/Switch.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Sparkles from "@/Icons/Sparkles.vue";

const { t: $t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    /**
     * How many posts the action creates; the button reads "Create posts (n)" above one.
     */
    count: {
        type: Number,
        default: 1,
    },
    /**
     * Whether images can be drawn at all (the idea's format carries images).
     */
    imagesAvailable: {
        type: Boolean,
        default: true,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "submit"]);

// Text is always generated; images are the one choice. Off and locked when the format has none.
const images = ref(props.imagesAvailable);

watch(
    () => props.show,
    (shown) => {
        if (shown) images.value = props.imagesAvailable;
    },
);

const submit = () => emit("submit", { images: props.imagesAvailable && images.value });
</script>

<template>
    <DialogModal :show="show" max-width="md" @close="$emit('close')">
        <template #header>
            {{ count > 1 ? $t("genie.create_posts") : $t("genie.create_post") }}
        </template>

        <template #body>
            <div class="flex items-start justify-between gap-md py-sm">
                <div>
                    <div class="font-medium">{{ $t("genie.create_post_text") }}</div>
                    <div class="text-sm text-gray-500">{{ $t("genie.create_post_text_help") }}</div>
                </div>
                <Switch :model-value="true" disabled class="opacity-60 pointer-events-none" />
            </div>

            <div class="flex items-start justify-between gap-md py-sm border-t border-gray-100">
                <div>
                    <div class="font-medium">{{ $t("genie.create_post_images") }}</div>
                    <div class="text-sm text-gray-500">
                        {{ imagesAvailable ? $t("genie.create_post_images_help") : $t("genie.create_post_images_none") }}
                    </div>
                </div>
                <Switch
                    v-model="images"
                    :class="{ 'opacity-60 pointer-events-none': !imagesAvailable }"
                />
            </div>
        </template>

        <template #footer>
            <SecondaryButton class="mr-xs rtl:mr-0 rtl:ml-xs" @click="$emit('close')">
                {{ $t("general.cancel") }}
            </SecondaryButton>
            <WarningButton :disabled="processing" :isLoading="processing" @click="submit">
                <template #icon>
                    <Sparkles />
                </template>
                {{ count > 1 ? $t("genie.create_posts_count", { count }) : $t("genie.create_post") }}
            </WarningButton>
        </template>
    </DialogModal>
</template>
