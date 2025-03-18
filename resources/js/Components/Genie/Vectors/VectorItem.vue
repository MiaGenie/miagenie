<script setup>
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import VectorItemAction from "./VectorItemAction.vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.name }}
        </TableCell>

        <TableCell>
            <VectorItemAction :itemId="item.id"/>
        </TableCell>
    </TableRow>
</template>
