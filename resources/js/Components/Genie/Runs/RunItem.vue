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

const rules = usePage().props.rules;
const ruleTypes = usePage().props.ruleTypes;

const getRuleType = () => {
    let ruleType = find(rules, ['id', Number(props.item.rule_id)]).rule_type;
    return find(ruleTypes, ['value', Number(ruleType)]).name;
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.id }}
        </TableCell>

        <TableCell >
            {{ getRuleType() }}
        </TableCell>

        <TableCell>
            <RunItemAction
                :itemId="item.id"
            />
        </TableCell>

    </TableRow>
</template>
