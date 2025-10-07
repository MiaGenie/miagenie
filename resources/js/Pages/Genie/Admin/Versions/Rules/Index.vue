<script setup>
import {ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import {cloneDeep, pickBy, throttle} from "lodash";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RuleItem from "@/Components/Genie/Rules/RuleItem.vue";
import Tabs from "@/Components/Navigation/Tabs.vue"
import Tab from "@/Components/Navigation/Tab.vue"
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";
import VersionHeader from "@/Components/DataDisplay/Genie/VersionHeader.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
        default: {}
    },
    version: {
        type: Object,
        required: true
    },
    ruleTypes: {
        type: Object,
        required: true
    },
    statusTypes: {
        type: Object,
        required: true
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
    router.get(route('genie.admin.versions.rules.index'), pickBy(filter.value), {
        preserveState: true,
        only: ['records', 'filter']
    });
}, 300))

watch(() => currentFilter.value.rule_type, throttle(() => {
    isLoading.value = true;

    router.get(route(
        'genie.admin.versions.rules.index',
        {
            version: props.version.id
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

const createRule = () => {
    router.get(
        route(
            'genie.admin.versions.rules.create', {
                version: props.version.id
            }
        ),
        {rule_type: Number(currentFilter.value.rule_type)}
    );
}
</script>
<template>
    <Head :title="$t('genie.rules')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.rules')" />

        <VersionHeader />


        <div class="w-full row-px row-mb mt-lg">
            <PrimaryButton
                @click="createRule"
                :hiddenTextOnSmallScreen="true"
                :disabled="isLoading"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <Plus/>
                </template>
                {{ $t('genie.create_rule') }}
            </PrimaryButton>
        </div>

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
                                {{ $t('general.name') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t('genie.rule_version_id') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden"
                                :class="[isFiltered ? 'hidden' : 'lg:table-cell']"
                            >
                                {{ $t('genie.rule_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden sm:table-cell"
                            >
                                {{ $t('general.status') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden md:table-cell"
                            >
                                {{ $t('genie.is_default') }}
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
                            <RuleItem
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
