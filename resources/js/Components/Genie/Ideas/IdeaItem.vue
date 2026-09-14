<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {router} from "@inertiajs/vue3";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";
import useIdeaLabels from "@/Composables/Genie/useIdeaLabels.js";
import ProviderIcon from "@/Components/Account/ProviderIcon.vue";
import Sparkles from "@/Icons/Sparkles.vue";
import PureButton from "@/Components/Button/PureButton.vue";

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

/**
 * The Mixpost draft Genie produced from this idea, when there is one.
 */
const openPost = () => {
    if (!props.item.post?.mixpost_post) return;

    router.get(route('mixpost.posts.edit', {
        workspace: workspaceCtx.id,
        post: props.item.post.mixpost_post,
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

        <TableCell class="hidden md:table-cell" :clickable="true" @click="edit">
            <div class="flex flex-wrap items-center gap-xs">
                <span
                    v-for="provider in item.publish_to"
                    :key="provider"
                    v-tooltip="provider"
                    class="w-4 h-4 flex items-center justify-center"
                >
                    <ProviderIcon :provider="provider"/>
                </span>
            </div>
        </TableCell>

        <TableCell class="hidden sm:table-cell" :clickable="true" @click="edit">
            <Badge :variant="statusBadge()">
                {{ $t('genie.' + ideaStatus().title) }}
            </Badge>
        </TableCell>

        <TableCell class="w-10">
            <PureButton
                v-if="item.post?.mixpost_post"
                v-tooltip="$t('genie.view_post')"
                class="text-primary-500"
                @click="openPost"
            >
                <Sparkles class="w-5 h-5"/>
            </PureButton>
        </TableCell>
    </TableRow>
</template>
