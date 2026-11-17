<script setup>

import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import LogItem from "@/Components/Genie/Logs/LogItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Alert from "@/Components/Util/Alert.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
        default: {}
    },
    genieTypes: {
        type: Object,
        require: true
    },
    genieSyncActions: {
        type: Object,
        require: true
    },
    filter: {
        type: Object,
        default: () => ({run_step: ''})
    },
    // Set when the list is narrowed to one run step's calls.
    runStep: {
        type: [Object, null],
        default: null
    }
});

</script>
<template>
    <Head :title="$t('genie.logs')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.logs')" />

        <div class="w-full row-px">

            <Alert v-if="runStep" :closeable="false" class="mt-lg">
                <div class="flex flex-wrap items-center gap-sm">
                    <span>
                        {{ $t('genie.logs_filtered_by_step', {position: runStep.position, name: runStep.name ?? '----------'}) }}
                    </span>
                    <a
                        v-if="runStep.run"
                        :href="route('genie.admin.runs.steps.view', {run: runStep.run, step: runStep.id})"
                        class="text-primary-500 hover:underline"
                    >
                        {{ $t('genie.run_step_data') }}
                    </a>
                    <a :href="route('genie.admin.logs.index')" class="text-primary-500 hover:underline">
                        {{ $t('general.clear_filter') }}
                    </a>
                </div>
            </Alert>

            <Panel :with-padding="false" class="mt-lg">

                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.log_id') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.log_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.log_action') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.log_duration') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.run_tokens') }}
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
                            />

                        </TableRow>
                    </template>

                    <template #body>

                        <template
                            v-for="item in records.data"
                            :key="item.id"
                        >
                            <LogItem
                                :item="item"
                            />
                        </template>

                    </template>
                </Table>

                <NoResult v-if="!records.meta.total" class="py-md px-md"/>
            </Panel>

            <div v-if="records.meta.links.length > 3" class="mt-lg">
                <Pagination :meta="records.meta" :links="records.links"/>
            </div>
        </div>

    </div>
</template>
