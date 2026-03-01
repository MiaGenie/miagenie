<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, onBeforeMount, onMounted, ref, watch} from "vue";
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
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Check from "@/Icons/Check.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Document from "@/Icons/Document.vue";
import ArrowUturnLeft from "@/Icons/ArrowUturnLeft.vue";
import SuccessButton from "@/Components/Button/SuccessButton.vue";



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
    draftStatusTypes: {
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

const {isCreate, isEdit, isView} = usePageMode();
const {onError} = useRouter();
const confirmation = inject('confirmation');

const form = useForm(isEdit.value || isView.value ? cloneDeep(props.record) : {
    topic: '',
    goal: '',
    key_ideas: '',
    media: '',
    status: 1
});

onBeforeMount( () => {
    form.defaults();
})


const store = () => {
    form.post(route('genie.drafts.store',
        {
            workspace: workspaceCtx.id
        }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const restore = () => {
    form.status = statusValue('PENDING_REVIEW').value;
    update();
}

const update = () => {
    form.put(route('genie.drafts.update', {
        draft: props.record.id,
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
        'genie.drafts.index',
        {
            workspace: workspaceCtx.id,
            funnel_stage: props.funnelStage > 0 ? props.funnelStage : null,
        }
    ));
}

const deleteDraft = () => {
    confirmation()
        .title($t("genie.delete_draft"))
        .description($t("genie.delete_draft_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.drafts.delete',
                    {
                        workspace: workspaceCtx.id,
                        draft: props.record.id
                    }
                )
            );
        }).show();
}

const statusValue = (status) => {
    return find(props.draftStatusTypes, ['name', status]);
}

const formStatus = () => {
    return find(props.draftStatusTypes, ['value', Number(form.status)]);
}

const approvedStatus = () => {
    return find(props.draftStatusTypes, ['name', 'APPROVED']).value;
}

const formStatusApproved = () => {
    return formStatus()?.name === 'APPROVED';
}

const statusBadge = () => {
    switch (formStatus().name) {
        case 'APPROVED':
            return 'success';
        case 'PENDING_REVIEW':
            return 'warning';
        case 'PUBLISHED':
            return 'info';
        case 'DISMISSED':
            return 'error';
        default:
            return '';
    }
}

const approveDraft = () => {
    confirmation()
        .title($t('genie.approve_draft'))
        .description($t('genie.approve_draft_confirm'))
        .warning()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            form.status = approvedStatus();

            form.put(route('genie.drafts.approve', {
                draft: props.record.id,
                workspace: workspaceCtx.id
            }), {
                preserveScroll: true,
                onError: (errors) => {
                    onError(errors, update);
                }
            });

            dialog.reset();
        })
        .show();
}

const viewPost = () => {
    router.get(route('mixpost.posts.edit',{
        workspace: workspaceCtx.id,
        post: props.record.post
    }));
}

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_draft') : mode === 'edit' ? $t('genie.edit_draft') : $t('genie.view_draft')"/>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_draft') : mode === 'edit' ? $t('genie.edit_draft') : $t('genie.view_draft')"/>
        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg mx-auto">
                        <template #title>
                            <label for="topic">{{ $t("genie.drafts_topic") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input v-model="form.topic"
                               :disabled="isView"
                               :error="form.errors.topic !== undefined"
                               type="text"
                               id="topic"
                               :autofocus="isCreate"
                               required
                        />

                        <template #footer>
                            <Error :message="form.errors.topic"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg  mx-auto">
                        <template #title>
                            <label for="goal">{{ $t("genie.drafts_goal") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.goal"
                                  :disabled="isView"
                                  :error="form.errors.goal !== undefined"
                                  id="goal"
                                  class="w-full"
                                  rows="10"
                                  required
                        />

                        <template #footer>
                            <Error :message="form.errors.goal"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg  mx-auto">
                        <template #title>
                            <label for="key_ideas">{{ $t("genie.drafts_key_ideas") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.key_ideas"
                                  :disabled="isView"
                                  :error="form.errors.key_ideas !== undefined"
                                  id="key_ideas"
                                  class="w-full"
                                  rows="10"
                                  required
                        />

                        <template #footer>
                            <Error :message="form.errors.key_ideas"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg  mx-auto">
                        <template #title>
                            <label for="media">{{ $t("genie.drafts_media") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.media"
                                  :disabled="isView"
                                  :error="form.errors.media !== undefined"
                                  id="media"
                                  class="w-full"
                                  rows="10"
                                  required
                        />

                        <template #footer>
                            <Error :message="form.errors.media"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg  mx-auto">
                        <template #title>
                            <label for="status">{{ $t('general.status') }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input v-model="form.status"
                               :hidden="true"
                               id="status"
                        />

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
                        <WarningButton
                            v-if="formStatus().name === 'PUBLISHED'"
                            @click="viewPost"
                            :isLoading="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("genie.view_post") }}
                            <template #icon>
                                <Document/>
                            </template>
                        </WarningButton>

                        <WarningButton
                            v-if="isEdit && formStatus().name === 'PENDING_REVIEW'"
                            @click="approveDraft"
                            :isLoading="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("genie.generate_post") }}
                            <template #icon>
                                <Check/>
                            </template>
                        </WarningButton>

                        <PrimaryButton
                            v-if="formStatus().name === 'PENDING_REVIEW'"
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
                            @click="deleteDraft"
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
