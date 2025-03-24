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
import StrategyItem from "@/Components/Genie/Briefings/StrategyItem.vue";
import Plus from "@/Icons/Plus.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

const {t: $t} = useI18n()

const props = defineProps({
    filter: {
        type: Object,
        default: {}
    },
    fieldList: {
        type: Object,
        required: true,
    },
    records: {
        type: Object,
    }
});

const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx');
const confirmation = inject('confirmation');

const identifier = props.fieldList.find( field => field.is_identifier === 1);
provide('identifier', identifier);
provide('fieldList', props.fieldList);

</script>
<template>
    <Head :title="$t('genie.strategies')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.strategies')">
            <template #description>
                {{ $t('genie.strategies_desc') }}
            </template>
        </PageHeader>

        <div class="w-full row-px mt-lg">
            <Link :href="route(`${routePrefix}.strategies.create`, {workspace: workspaceCtx.id})">
                <PrimaryButton size="sm">
                    <Plus class="mr-xs" />
                    {{ $t('genie.create_strategy') }}
                </PrimaryButton>
            </Link>


            <Panel :with-padding="false" class="mt-lg">
                <Table>
                    <template #head>
                        <TableRow>

                            <TableCell component="th" scope="col">{{ identifier?.name }}</TableCell>

                            <TableCell component="th" scope="col" class="hidden md:table-cell">
                                {{ $t('general.status') }}
                            </TableCell>

                            <TableCell component="th" scope="col"/>

                        </TableRow>
                    </template>
                    <template #body>
                        <template v-for="item in records.data" :key="item.id">

                            <StrategyItem :item="item" />

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
