<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Alert from "@/Components/Util/Alert.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Refresh from "@/Icons/Refresh.vue";

const { t: $t } = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "submit"]);

const feedback = ref("");

watch(
    () => props.show,
    (shown) => {
        if (shown) feedback.value = "";
    },
);

const submit = () => {
    if (feedback.value.trim() === "") return;
    emit("submit", feedback.value.trim());
};
</script>

<template>
    <DialogModal :show="show" max-width="lg" @close="$emit('close')">
        <template #header>{{ $t("genie.post_regenerate_text") }}</template>

        <template #body>
            <Alert :closeable="false" variant="warning" class="mb-lg">
                {{ $t("genie.post_regenerate_text_warning") }}
            </Alert>

            <VerticalGroup class="form-field">
                <template #title>
                    <label for="genie_post_feedback">{{ $t("genie.post_feedback") }}</label>
                </template>
                <Textarea id="genie_post_feedback" v-model="feedback" class="w-full" rows="5" autofocus />
            </VerticalGroup>
        </template>

        <template #footer>
            <SecondaryButton class="mr-xs rtl:mr-0 rtl:ml-xs" @click="$emit('close')">
                {{ $t("general.cancel") }}
            </SecondaryButton>
            <WarningButton :disabled="feedback.trim() === ''" @click="submit">
                <template #icon>
                    <Refresh />
                </template>
                {{ $t("genie.post_regenerate_text") }}
            </WarningButton>
        </template>
    </DialogModal>
</template>
