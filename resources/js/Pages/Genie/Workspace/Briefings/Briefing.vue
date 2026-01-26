<script setup>
import {Head, Link} from '@inertiajs/vue3';
import {inject, provide} from "vue";
import {useI18n} from "vue-i18n";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Pagination from "@/Components/Navigation/Pagination.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import BriefingItem from "@/Components/Genie/Briefings/BriefingItem.vue";
import Plus from "@/Icons/Plus.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

const {t: $t} = useI18n()

const props = defineProps({
    fieldList: {
        type: Object,
        required: true,
    },
    record: {
        type: Object,
    }
});

const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx');

const identifier = props.fieldList.find( field => field.is_identifier === 1);
provide('identifier', identifier);
provide('fieldList', props.fieldList);

</script>
<template>
    <div class="w-full lg:w-2/5">

        <Head :title="$t('genie.briefings')"/>

        <div class="w-full mx-auto row-py whitespace-pre-line">
            <PageHeader :title="$t('genie.briefing')">
                <template #description>
                    {{ $t('genie.briefings_desc') }}
                </template>
            </PageHeader>

            <div class="w-full row-px mt-lg">
                <Link v-if="!record" :href="route(`${routePrefix}.briefings.create`, {workspace: workspaceCtx.id})">
                    <PrimaryButton size="sm">
                        <Plus class="mr-xs" />
                        {{ $t('genie.create_briefing') }}
                    </PrimaryButton>
                </Link>


                <Panel :with-padding="false" class="mt-lg">
                    <Table>
                        <template #head>
                            <TableRow>

                                <TableCell component="th" scope="col">
                                    {{ $t('genie.completion') }}
                                </TableCell>

                                <TableCell component="th" scope="col" class="hidden md:table-cell">
                                    {{ $t('general.status') }}
                                </TableCell>

                                <TableCell component="th" scope="col"/>

                            </TableRow>
                        </template>
                        <template #body>

                                <BriefingItem v-if="record" :item="record" />

                        </template>
                    </Table>

                    <NoResult v-if="!record" class="py-md px-md"/>

                </Panel>
            </div>
        </div>

    </div>
</template>
