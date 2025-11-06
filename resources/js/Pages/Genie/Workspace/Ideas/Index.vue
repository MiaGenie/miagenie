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
import Select from "@/Components/Form/Select.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import DraftIcon from "mixpost-pro-team/resources/js/Icons/Genie/Draft.vue";

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
    contentPillars: {
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

const {notify} = useNotifications();
const confirmation = inject('confirmation');
const workspaceCtx = inject('workspaceCtx');

const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);
const isLoading = ref(false);
const confirmationDeletion = ref(false);
const filter = ref({type: props.filter.type})

const itemsId = () => {
    return props.records.data.map(item => item.id);
}

onMounted(() => {
    putPageRecords(itemsId());
});

watch(() => cloneDeep(filter.value), throttle(() => {
    router.get(route('genie.ideas.index'), pickBy(filter.value), {
        preserveState: true,
        only: ['records', 'filter']
    });
}, 300))

watch(() => [
        currentFilter.value.status,
        currentFilter.value.funnel_stage,
        currentFilter.value.content_pillar
    ],
    throttle(() => {
        isLoading.value = true;

        router.get(route(
            'genie.ideas.index',
            {
                workspace: workspaceCtx.id
            }
        ), pickBy(
            {
                'status': currentFilter.value.status,
                'funnel_stage': currentFilter.value.funnel_stage,
                'content_pillar': currentFilter.value.content_pillar,
            }
        ), {
            preserveState: true,
            only: ['records', 'filter']
        });

        isFiltered.value = Number(currentFilter.value.funnel_stage) > 0 || Number(currentFilter.value.status) > 0;
        isLoading.value = false;

    }, 300)
)

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

const generateDrafts = () => {
    confirmation()
        .title($t('genie.generate_drafts'))
        .description($t('genie.generate_drafts_confirm'))
        .warning()
        .onConfirm((dialog) => {
            router.post(route('genie.drafts.generateMultiple',{
                workspace: workspaceCtx.id
            }),{
                ideas: selectedRecords.value
            });
            deselectAllRecords();
            dialog.reset();
        })
        .show();
}

const deleteIdeas = () => {
    router.delete(route('genie.ideas.deleteMultiple', {workspace: workspaceCtx.id}), {
        data: {
            ideas: selectedRecords.value,
            filter: currentFilter.value
        },
        onSuccess() {
            deselectAllRecords();
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

        <div class="w-full row-px row-mb mt-lg flex justify-between grow gap-6">

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

            <WarningButton
                v-if="selectedRecords.length > 0"
                @click="generateDrafts"
                :hiddenTextOnSmallScreen="true"
                :disabled="isLoading"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <DraftIcon/>
                </template>
                {{ $t('genie.generate_drafts') }}
            </WarningButton>

        </div>

        <div class="w-full row-px">

            <Panel>

                <div class="flex flex-row justify-around items-center">

                    <VerticalGroup class="w-1/3">
                        <template #title>
                            {{ $t('genie.funnel_stage')}}
                        </template>

                        <template #default>
                            <Select v-model="currentFilter.funnel_stage">
                                <option value=''>{{ $t(`general.all`)}}</option>
                                <option
                                    v-for="funnelStage in funnelStages"
                                    :key="funnelStage.value"
                                    :value="funnelStage.value"
                                >
                                    {{ $t(`genie.funnel_stage_${funnelStage.title}`) }}
                                </option>
                            </Select>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="w-1/3">
                        <template #title>
                            {{ $t('genie.content_pillar')}}
                        </template>

                        <template #default>
                            <Select v-model="currentFilter.content_pillar">
                                <option value=''>{{ $t(`general.all`)}}</option>
                                <option
                                    v-for="contentPillar in contentPillars"
                                    :key="contentPillar.value"
                                    :value="contentPillar.value"
                                >
                                    {{ contentPillar }}
                                </option>
                            </Select>
                        </template>
                    </VerticalGroup>

                </div>
            </Panel>

            <Tabs class="mt-lg">
                <Tab
                    @click="currentFilter.status= null"
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
