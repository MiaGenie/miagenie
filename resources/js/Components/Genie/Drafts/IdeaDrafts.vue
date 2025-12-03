<script setup>
import Panel from "@/Components/Surface/Panel.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import DraftItem from "@/Components/Genie/Drafts/DraftItem.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    isLoading: {
        type: Boolean,
        required: true,
    }
})


</script>
<template>

    <Panel
        :with-padding="false"
        class="mt-lg"
    >
        <Table>
            <template #head>

                <TableRow>

                    <TableCell component="th" scope="col" class="w-10">

                    </TableCell>

                    <TableCell
                        component="th"
                        scope="col"
                    >
                        {{ $t('genie.drafts_topic') }}
                    </TableCell>

                    <TableCell
                        component="th"
                        scope="col"
                        class="hidden sm:table-cell"
                    >
                        {{ $t('general.status') }}
                    </TableCell>

                    <TableCell
                        component="th"
                        scope="col"
                    />

                </TableRow>

            </template>

            <template #body>
                <template
                    v-for="item in data"
                    :key="item.id"
                >
                    <DraftItem :item="item" />

                </template>
            </template>

        </Table>

        <NoResult
            v-if="!data"
            class="py-md px-md"
        />

    </Panel>

</template>
