<script setup>
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import GripVertical from "@/Icons/GripVertical.vue";
import RuleStepItemAction from "./RuleStepItemAction.vue";


const props = defineProps({
    step: {
        type: Object,
        required: true
    },
    editingPositions: {
        type: Boolean,
        default: false
    }
})

const rule = usePage().props.rule;

</script>
<template>
    <TableRow :hoverable="true">
        <TableCell
            v-if="editingPositions"
            class="w-10"
        >
            <GripVertical class="handle transition ease-in-out duration-200 text-gray-400 hover:text-gray-700"/>
        </TableCell>

        <TableCell>
            {{ step.name }}

            <Flex class="text-gray-500 italic md:hidden">
                {{ step.output }}
            </Flex>
        </TableCell>

        <TableCell class="hidden md:table-cell">
            {{ step.output }}
        </TableCell>

        <TableCell>
            <RuleStepItemAction
                v-if="!editingPositions"
                :item="step"
                :rule="rule"
            />
        </TableCell>
    </TableRow>
</template>
