<script setup>
import {Head, router} from '@inertiajs/vue3';
import {inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import IdeaItem from "@/Components/Genie/Ideas/IdeaItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";
import {cloneDeep, pickBy, throttle} from "lodash";
import useSelectable from "@/Composables/useSelectable";
import Tabs from "@/Components/Navigation/Tabs.vue";
import Tab from "@/Components/Navigation/Tab.vue";
import SelectableBar from "@/Components/DataDisplay/SelectableBar.vue";
import PureDangerButton from "@/Components/Button/PureDangerButton.vue";
import TrashIcon from "@/Icons/Trash.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";


const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
    },
    statusTypes: {
        type: Object,
        required: true
    },
    funnelStages: {
        type: Object,
        required: true
    },
    filter: {
        type: Object,
        default: {}
    },
});

const {
    selectedRecords,
    putPageRecords,
    toggleSelectRecordsOnPage,
    deselectRecord,
    deselectAllRecords
} = useSelectable();

const workspaceCtx = inject('workspaceCtx');

const filter = ref({
    type: props.filter.type
})

const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);
const isLoading = ref(false);


watch(() => cloneDeep(filter.value), throttle(() => {
    router.get(route('genie.drafts.index'), pickBy(filter.value), {
        preserveState: true,
        only: ['records', 'filter']
    });
}, 300))

watch(() => currentFilter.value.status, throttle(() => {
    isLoading.value = true;

    router.get(route(
        'genie.drafts.index',
        {
            workspace: workspaceCtx.id
        }
    ), pickBy(
        { 'status': currentFilter.value.status }
    ), {
        preserveState: true,
        only: ['records', 'filter']
    });

    isFiltered.value = Number(currentFilter.value.status) > 0;
    isLoading.value = false;

}, 300))

const createDraft = () => {
    router.get(
        route(
            'genie.drafts.create', {
                workspace: workspaceCtx.id
            }
        ),
        {status: Number(currentFilter.value.status)}
    );
}

</script>
<template>

    <Head :title="$t('genie.drafts')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.drafts')">
            <template #description>
                {{ $t('genie.drafts_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px row-mb mt-lg flex items-center grow gap-6">

            <PrimaryButton
                @click="createDraft"
                :hiddenTextOnSmallScreen="true"
                :disabled="isLoading"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <Plus/>
                </template>
                {{ $t('genie.create_draft') }}
            </PrimaryButton>

        </div>

        <div class="w-full row-px">
            <Tabs>
                <Tab
                    @click="currentFilter.status = null"
                    :active="!currentFilter.status"
                >
                    {{ $t('general.all') }}
                </Tab>

                <Tab
                    v-for="statusType in statusTypes"
                    :key="statusType.value"
                    @click="currentFilter.status = statusType.value"
                    :active="currentFilter.status == statusType.value"
                >
                    {{ $t(`genie.${statusType.title}`) }}
                </Tab>

            </Tabs>
        </div>

        <div class="w-full row-px">

            <SelectableBar :count="selectedRecords.length" @close="deselectAllRecords">
                <PureDangerButton @click="confirmationDeletion = true" v-tooltip="$t('general.delete')">
                    <TrashIcon/>
                </PureDangerButton>
            </SelectableBar>

            <Panel
                :with-padding="false"
                class="mt-lg"
            >
                <Table>
                    <template #head>

                        <TableRow>
                            <TableCell component="th" scope="col" class="w-10">
                                <Checkbox v-model:checked="toggleSelectRecordsOnPage" :disabled="!records.meta.total"/>
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            >
                                {{ $t('general.name') }}
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
                            <IdeaItem :item="item">
                                <template #checkbox>
                                    <Checkbox v-model:checked="selectedRecords" :value="item.id"/>
                                </template>
                            </IdeaItem>
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
