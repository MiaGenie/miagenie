<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import AIModelItem from "@/Components/Genie/AIModels/AIModelItem.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Plus from "@/Icons/Plus.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    records: {
        type: Object,
        default: {}
    }
});

const createAIModel = () => {
    router.get(
        route(
            'genie.admin.ai_models.create'
        )
    );
}
</script>
<template>
    <Head :title="$t('genie.ai_models')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.ai_models')" />

        <div class="w-full row-px row-mb mt-lg">
            <PrimaryButton
                @click="createAIModel"
                :hiddenTextOnSmallScreen="true"
                size="sm"
            >

                <template #icon>
                    <Plus/>
                </template>
                {{ $t('genie.create_ai_model') }}
            </PrimaryButton>
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
                                {{ $t('genie.ai_model_file_search') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t('genie.ai_model_json_schema') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t('genie.ai_model_temperature_top_p') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                                class="hidden lg:table-cell"
                            >
                                {{ $t('genie.ai_model_reasoning_effort') }}
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
                            <AIModelItem
                                :item="item"
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
