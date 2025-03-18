<script setup>
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import QueueList from "@/Icons/QueueList.vue";
import {inject} from "vue";

const {t: $t} = useI18n();
const routePrefix = inject('routePrefix');

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
})

const getRoute = () => {
    return route('genie.admin.versions.groups.fields.index', {
        version: props.version.id,
        group: props.item.id,
    });
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.type }}
        </TableCell>

        <TableCell>
            <PureButtonLink
                :href="getRoute()"
                v-tooltip="$t('genie.fields')"
            >
                <QueueList/>
            </PureButtonLink>
        </TableCell>

    </TableRow>
</template>
