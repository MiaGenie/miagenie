<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import {find} from "lodash";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunStepItem from "@/Components/Genie/Runs/RunStepItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import RunHeader from '@/Components/DataDisplay/Genie/RunHeader.vue';
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Refresh from "@/Icons/Refresh.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    versionName: {
        type: String,
    },
    workspaceName: {
        type: String,
    },
    ruleName: {
        type: String,
    },
    ruleType: {
        type: String,
    },
    ruleSteps: {
        type: Object,
        default: {}
    },
    ruleSubTypes: {
        type: Object,
        required: true
    },
    run: {
        type: Object,
        required: true
    },
    runStatus: {
        type: Object,
        required: true
    },
    records: {
        type: Object,
        default: {}
    },
});

const backToList = () => {
    router.get(route(
        'genie.admin.runs.index',
    ));
}

const resumeRun = () => {
    router.put(route(
        'genie.admin.runs.resume',
        {run: props.run.id}
    ));
}

const status = () => {
    return find(props.runStatus, ['value', Number(props.run.status)])?.name;
}

</script>
<template>
    <Head :title="$t('genie.run_steps')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.run_steps')"/>

        <RunHeader/>

        <div class="w-full row-px">

            <Panel :with-padding="false" class="mt-lg">

                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.run_step_position') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_run_step') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_run_sub_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.created_at') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.run_step_duration') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.status') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            />

                        </TableRow>
                    </template>

                    <template #body>

                        <template
                            v-for="item in records.data"
                            :key="item.id"
                        >
                            <RunStepItem
                                :item="item"
                            />
                        </template>

                    </template>
                </Table>

                <NoResult v-if="!records.meta.total" class="py-md px-md"/>
            </Panel>

            <div class="flex flex-row items-center justify-between mt-lg">
                <div class="flex gap-6">

                    <PrimaryButton
                        @click="backToList"
                        type="button"
                    >
                        {{ $t("general.close") }}
                        <template #icon>
                            <X/>
                        </template>
                    </PrimaryButton>

                    <SecondaryButton
                        @click="resumeRun"
                        type="button"
                        v-if="status() !== 'COMPLETE'"
                        :hidden-text-on-small-screen=true
                    >
                        {{ $t("genie.run_resume") }}
                        <template #icon>
                            <Refresh/>
                        </template>
                    </SecondaryButton>

                </div>
            </div>

            <div v-if="records.meta.links.length > 3" class="mt-lg">
                <Pagination :meta="records.meta" :links="records.links"/>
            </div>
        </div>

    </div>
</template>
