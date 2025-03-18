<script setup>
import {inject, onMounted, onUnmounted, watch} from "vue";
import {useI18n} from "vue-i18n";
import {Head, Link} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import emitter from "@/Services/emitter";
import useRouter from "@/Composables/useRouter";
import useSelectable from "@/Composables/useSelectable";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PureDangerButton from "@/Components/Button/PureDangerButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import SelectableBar from "@/Components/DataDisplay/SelectableBar.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import VectorItem from "@/Components/Genie/Vectors/VectorItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";
import TrashIcon from "@/Icons/Trash.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
    }
});

const confirmation = inject('confirmation');

const {
    selectedRecords,
    putPageRecords,
    deselectRecord,
    deselectAllRecords
} = useSelectable();

const {onError} = useRouter();

const itemsId = () => {
    return props.records.data.map(item => item.id);
}

const confirmDelete = () => {
    confirmation()
        .title($t("genie.delete_vectors"))
        .description($t("genie.delete_vectors_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            deleteAfterConfirmed(dialog);
        })
        .show();
}

const deleteAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(route(`genie.admin.vectors.deleteMultiple`), {
        data: {
            vectors: selectedRecords.value,
        },
        preserveScroll: true,
        onSuccess() {
            dialog.reset();
            deselectAllRecords();
        },
        onError(errors) {
            onError(errors, () => {
                deleteAfterConfirmed(dialog);
            });
        },
        onFinish() {
            dialog.isLoading(false);
        }
    });
}

onMounted(() => {
    putPageRecords(itemsId());

    emitter.on('vectorDeleted', id => {
        deselectRecord(id);
    });
});

onUnmounted(() => {
    emitter.off('vectorDeleted');
})

watch(() => props.records.data, () => {
    putPageRecords(itemsId());
})
</script>
<template>

    <Head :title="$t('genie.vectors')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.vectors')">
            <template #description>
                {{ $t('genie.vectors_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px mt-lg">
            <Link :href="route(`genie.admin.vectors.create`)">
                <PrimaryButton size="sm">
                    <Plus class="mr-xs" />
                    {{ $t('genie.create_vector') }}
                </PrimaryButton>
            </Link>

            <SelectableBar
                :count="selectedRecords.length"
                @close="deselectAllRecords"
            >
                <PureDangerButton
                    @click="confirmDelete"
                    v-tooltip="$t('general.delete')"
                >
                    <TrashIcon/>
                </PureDangerButton>
            </SelectableBar>

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
                            />
                        </TableRow>
                    </template>

                    <template #body>
                        <template
                            v-for="item in records.data"
                            :key="item.id"
                        >
                            <VectorItem
                                :item="item"
                                @onDelete="()=> {deselectRecord(item.id)}"
                            >
                                <template #checkbox>
                                    <Checkbox
                                        v-model:checked="selectedRecords"
                                        :value="item.id"
                                    />
                                </template>
                            </VectorItem>
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
