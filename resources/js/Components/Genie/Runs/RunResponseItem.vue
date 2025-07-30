<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunResponseItemAction from "@/Components/Genie/Runs/RunResponseItemAction.vue";
const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
})

const ruleSteps = usePage().props.ruleSteps;
const ruleSubTypes = usePage().props.ruleSubTypes;
const statusTypes = usePage().props.statusTypes;

const getStepName = (stepId) => {
    return find(ruleSteps, ['id', stepId]).name;
}

const getRuleSubType = (stepId) => {
    let ruleSubTypeId = find(ruleSteps, ['id', stepId]).rule_sub_type
    return find(ruleSubTypes, ['value', ruleSubTypeId]).name;
}

const versionStatus = () => {
    return find(statusTypes, ['value', Number(props.item.status)]).name;
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ getStepName(item.step_id) }}
        </TableCell>

        <TableCell>
            {{ getRuleSubType(item.step_id) }}
        </TableCell>

        <TableCell>
            {{ item.created_at }}
        </TableCell>

        <TableCell>
            {{ versionStatus() }}
        </TableCell>

        <TableCell>
            <RunResponseItemAction
                :itemId="item.id"
            />
        </TableCell>

    </TableRow>
</template>
