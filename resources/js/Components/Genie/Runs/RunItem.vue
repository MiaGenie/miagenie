<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunItemAction from "@/Components/Genie/Runs/RunItemAction.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    isFiltered: {
        type: Boolean,
        default: false
    }
})

const workspaces = usePage().props.workspaces;
const rules = usePage().props.rules;
const versions = usePage().props.versions;
const ruleTypes = usePage().props.ruleTypes;
const runStatus = usePage().props.runStatus;

const getRuleType = () => {
    let ruleType = find(rules, ['id', props.item.rule_id]).rule_type;
    return find(ruleTypes, ['value', ruleType]).name;
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ workspaces[item.workspace_id]  + item.workspace_id }}
        </TableCell>

        <TableCell>
            {{ find(versions, ['id', find(rules, ['id', props.item.rule_id] ).version_id]).name }}
        </TableCell>

        <TableCell >
            {{ getRuleType() }}
        </TableCell>

        <TableCell >
            {{ props.item.created_at }}
        </TableCell>

        <TableCell >
            {{ find(runStatus,['value', props.item.status]).name }}
        </TableCell>

        <TableCell>
            <RunItemAction
                :item="item"
            />
        </TableCell>

    </TableRow>
</template>
