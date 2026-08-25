<script setup>
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Eye from "@/Icons/Eye.vue";

const {t: $t} = useI18n()

const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'view':
            return route('genie.admin.runs.steps.view', {
                run: route().params.run,
                step: props.item.id,
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
