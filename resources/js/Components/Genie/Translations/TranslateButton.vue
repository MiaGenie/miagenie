<script setup>
import {ref} from "vue";
import {useI18n} from "vue-i18n";
import PureButton from "@/Components/Button/PureButton.vue";
import Translation from "@/Icons/Genie/Translation.vue";
import TranslationsModal from "@/Components/Genie/Translations/TranslationsModal.vue";

const {t: $t} = useI18n();

const props = defineProps({
    type: {
        type: String,
        required: true,
    },
    id: {
        type: String,
        default: null,
    },
    attribute: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
    /**
     * The editor passes its form's dirty state: translating reads the saved record, so an
     * unsaved form would be translated from stale text.
     */
    disabled: {
        type: Boolean,
        default: false,
    },
});

const showModal = ref(false);
</script>
<template>
    <span v-if="id" class="inline-flex ml-xs align-middle">
        <PureButton
            :disabled="disabled"
            v-tooltip="disabled ? $t('genie.translate_requires_save') : $t('genie.translate')"
            class="disabled:opacity-40"
            @click="showModal = true"
        >
            <template #icon>
                <Translation class="w-5 h-5"/>
            </template>
        </PureButton>

        <TranslationsModal
            :show="showModal"
            :type="type"
            :id="id"
            :attribute="attribute"
            :label="label"
            @close="showModal = false"
        />
    </span>
</template>
