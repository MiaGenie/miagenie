<script setup>
import {ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import {cloneDeep, pickBy, throttle} from "lodash";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RunItem from "@/Components/Genie/Runs/RunItem.vue";
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
        default: {}
    },
    workspaces: {
        type:Object,
        require: true
    },
    rules: {
        type:Object,
        require: true,
    },
    versions: {
        type:Object,
        require: true,
    },
    ruleTypes: {
        type: Object,
        required: true
    },
    runStatus: {
        type: Object,
        require: true
    },
    filter: {
        type: Object,
        default: {}
    },
});

const filter = ref({
    type: props.filter.type
})

const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);
const isLoading = ref(false);

watch(() => cloneDeep(filter.value), throttle(() => {
    router.get(route('genie.admin.runs.index'), pickBy(filter.value), {
        preserveState: true,
        only: ['records', 'filter']
    });
}, 300))

watch(() => currentFilter.value.rule_type, throttle(() => {
    isLoading.value = true;

    router.get(route(
        'genie.admin.runs.index',
        {

        }
    ), pickBy(
        { 'rule_type': currentFilter.value.rule_type }
    ), {
        preserveState: true,
        only: ['records', 'filter']
    });

    isFiltered.value = Number(currentFilter.value.rule_type) > 0;
    isLoading.value = false;

}, 300))

</script>
<template>
    <Head :title="$t('genie.runs')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.runs')" />

        <div class="w-full row-px">
            <Tabs>
                <Tab
                    @click="currentFilter.rule_type = null"
                    :active="!currentFilter.rule_type"
                >
                    {{ $t('general.all') }}
                </Tab>

                <Tab
                    v-for="ruleType in ruleTypes"
                    :key="ruleType.value"
                    @click="currentFilter.rule_type = ruleType.value"
                    :active="currentFilter.rule_type == ruleType.value"
                >
                    {{ $t(`genie.rule_type_${ruleType.title}`) }}
                </Tab>

            </Tabs>
        </div>

        <div class="w-full row-px">

            <Panel :with-padding="false" class="mt-lg">

                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.run_workspace') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.run_version') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('genie.rule_run_type') }}
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
                            <RunItem
                                :item="item"
                                :is-filtered="isFiltered"
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
