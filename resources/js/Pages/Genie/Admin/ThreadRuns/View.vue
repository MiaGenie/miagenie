<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Label from "mixpost-enterprise/resources/js/Components/Form/Label.vue";
import ListItem from "mixpost-enterprise/resources/js/Components/DataDisplay/ListItem.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    threadRun: {
        type: Object,
        default: {}
    },
    threadUuid: {
        type: String,
        require: true
    }
});

const backToList = () => {
    router.get(route(
        'genie.admin.thread_runs.index', {
            thread: props.threadUuid,
        }));
}

</script>
<template>
    <Head :title="$t('genie.thread_run_message')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.thread_run_message')"/>

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <Label class="mt-lg">{{ $t('genie.thread_uuid') }}</Label>
                <ListItem :active="true">{{ threadRun.uuid }}</ListItem>

                <Label class="mt-lg">{{ $t('genie.rule_thread_step') }}</Label>
                <ListItem :active="true">{{ threadRun.step_id }}</ListItem>

                <Label class="mt-lg">{{ $t('genie.status') }}</Label>
                <ListItem :active="true">{{ threadRun.status }}</ListItem>

                <Label class="mt-lg">{{ $t('genie.thread_run_message') }}</Label>
                <ListItem :active="true" :withClassesForLast="false">{{ threadRun.message ? threadRun.message.text.value : '' }}</ListItem>

            </Panel>

            <div class="flex flex-row items-center justify-between mt-lg">
                <div class="flex gap-6">

                    <PrimaryButton
                        @click="backToList"
                        type="button"
                    >
                        {{ $t("general.close") }}
                        <template #icon>
                            <X/>
                        </template>
                    </PrimaryButton>

                </div>
            </div>
        </div>
    </div>
</template>
