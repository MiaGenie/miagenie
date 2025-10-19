<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import VersionFieldItemTranslateAction from "@/Components/Genie/Versions/VersionFieldItemTranslateAction.vue";
import {provide} from "vue";

const {t: $t} = useI18n();

const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    isFiltered: {
        type: Boolean,
        default: false
    }
})

const fieldTypes = usePage().props.fieldTypes;
const groupTypes = usePage().props.groupTypes;

const fieldType = () => {
    return find(fieldTypes, ['value', Number(props.field.field_type)]);
}
provide('fieldType', fieldType);

const currentGroup = () => {
    return find(groupTypes, ['value', Number(props.field.group_type)]);
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ field.name }}

            <Flex
                class="text-gray-500 italic"
                :class="[isFiltered ? 'hidden' : 'lg:hidden']"
            >
                {{ currentGroup().title }}
            </Flex>

            <Flex class="text-gray-500 italic sm:hidden">
                {{ $t(`genie.field_type_${fieldType().title}`) }}
            </Flex>
        </TableCell>

        <TableCell
            class="hidden"
            :class="[isFiltered ? '' : 'lg:table-cell']"
        >
            {{ currentGroup().title }}
        </TableCell>

        <TableCell class="hidden sm:table-cell">
            {{ $t(`genie.field_type_${fieldType().title}`) }}
        </TableCell>

        <TableCell>
            <VersionFieldItemTranslateAction
                :item="field"
            />
        </TableCell>
    </TableRow>
</template>
