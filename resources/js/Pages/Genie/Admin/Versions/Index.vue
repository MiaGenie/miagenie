<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import VersionItem from "@/Components/Genie/Versions/VersionItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
    },
    statusTypes: {
        type: Object,
        required: true
    }
});

</script>
<template>

    <Head :title="$t('genie.versions')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.versions')">
            <template #description>
                {{ $t('genie.versions_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px mt-lg">

            <Link :href="route('genie.admin.versions.create')">
                <PrimaryButton size="sm">
                    <Plus class="mr-xs" />
                    {{ $t('genie.create_version') }}
                </PrimaryButton>
            </Link>

            <Panel
                :with-padding="false"
                class="mt-lg"
            >
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
                            <VersionItem :item="item" />
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
