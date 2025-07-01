<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import ThreadRunItemAction from "@/Components/Genie/Runs/ThreadRunItemAction.vue";
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

const messagePreview = () => {

    if(props.item.message) {
        if(props.item.message.text.value.length > 15) {
            return props.item.message.text.value.replace(props.item.message.text.value.slice(12), '...');
        }
        else {
            return props.item.message.text.value;
        }
    }

    return '';
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
            {{ messagePreview() }}
        </TableCell>

        <TableCell>
            <ThreadRunItemAction
                :itemId="item.uuid"
            />
        </TableCell>

    </TableRow>
</template>
