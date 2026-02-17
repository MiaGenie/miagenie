<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import {inject, onBeforeUnmount, onMounted, onUnmounted, onUpdated, provide, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import StrategyView from "@/Pages/Genie/Workspace/Strategies/ViewEdit.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Cog from "@/Icons/Cog.vue";
import {find, result} from "lodash";
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
    strategyStatusTypes: {
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
const strategyStatus = ref('');
const reviewing = ref(false);
provide('identifier', identifier);
provide('fieldList', props.fieldList);
provide('strategyStatus', strategyStatus)

const updateStatus = () => {
    return props.record?.status ? find(props.strategyStatusTypes, ['value', Number(props.record.status)]).name : '';
}

let refreshStatus;

onMounted(() => {
    strategyStatus.value = updateStatus();

    if (strategyStatus.value === 'OPEN' || strategyStatus.value === 'RUNNING' || strategyStatus.value === 'REVIEWED') {
        refreshStatus = setInterval(() => {
            router.get(
                route('genie.strategies.index',
                    {workspace: workspaceCtx.id}
                ), result(), {
                    preserveState: true,
                    only: ['record']
                });
        }, 5000)
    }
})

onUpdated(() => {
    strategyStatus.value = updateStatus();

    if (strategyStatus.value !== 'OPEN' && strategyStatus.value !== 'RUNNING' && strategyStatus.value !== 'REVIEWED') {
        clearInterval(refreshStatus);
    }
})

onBeforeUnmount(() => {
    clearInterval(refreshStatus);
})

</script>
<template>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <Head :title="$t('genie.strategy')"/>

        <PageHeader :title="$t('genie.strategy')" />

        <div class="w-full row-px mt-lg whitespace-pre-line">

            <Panel
                v-if="!record"
                class="w-full row-px mt-lg text-center"
            >
                <Flex
                    :col="true"
                >
                    <div class="text-lg">
                        {{ $t('genie.strategy_empty_desc') }}
                    </div>

                    <Link :href="route(`genie.config.config`, {workspace: workspaceCtx.id})">
                        <PrimaryButton class="my-xl" size="sm">
                            <Cog class="mr-xs" />
                            {{ $t('genie.config') }}
                        </PrimaryButton>
                    </Link>

                </Flex>

            </Panel>

            <StrategyView
                v-else-if="strategyStatus === 'APPROVED' || reviewing === true"
                :record="record"
                :schemas="schemas"
                :fieldTypes="fieldTypes"
                :fieldList="fieldList"
            />

            <Panel
                v-else-if="strategyStatus === 'PENDING_REVIEW'"
                class="w-full row-px mt-lg text-center"
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
                        <PrimaryButton class="my-xl" size="sm">
                            <PencilSquare class="mr-xs" />
                            {{ $t('genie.review_strategy') }}
                        </PrimaryButton>
                    </Link>

                </Flex>

            </Panel>

            <Panel
                v-else-if="strategyStatus === 'ERROR'"
                class="w-full row-px mt-lg text-center"
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
                        <PrimaryButton class="my-xl" size="sm">
                            <Support class="mr-xs" />
                            {{ $t('genie.support') }}
                        </PrimaryButton>
                    </Link>

                </Flex>

            </Panel>

            <Panel
                v-else-if="strategyStatus === 'PENDING_APPROVAL'"
                class="w-full row-px mt-lg text-center"
            >
                <Flex
                    :col="true"
                    :wrap="true"
                    :responsive="false"
                >
                    <div class="text-lg">
                        {{ $t('genie.strategy_pending_approval') }}
                    </div>

                    <div>
                        <PrimaryButton
                            class="my-xl"
                            size="sm"
                            @click="reviewing = true"
                        >
                            <PencilSquare class="mr-xs" />
                            {{ $t('genie.review_strategy') }}
                        </PrimaryButton>
                    </div>

                </Flex>

            </Panel>

            <Panel
                v-else-if="strategyStatus !== ''"
                class="w-full row-px mt-lg text-center"
            >
                <Flex
                    :col="true"
                    class="items-center"
                >
                    <div class="text-lg">
                        {{ $t('genie.strategy_running') }}
                    </div>

                    <div class="fulfilling-bouncing-circle-spinner">
                        <div class="circle"></div>
                        <div class="orbit"></div>
                    </div>

                </Flex>

            </Panel>

        </div>

    </div>
</template>
