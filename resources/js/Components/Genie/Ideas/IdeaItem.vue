<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {find, size} from "lodash";
import {router, usePage} from "@inertiajs/vue3";
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

const workspaceCtx = inject('workspaceCtx');
const ideaStatusTypes = usePage().props.ideaStatusTypes;
const draftStatusTypes = usePage().props.draftStatusTypes;

const ideaStatus = () => {
    return find(ideaStatusTypes, ['value', Number(props.item.status)]);
}

const draftStatus = (name) => {
    return find(draftStatusTypes, ['name', name]);
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

const edit = () => {
    router.get(route('genie.ideas.edit', {
        workspace: workspaceCtx.id,
        idea: props.item.id,
    }));
}

const draftsByStatus = (status) => {
    return props.item.drafts.filter( (item) => {
        return item.status === draftStatus(status).value
    }).length;
}

</script>
<template>
    <TableRow :hoverable="true">
        <TableCell class="w-10">
            <slot name="checkbox"/>
        </TableCell>

        <TableCell :clickable="true" @click="edit">
            {{ item.theme }}
            <Flex class="items-start">
                <Badge
                    :variant="statusBadge()"
                    class="sm:hidden">
                    {{ $t('genie.' + ideaStatus().title) }}
                </Badge>
            </Flex>
            <Flex class="items-start sm:hidden text-sm">
                <Flex :responsive="false" gap="gap-0">
                    {{ $t('genie.drafts') + ': ' }}
                    <Badge variant="warning" v-tooltip="$t('genie.pending_review')">
                        {{ draftsByStatus('PENDING_REVIEW') }}
                    </Badge>
                    /
                    <Badge variant="success" v-tooltip="$t('genie.published')">
                        {{ draftsByStatus('PUBLISHED') }}
                    </Badge>
                    /
                    <Badge v-tooltip="$t('genie.trash')">
                        {{ draftsByStatus('TRASH') }}
                    </Badge>
                </Flex>
            </Flex>
        </TableCell>

        <TableCell class="hidden sm:table-cell" :clickable="true" @click="edit">
            <Badge :variant="statusBadge()">
                {{ $t('genie.' + ideaStatus().title) }}
            </Badge>
        </TableCell>

        <TableCell class="hidden sm:table-cell text-sm" :clickable="true" @click="edit">
            <Flex gap="gap-0">
                <Badge variant="warning" v-tooltip="$t('genie.pending_review')">
                    {{ draftsByStatus('PENDING_REVIEW') }}
                </Badge>
                /
                <Badge variant="success" v-tooltip="$t('genie.published')">
                    {{ draftsByStatus('PUBLISHED') }}
                </Badge>
                /
                <Badge v-tooltip="$t('genie.trash')">
                    {{ draftsByStatus('TRASH') }}
                </Badge>
            </Flex>
        </TableCell>


    </TableRow>
</template>
