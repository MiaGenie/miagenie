<script setup>
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Eye from "mixpost-enterprise/resources/js/Icons/Eye.vue";

const {t: $t} = useI18n()

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'view':
            return route('genie.admin.thread_runs.view', {
                thread_run: props.itemId,
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
