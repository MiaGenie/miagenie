<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {reduce} from "lodash";
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

const fieldsCount = reduce(fieldList, (acc, field) => {
        acc.total ++;
        acc.filled += props.item.content[field.code_name] ? 1 : 0;
        return acc;
    }, {
        'total': 0,
        'filled': 0
    }
);

const percentage = Number(fieldsCount.filled / fieldsCount.total * 100).toFixed(0);

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.id }}

            <Flex :responsive="false" class="md:hidden">
                <Badge :variant="percentage == 100 ? 'success' : 'error'">
                    {{ percentage + '%'}}
                </Badge>
            </Flex>

        </TableCell>

        <TableCell class="hidden md:table-cell">
            <Badge :variant="percentage == 100 ? 'success' : 'error'">
                {{ percentage + '%'}}
            </Badge>
        </TableCell>

        <TableCell>
            <StrategyItemAction :itemId="item.id"/>
        </TableCell>
    </TableRow>
</template>
