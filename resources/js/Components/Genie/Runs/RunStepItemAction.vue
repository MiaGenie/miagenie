<script setup>
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Eye from "@/Icons/Eye.vue";
import QueueList from "@/Icons/QueueList.vue";

const {t: $t} = useI18n()

const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'view':
            return route('genie.admin.runs.steps.view', {
                run: route().params.run,
                step: props.item.id,
            });
        case 'logs':
            // The step's model calls, on the logs index narrowed to this run step.
            return route('genie.admin.logs.index', {
                run_step: props.item.id,
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
                :href="getRoute('logs')"
                v-tooltip="$t('genie.run_step_calls')"
            >
                <QueueList/>
            </PureButtonLink>
            <PureButtonLink
                :href="getRoute('view')"
                v-tooltip="$t('general.view')"
            >
                <Eye/>
            </PureButtonLink>
        </div>
    </div>
</template>
