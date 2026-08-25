<script setup>
import { useI18n } from "vue-i18n";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import ModelProfileItemAction from "@/Components/Genie/ModelProfiles/ModelProfileItemAction.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";

const { t: $t } = useI18n();

defineProps({
    item: {
        type: Object,
        required: true,
    },
});

/**
 * A profile on a tier has no model name of its own — the SDK resolves one per provider.
 */
const modelLabel = (item) =>
    item.model_tier === "other" ? item.model : item.model_tier;
</script>
<template>
    <TableRow :hoverable="true">
        <TableCell>
            {{ item.name }}

            <Flex class="text-gray-500 italic lg:hidden">
                {{ item.provider }} / {{ modelLabel(item) }}
            </Flex>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            <Badge variant="info">{{ item.provider }}</Badge>
        </TableCell>

        <TableCell class="hidden lg:table-cell">
            {{ modelLabel(item) }}
        </TableCell>

        <TableCell>
            <ModelProfileItemAction :itemId="item.id" />
        </TableCell>
    </TableRow>
</template>
