<script setup>
import {Head, router} from '@inertiajs/vue3';
import {inject, onMounted, ref, watch} from "vue";
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
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import useNotifications from "@/Composables/useNotifications.js";
import emitter from "@/Services/emitter.js";


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

const itemsId = () => {
    return props.records.data.map(item => item.id);
}

onMounted(() => {
    putPageRecords(itemsId());

});
const workspaceCtx = inject('workspaceCtx');

const filter = ref({
    type: props.filter.type
})

const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);
const isLoading = ref(false);


watch(() => cloneDeep(filter.value), throttle(() => {
    router.get(route('genie.ideas.index'), pickBy(filter.value), {
        preserveState: true,
        only: ['records', 'filter']
    });
}, 300))

watch(() => currentFilter.value.funnel_stage, throttle(() => {
    isLoading.value = true;

    router.get(route(
        'genie.ideas.index',
        {
            workspace: workspaceCtx.id
        }
    ), pickBy(
        { 'funnel_stage': currentFilter.value.funnel_stage }
    ), {
        preserveState: true,
        only: ['records', 'filter']
    });

    isFiltered.value = Number(currentFilter.value.funnel_stage) > 0;
    isLoading.value = false;

}, 300))

const createIdea = () => {
    router.get(
        route(
            'genie.ideas.create', {
                workspace: workspaceCtx.id
            }
        ),
        {funnel_stage: Number(currentFilter.value.funnel_stage)}
    );
}

const {notify} = useNotifications();
const confirmationDeletion = ref(false);

const deleteIdeas = () => {
    router.delete(route('genie.ideas.deleteMultiple', {workspace: workspaceCtx.id}), {
        data: {
            ideas: selectedRecords.value,
            status: filter.value.status
        },
        onSuccess() {
            deselectAllRecords();
            notify('success',  $t("genie.ideas_deleted"))
        },
        onFinish() {
            confirmationDeletion.value = false;
        }
    });
}

</script>
<template>

    <Head :title="$t('genie.ideas')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.ideas')">
            <template #description>
                {{ $t('genie.ideas_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px row-mb mt-lg flex items-center grow gap-6">

            <PrimaryButton
                @click="createIdea"
                :hiddenTextOnSmallScreen="true"
                :disabled="isLoading"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <Plus/>
                </template>
                {{ $t('genie.create_idea') }}
            </PrimaryButton>

        </div>

        <div class="w-full row-px">
            <Tabs>
                <Tab
                    @click="currentFilter.funnel_stage = null"
                    :active="!currentFilter.funnel_stage"
                >
                    {{ $t('general.all') }}
                </Tab>

                <Tab
                    v-for="funnelStage in funnelStages"
                    :key="funnelStage.value"
                    @click="currentFilter.funnel_stage = funnelStage.value"
                    :active="currentFilter.funnel_stage == funnelStage.value"
                >
                    {{ $t(`genie.funnel_stage_${funnelStage.title}`) }}
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
    <ConfirmationModal :show="confirmationDeletion" variant="danger" @close="confirmationDeletion = false">
        <template #header>
            {{ $t("genie.delete_ideas") }}
        </template>
        <template #body>
            {{ $t("genie.confirmation_delete_idea") }}
        </template>
        <template #footer>
            <SecondaryButton @click="confirmationDeletion = false" class="mr-xs rtl:mr-0 rtl:ml-xs">{{
                    $t("general.cancel")
                }}
            </SecondaryButton>
            <DangerButton @click="deleteIdeas">{{ $t("general.delete") }}</DangerButton>
        </template>
    </ConfirmationModal>
</template>
