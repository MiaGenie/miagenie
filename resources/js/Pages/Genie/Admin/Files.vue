<script setup>
import {computed, provide, ref} from "vue";
import {Head} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import useFile from "@/Composables/Genie/useFile.js";
import AdminLayout from "@/Layouts/Admin.vue";
import DangerButton from "@/Components/Button/DangerButton.vue"
import PureDangerButton from "@/Components/Button/PureDangerButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import SelectableBar from "@/Components/DataDisplay/SelectableBar.vue";
import FileUploads from "@/Components/Genie/Files/FileUploads.vue";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import Panel from "@/Components/Surface/Panel.vue";
import TrashIcon from "@/Icons/Trash.vue";

defineOptions({layout: AdminLayout});

const props = defineProps({
    mimeTypes: {
        type: Object,
        required: true
    },
})

provide('mimeTypes', props.mimeTypes);

const {
    isDeleting,
    deletePermanently
} = useFile('genie.admin.files.fetchUploads');

const {t: $t} = useI18n()
const sourceProperties = ref();

const selectedItems = computed(() => {
    return sourceProperties.value ? sourceProperties.value.selected : [];
})

const deselectAll = () => {
    sourceProperties.value.deselectAll()
}

const confirmationDeletion = ref(false);

const deleteSelectedItems = () => {
    const items = selectedItems.value.map((item) => item.id);

    deletePermanently(items, () => {
        deselectAll();
        sourceProperties.value.removeItems(items);
        confirmationDeletion.value = false;
    })
}
</script>
<template>
    <Head :title="$t('genie.file_library')"/>

    <div class="w-full mx-auto row-py mb-2xl">
        <PageHeader :title="$t('genie.file_library')"/>

        <div class="w-full row-px mt-lg">
            <Panel>
                <FileUploads
                    ref="sourceProperties"
                    :columns="2"
                />

                <SelectableBar
                    :count="selectedItems.length"
                    @close="deselectAll()"
                >
                    <PureDangerButton
                        @click="confirmationDeletion = true"
                        v-tooltip="$t('general.delete')"
                    >
                        <TrashIcon/>
                    </PureDangerButton>
                </SelectableBar>
            </Panel>
        </div>
    </div>

    <ConfirmationModal
        :show="confirmationDeletion"
        variant="danger"
        @close="confirmationDeletion = false"
    >
        <template #header>
            {{ $t("genie.delete_file") }}
        </template>

        <template #body>
            {{ $t("genie.do_you_want_delete") }}
        </template>

        <template #footer>
            <SecondaryButton
                @click="confirmationDeletion = false"
                class="mr-xs rtl:mr-0 rtl:ml-xs"
            >
                {{ $t("general.cancel") }}
            </SecondaryButton>

            <DangerButton
                :isLoading="isDeleting"
                :disabled="isDeleting"
                @click="deleteSelectedItems"
            >
                {{ $t("general.delete") }}
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>
