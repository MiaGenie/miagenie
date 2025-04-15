<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, onMounted, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import {cloneDeep} from "lodash";
import usePageMode from "@/Composables/usePageMode";
import AdminLayout from "@/Layouts/Admin.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Select from "@/Components/Form/Select.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import RuleStepAction from "@/Components/Genie/Rules/RuleStepAction.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    rule: {
        type: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
    assistants: {
        type: Object,
        required: true
    },
    outputFields: {
        type: Object,
        required: true
    },
    record: {
        type: Object
    }
})

const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();
const confirmation = inject('confirmation');

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    name: '',
    description: '',
    assistant_id: '',
    message: '',
    output: '',
});

const store = () => {
    form.post(route('genie.admin.versions.rules.steps.store', {
        version: props.version.id,
        rule: props.rule.id
    }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.admin.versions.rules.steps.update', {
        version: props.version.id,
        rule: props.rule.id,
        step: props.record.id
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
        'genie.admin.versions.rules.steps.index',
        {
            version: props.version.id,
            rule: props.rule.id
        }
    ));
}

const deleteStep = () => {
    confirmation()
        .title($t("genie.delete_step"))
        .description($t("genie.delete_step_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.versions.rules.steps.delete',
                    {
                        version: props.version.id,
                        rule: props.rule.id,
                        step: props.record.id
                    }
                )
            );
        }).show();
}

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_step') : $t('genie.edit_step')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_step') : $t('genie.edit_step')" />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="name">{{ $t("general.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input v-model="form.name"
                               :error="form.errors.name !== undefined"
                               type="text"
                               id="name"
                               :autofocus="isCreate"
                               required
                        />

                        <template #footer>
                            <Error :message="form.errors.name"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="description">{{ $t("genie.description") }}</label>
                        </template>

                        <Textarea v-model="form.description"
                                  :error="form.errors.description !== undefined"
                                  id="description"
                                  class="w-full"
                                  rows="3"/>

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="assistant_id">{{ $t("genie.step_assistant_id") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.assistant_id"
                            :error="form.errors.assistant_id !== undefined"
                            id="assistant_id"
                            required
                        >
                            <template v-for="(assistant) in assistants">
                                <option :value="assistant.id">
                                    {{assistant.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.assistant_id"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_message") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.message"
                                  :error="form.errors.message !== undefined"
                                  id="message"
                                  class="w-full"
                                  rows="12"
                                  required/>

                        <template #footer>
                            <Error :message="form.errors.message"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="output">{{ $t("genie.step_output") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.output"
                            :error="form.errors.output !== undefined"
                            id="vector_id"
                            required
                        >
                            <template v-for="(output) in outputFields">
                                <option :value="output.code_name">
                                    {{output.code_name }} - {{ output.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.output"/>
                        </template>
                    </VerticalGroup>

                </Panel>

                <div class="flex flex-row items-center justify-between mt-lg">
                    <div class="flex gap-6">

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
                            @click="deleteStep"
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
