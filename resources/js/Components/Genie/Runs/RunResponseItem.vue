<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import RunResponseItemAction from "@/Components/Genie/Runs/RunResponseItemAction.vue";
const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
})

const ruleType = usePage().props.ruleType;
const ruleSteps = usePage().props.ruleSteps;
const ruleSubTypes = usePage().props.ruleSubTypes;
const statusTypes = usePage().props.statusTypes;

const ruleSubType = (ruleSubTypeId) => {
    return find(ruleSubTypes, ['value', Number(ruleSubTypeId)]);
}

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

const messagePreview = (message) => {

    if(message) {
        if(message.length > 15) {
            return message.replace(message.slice(12), '...');
        }
        else {
            return message;
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
            {{ ruleSubType(ruleSteps.find(({id}) => id === item.step_id).rule_sub_type).title }}
        </TableCell>

        <TableCell>
            <Badge
                :variant="statusBadge()">
                {{ versionStatus().title }}
            </Badge>
        </TableCell>

        <TableCell>
            {{ messagePreview(ruleSteps.find(({id}) => id === item.step_id).message) }}
        </TableCell>

        <TableCell>
            <RunResponseItemAction
                :itemId="item.uuid"
            />
        </TableCell>

    </TableRow>
</template>
