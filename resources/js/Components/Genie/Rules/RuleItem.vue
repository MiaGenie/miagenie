<script setup>
import {find} from "lodash";
import {useI18n} from "vue-i18n";
import {usePage} from "@inertiajs/vue3";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import RuleItemAction from "@/Components/Genie/Rules/RuleItemAction.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";

const {t: $t} = useI18n();

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    isFiltered: {
        type: Boolean,
        default: false
    }
})

const ruleTypes = usePage().props.ruleTypes;

const currentType = () => {
    return find(ruleTypes, ['value', Number(props.item.rule_type)]);
}

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
            <Flex
                class="text-gray-500 italic lg:hidden"
            >
                {{ item.version_id }}
            </Flex>
            <Flex
                class="text-gray-500 italic"
                :class="[isFiltered ? 'hidden' : 'lg:hidden']"
            >
                {{ $t(`genie.rule_type_${currentType().title}`) }}
            </Flex>
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

        <TableCell
            class="hidden lg:table-cell"
        >
            {{ item.version_id }}
        </TableCell>

        <TableCell
            class="hidden"
            :class="[isFiltered ? '' : 'lg:table-cell']"
        >
            {{ $t(`genie.rule_type_${currentType().title}`) }}
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
            <RuleItemAction
                :itemId="item.id"
            />
        </TableCell>

    </TableRow>
</template>
