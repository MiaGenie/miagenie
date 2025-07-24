<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import {find} from "lodash";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    runResponse: {
        type: Object,
        default: {}
    },
    ruleType: {
        type: String,
        required: true
    },
    ruleSteps: {
        type: Object,
        default: {}
    },
    ruleSubTypes: {
        type: Object,
        required: true
    },
    statusTypes: {
        type: Object,
        required: true
    },
    runUuid: {
        type: String,
        require: true
    }
});

const ruleSubType = (ruleSubTypeId) => {
    return find(props.ruleSubTypes, ['value', Number(ruleSubTypeId)]);
}

const backToList = () => {
    router.get(route(
        'genie.admin.run_responses.index', {
            run: props.runUuid,
        }));
}

</script>
<template>
    <Head :title="$t('genie.run_response_data')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.run_response_data')"/>

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <Table class="flex flex-col">

                    <TableCell :class="'pb-1'">
                        {{ $t('genie.run_uuid') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ runResponse.uuid }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.rule_run_step') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ runResponse.step_id }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.rule_run_type') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ ruleType }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.rule_run_sub_type') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ ruleSubType(ruleSteps.rule_sub_type).title }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.status') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ runResponse.status }}
                    </TableCell>

                    <table-cell :class="'pb-1 pt-5'">
                        {{ $t('genie.run_response_message') }}
                    </table-cell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ ruleSteps.message ? ruleSteps.message: '----------' }}
                    </TableCell>

                </Table>
            </Panel>

            <div class="flex flex-row items-center justify-between mt-lg">
                <div class="flex gap-6">

                    <PrimaryButton
                        @click="backToList"
                        type="button"
                    >
                        {{ $t("general.close") }}
                        <template #icon>
                            <X/>
                        </template>
                    </PrimaryButton>

                </div>
            </div>
        </div>
    </div>
</template>
