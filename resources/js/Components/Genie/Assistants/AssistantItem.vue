<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import AssistantItemAction from "@/Components/Genie/Assistants/AssistantItemAction.vue";
import Flex from "@/Components/Layout/Flex.vue";

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

const assistantTypes = usePage().props.assistantTypes;

const currentType = () => {
    return find(assistantTypes, ['value', Number(props.item.assistant_type)]);
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.name }}

            <Flex
                class="text-gray-500 italic"
                :class="[isFiltered ? 'hidden' : 'lg:hidden']"
            >
                {{ $t(`genie.assistant_type_${currentType().title}`) }}
            </Flex>
        </TableCell>

        <TableCell
            class="hidden"
            :class="[isFiltered ? '' : 'lg:table-cell']"
        >
            {{ $t(`genie.assistant_type_${currentType().title}`) }}
        </TableCell>

        <TableCell>
            <AssistantItemAction :itemId="item.id"/>
        </TableCell>

    </TableRow>
</template>
