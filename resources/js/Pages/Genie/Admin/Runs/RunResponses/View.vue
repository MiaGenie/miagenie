<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import {find} from "lodash";
import RunResponseHeader from "@/Components/DataDisplay/Genie/RunResponseHeader.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Trash from "@/Icons/Trash.vue";
import {inject} from "vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    versionName: {
        type: String,
        require: true
    },
    workspaceName: {
        type: String,
        require: true
    },
    ruleName: {
        type: String,
        require: true
    },
    runResponse: {
        type: Object,
        default: {}
    },
    ruleType: {
        type: String,
        required: true
    },
    ruleStep: {
        type: Object,
        default: {}
    },
    ruleSubTypes: {
        type: Object,
        required: true
    },
    runResponseProviderStatus: {
        type: Object,
        required: true
    },
    runResponseStatus: {
        type: Object,
        required: true
    },
    isLast: {
        type: Boolean,
    }
});

const confirmation = inject('confirmation');

const getRuleSubType = () => {
    return find(props.ruleSubTypes, ['value', props.ruleStep.rule_sub_type]).name;
}

const status = () => {
    return find(props.runResponseStatus, ['value', Number(props.runResponse.status)]).name;
}

const providerStatus = () => {
    return find(props.runResponseProviderStatus, ['value', Number(props.runResponse.provider_status)])?.name;
}

const backToList = () => {
    router.get(route(
        'genie.admin.runs.run_responses.index', {
            run: route().params.run,
        }));
}

const deleteResponse = () => {
    confirmation()
        .title($t("genie.delete_run_response"))
        .description($t("genie.delete_run_response_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.runs.run_responses.delete',
                    {
                        run: route().params.run,
                        run_response: props.runResponse.id
                    }
                )
            );
        }).show();
}

</script>
<template>
    <Head :title="$t('genie.run_response_data')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.run_response_data')"/>

        <RunResponseHeader />

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <div class="pb-2 pl-2">
                    {{ $t('genie.rule_run_step') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words ">
                    {{ ruleStep.name }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.rule_run_sub_type') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{  getRuleSubType()}}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.created_at') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runResponse.created_at }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.status') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ status() }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_response_message') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ ruleStep.message ? ruleStep.message : '----------' }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.response_provider_id') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runResponse.response_provider_id }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.response_provider_status') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ providerStatus() }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_response_error') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runResponse.error ? runResponse.error : '----------' }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_response_error_details') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runResponse.error_details ? runResponse.error_details : '----------' }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_response_output') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    <pre class="wrap-anywhere text-wrap">{{ JSON.stringify(runResponse.output, null, 4) }}</pre>
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_response_output_text') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runResponse.output_text }}
                </div>
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

                    <div v-if="isLast">

                        <DangerButton
                            @click="deleteResponse"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.delete") }}
                            <template #icon>
                                <Trash/>
                            </template>
                        </DangerButton>

                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
