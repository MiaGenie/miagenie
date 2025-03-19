<script setup>
import {computed, inject, defineAsyncComponent} from "vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import X from "@/Icons/X.vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const mimeTypes = inject("mimeTypes");

const fileTypeIcon = computed(() => {
    if (Object.keys(mimeTypes).length > 0) {
        return defineAsyncComponent(
            () => import(
                `../../../Icons/Genie/FileTypes/${mimeTypes[props.item.mime_type].split(" ")[0]}.vue`
            )
        )
    }
})

</script>
<template>
    <TableRow :hoverable="true" class="align-middle">

        <TableCell class="w-6">
            <component :is="fileTypeIcon"></component>
        </TableCell>

        <TableCell>
            <div class="text-sm">{{ item.name }}</div>
        </TableCell>

        <TableCell
            class="w-6"
            :clickable="true"
            @click="$emit('removeFile', item)"
        >
            <X/>
        </TableCell>
    </TableRow>
</template>
