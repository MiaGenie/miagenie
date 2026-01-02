<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {inject, provide} from "vue";
import {useI18n} from "vue-i18n";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import StrategyView from "@/Pages/Genie/Workspace/Strategies/ViewEdit.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Cog from "@/Icons/Cog.vue";
import {find} from "lodash";
import Panel from "@/Components/Surface/Panel.vue";
import Flex from "@/Components/Layout/Flex.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Support from "@/Icons/Genie/Support.vue";

const {t: $t} = useI18n()
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    fieldList: {
        type: Object,
        required: true,
    },
    runStatusTypes: {
        type: Object,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    record: {
        type: Object
    },
    schemas: {
        type: Object,
    }
});

const identifier = props.fieldList.find( field => field.is_identifier === 1);
provide('identifier', identifier);
provide('fieldList', props.fieldList);

const runStatus = () => {
    return props.record?.status ? find(props.runStatusTypes, ['value', Number(props.record.status)]).name : '';
}

</script>
<template>

    <Head :title="$t('genie.strategy')"/>

    <div class="w-full max-w-[1200px] mx-auto row-py">
        <PageHeader :title="$t('genie.strategy')">

        </PageHeader>

        <Panel
            v-if="!record"
            class="w-full row-px mt-lg"
        >
            <Flex
                :col="true"
            >
                <div class="text-lg">
                    {{ $t('genie.strategy_empty_desc') }}
                </div>

                <Link :href="route(`genie.config.config`, {workspace: workspaceCtx.id})">
                    <PrimaryButton size="sm">
                        <Cog class="mr-xs" />
                        {{ $t('genie.config') }}
                    </PrimaryButton>
                </Link>

            </Flex>

        </Panel>

        <StrategyView
            v-else-if="runStatus() === 'COMPLETE'"
            :record="record"
            :schemas="schemas"
            :fieldTypes="fieldTypes"
            :fieldList="fieldList"
        />

        <Panel
            v-else-if="runStatus() === 'PENDING_REVIEW'"
            class="w-full row-px mt-lg"
        >
            <Flex
                :col="true"
            >
                <div class="text-lg">
                    {{ $t('genie.strategy_pending_review') }}
                </div>

                <Link
                    :href="route('genie.strategies.review', {
                        workspace: workspaceCtx.id,
                        strategy: record.id
                    })"
                >
                    <PrimaryButton size="sm">
                        <PencilSquare class="mr-xs" />
                        {{ $t('genie.review_strategy') }}
                    </PrimaryButton>
                </Link>

            </Flex>

        </Panel>

        <Panel
            v-else-if="runStatus() === 'ERROR'"
            class="w-full row-px mt-lg"
        >
            <Flex
                :col="true"
            >
                <div class="text-lg">
                    {{ $t('genie.strategy_error') }}
                </div>

                <Link
                    href="mailto:hello@miagenie.com"
                >
                    <PrimaryButton size="sm">
                        <Support class="mr-xs" />
                        {{ $t('genie.support') }}
                    </PrimaryButton>
                </Link>

            </Flex>

        </Panel>

        <Panel
            v-else-if="runStatus() !== ''"
            class="w-full row-px mt-lg"
        >
            <Flex
                :col="true"
            >
                <div class="text-lg">
                    {{ $t('genie.strategy_running') }}
                </div>

            </Flex>

        </Panel>

    </div>
</template>
