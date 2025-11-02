<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";
import RulesIcon from "@/Icons/Genie/Rules.vue";

const {t: $t} = useI18n()
const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.drafts.edit', {
                workspace: workspaceCtx.id,
                draft: props.itemId,
            });
        default:
            return '';
    }
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
                    <PencilSquare/>
                </template>
                <template #default>
                    {{ $t('general.edit') }}
                </template>
            </PureButtonLink>
        </div>
    </div>
</template>
