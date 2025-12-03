<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";
import RulesIcon from "@/Icons/Genie/Rules.vue";
import Eye from "@/Icons/Eye.vue";
import {find} from "lodash";

const {t: $t} = useI18n()
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    status: {
        type: Object,
        required: true
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.ideas.edit', {
                workspace: workspaceCtx.id,
                idea: props.item.id,
            });
        default:
            return '';
    }
}

const ideaStatus = () => {
    return find(ideaStatusTypes, ['value', Number(props.item.status)]);
}
</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-lg">

            <PureButtonLink
                :href="getRoute('edit')"
                v-tooltip="$t('general.edit')"
            >
                <template #icon>
                    <PencilSquare v-if="status.name !== 'APPROVED'"/>
                    <Eye v-if="status.name === 'APPROVED'" />
                </template>
                <template #default>
                    {{ status.name !== 'APPROVED' ? $t('general.edit') : $t('general.view') }}
                </template>
            </PureButtonLink>
        </div>
    </div>
</template>
