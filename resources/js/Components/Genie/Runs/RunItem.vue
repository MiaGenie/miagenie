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

/**
 * A run keeps its row when the rule it ran is deleted, so neither the rule nor anything reached
 * through it can be taken for granted.
 */
const rule = () => find(rules, ['id', props.item.rule_id]);

const versionName = () => find(versions, ['id', rule()?.version_id])?.name ?? '—';

const ruleType = () => find(ruleTypes, ['value', rule()?.rule_type])?.name ?? '—';

const status = () => find(runStatus, ['value', Number(props.item.status)])?.name ?? '—';

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ workspaces[item.workspace_id] ?? '—' }}
        </TableCell>

        <TableCell>
            {{ versionName() }}
        </TableCell>

        <TableCell>
            {{ ruleType() }}
        </TableCell>

        <TableCell>
            {{ item.created_at }}
        </TableCell>

        <TableCell>
            {{ status() }}
        </TableCell>

        <TableCell>
            <RunItemAction
                :item="item"
            />
        </TableCell>

    </TableRow>
</template>
