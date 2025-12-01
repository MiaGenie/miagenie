<script setup>
import {inject, provide, reactive} from "vue";
import {useI18n} from "vue-i18n";
import Panel from "@/Components/Surface/Panel.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import DashboardPostItem from "@/Components/Genie/Dashboard/DashboardPostItem.vue";

const {t: $t} = useI18n()

const props = defineProps({
    posts: {
        type: Object,
    },
    type: {
        type: String,
        default: ""
    },
    title: {
        type: String,
        default: ""
    }
});

const postContext = reactive({
    urlMeta: {},
});

provide('postCtx', postContext);

const workspaceCtx = inject('workspaceCtx');

</script>
<template>

    <div class="w-full">

        <Panel>
            <template #title><span
                v-tooltip="$t('service.facebook.report.number_times_post_engagements')">{{ title }}</span>
            </template>

            <Table>
                <template #head>
                    <TableRow>
                        <TableCell v-if="type === 'scheduled'" component="th" scope="col" class="!pl-0 text-left">
                            {{ $t("genie.date") }}
                        </TableCell>
                        <TableCell component="th" scope="col" class="!pl-0 text-left">
                            {{ $t("post.content") }}
                        </TableCell>
                        <TableCell v-if="type === 'scheduled'" component="th" scope="col">{{ $t("post.accounts") }}</TableCell>
                        <TableCell component="th" scope="col"/>
                    </TableRow>
                </template>
                <template #body>
                    <template v-for="item in posts.data" :key="item.id">
                        <DashboardPostItem :item="item" :type="type" />
                    </template>
                </template>
            </Table>

            <NoResult v-if="!posts.meta.total" :withPadding="true">{{ $t("post.no_posts_found") }}</NoResult>
        </Panel>
    </div>

</template>
