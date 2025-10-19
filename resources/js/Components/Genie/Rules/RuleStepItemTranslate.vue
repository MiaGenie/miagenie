<script setup>
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import RuleStepItemTranslateAction from "@/Components/Genie/Rules/RuleStepItemTranslateAction.vue";

const props = defineProps({
    step: {
        type: Object,
        required: true
    }
})

const rule = usePage().props.rule;

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ step.name }}

            <Flex class="text-gray-500 italic md:hidden">
                {{ step.output }}
            </Flex>
        </TableCell>

        <TableCell>
            <RuleStepItemTranslateAction
                :item="step"
                :rule="rule"
            />
        </TableCell>

    </TableRow>
</template>
