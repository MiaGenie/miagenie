<script setup>
import {find, pickBy, throttle} from "lodash";
import {useI18n} from "vue-i18n";
import {router, usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import ThreadItemAction from "@/Components/Genie/Threads/ThreadItemAction.vue";
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
const ruleSubTypes = usePage().props.ruleSubTypes;

const getRuleType = () => {
    let ruleType = find(rules, ['id', Number(props.item.rule_id)]).rule_type;
    return find(ruleTypes, ['value', Number(ruleType)]).name;
}

const getRuleSubType = () => {
    let ruleSubType = find(rules, ['id', Number(props.item.rule_id)]).rule_sub_type;
    return find(ruleSubTypes, ['value', Number(ruleSubType)]).name;
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.id }}
        </TableCell>

        <TableCell class="hidden md:table-cell">
            {{ getRuleType() }}
        </TableCell>

        <TableCell class="hidden md:table-cell">
            {{ getRuleSubType() }}
        </TableCell>

        <TableCell>
            <ThreadItemAction
                :itemId="item.id"
            />
        </TableCell>

    </TableRow>
</template>
