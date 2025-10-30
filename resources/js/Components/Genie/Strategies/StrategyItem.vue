<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {find, reduce} from "lodash";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import StrategyItemAction from "./StrategyItemAction.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const identifier = inject("identifier");
const fieldList = inject("fieldList");
const runStatus = inject("runStatus");

const itemRunStatus = () => {
    return find(runStatus, ['value',props.item.status]);
}

const itemBadge = () => {
    return itemRunStatus().isError ? 'error' :
        itemRunStatus().requiresUpdate ? 'warning' :
        itemRunStatus().isComplete ? 'success' : 'info';
}

const fieldsCount = reduce(fieldList, (acc, field) => {
        acc.total ++;
        acc.filled += (props.item?.content !== null && typeof props.item.content[field.code_name] !== "undefined") ? 1 : 0;
        return acc;
    }, {
        'total': 0,
        'filled': 0
    }
);

const itemDate = () => {
    return new Date(props.item.created_at).toLocaleString();
}

const percentage = Number(fieldsCount.filled / fieldsCount.total * 100).toFixed(0);

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ itemDate() }}

            <Flex :responsive="false" class="md:hidden">
                <Badge :variant="itemBadge()">
                    {{ percentage + '%'}}
                </Badge>
            </Flex>

        </TableCell>

        <TableCell class="hidden md:table-cell">
            <Badge :variant="itemBadge()">
                {{ percentage + '%'}}
            </Badge>
        </TableCell>

        <TableCell class="hidden md:table-cell">
            <Badge :variant="itemBadge()">
                {{ itemRunStatus().title }}
            </Badge>
        </TableCell>

        <TableCell>
            <StrategyItemAction
                :itemId="item.id"
                :review="itemRunStatus().requiresUpdate"
                :complete="itemRunStatus().isComplete"
            />
        </TableCell>
    </TableRow>
</template>
