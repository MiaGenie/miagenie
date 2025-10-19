<script setup>
import {Head, router} from '@inertiajs/vue3';
import {cloneDeep, pickBy, throttle} from "lodash";
import {provide, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import VersionHeader from '@/Components/DataDisplay/Genie/VersionHeader.vue';
import Pagination from "@/Components/Navigation/Pagination.vue";
import Tabs from "@/Components/Navigation/Tabs.vue";
import Tab from "@/Components/Navigation/Tab.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import ChevronLeft from "@/Icons/ChevronLeft.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import VersionFieldItemTranslate from "@/Components/Genie/Versions/VersionFieldItemTranslate.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
    groupTypes: {
        type: Object,
        required: true
    },
    fieldTypes: {
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
    translations: {
        type: Object,
        required: true
    },
    locales: {
        type: Object,
        required: true
    }
});

const isLoading = ref(false);
const fieldList = ref(cloneDeep(props.records.data))
const editingPositions = ref(false);
const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);

provide('currentFilter', currentFilter);

watch(() => currentFilter.value.group_type, throttle(() => {
    isLoading.value = true;

    router.get(route(
        'genie.admin.versions.fields.index-translate',
        {version: props.version.id}
    ), pickBy(
        { 'group_type': currentFilter.value.group_type }
    ), {
        preserveState: true,
        only: ['records', 'filter', 'translations']
    });

    isFiltered.value = Number(currentFilter.value.group_type) > 0;
    isLoading.value = false;

}, 300))

watch(() => props.records.data, () => {
    fieldList.value = cloneDeep(props.records.data);
})

const closeTranslations = () => {
    router.get(route('genie.admin.versions.fields.index',
        {
            version: props.version.id
        }),
        {group_type: Number(currentFilter.value.group_type)}
    );
}
</script>
<template>

    <Head :title="$t('genie.fields')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.fields')" />

        <VersionHeader />

        <div class="w-full row-px">

            <div class="flex flex-row items-center row-mt content-stretch">

                <SecondaryButton
                    @click="closeTranslations"
                    :hiddenTextOnSmallScreen="true"
                    size="sm"
                >
                    <template #icon>
                        <ChevronLeft/>
                    </template>
                    {{ $t("genie.back") }}
                </SecondaryButton>

            </div>

            <div class="w-full row-mt" >
                <Tabs v-if="!editingPositions">
                    <Tab
                        @click="currentFilter.group_type = null"
                        :active="!currentFilter.group_type"
                    >
                        {{ $t('general.all') }}
                    </Tab>

                    <Tab
                        v-for="groupType in groupTypes"
                        :key="groupType.value"
                        @click="currentFilter.group_type = groupType.value"
                        :active="currentFilter.group_type == groupType.value"
                    >
                        {{ $t(`genie.version_group_type_${groupType.title}`) }}
                    </Tab>
                </Tabs>
            </div>

            <Panel
                :with-padding="false"
                class="mt-sm"
            >

                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell
                                v-if="editingPositions"
                                component="th"
                                scope="col"
                                class="w-10"
                            />

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('general.name') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden"
                                :class="[isFiltered ? 'hidden' : 'lg:table-cell']"
                            >
                                {{ $t('genie.field_group_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden sm:table-cell"
                            >
                                {{ $t('genie.field_type') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            />

                        </TableRow>
                    </template>

                    <template #body>

                        <template v-for="field in props.records.data" :key="field.id">
                            <VersionFieldItemTranslate
                                :field="field"
                                :is-filtered="isFiltered"
                                :editing-positions="editingPositions"
                            />
                        </template>

                    </template>
                </Table>

                <NoResult
                    v-if="!records.meta.total"
                    class="py-md px-md"
                />

            </Panel>

            <div
                v-if="records.meta.links.length > 3"
                class="mt-lg"
            >
                <Pagination
                    :meta="records.meta"
                    :links="records.links"
                />
            </div>

        </div>
    </div>
</template>
