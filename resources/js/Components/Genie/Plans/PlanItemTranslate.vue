<script setup>
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import PlanItemTranslateAction from "@/Components/Genie/Plans/PlanItemTranslateAction.vue";

const props = defineProps({
    plan: {
        type: Object,
        required: true
    }
})

const rule = usePage().props.rule;

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ plan.name }}
        </TableCell>

        <TableCell>
            <PlanItemTranslateAction
                :item="plan"
            />
        </TableCell>

    </TableRow>
</template>
