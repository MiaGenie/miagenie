<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunResponseItem from "@/Components/Genie/Runs/RunResponseItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    runUuid: {
        type:String,
        require: true,
    },
    ruleType: {
        type: String,
        required: true
    },
    ruleSteps: {
        type: Object,
        default: {}
    },
    ruleSubTypes: {
        type: Object,
        required: true
    },
    statusTypes: {
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

</script>
<template>
    <Head :title="$t('genie.thread_runs')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.thread_runs') + ' (' + props.runUuid + ')'"/>

        <div class="w-full row-px">

            <Panel :with-padding="false" class="mt-lg">

                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.thread_uuid') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_thread_step') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_thread_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_thread_sub_type') }}
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
                            >
                                {{ $t('genie.thread_run_message') }}
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
                            <RunResponseItem
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

                </div>
            </div>

            <div v-if="records.meta.links.length > 3" class="mt-lg">
                <Pagination :meta="records.meta" :links="records.links"/>
            </div>
        </div>

    </div>
</template>
