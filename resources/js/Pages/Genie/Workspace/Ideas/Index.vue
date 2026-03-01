<script setup>
import {Head, router} from '@inertiajs/vue3';
import {computed, inject, onBeforeUnmount, onMounted, onUnmounted, ref, watch} from "vue";
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
import {cloneDeep, find, pickBy, result, filter, size, throttle} from "lodash";
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
import Lamp from "@/Icons/Genie/Lamp.vue";
import Flex from "@/Components/Layout/Flex.vue";
import emitter from "@/Services/emitter.js";
import PureSuccessButton from "@/Components/Button/Genie/PureSuccessButton.vue";
import ArrowUturnLeft from "@/Icons/ArrowUturnLeft.vue";
import SuccessButton from "@/Components/Button/SuccessButton.vue";

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
    },
    strategy: {
        type: Object,
    },
    ideaStatusTypes: {
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
    generating: {
        type: Boolean,
        default: false
    },
    hasPending: {
        type: Boolean,
        default: false
    }
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
const confirmationRestore = ref(false);

const itemsId = () => {
    return props.records.data.map(item => item.id);
}

let refreshStatus;

const startRefresh = () => {
    refreshStatus = setInterval(() => {
        router.get(
            route('genie.ideas.index',
                {workspace: workspaceCtx.id}
            ), result(), {
                preserveScroll: true
            });
    }, 2500)
}

const generatingIdeas = ref(props.generating);

watch(generatingIdeas, (newValue, oldValue) => {
    if (newValue && !oldValue) {
        startRefresh();
    } else if (!newValue && oldValue) {
        clearInterval(refreshStatus);
    }
});

watch(() => props.generating, () => {
    generatingIdeas.value = props.generating;
})

onMounted(() => {
    putPageRecords(itemsId());

    emitter.on('itemDelete', id => {
        deselectRecord(id);
    });

    if (generatingIdeas.value) {
        startRefresh()
    }
})

onBeforeUnmount(() => {
    clearInterval(refreshStatus);
})

onUnmounted(() => {
    emitter.off('postDelete');
})

watch(() => props.records.data, () => {
    putPageRecords(itemsId());
})

watch(() => cloneDeep(currentFilter.value.type), throttle(() => {
    router.get(route('genie.ideas.index'), pickBy(currentFilter.value.type), {
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
        deselectAllRecords();

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

const generateIdeas = () => {
    confirmation()
        .title($t('genie.generate_ideas'))
        .description($t('genie.generate_ideas_confirm'))
        .warning()
        .onConfirm((dialog) => {
            router.put(route('genie.ideas.generate', {
                workspace: workspaceCtx.id,
                strategy: props.strategy.id
            }));
            generatingIdeas.value = true;
            dialog.reset();
        }).show();
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

const restoreIdeas = () => {
    router.post(route('genie.ideas.restoreMultiple', {workspace: workspaceCtx.id}), {
        ideas: selectedRecords.value,
        filter: currentFilter.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            deselectAllRecords();
        },
        onFinish: () => {
            confirmationRestore.value = false;
        },
        onError: (errors) => {
            onError(errors, update);
        },
    });

}

const itemStatus = (item) => {
    return find(props.ideaStatusTypes, ['value', Number(item.status)]);
}

const statusValue = (status) => {
    return find(props.ideaStatusTypes, ['value', status]);
}

</script>
<template>

    <Head :title="$t('genie.ideas')"/>

    <div class="w-full mx-auto row-py whitespace-pre-line">

        <PageHeader :title="$t('genie.ideas')">
            <template #description>
                {{ $t('genie.ideas_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px row-mb mt-lg flex justify-between grow gap-6">

            <div class="flex justify-start gap-6">

            <WarningButton
                @click="generateIdeas"
                :disabled="isLoading || !strategy || hasPending || generating"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <Lamp/>
                </template>
                {{ $t('genie.generate_ideas') }}
            </WarningButton>

            <PrimaryButton
                @click="createIdea"
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
                    v-for="statusType in ideaStatusTypes"
                    :key="statusType.value"
                    @click="currentFilter.status = statusType.value"
                    :active="currentFilter.status == statusType.value"
                >
                    {{ $t(`genie.${statusType.title}`) }}
                </Tab>

            </Tabs>
        </div>

        <Flex
            v-if="generatingIdeas"
            :col="true"
            class="items-center mt-xl"
        >
            <div class="text-lg">
                {{ $t('genie.generating_ideas') }}
            </div>

            <div class="fulfilling-bouncing-circle-spinner">
                <div class="circle"></div>
                <div class="orbit"></div>
            </div>

        </Flex>


        <div class="w-full row-px">

            <SelectableBar :count="selectedRecords.length" @close="deselectAllRecords">
                <PureSuccessButton v-if="statusValue(currentFilter.status)?.name === 'TRASH'" @click="confirmationRestore = true" v-tooltip="$t('general.restore')" class="mx-md">
                    <ArrowUturnLeft/>
                </PureSuccessButton>
                <PureDangerButton @click="confirmationDeletion = true" v-tooltip="$t('general.delete')" class="mx-md">
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
                                <Checkbox
                                    v-model:checked="toggleSelectRecordsOnPage"
                                    :disabled="!records.meta.total || (statusValue(currentFilter.status)?.name !== 'TRASH' && statusValue(currentFilter.status)?.name !== 'PENDING_REVIEW')"
                                />
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
                                class="hidden sm:table-cell"
                            >
                                {{ $t('genie.uses') }}
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
                                    <Checkbox
                                        v-model:checked="selectedRecords"
                                        :disabled="itemStatus(item).name !== 'PENDING_REVIEW' && itemStatus(item).name !== 'TRASH'"
                                        :value="item.id"
                                    />
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
    <ConfirmationModal :show="confirmationRestore" @close="confirmationRestore = false">
        <template #header>
            {{ $t("general.restore") }}
        </template>
        <template #body>
            {{ $t("genie.confirmation_restore_ideas") }}
        </template>
        <template #footer>
            <SecondaryButton @click="confirmationRestore = false" class="mr-xs rtl:mr-0 rtl:ml-xs">{{
                    $t("general.cancel")
                }}
            </SecondaryButton>
            <SuccessButton @click="restoreIdeas">{{ $t("general.restore") }}</SuccessButton>
        </template>
    </ConfirmationModal>
</template>
