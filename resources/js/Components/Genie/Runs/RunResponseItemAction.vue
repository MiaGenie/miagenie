<script setup>
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Eye from "@/Icons/Eye.vue";
import {usePage} from "@inertiajs/vue3";

const {t: $t} = useI18n()

const props = defineProps({
    itemId: {
        type: Number,
        required: true,
    }
})

const runId = usePage().props.runUuid;

const getRoute = (name) => {
    switch (name) {
        case 'view':
            return route('genie.admin.run_responses.view', {
                run_response: props.itemId,
            });
        default:
            return '';
    }
}

</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-lg">
            <PureButtonLink
                :href="getRoute('view')"
                v-tooltip="$t('general.view')"
            >
                <Eye/>
            </PureButtonLink>
        </div>
    </div>
</template>
