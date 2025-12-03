<script setup>
import {Head, router} from '@inertiajs/vue3';
import {inject, onMounted, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import DraftItem from "@/Components/Genie/Drafts/DraftItem.vue";
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
import WarningButton from "@/Components/Button/WarningButton.vue";
import DraftIcon from "mixpost-pro-team/resources/js/Icons/Genie/Draft.vue";

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
    },
    draftStatusTypes: {
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

const {notify} = useNotifications();
const confirmation = inject('confirmation');
const workspaceCtx = inject('workspaceCtx');

const filter = ref({
    type: props.filter.type
})

const currentFilter = ref(cloneDeep(props.filter));
const isFiltered = ref(false);
const isLoading = ref(false);
const confirmationDeletion = ref(false);

const itemsId = () => {
    return props.records.data.map(item => item.id);
}

onMounted(() => {
    putPageRecords(itemsId());
});

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

const generatePrePosts = () => {
    confirmation()
        .title($t('genie.generate_posts'))
        .description($t('genie.generate_posts_confirm'))
        .warning()
        .onConfirm((dialog) => {
            router.post(route('genie.pre_posts.generateMultiple',{
                workspace: workspaceCtx.id
            }),{
                drafts: selectedRecords.value
            });
            deselectAllRecords();
            dialog.reset();
        })
        .show();
}

const deleteDrafts = () => {
    router.delete(route('genie.drafts.deleteMultiple', {workspace: workspaceCtx.id}), {
        data: {
            drafts: selectedRecords.value,
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

    <Head :title="$t('genie.drafts')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.drafts')">
            <template #description>
                {{ $t('genie.drafts_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px row-mb mt-lg flex justify-between grow gap-6">

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

            <WarningButton
                v-if="selectedRecords.length > 0"
                @click="generatePrePosts"
                :hiddenTextOnSmallScreen="true"
                :disabled="isLoading"
                :isLoading="isLoading"
                size="sm"
            >

                <template #icon>
                    <DraftIcon/>
                </template>
                {{ $t('genie.generate_posts') }}
            </WarningButton>

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
                    v-for="statusType in draftStatusTypes"
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
                                {{ $t('genie.drafts_topic') }}
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
                            <DraftItem :item="item">
                                <template #checkbox>
                                    <Checkbox v-model:checked="selectedRecords" :value="item.id"/>
                                </template>
                            </DraftItem>
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
            {{ $t("genie.delete_drafts") }}
        </template>
        <template #body>
            {{ $t("genie.confirmation_delete_draft") }}
        </template>
        <template #footer>
            <SecondaryButton @click="confirmationDeletion = false" class="mr-xs rtl:mr-0 rtl:ml-xs">{{
                    $t("general.cancel")
                }}
            </SecondaryButton>
            <DangerButton @click="deleteDrafts">{{ $t("general.delete") }}</DangerButton>
        </template>
    </ConfirmationModal>
</template>
