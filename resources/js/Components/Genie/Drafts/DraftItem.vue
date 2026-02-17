<script setup>
import {useI18n} from "vue-i18n";
import {find} from "lodash";
import {router, usePage} from "@inertiajs/vue3";
import DraftItemAction from "./DraftItemAction.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import {inject} from "vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const workspaceCtx = inject('workspaceCtx');
const draftStatusTypes = usePage().props.draftStatusTypes;

const draftStatus = () => {
    return find(draftStatusTypes, ['value', Number(props.item.status)]);
}

const statusBadge = () => {
    switch (draftStatus().name) {
        case 'APPROVED':
            return 'success';
        case 'PENDING_REVIEW':
            return 'warning';
        case 'PUBLISHED':
            return 'info';
        case 'DISMISSED':
            return 'error';
        default:
            return '';
    }
}

const edit = () => {
    router.get(route('genie.drafts.edit', {
        workspace: workspaceCtx.id,
        draft: props.item.id,
    }));
}

</script>
<template>
    <TableRow :hoverable="true">
        <TableCell class="w-10">
            <slot name="checkbox"/>
        </TableCell>

        <TableCell :clickable="true" @click="edit">
            {{ item.topic }}
            <Flex class="items-start">
                <Badge
                    :variant="statusBadge()"
                    class="sm:hidden">
                    {{ $t('genie.' + draftStatus().title) }}
                </Badge>

            </Flex>
        </TableCell>

        <TableCell class="hidden sm:table-cell" :clickable="true" @click="edit">
            <Badge :variant="statusBadge()">
                {{ $t('genie.' + draftStatus().title) }}
            </Badge>
        </TableCell>

    </TableRow>
</template>
