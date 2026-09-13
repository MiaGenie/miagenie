<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {router} from "@inertiajs/vue3";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import useIdeaLabels from "@/Composables/Genie/useIdeaLabels.js";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const workspaceCtx = inject('workspaceCtx');

const {ideaStatus, formatLabel, stageLabel, statusBadge} = useIdeaLabels(props.item);

const edit = () => {
    router.get(route('genie.ideas.edit', {
        workspace: workspaceCtx.id,
        idea: props.item.id,
    }));
}
</script>
<template>
    <TableRow :hoverable="true">
        <TableCell class="w-10">
            <slot name="checkbox"/>
        </TableCell>

        <TableCell :clickable="true" @click="edit">
            {{ item.theme }}
            <Flex class="items-start sm:hidden">
                <Badge :variant="statusBadge()">
                    {{ $t('genie.' + ideaStatus().title) }}
                </Badge>
            </Flex>
            <div class="md:hidden text-sm text-gray-500">
                {{ [formatLabel(), stageLabel(), item.content_pillar].filter(Boolean).join(' · ') }}
            </div>
        </TableCell>

        <TableCell class="hidden sm:table-cell text-sm" :clickable="true" @click="edit">
            {{ formatLabel() }}
        </TableCell>

        <TableCell class="hidden md:table-cell text-sm" :clickable="true" @click="edit">
            {{ stageLabel() }}
        </TableCell>

        <TableCell class="hidden md:table-cell text-sm" :clickable="true" @click="edit">
            {{ item.content_pillar }}
        </TableCell>

        <TableCell class="hidden sm:table-cell" :clickable="true" @click="edit">
            <Badge :variant="statusBadge()">
                {{ $t('genie.' + ideaStatus().title) }}
            </Badge>
        </TableCell>
    </TableRow>
</template>
