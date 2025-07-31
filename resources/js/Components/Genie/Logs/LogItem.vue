<script setup>
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import LogItemAction from "@/Components/Genie/Logs/LogItemAction.vue";
import {find} from "lodash";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
})

const genieTypes = usePage().props.genieTypes;
const genieSyncStatus = usePage().props.genieSyncStatus;

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.id }}
        </TableCell>

        <TableCell>
            {{ find(genieTypes, ['value', item.type]).name }}
        </TableCell>

        <TableCell>
            {{ find(genieSyncStatus, ['value', item.action]).name }}
        </TableCell>

        <TableCell>
            {{ item.duration }}
        </TableCell>

        <TableCell>
            {{ item.created_at }}
        </TableCell>

        <TableCell>
            <LogItemAction
                :itemId="item.id"
            />
        </TableCell>

    </TableRow>
</template>
