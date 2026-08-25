<script setup>
import {Head} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import usePageMode from "@/Composables/usePageMode";
import BriefingWizard from "@/Components/Form/Genie/Wizard.vue";

const {t: $t} = useI18n()

const {isCreate} = usePageMode();
</script>
<template>
    <Head :title="isCreate ? $t('genie.create_briefing') : $t('genie.edit_briefing')"/>

    <div class="row-px row-py">
        <BriefingWizard/>
    </div>
</template>
