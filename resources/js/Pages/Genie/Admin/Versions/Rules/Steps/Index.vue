<script setup>
import {Head, router} from '@inertiajs/vue3';
import Draggable from 'vuedraggable';
import {cloneDeep} from "lodash";
import {ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import AdminLayout from "@/Layouts/Admin.vue";
import DragTable from "@/Components/DataDisplay/Genie/DragTable.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import RuleHeader from '@/Components/DataDisplay/Genie/RuleHeader.vue';
import RuleStepItem from "@/Components/Genie/Rules/RuleStepItem.vue";
import RuleStepsIndexActions from "@/Components/Genie/Rules/RuleStepsIndexActions.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import Panel from "@/Components/Surface/Panel.vue";
import NoResult from "@/Components/Util/NoResult.vue";

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
    rule: {
        type: Object,
        required: true
    },
    ruleTypes: {
        type: Object,
        required: true
    },
    versionStatusTypes: {
        type: Object,
        required: true
    },
    ruleStatusTypes: {
        type: Object,
        required: true
    }
});

const isLoading = ref(false);
const stepList = ref(cloneDeep(props.records.data))
const editingPositions = ref(false);

watch(() => props.records.data, () => {
    stepList.value = cloneDeep(props.records.data);
})

</script>
<template>

    <Head :title="$t('genie.steps')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.steps')" />

        <RuleHeader />

        <div class="w-full row-px">

            <RuleStepsIndexActions
                @editingPositions="editingPositions = $event"
                @isLoading="isLoading = $event"
                :stepList="stepList"
                :editing-positions="editingPositions"
                :is-loading="isLoading"
            />

            <Panel
                :with-padding="false"
                class="mt-sm"
            >

                <DragTable>
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
                                class="hidden md:table-cell"
                            >
                                {{ $t('genie.step_output') }}
                            </TableCell>

                            <TableCell
                                component="th"
                                scope="col"
                            />

                        </TableRow>
                    </template>

                    <template #body>
                        <Draggable
                            :list="stepList"
                            tag="tbody"
                            group="steps"
                            handle=".handle"
                            item-key="id"
                        >
                            <template #item="{element}">
                                <RuleStepItem
                                    :step="element"
                                    :editing-positions="editingPositions"
                                />
                            </template>
                        </Draggable>
                    </template>
                </DragTable>

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
