<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import {cloneDeep, find} from "lodash";
import usePageMode from "@/Composables/usePageMode";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Select from "@/Components/Form/Select.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import SuccessButton from "@/Components/Button/SuccessButton.vue";
import Check from "@/Icons/Check.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import ArrowUturnLeft from "@/Icons/ArrowUturnLeft.vue";

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    funnelStages: {
        type: Object,
        required: true
    },
    funnelStage: {
        type: String
    },
    postFormats: {
        type: Object,
        required: true
    },
    ideaStatusTypes: {
        type: Object,
        required: true
    },
    contentPillars: {
        type: Array,
        required: true
    },
    record: {
        type: Object
    },
})

const workspaceCtx = inject('workspaceCtx');

const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();
const confirmation = inject('confirmation');

/**
 * The texts every idea carries, in the order they are shown; each has a label and a helper text.
 */
const ideaTexts = ['objective', 'suggested_content', 'multimedia_suggestion', 'production_instructions'];

// A null select value would match no option, so a stored null reads as the blank choice.
const nullToBlank = (record) => ({
    ...record,
    post_format: record.post_format ?? '',
    content_pillar: record.content_pillar ?? '',
    funnel_stage: record.funnel_stage ?? '',
});

const form = useForm(isEdit.value ? nullToBlank(cloneDeep(props.record)) : {
    theme: '',
    objective: '',
    suggested_content: '',
    multimedia_suggestion: '',
    production_instructions: '',
    post_format: '',
    content_pillar: '',
    funnel_stage: props.funnelStage ?? '',
    status: '1',
    source: '2'
});

