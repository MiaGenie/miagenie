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


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    threadRun: {
        type: Object,
        default: {}
    },
    threadUuid: {
        type: String,
        require: true
    }
});

const backToList = () => {
    router.get(route(
        'genie.admin.thread_runs.index', {
            thread: props.threadUuid,
        }));
}

</script>
<template>
    <Head :title="$t('genie.thread_run_message')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.thread_run_message')"/>

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <Table class="flex flex-col">

                    <TableCell :class="'pb-1'">
                        {{ $t('genie.thread_uuid') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ threadRun.uuid }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.rule_thread_step') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ threadRun.step_id }}
                    </TableCell>

                    <TableCell :class="'pb-1 pt-5'">
                        {{ $t('genie.status') }}
                    </TableCell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ threadRun.status }}
                    </TableCell>

                    <table-cell :class="'pb-1 pt-5'">
                        {{ $t('genie.thread_run_message') }}
                    </table-cell>
                    <TableCell :class="'bg-primary-50 rounded-lg pb-1 pt-1 w-full'">
                        {{ threadRun.message ? threadRun.message.text.value : '----------' }}
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
