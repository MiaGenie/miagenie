<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {cloneDeep, find} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import CompetitorAction from "@/Components/Genie/Competitors/CompetitorAction.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Trash from "@/Icons/Trash.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    fieldList: {
        type: Object,
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    record: {
        type: Object
    }
})

const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx')
const confirmation = inject('confirmation');
const authPasswordConfirmation = inject('authPasswordConfirmation');

const {notify} = useNotifications();
const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

const form = useForm(isEdit.value ? cloneDeep(props.record) :

    props.fieldList.competitors.reduce(
        (list, field) => {
            list.content[field.code_name] = fieldType(field).hasOptions ? [] : '';
            return list;
        }, {
            'content': {}
        }
    )
);

const store = () => {
    form.transform((data) => ({
        ...data,
        'version' : props.fieldList.uuid
    })).post(route(`${routePrefix}.competitors.store`, {
        'workspace': workspaceCtx.id
    }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.transform((data) => ({
        ...data,
        'version' : props.fieldList.uuid
    })).put(route(`${routePrefix}.competitors.update`, {
        'workspace': workspaceCtx.id,
        competitor: props.record.id
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
    router.get(route('genie.competitors.index', {
        workspace: workspaceCtx.id
    }));
}

const deleteCompetitor = () => {
    confirmation()
        .title($t("genie.delete_competitor"))
        .description($t("genie.delete_competitor_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route('genie.competitors.delete',
                    {
                        workspace: workspaceCtx.id,
                        competitor: props.record.id
                    }), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.competitor_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        })
        .show();
}

</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_competitor') : $t('genie.edit_competitor')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="mode === 'create' ? $t('genie.create_competitor') : $t('genie.edit_competitor')">
            <template v-if="isEdit">
                <CompetitorAction :record="record" />
            </template>
        </PageHeader>

        <div class="row-px">
            <form method="post" @submit.prevent="submit">

                <Panel>

                    <template #title>{{ $t("general.details") }}</template>

                    <template v-for="(field, index) in fieldList.competitors" :key="index">
                        <VerticalGroup class="form-field mt-lg">
                            <template #title>
                                <label :for="field.code_name">{{ field.name }}</label>
                                <LabelSuffix v-if="field.required" :danger="true">*</LabelSuffix>
                            </template>

                            <template v-if="fieldType(field).name === 'INPUT'">

                                <Input v-model="form.content[field.code_name]"
                                       type="text"
                                       :id="field.code_name"
                                       @keydown.enter.prevent=""
                                       :placeholder="field.description"
                                       :error="form.errors[field.code_name] !== undefined"
                                       :required="field.required"
                                />

                            </template>
                            <template v-if="fieldType(field).name === 'TEXTAREA'">

                                <Textarea v-model="form.content[field.code_name]"
                                          :placeholder="field.description"
                                          :error="form.errors[field.code_name] !== undefined"
                                          :id="field.code_name"
                                          class="w-full placeholder:italic placeholder:text-sm"
                                          :rows="field.size ?? 4"
                                />

                            </template>
                            <template v-if="fieldType(field).name === 'CHECKBOX'">

                                <Flex class="items-start">
                                    <Checkbox v-model:checked="form.active" id="active"/>
                                    <Label for="active">{{ $t('general.active') }}</Label>
                                </Flex>

                            </template>

                            <template #footer>
                                <Error :message="form.errors.content?.field.code_name"/>
                            </template>
                        </VerticalGroup>
                    </template>

                </Panel>

                <Flex
                    class="flex-row items-center justify-between mt-lg"
                    :responsive="false"
                >
                    <Flex
                        class="gap-6"
                        :responsive="false"
                    >

                        <PrimaryButton
                            type="submit"
                            :isLoading="form.processing"
                            :disabled="form.processing"
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
                        >
                            {{ $t("general.close") }}
                            <template #icon>
                                <X/>
                            </template>
                        </SecondaryButton>

                    </Flex>
                    <Flex v-if="isEdit">

                        <DangerButton
                            @click="deleteCompetitor"
                            :disabled="form.processing"
                        >
                            {{ $t("general.delete") }}
                            <template #icon>
                                <Trash/>
                            </template>
                        </DangerButton>

                    </Flex>
                </Flex>
            </form>
        </div>
    </div>
</template>
