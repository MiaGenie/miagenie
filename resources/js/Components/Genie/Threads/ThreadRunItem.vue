<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import ThreadRunItemAction from "@/Components/Genie/Threads/ThreadRunItemAction.vue";
const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
})

const ruleType = usePage().props.ruleType;
const ruleSubType = usePage().props.ruleSubType;
const statusTypes = usePage().props.statusTypes;
const versionStatus = () => {
    return find(statusTypes, ['value', Number(props.item.status)]);
}

const statusBadge = () => {
    switch (versionStatus().name) {
        case 'ENABLED':
            return 'success';
        case 'DISABLED':
            return 'dark';
        case 'TESTING':
            return 'warning';
        case 'ARCHIVED':
            return 'info';
        default:
            return '';
    }
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.uuid }}
        </TableCell>

        <TableCell>
            {{ item.step_id }}
        </TableCell>

        <TableCell>
            {{ ruleType }}
        </TableCell>

        <TableCell>
            {{ ruleSubType }}
        </TableCell>

        <TableCell>
            <Badge
                :variant="statusBadge()">
                {{ versionStatus().title }}
            </Badge>
        </TableCell>

        <TableCell>
            {{ item.message ? item.message.text.value : '' }}
        </TableCell>

        <TableCell>
            <ThreadRunItemAction
                :itemId="item.uuid"
            />
        </TableCell>

    </TableRow>
</template>