const store = () => {
    form.transform(blankToNull).post(route('genie.ideas.store',
        {
            workspace: workspaceCtx.id
        }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.transform(blankToNull).put(route('genie.ideas.update', {
        idea: props.record.id,
        workspace: workspaceCtx.id
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
}

// An unpicked select is an empty string on the form and a null in the database.
const blankToNull = (data) => ({
    ...data,
    post_format: data.post_format === '' ? null : data.post_format,
    content_pillar: data.content_pillar === '' ? null : data.content_pillar,
    funnel_stage: data.funnel_stage === '' ? null : data.funnel_stage,
});

const restore = () => {
    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.confirmation_restore_idea'))
        .btnConfirmName($t('genie.restore'), {})
        .onConfirm((dialog) => {
            dialog.isLoading(true);
            form.status = statusValue('PENDING_REVIEW').value;
            update();
            dialog.close();
        })
        .show();
}

const submit = () => {
    if (isCreate.value) {
        store();
    }

    if (isEdit.value) {
        update();
    }
}

const attemptClose = () => {
    if (!form.isDirty) {
        backToList();
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm(() => {
            backToList();
        })
        .show();
}

const backToList = () => {
    router.get(route(
        'genie.ideas.index',
        {
            workspace: workspaceCtx.id,
            funnel_stage: props.funnelStage > 0 ? props.funnelStage : null,
        }
    ));
}

const deleteIdea = () => {
    confirmation()
        .title($t("genie.delete_idea"))
        .description($t("genie.delete_idea_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.ideas.delete',
                    {
                        workspace: workspaceCtx.id,
                        idea: props.record.id
                    }
                )
            );
        }).show();
}

const statusValue = (status) => {
    return find(props.ideaStatusTypes, ['name', status]);
}

const formStatus = () => {
    return find(props.ideaStatusTypes, ['value', Number(form.status)]);
}

const statusBadge = () => {
    switch (formStatus().name) {
        case 'APPROVED':
            return 'success';
        case 'PENDING_REVIEW':
            return 'warning';
        case 'TRASH':
            return 'error';
        default:
            return '';
    }
}

const approveIdea = () => {
    confirmation()
        .title($t('genie.approve_idea'))
        .description($t('genie.approve_idea_confirm'))
        .warning()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            form.status = statusValue('APPROVED').value;

            update();

            dialog.reset();
        })
        .show();
}
</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_idea') : $t('genie.edit_idea')"/>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_idea') : $t('genie.edit_idea')" />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg  mx-auto">
                        <template #title>
                            <label for="theme">{{ $t("genie.idea_title") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input v-model="form.theme"
                               :error="form.errors.theme !== undefined"
                               type="text"
                               id="theme"
                               :autofocus="isCreate"
                               required
                        />

                        <template #footer>
                            <Error :message="form.errors.theme"/>
                        </template>
                    </VerticalGroup>

                    <div class="form-field flex mt-lg justify-between mx-auto flex-col sm:flex-row gap-md">

                        <VerticalGroup class="form-field mt-lg">
                            <template #title>
                                <label for="post_format">{{ $t("genie.post_format") }}</label>
                            </template>

                            <Select
                                v-model="form.post_format"
                                id="post_format"
                            >
                                <option value="">{{ $t('genie.post_format_any') }}</option>
                                <option v-for="postFormat in postFormats" :key="postFormat.value" :value="postFormat.value">
                                    {{ $t(`genie.post_format_${postFormat.title}`) }}
                                </option>
                            </Select>

                            <template #footer>
                                <Error :message="form.errors.post_format"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field mt-lg">
                            <template #title>
                                <label for="funnel_stage">{{ $t("genie.funnel_stage") }}</label>
                            </template>

                            <Select
                                v-model="form.funnel_stage"
                                id="funnel_stage"
                            >
                                <option value="">{{ $t('general.all') }}</option>
                                <option v-for="funnelStage in funnelStages" :key="funnelStage.value" :value="funnelStage.value">
                                    {{ $t(`genie.funnel_stage_${funnelStage.title}`) }}
                                </option>
                            </Select>

                            <template #footer>
                                <Error :message="form.errors.funnel_stage"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field mt-lg">
                            <template #title>
                                <label for="content_pillar">{{ $t("genie.content_pillar") }}</label>
                            </template>

                            <Select
                                v-model="form.content_pillar"
                                id="content_pillar"
                            >
                                <option value="">{{ $t('general.all') }}</option>
                                <option v-for="contentPillar in contentPillars" :key="contentPillar" :value="contentPillar">
                                    {{ contentPillar }}
                                </option>
                            </Select>

                            <template #footer>
                                <Error :message="form.errors.content_pillar"/>
                            </template>
                        </VerticalGroup>

                    </div>

                    <VerticalGroup
                        v-for="text in ideaTexts"
                        :key="text"
                        class="form-field mt-lg mx-auto"
                    >
                        <template #title>
                            <label :for="text">{{ $t(`genie.idea_${text}`) }}</label>
                        </template>

                        <Textarea v-model="form[text]"
                                  :error="form.errors[text] !== undefined"
                                  :id="text"
                                  class="w-full"
                                  rows="4"
                                  :placeholder="$t(`genie.idea_${text}_desc`)"
                        />

                        <template #footer>
                            <div class="text-sm text-gray-500">{{ $t(`genie.idea_${text}_desc`) }}</div>
                            <Error :message="form.errors[text]"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg mx-auto">

                        <template #title>
                            <label for="status">{{ $t('general.status') }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Badge :variant="statusBadge()">
                            {{ $t(`genie.${formStatus().title}`) }}
                        </Badge>

                        <template #footer>
                            <Error :message="form.errors.status"/>
                        </template>

                    </VerticalGroup>

                </Panel>

                <div class="flex flex-row items-center justify-between mt-lg">
                    <div class="flex gap-6">
                        <SuccessButton
                            v-if="isEdit && formStatus().name === 'PENDING_REVIEW'"
                            @click="approveIdea"
                            :isLoading="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("post.approve") }}
                            <template #icon>
                                <Check/>
                            </template>
                        </SuccessButton>

                        <PrimaryButton
                            v-if="formStatus().name !== 'TRASH'"
                            type="submit"
                            :isLoading="form.processing"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ isCreate ? $t("general.create") : $t("general.update") }}
                            <template #icon>
                                <Save/>
                            </template>
                        </PrimaryButton>

                        <SecondaryButton
                            @click="attemptClose"
                            type="button"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.close") }}
                            <template #icon>
                                <X/>
                            </template>
                        </SecondaryButton>

                    </div>
                    <div class="flex gap-6">

                        <SuccessButton
                            v-if="formStatus().name === 'TRASH'"
                            @click="restore"
                            :isLoading="form.processing"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.restore") }}
                            <template #icon>
                                <ArrowUturnLeft/>
                            </template>
                        </SuccessButton>

                        <DangerButton
                            v-if="formStatus().name === 'PENDING_REVIEW' || formStatus().name === 'TRASH'"
                            @click="deleteIdea"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.delete") }}
                            <template #icon>
                                <Trash/>
                            </template>
                        </DangerButton>

                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
