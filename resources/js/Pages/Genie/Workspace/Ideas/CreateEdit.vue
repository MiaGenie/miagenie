<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, onMounted, ref, watch} from "vue";
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
import Label from "@/Components/Form/Label.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Switch from "@/Components/Form/Switch.vue";
import DraftIcon from "mixpost-pro-team/resources/js/Icons/Genie/Draft.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";


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
    statusTypes: {
        type: Object,
        required: true
    },
    contentPillars: {
        type: Object,
        required: true
    },
    record: {
        type: Object
    }
})

const workspaceCtx = inject('workspaceCtx');

const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();
const confirmation = inject('confirmation');

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    funnel_stage: props.funnelStage ?? '',
    theme: '',
    description: '',
    status: '1'
});

const store = () => {
    form.post(route('genie.ideas.store',
        {
            workspace: workspaceCtx.id
        }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.ideas.update', {
        idea: props.record.id,
        workspace: workspaceCtx.id
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
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

const currentStatus = () => {
    return find(props.statusTypes, ['value', Number(props.record.status)]);
}

const formStatus = () => {
    return find(props.statusTypes, ['value', Number(form.status)]);
}

const formStatusApproved = () => {
    return formStatus()?.name === 'APPROVED';
}

const currentStatusApproved = () => {
    return currentStatus()?.name === 'APPROVED';
}

const updateGenerateDraft = () => {
    confirmation()
        .title($t('genie.generate_draft'))
        .description($t('genie.generate_draft_confirm'))
        .warning()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            form.put(route('genie.ideas.updateGenerate', {
                idea: props.record.id,
                workspace: workspaceCtx.id
            }), {
                preserveScroll: true,
                onError: (errors) => {
                    onError(errors, update);
                },
            });

        })
        .show();
}

const generateDraft = () => {
    confirmation()
        .title($t('genie.generate_draft'))
        .description($t('genie.generate_draft_confirm'))
        .warning()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.put(route('genie.drafts.generate',{
                workspace: workspaceCtx.id,
                idea: props.record.id
            }));
        })
        .show();
}

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_idea') : $t('genie.edit_idea')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_idea') : $t('genie.edit_idea')" />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="theme">{{ $t("genie.ideas_theme") }}
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

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="description">{{ $t("genie.description") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.description"
                                  :error="form.errors.description !== undefined"
                                  id="description"
                                  class="w-full"
                                  rows="6"
                                  required
                        />

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="funnel_stage">{{ $t("genie.funnel_stage") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.funnel_stage"
                            :disabled="isEdit"
                            id="funnel_stage"
                            required
                        >
                            <option v-for="funnelStage in funnelStages" :value="funnelStage.value">
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
                            :disabled="isEdit"
                            id="content_pillar"
                        >
                            <option v-for="contentPillar in contentPillars" :value="contentPillar">
                                {{ contentPillar }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.content_pillar"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="status">{{ $t('general.status') }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Flex class="items-start">

                            <Select
                                v-model="form.status"
                                id="status"
                                required
                            >
                                <option
                                    v-for="status in props.statusTypes"
                                    :value="status.value"
                                >
                                    {{ $t(`genie.${status.title}`) }}
                                </option>
                            </Select>

                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.status"/>
                        </template>
                    </VerticalGroup>

                </Panel>

                <div class="flex flex-row items-center justify-between mt-lg">
                    <div class="flex gap-6">
                        <WarningButton
                            v-if="currentStatusApproved()"
                            @click="generateDraft"
                            :hiddenTextOnSmallScreen="true"
                            :disabled="form.processing"
                            :isLoading="form.processing"
                            size="sm"
                        >

                            <template #icon>
                                <DraftIcon/>
                            </template>
                            {{ $t('genie.generate_draft') }}
                        </WarningButton>

                        <PrimaryButton
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

                        <WarningButton
                            v-if="!currentStatusApproved() && formStatusApproved()"
                            @click="updateGenerateDraft"
                            :hiddenTextOnSmallScreen="true"
                            :disabled="form.processing"
                            :isLoading="form.processing"
                            size="sm"
                        >

                            <template #icon>
                                <DraftIcon/>
                            </template>
                            {{ $t('genie.update_generate_draft') }}
                        </WarningButton>

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
                    <div v-if="isEdit">

                        <DangerButton
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
