<script setup>
import { useI18n } from "vue-i18n";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import ModelProfileItem from "@/Components/Genie/ModelProfiles/ModelProfileItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";

defineOptions({ layout: AdminLayout });

const { t: $t } = useI18n();

defineProps({
    records: {
        type: Object,
        default: () => ({}),
    },
});

const createModelProfile = () => {
    router.get(route("genie.admin.model_profiles.create"));
};
</script>
<template>
    <Head :title="$t('genie.model_profiles')" />

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.model_profiles')" />

        <div class="w-full row-px row-mb mt-lg">
            <PrimaryButton
                @click="createModelProfile"
                :hiddenTextOnSmallScreen="true"
                size="sm"
            >
                <template #icon>
                    <Plus />
                </template>
                {{ $t("genie.create_model_profile") }}
            </PrimaryButton>
        </div>

        <div class="w-full row-px">
            <Panel :with-padding="false" class="mt-lg">
                <Table>
                    <template #head>
                        <TableRow>
                            <TableCell component="th" scope="col">
                                {{ $t("general.name") }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t("genie.model_profile_provider") }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t("genie.model_profile_model") }}
                            </TableCell>

                            <TableCell component="th" scope="col" />
                        </TableRow>
                    </template>

                    <template #body>
                        <template v-for="item in records.data" :key="item.id">
                            <ModelProfileItem :item="item" />
                        </template>
                    </template>
                </Table>

                <NoResult v-if="!records.meta.total" class="py-md px-md" />
            </Panel>

            <div v-if="records.meta.links.length > 3" class="mt-lg">
                <Pagination :meta="records.meta" :links="records.links" />
            </div>
        </div>
    </div>
</template>
