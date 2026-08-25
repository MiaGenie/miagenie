<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunStepItemAction from "@/Components/Genie/Runs/RunStepItemAction.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
})

const ruleSteps = usePage().props.ruleSteps;
const ruleSubTypes = usePage().props.ruleSubTypes;
const runStatus = usePage().props.runStatus;

/**
 * A step whose rule step was deleted keeps its row with `step_id` null, so nothing here can assume
 * the definition is still there.
 */
const ruleStep = () => find(ruleSteps, ['id', props.item.step_id]);

const stepName = () => ruleStep()?.name ?? '—';

const ruleSubType = () => {
    const subType = find(ruleSubTypes, ['value', ruleStep()?.rule_sub_type]);

    return subType ? subType.name : '—';
}

const itemStatus = () => {
    return find(runStatus, ['value', Number(props.item.status)])?.name ?? '—';
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.position }}
        </TableCell>

        <TableCell>
            {{ stepName() }}
        </TableCell>

        <TableCell>
            {{ ruleSubType() }}
        </TableCell>

        <TableCell>
            {{ item.created_at }}
        </TableCell>

        <TableCell>
            {{ item.duration !== null ? `${item.duration}s` : '—' }}
        </TableCell>

        <TableCell>
            {{ itemStatus() }}
        </TableCell>

        <TableCell>
            <RunStepItemAction
                :item="item"
            />
        </TableCell>

    </TableRow>
</template>
