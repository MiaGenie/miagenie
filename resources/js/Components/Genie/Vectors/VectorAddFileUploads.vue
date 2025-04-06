<script setup>
import {onMounted} from "vue";
import {useI18n} from "vue-i18n";
import useFile from "@/Composables/Genie/useFile";
import FileSelectable from "@/Components/Genie/Files/FileSelectable.vue";
import SectionTitle from "@/Components/DataDisplay/SectionTitle.vue";
import File from "@/Components/Genie/Files/File.vue";
import Masonry from "@/Components/Layout/Masonry.vue";

const props = defineProps({
    columns: {
        type: Number,
        default: 1
    }
})

const {t: $t} = useI18n()

const {
    items,
    endlessPagination,
    selected,
    toggleSelect,
    deselectAll,
    removeItems,
    isSelected,
    createObserver,
    downloadFile
} = useFile('genie.admin.files.fetchUploads');

onMounted(() => {
    createObserver();
});

defineExpose({selected, deselectAll, removeItems})
</script>
<template>

    <template v-if="items.length">
        <SectionTitle class="mb-4 mt-lg">{{ $t('media.library') }}</SectionTitle>

        <Masonry
            :items="items"
            :columns="columns"
        >
            <template #default="{item}">
                <FileSelectable
                    v-if="item"
                    :active="isSelected(item)"
                    @click="toggleSelect(item)"
                >
                    <File
                        :media="item"
                        @download="downloadFile(item)"
                    />
                </FileSelectable>
            </template>
        </Masonry>
    </template>

    <div
        ref="endlessPagination"
        class="-z-10 w-full"
    />

</template>
