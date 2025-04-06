<script setup>
import {computed, inject, ref} from "vue";
import useFile from "@/Composables/Genie/useFile";
import DialogModal from "@/Components/Modal/DialogModal.vue"
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import VectorAddFileUploads from "@/Components/Genie/Vectors/VectorAddFileUploads.vue";
import Preloader from "@/Components/Util/Preloader.vue"
import XIcon from "@/Icons/X.vue"


const props = defineProps({
    maxSelection: {
        type: Number,
        default: 1,
    }
})

const mimeTypes = inject("mimeTypes");
const emit = defineEmits(['insert']);
const show = ref(false);

const {
    isDownloading,
} = useFile('genie.admin.files.fetchUploads');

const sourceProperties = ref();

const selectedItems = computed(() => {
    return sourceProperties.value ? sourceProperties.value.selected : [];
})

const deselectAll = () => {
    sourceProperties.value.deselectAll()
}

const close = () => {
    deselectAll();
    show.value = false;
};

const insert = () => {
    emit('insert', {
        items: selectedItems.value
    });

    close();
}
</script>
<template>
    <div @click="show = !show">
        <slot/>
    </div>

    <DialogModal :show="show"
                 max-width="xl"
                 :closeable="true"
                 :scrollable-body="true"
                 @close="close">
        <template #header>
            {{ $t('genie.add_file') }}
        </template>

        <template #body>
            <Preloader v-if="isDownloading" :opacity="75">
                {{ $t('media.downloading') }}
            </Preloader>

            <div class="mt-lg">
                <VectorAddFileUploads ref="sourceProperties" />
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="close" class="mr-xs rtl:mr-0 rtl:ml-xs">{{ $t('general.cancel') }}</SecondaryButton>

            <template v-if="selectedItems.length">
                <SecondaryButton @click="deselectAll" v-tooltip.top="$t('general.dismiss')" class="mr-xs rtl:mr-0 rtl:ml-xs">
                    <template #icon>
                        <XIcon/>
                    </template>
                </SecondaryButton>

                <PrimaryButton @click="insert">{{ $t('general.insert') }} {{ selectedItems.length }}
                    {{ $t('general.items') }}
                </PrimaryButton>
            </template>
        </template>
    </DialogModal>
</template>
