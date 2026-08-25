<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {provide, inject, onBeforeMount, ref, watch} from "vue";
import {cloneDeep, keys} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import useVersionField from "@/Composables/Genie/useVersionField.js";
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
import VersionHeader from "@/Components/DataDisplay/Genie/VersionHeader.vue";
import VersionFieldOptionsGroup from "@/Components/Genie/Versions/VersionFieldOptionsGroup.vue";
import VersionFieldSubFields from "@/Components/Genie/Versions/VersionFieldSubFields.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import Switch from "@/Components/Form/Switch.vue";
import Flex from "@/Components/Layout/Flex.vue";
import EditorClassic from "@/Components/Package/EditorClassic.vue";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    version: {
        type: Object,
        required: true
    },
    record: {
        type: Object
    },
    groupType: {
        type: String
    },
    groupTypes: {
        type: Object,
        required: true
    },
    fieldTypes: {
        type: Object,
        required: true
    },
    fileTypes: {
        type: Object,
        required: true
    },
    inputTypes: {
        type: Object,
        required: true
    },
    statusTypes: {
        type: Object,
        required: true
    }
})

const confirmation = inject('confirmation');
const {notify} = useNotifications();
const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    group_type: props.groupType ?? '',
    name: '',
    code_name: '',
    description: '',
    sub_description: '',
    field_type: '',
    input_type: '',
    file_type: '',
    options : [],
    sub_fields: [],
    min_length: '',
    max_length: '',
    min_value: '',
    max_value: '',
    step: '',
    rows: '',
    is_multiple: false,
    required: false,
    genie_required: true,
    is_identifier: false,
    hidden: false,
    is_linkable: false,
    display_title: true,
    display_grouped: false,
    display_field_title: false,
    display_item_title: false,
    display_faq_title: '',
    display_faq_text: '',
    class: '',
    block: '',
    position: '',
});

const {
    formatOptions,
    formatSubFields,
    optionsErrors,
    subFieldsErrors,
    currentGroupType,
    currentFieldType,
    currentInputType,
    currentFileType,
    setCodeName,
    checkForm,
} = useVersionField(form);

provide('currentFieldTypeCtx', () => {return currentFieldType});
provide('formCtx', () => {return form});

onBeforeMount( () => {
    formatSubFields();
    formatOptions();
})

const identifierAvailable = ref (
    currentGroupType.value?.hasIdentifier &&
    currentFieldType.value?.isInput &&
    currentInputType.value?.name === 'TEXT'
);

watch( () => form.is_identifier, () => {
    if (form.is_identifier) {
        form.required = true;
        form.genie_required = true;
    }
})

watch([currentGroupType, currentFieldType, currentInputType], () => {

    identifierAvailable.value = currentGroupType.value?.hasIdentifier &&
        currentFieldType.value?.isInput &&
        currentInputType.value?.name === 'TEXT';

    if (!identifierAvailable.value) {
        form.is_identifier = false;
    }
});

const store = () => {
    form.post(
        route(
            'genie.admin.versions.fields.store',
            {version: props.version.id}
        ),
        {
            onError: (errors) => {
                onError(errors, store);
            },
            preserveState: (page) => {
                return keys(page.props.errors).length > 0;
            },
        }
    );
}

const update = () => {
    form.put(
        route(
            'genie.admin.versions.fields.update',
            {version: props.version.id, field: props.record.id}
        ),
        {
            preserveScroll: true,
            onError: (errors) => {
                onError(errors, update);
            },
            preserveState: (page) => {
                return keys(page.props.errors).length > 0;
            },
        }
    );
}

const submit = () => {
    if (!checkForm()) return;

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
        'genie.admin.versions.fields.index',
        {version: props.version.id, group_type: form.group_type}
    ));
}

