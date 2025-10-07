<script setup>
import {useI18n} from "vue-i18n";
import {find} from "lodash";
import {usePage} from "@inertiajs/vue3";
import VersionItemAction from "./VersionItemAction.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const statusTypes = usePage().props.statusTypes;

const versionStatus = () => {
    return find(statusTypes, ['value', Number(props.item.status)]);
}

const statusBadge = () => {
    switch (versionStatus().name) {
        case 'ENABLED':
            return 'success';
        case 'DISABLED':
            return 'dark';
        case 'TESTING':
            return 'warning';
        case 'ARCHIVED':
            return 'info';
        default:
            return '';
    }
}

</script>
<template>
    <TableRow :hoverable="true">

        <TableCell>
            {{ item.name }}
            <Flex class="items-start">
                <Badge
                    :variant="statusBadge()"
                    class="sm:hidden">
                    {{ versionStatus().title }}
                </Badge>

                <Badge
                    :variant="item.is_default ? 'success' : ''"
                    class="md:hidden"
                >
                    {{ item.is_default ? $t('genie.is_default') : '' }}
                </Badge>
            </Flex>
        </TableCell>

        <TableCell class="hidden sm:table-cell">
            <Badge :variant="statusBadge()">
                {{ versionStatus().title }}
            </Badge>
        </TableCell>

        <TableCell class="hidden md:table-cell">
            <Badge :variant="item.is_default ? 'success' : ''">
                {{ item.is_default ? $t('general.yes') : '' }}
            </Badge>
        </TableCell>

        <TableCell>
            <VersionItemAction :itemId="item.id"/>
        </TableCell>

    </TableRow>
</template>
