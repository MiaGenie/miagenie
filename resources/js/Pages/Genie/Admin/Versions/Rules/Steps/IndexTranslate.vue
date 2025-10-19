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
import Save from "@/Icons/Genie/Save.vue";
import Sort from "@/Icons/Genie/Sort.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ChevronLeft from "@/Icons/ChevronLeft.vue";
import Plus from "@/Icons/Plus.vue";
import RuleStepItemTranslate from "@/Components/Genie/Rules/RuleStepItemTranslate.vue";
import Table from "@/Components/DataDisplay/Table.vue";

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
    translations: {
        type: Object,
        required: true
    },
    locales: {
        type: Object,
        required: true
    }
});

const stepList = ref(cloneDeep(props.records.data))

watch(() => props.records.data, () => {
    stepList.value = cloneDeep(props.records.data);
})

const closeTranslations = () => {
    router.get(route('genie.admin.versions.rules.steps.index',
        {
            version: props.version.id,
            rule: props.rule.id
        }));
}
</script>
<template>

    <Head :title="$t('genie.steps') + ' - ' + $t('genie.translations')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.steps') + ' - ' + $t('genie.translations')" />

        <RuleHeader />

        <div class="w-full row-px">

            <div class="flex flex-row items-center row-mt content-stretch">

                <SecondaryButton
                    @click="closeTranslations"
                    :hiddenTextOnSmallScreen="true"
                    size="sm"
                >
                    <template #icon>
                        <ChevronLeft/>
                    </template>
                    {{ $t("genie.back") }}
                </SecondaryButton>

            </div>

            <Panel
                :with-padding="false"
                class="mt-sm"
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
                            />

                        </TableRow>
                    </template>

                    <template #body>

                        <template v-for="step in stepList">

                            <RuleStepItemTranslate :step="step"/>

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
