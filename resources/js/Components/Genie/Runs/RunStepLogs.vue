<script setup>
import { useI18n } from "vue-i18n";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import LogItemAction from "@/Components/Genie/Logs/LogItemAction.vue";

const { t: $t } = useI18n();

defineProps({
    /**
     * The genie_logs entries made for this run step, newest first: one per model call.
     */
    logs: {
        type: Array,
        required: true,
    },
});

const blank = "----------";

/**
 * What one call was about, from what the log recorded about the request and the response.
 */
const summary = (log) => {
    const parts = [];

    if (log.request?.position) parts.push($t("genie.idea_image", { n: log.request.position }));
    if (log.request?.mode) parts.push(log.request.mode);
    if (log.response?.file) parts.push(log.response.file);
    // A web-searching call lists the pages it cited; the entry itself (LogItemAction) has the URLs.
    if (log.response?.citations?.length)
        parts.push($t("genie.run_step_sources", { n: log.response.citations.length }));

    return parts.join(" · ") || blank;
};
</script>

<template>
    <Table>
        <template #head>
            <TableRow>
                <TableCell component="th" scope="col">{{ $t("general.created_at") }}</TableCell>
                <TableCell component="th" scope="col">{{ $t("genie.step_model") }}</TableCell>
                <TableCell component="th" scope="col">{{ $t("genie.run_step_duration") }}</TableCell>
                <TableCell component="th" scope="col">{{ $t("genie.run_step_call") }}</TableCell>
                <TableCell component="th" scope="col" class="w-10" />
            </TableRow>
        </template>

        <template #body>
            <TableRow v-for="log in logs" :key="log.id" :hoverable="true">
                <TableCell class="text-sm whitespace-nowrap">{{ log.created_at }}</TableCell>
                <TableCell class="text-sm">
                    {{ log.request?.model ?? blank }}
                    <span v-if="log.request?.provider" class="text-gray-500">({{ log.request.provider }})</span>
                </TableCell>
                <TableCell class="text-sm">{{ log.duration ?? blank }}s</TableCell>
                <TableCell class="text-sm break-all">
                    <Badge v-if="log.response?.error" variant="error" class="mr-xs">{{ $t("genie.run_step_error") }}</Badge>
                    {{ log.response?.error ?? summary(log) }}
                </TableCell>
                <TableCell>
                    <LogItemAction :item-id="log.id" />
                </TableCell>
            </TableRow>
        </template>
    </Table>
</template>
