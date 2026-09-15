<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Refresh from "@/Icons/Refresh.vue";

const { t: $t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    /**
     * `fix` keeps the image and asks what to change; `rethink` starts over from a brief.
     */
    mode: {
        type: String,
        default: "fix",
    },
    image: {
        type: [Object, null],
        default: null,
    },
});

const emit = defineEmits(["close", "submit"]);

const text = ref("");

const isFix = computed(() => props.mode === "fix");

watch(
    () => props.show,
    (shown) => {
        if (shown) text.value = isFix.value ? "" : (props.image?.brief ?? "");
    },
);

const submit = () => {
    if (text.value.trim() === "") return;

    emit("submit", {
        mode: props.mode,
        feedback: isFix.value ? text.value.trim() : null,
        brief: isFix.value ? null : text.value.trim(),
    });
};
</script>

<template>
    <DialogModal :show="show" max-width="lg" @close="$emit('close')">
        <template #header>
            {{ $t(isFix ? "genie.post_image_fix" : "genie.post_image_rethink") }}
        </template>

        <template #body>
            <div class="flex gap-md">
                <img
                    v-if="image?.file"
                    :src="image.file.url"
                    :alt="image.file.name"
                    class="w-24 h-24 rounded-lg object-cover shrink-0 bg-gray-50"
                />
                <div class="text-sm text-gray-500">
                    {{ $t(isFix ? "genie.post_image_fix_desc" : "genie.post_image_rethink_desc") }}
                </div>
            </div>

            <VerticalGroup class="form-field mt-lg">
                <template #title>
                    <label for="genie_image_text">
                        {{ $t(isFix ? "genie.post_feedback" : "genie.post_image_brief") }}
                    </label>
                </template>
                <Textarea id="genie_image_text" v-model="text" class="w-full" rows="6" autofocus />
            </VerticalGroup>
        </template>

        <template #footer>
            <SecondaryButton class="mr-xs rtl:mr-0 rtl:ml-xs" @click="$emit('close')">
                {{ $t("general.cancel") }}
            </SecondaryButton>
            <WarningButton :disabled="text.trim() === ''" @click="submit">
                <template #icon>
                    <Refresh />
                </template>
                {{ $t(isFix ? "genie.post_image_fix" : "genie.post_image_rethink") }}
            </WarningButton>
        </template>
    </DialogModal>
</template>
