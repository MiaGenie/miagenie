<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {find, size} from "lodash";
import {usePage} from "@inertiajs/vue3";
import IdeaItemAction from "./IdeaItemAction.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const ideaStatusTypes = usePage().props.ideaStatusTypes;

const ideaStatus = () => {
    return find(ideaStatusTypes, ['value', Number(props.item.status)]);
}

const statusBadge = () => {
    switch (ideaStatus().name) {
        case 'APPROVED':
            return 'success';
        case 'PENDING_REVIEW':
            return 'warning';
        case 'DISMISSED':
            return 'error';
        default:
            return '';
    }
}

</script>
<template>
    <TableRow :hoverable="true">
        <TableCell class="w-10">
            <slot name="checkbox"/>
        </TableCell>

        <TableCell>
            {{ item.theme }}
            <Flex class="items-start">
                <Badge
                    :variant="statusBadge()"
                    class="sm:hidden">
                    {{ $t('genie.' + ideaStatus().title) }}
                </Badge>
            </Flex>
            <Flex class="items-start sm:hidden text-sm">
                {{ $t('genie.uses') + ': ' + props.item.drafts.length  }}
            </Flex>
        </TableCell>

        <TableCell class="hidden sm:table-cell">
            <Badge :variant="statusBadge()">
                {{ $t('genie.' + ideaStatus().title) }}
            </Badge>
        </TableCell>

        <TableCell class="hidden sm:table-cell">
            {{  props.item.drafts.length  }}
        </TableCell>

        <TableCell>
            <IdeaItemAction
                :item="item"
                :status="ideaStatus()"
            />
        </TableCell>

    </TableRow>
</template>
