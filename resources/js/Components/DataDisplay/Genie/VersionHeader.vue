<script setup>
import {usePage} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import {find} from "lodash";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const version = usePage().props.version;
const statusTypes = usePage().props.statusTypes;

const versionStatus = () => {
    return find(statusTypes, ['value', version.status]);
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
    <div class="row-px row-mt">

        <Flex class="justify-between items-start">
            <div>
                <Flex :responsive="false" class="items-center">

                    <h2 class="font-semibold text-lg">
                        {{ version.name }}
                    </h2>

                    <Badge :variant="statusBadge()">
                        {{ versionStatus().title }}
                    </Badge>

                    <Badge :variant="version.is_default ? 'success' : ''">
                        {{ version.is_default ? $t('genie.is_default') : '' }}
                    </Badge>

                </Flex>

                <div v-if="version.description" class="mt-xs text-md">
                    {{ version.description }}
                </div>
            </div>

            <slot/>

        </Flex>
    </div>
</template>
