<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {reduce} from "lodash";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import BriefingItemAction from "./BriefingItemAction.vue";

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

            <Badge :variant="percentage == 100 ? 'success' : 'error'">
                {{ percentage + '%'}}
            </Badge>

        </TableCell>

        <TableCell class="hidden md:table-cell">
            <Badge :variant="item.runStatus ? item.runStatus === 'COMPLETE' ? 'success' : 'info' : 'neutral'">
                {{ item.runStatus ? item.runStatus === 'COMPLETE' ? $t('genie.done') : $t('genie.working') : $t('genie.open') }}
            </Badge>
        </TableCell>

        <TableCell>
            <BriefingItemAction v-if="!item.runStatus" :itemId="item.id"/>
        </TableCell>
    </TableRow>
</template>
