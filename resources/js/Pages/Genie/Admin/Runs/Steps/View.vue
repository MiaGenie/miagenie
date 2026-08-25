<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import {find} from "lodash";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import RunHeader from "@/Components/DataDisplay/Genie/RunHeader.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import X from "@/Icons/X.vue";
import Trash from "@/Icons/Trash.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    versionName: {
        type: String,
    },
    workspaceName: {
        type: String,
    },
    ruleName: {
        type: String,
    },
    ruleType: {
        type: String,
    },
    runStep: {
        type: Object,
        default: {}
    },
    ruleStep: {
        type: Object,
        default: null
    },
    ruleSubTypes: {
        type: Object,
        required: true
    },
    runStatus: {
        type: Object,
        required: true
    },
    modalities: {
        type: Object,
        required: true
    },
    conversation: {
        type: Object,
        default: () => ({})
    },
    isLast: {
        type: Boolean,
    }
});

const confirmation = inject('confirmation');

const blank = '----------';

/**
 * The rule step is gone once an admin deletes it, which nulls `step_id` and leaves the run's own
 * record of the turn behind.
 */
const ruleSubType = () => {
    return find(props.ruleSubTypes, ['value', props.ruleStep?.rule_sub_type])?.name ?? blank;
}

const status = () => {
    return find(props.runStatus, ['value', Number(props.runStep.status)])?.name ?? blank;
}

const modality = () => {
    return find(props.modalities, ['value', Number(props.runStep.modality)])?.title ?? blank;
}

const usage = () => {
    return props.conversation.usage
        ? Object.entries(props.conversation.usage).map(([key, value]) => `${key}: ${value}`).join(', ')
        : blank;
}

const backToList = () => {
    router.get(route(
        'genie.admin.runs.steps.index', {
            run: route().params.run,
        }));
}

const deleteStep = () => {
    confirmation()
        .title($t("genie.delete_run_step"))
        .description($t("genie.delete_run_step_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.runs.steps.delete',
                    {
                        run: route().params.run,
                        step: props.runStep.id
                    }
                )
            );
        }).show();
}

</script>
<template>
    <Head :title="$t('genie.run_step_data')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.run_step_data')"/>

        <RunHeader/>

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <div class="pb-2 pl-2">
                    {{ $t('genie.rule_run_step') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ ruleStep?.name ?? blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.rule_run_sub_type') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ ruleSubType() }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_position') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.position }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.created_at') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.created_at }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.status') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ status() }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_modality') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ modality() }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_duration') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.duration !== null ? `${runStep.duration}s` : blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_invocation_id') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.invocation_id ?? blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_message_id') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.message_id ?? blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_error') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.error ?? blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_error_details') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ runStep.error_details ?? blank }}
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_step_output') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    <pre class="wrap-anywhere text-wrap">{{ JSON.stringify(runStep.output, null, 4) }}</pre>
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_prompt') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    <pre class="wrap-anywhere text-wrap">{{ conversation.prompt ?? blank }}</pre>
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_answer') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    <pre class="wrap-anywhere text-wrap">{{ conversation.answer ?? blank }}</pre>
                </div>

                <div class="pb-2 pl-2 pt-5">
                    {{ $t('genie.run_token_usage') }}
                </div>
                <div class="bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words">
                    {{ usage() }}
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
                            @click="deleteStep"
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