const deleteField = () => {
    confirmation()
        .title($t("genie.delete_field"))
        .description($t("genie.delete_field_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.versions.fields.delete',
                    {version: props.version.id, field: props.record.id}
                ), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.field_deleted'))
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

    <Head :title="mode === 'create' ? $t('genie.create_field') : $t('genie.edit_field')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_field') : $t('genie.edit_field')" />

        <VersionHeader />

        <div class="row-px">
            <form
                method="post"
                @submit.prevent="submit"
            >
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="group_type">{{ $t("genie.field_group_type") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Select
                            v-model="form.group_type"
                            id="group_type"
                            required
                        >
                            <option
                                v-for="group in props.groupTypes"
                                :value="group.value"
                            >
                                {{ group.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.group_type"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="field_type">{{ $t("genie.field_type") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Select
                            v-model="form.field_type"
                            id="field_type"
                            required
                        >
                            <option
                                v-for="option in props.fieldTypes"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.field_type"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="currentFieldType?.isInput"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="input_type">{{ $t("genie.field_input_type") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Select
                            v-model="form.input_type"
                            id="input_type"
                        >
                            <option
                                v-for="option in props.inputTypes"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.input_type"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="currentFieldType?.isFile"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="file_type">{{ $t("genie.field_file_type") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Select
                            v-model="form.file_type"
                            id="file_type"
                        >
                            <option
                                v-for="option in props.fileTypes"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.input_type"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="name">{{ $t("general.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.name"
                            type="text"
                            id="name"
                            :autofocus="isCreate"
                            @focusout="setCodeName"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.name"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="code_name">{{ $t("genie.code_name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.code_name"
                            type="text"
                            id="code_name"
                            :placeholder="'(snake_case)'"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.code_name"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="description">{{ $t("genie.description") }}</label>
                        </template>

                        <Textarea
                            v-if="!currentFieldType?.isOutput"
                            v-model="form.description"
                            :error="form.errors.description !== undefined"
                            id="description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <EditorClassic
                            v-else
                            :value="form.description"
                            @update="form.description = $event"
                            :error="form.errors.description !== undefined"
                            id="description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="sub_description">{{ $t("genie.sub_description") }}</label>
                        </template>

                        <Textarea
                            v-if="!currentFieldType?.isOutput"
                            v-model="form.sub_description"
                            :error="form.errors.sub_description !== undefined"
                            id="sub_description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <EditorClassic
                            v-else
                            :value="form.sub_description"
                            @update="form.sub_description = $event"
                            :error="form.errors.sub_description !== undefined"
                            id="sub_description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.sub_description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="currentFieldType?.hasOptions"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            {{ $t("genie.field_options") }}
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <VersionFieldOptionsGroup v-model="form.options" />

                        <template #footer>
                            <Error :message="optionsErrors()"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="currentGroupType?.name === 'STRATEGIES'"
                        :force-full-width="true"
                        class="form-field-xl mt-lg"
                    >
                        <template #title>
                            {{ $t("genie.sub_fields") }}
                        </template>

                        <template #description>
                            {{ $t("genie.sub_fields_description") }}
                        </template>

                        <VersionFieldSubFields v-model="form.sub_fields" />

                        <template #footer>
                            <Error :message="subFieldsErrors()"/>
                        </template>
                    </VerticalGroup>

                    <Flex
                        :responsive="false"
                        class="form-field mt-lg"
                    >

                        <VerticalGroup
                            v-if="currentFieldType?.hasLength ||
                             (currentFieldType?.isInput && currentInputType?.hasLength)"
                            :class="[currentFieldType?.isInput ? 'basis-1/2' : 'basis-1/3']"
                        >
                            <template #title>
                                <label for="min_length">{{ $t("genie.field_min_length") }}</label>
                            </template>

                            <Input
                                v-model="form.min_length"
                                type="number"
                                min="0"
                                :max="currentFieldType?.isInput ? 500 : 10000"
                                :placeholder="currentFieldType?.isInput ? 1 : 10"
                                step="1"
                                id="min_length"
                            />

                            <template #footer>
                                <Error :message="form.errors.min_length"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup
                            v-if="currentFieldType?.hasLength ||
                             (currentFieldType?.isInput && currentInputType?.hasLength)"
                            :class="[currentFieldType?.isInput ? 'basis-1/2' : 'basis-1/3']"
                        >
                            <template #title>
                                <label for="max_length">{{ $t("genie.field_max_length") }}</label>
                            </template>

                            <Input
                                v-model="form.max_length"
                                type="number"
                                min="0"
                                :max="currentFieldType?.isInput ? 500 : 10000"
                                :placeholder="currentFieldType?.isInput ? 500 : 10000"
                                step="1"
                                id="max_length"
                            />

                            <template #footer>
                                <Error :message="form.errors.max_length"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup
                            v-if="currentFieldType?.hasRows"
                            class="basis-1/3"
                        >
                            <template #title>
                                <label for="rows">{{ $t("genie.field_rows") }}</label>
                            </template>

                            <Input
                                v-model="form.rows"
                                placeholder="4"
                                type="number"
                                min="2"
                                max="30"
                                step="1"
                                id="rows"
                            />

                            <template #footer>
                                <Error :message="form.errors.rows"/>
                            </template>
                        </VerticalGroup>

                    </Flex>


                    <Flex
                        :responsive="false"
                        class="form-field"
                    >

                        <VerticalGroup
                            v-if="currentFieldType?.isInput && currentInputType?.hasValues"
                            class="basis-1/3"
                        >
                            <template #title>
                                <label for="min_value">{{ $t("genie.field_min_value") }}</label>
                            </template>

                            <Input
                                v-model="form.min_value"
                                type="number"
                                id="min_value"
                            />

                            <template #footer>
                                <Error :message="form.errors.min_value"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup
                            v-if="currentFieldType?.isInput && currentInputType?.hasValues"
                            class="basis-1/3"
                        >
                            <template #title>
                                <label for="max_value">{{ $t("genie.field_max_value") }}</label>
                            </template>

                            <Input
                                v-model="form.max_value"
                                type="number"
                                id="max_value"
                            />

                            <template #footer>
                                <Error :message="form.errors.max_value"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup
                            v-if="currentFieldType?.isInput && currentInputType?.hasStep"
                            class="basis-1/3"
                        >
                            <template #title>
                                <label for="step">{{ $t("genie.field_step") }}</label>
                            </template>

                            <Input
                                v-model="form.step"
                                type="number"
                                id="step"
                            />

                            <template #footer>
                                <Error :message="form.errors.step"/>
                            </template>
                        </VerticalGroup>

                    </Flex>

                    <Flex
                        v-if="!currentFieldType?.isOutput"
                        :responsive="false"
                        class="form-field mt-lg"
                    >

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="genie_required">{{ $t("genie.field_genie_required") }}</label>
                            </template>

                            <Switch
                                v-model="form.genie_required"
                                id="genie_required"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.genie_required"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="required">{{ $t("genie.field_required") }}</label>
                            </template>

                            <Switch
                                v-model="form.required"
                                id="required"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.required"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup
                            v-if="identifierAvailable"
                            class="form-field basis-1/3"
                        >
                            <template #title>
                                <label for="is_identifier">{{ $t("genie.field_is_identifier") }}</label>
                            </template>

                            <Switch
                                v-model="form.is_identifier"
                                id="is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.is_identifier"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="is_multiple">{{ $t("genie.field_is_multiple") }}</label>
                            </template>

                            <Switch
                                v-model="form.is_multiple"
                                id="is_multiple"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.is_multiple"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="is_linkable">{{ $t("genie.field_is_linkable") }}</label>
                            </template>

                            <Switch
                                v-model="form.is_linkable"
                                id="is_linkable"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.is_linkable"/>
                            </template>
                        </VerticalGroup>

                    </Flex>

                    <Flex
                        :responsive="false"
                        class="form-field mt-lg"
                    >

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="hidden">{{ $t("genie.field_hidden") }}</label>
                            </template>

                            <Switch
                                v-model="form.hidden"
                                id="hidden"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.hidden"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field basis-1/3">
                            <template #title>
                                <label for="display_title">{{ $t("genie.field_display_title") }}</label>
                            </template>

                            <Switch
                                v-model="form.display_title"
                                id="display_title"

                            />

                            <template #footer>
                                <Error :message="form.errors.display_title"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup v-if="!currentFieldType?.isOutput" class="form-field basis-1/3">
                            <template #title>
                                <label for="display_grouped">{{ $t("genie.field_display_grouped") }}</label>
                            </template>

                            <Switch
                                v-model="form.display_grouped"
                                id="display_grouped"
                                :disabled="form.is_identifier"
                            />

                            <template #footer>
                                <Error :message="form.errors.display_grouped"/>
                            </template>
                        </VerticalGroup>

                    </Flex>

                    <Flex
                        v-if="!currentFieldType?.isOutput"
                        :responsive="false"
                        class="form-field mt-lg"
                    >


                    <VerticalGroup class="form-field">
                            <template #title>
                                <label for="display_field_title">{{ $t("genie.field_display_field_title") }}</label>
                            </template>

                            <Switch
                                v-model="form.display_field_title"
                                id="display_field_title"

                            />

                            <template #footer>
                                <Error :message="form.errors.display_field_title"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field">
                            <template #title>
                                <label for="display_item_title">{{ $t("genie.field_display_item_title") }}</label>
                            </template>

                            <Switch
                                v-model="form.display_item_title"
                                id="display_item_title"

                            />

                            <template #footer>
                                <Error :message="form.errors.display_item_title"/>
                            </template>
                        </VerticalGroup>

                    </Flex>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="display_faq_title">{{ $t("genie.field_display_faq_title") }}
                            </label>
                        </template>

                        <Input
                            v-model="form.display_faq_title"
                            type="text"
                            id="display_faq_title"
                        />

                        <template #footer>
                            <Error :message="form.errors.display_faq_title"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="display_faq_text">{{ $t("genie.field_display_faq_text") }}</label>
                        </template>

                        <EditorClassic
                            :value="form.display_faq_text"
                            @update="form.display_faq_text = $event"
                            :error="form.errors.display_faq_text !== undefined"
                            id="display_faq_text"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.display_faq_text"/>
                        </template>
                    </VerticalGroup>

                    <Flex
                        :responsive="false"
                        class="form-field mt-lg"
                    >

                        <VerticalGroup class="form-field basis-1/2">
                            <template #title>
                                <label for="class">{{ $t("genie.field_class") }}</label>
                            </template>

                            <Input
                                v-model="form.class"
                                type="text"
                                id="class"
                            />

                            <template #footer>
                                <Error :message="form.errors.class"/>
                            </template>
                        </VerticalGroup>

                        <VerticalGroup class="form-field basis-1/2">
                            <template #title>
                                <label for="block">{{ $t("genie.field_block") }}</label>
                            </template>

                            <Input
                                v-model="form.block"
                                type="text"
                                id="block"
                            />

                            <template #footer>
                                <Error :message="form.errors.block"/>
                            </template>
                        </VerticalGroup>

                    </Flex>

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
                            @click="deleteField"
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
