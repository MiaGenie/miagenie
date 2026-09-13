<script setup>
import {useI18n} from "vue-i18n";
import {inject} from "vue";
import {router} from "@inertiajs/vue3";
import Badge from "@/Components/DataDisplay/Badge.vue";
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
    <div class="relative flex flex-col h-full bg-white border border-gray-100 rounded-lg p-md hover:border-primary-100 transition-colors ease-in-out duration-200">
        <div class="flex items-start justify-between gap-xs">
            <div class="font-semibold text-black cursor-pointer break-words grow" @click="edit">
                {{ item.theme }}
            </div>

            <slot name="checkbox"/>
        </div>

        <div class="mt-sm flex flex-wrap gap-xs text-xs cursor-pointer" @click="edit">
            <Badge v-if="formatLabel()">
                {{ formatLabel() }}
            </Badge>

            <Badge v-if="stageLabel()" variant="info">
                {{ stageLabel() }}
            </Badge>

            <Badge v-if="item.content_pillar" variant="dark">
                {{ item.content_pillar }}
            </Badge>

            <Badge :variant="statusBadge()">
                {{ $t('genie.' + ideaStatus().title) }}
            </Badge>
        </div>
    </div>
</template>
