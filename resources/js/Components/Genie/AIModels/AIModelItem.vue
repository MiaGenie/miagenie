<script setup>
import {useI18n} from "vue-i18n";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import AIModelItemAction from "@/Components/Genie/AIModels/AIModelItemAction.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const status = (option) => {
    return option == 1 ? "Yes" : "No";
}


</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.model }}

            <Flex class="text-gray-500 italic lg:hidden">
                {{ $t(`genie.assistant_type`) }}
            </Flex>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            <Badge :variant="item.file_search == 1 ? 'success' : 'error'">
                {{ status(item.file_search) }}
            </Badge>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            <Badge :variant="item.json_schema == 1 ? 'success' : 'error'">
                {{ status(item.json_schema) }}
            </Badge>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            <Badge :variant="item.temperature_top_p == 1 ? 'success' : 'error'">
                {{ status(item.temperature_top_p) }}
            </Badge>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            <Badge :variant="item.reasoning_effort == 1 ? 'success' : 'error'">
                {{ status(item.reasoning_effort) }}
            </Badge>
        </TableCell>

        <TableCell>
            <AIModelItemAction :itemId="item.id"/>
        </TableCell>

    </TableRow>
</template>
