<script setup>
import {inject} from "vue";
import {Head, Link, router} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import useWorkspace from "@/Composables/useWorkspace.js";
import Competitors from "@/Pages/Genie/Workspace/Competitors/Index.vue"
import Briefing from "@/Pages/Genie/Workspace/Briefings/Briefing.vue"
import Flex from "@/Components/Layout/Flex.vue";
import DashboardButton from "@/Components/Button/Genie/DashboardButton.vue";
import Strategies from "@/Icons/Genie/Strategies.vue";
import Plus from "@/Icons/Plus.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

const {t: $t} = useI18n()

const props = defineProps({
    competitorsFieldList: {
        type: Object,
        required: true,
    },
    competitors: {
        type: Object,
    },
    briefingsFieldList: {
        type: Object,
        required: true,
    },
    briefing: {
        type: Object,
    }
})

const workspaceCtx = inject('workspaceCtx');

const {notify} = useNotifications();
const {isWorkspaceAdminRole} = useWorkspace();

const createStrategy = () => {
    router.get(route(`genie.strategies.create`, {workspace: workspaceCtx.id}))
}


</script>
<template>
    <Head :title="$t('genie.genie_setup')"/>

    <div class="row-py">
        <PageHeader :title="$t('genie.genie_setup')" />

        <Flex class="px-lg gap-xl items-center">

            <DashboardButton
                @click="createStrategy()"
                :disabled="!briefing || briefing.runStatus"
                v-tooltip="$t('genie.create_strategy')"
                colorStyle="strategy"
            >
                {{ $t('genie.create_strategy') }}

                <template #icon>
                    <Strategies/>
                </template>
            </DashboardButton>

            <div class="mt-xs mx-lg text-lg whitespace-pre-line">
                {{ $t('genie.genie_setup_description') }}
            </div>

        </Flex>

        <div class="my-md">
            <Flex>
                <Briefing
                    :record="briefing"
                    :fieldList="briefingsFieldList"
                />
                <Competitors
                    :records="competitors"
                    :fieldList="competitorsFieldList"
                />
            </Flex>
        </div>


    </div>

</template>
