<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {computed, inject, onMounted, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import {cloneDeep, find} from "lodash";
import usePageMode from "@/Composables/usePageMode";
import AdminLayout from "@/Layouts/Admin.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Radio from "@/Components/Form/Radio.vue";
import Select from "@/Components/Form/Select.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import RuleStepAction from "@/Components/Genie/Rules/RuleStepAction.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import Label from "@/Components/Form/Label.vue";
import Switch from "@/Components/Form/Switch.vue";
import Flex from "@/Components/Layout/Flex.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";

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
    ruleSubTypes: {
        type: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
    models: {
        type: Object,
        required: true
    },
    vectorIds: {
        type: Object,
        required: true
    },
    outputFields: {
        type: Object,
        required: true
    },
    outputIdeaFields: {
        type: Array,
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
    rule_sub_type: isEdit.value ? props.record.rule_sub_type : '',
    name: isEdit.value ? props.record.name : '',
    description: isEdit.value ? props.record.description : '',
    instructions: isEdit.value ? props.record.instructions : '',
    ai_model: isEdit.value ? props.record.ai_model : '',
    response_format: isEdit.value ? props.record.response_format ?? '' : '',
    json_schema: isEdit.value ? props.record.json_schema ?? '' : '',
    temperature: isEdit.value ? props.record.temperature ?? 1 : 1,
    top_p: isEdit.value ? props.record.top_p ?? 1 : 1,
    reasoning_effort: isEdit.value ? props.record.reasoning_effort ?? '' : '',
    vector_id: isEdit.value ? props.record.vector_id ?? '' : '',
    message: isEdit.value ? props.record.message : '',
    output: isEdit.value ? props.record.output : [],
    requires_review: isEdit.value ? props.record.requires_review : 0,
    review_message_user: isEdit.value ? props.record.review_message_user : '',
    review_message_system: isEdit.value ? props.record.review_message_system : '',
    optional: isEdit.value ? props.record.optional : 0,
    depends_on_field: isEdit.value ? props.record.depends_on_field : '',
    depends_on_option: isEdit.value ? props.record.depends_on_option : '',
});

const filteredModels = ref(props.models);
const isMultiple = ref(false);

const checkMultiple = () => {
    if (isMultiple.value) {
        filteredModels.value = cloneDeep(props.models).filter(
            (model) => {
                return Boolean(model.json_schema) === isMultiple.value;
            }
        );
        form.response_format = 'json_schema';
    } else {
        filteredModels.value = cloneDeep(props.models);
    }
}

const modelHas = ref({});
const ruleSubType = ref({});

onMounted( () => {
    ruleSubType.value = find(props.ruleSubTypes, ['value', parseInt(form.rule_sub_type)]) ?? {}
})

watch( () => form.ai_model, () => {
    modelHas.value = find(props.models, ['model', form.ai_model]) ?? {}
})

watch( () => form.rule_sub_type, () => {
    ruleSubType.value = find(props.ruleSubTypes, ['value', parseInt(form.rule_sub_type)]) ?? {};
    isMultiple.value = ruleSubType.value?.name === 'BRIEFINGS_MULTIPLE';
    checkMultiple()
})

const dependsOnOptions = () => {
    return find(props.outputFields, ['id', parseInt(form.depends_on_field)]) ?? {}
}

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
                            <label for="rule_sub_type">{{ $t("genie.rule_sub_type") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.rule_sub_type"
                            :disabled="isEdit"
                            id="rule_sub_type"
                            required
                        >
                            <option v-for="(option) in ruleSubTypes" :value="option.value">{{option.title}}</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.rule_sub_type"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="ruleSubType['name'] === 'CHANNELS' || ruleSubType['name'] === 'IDEAS_MULTIPLE'" class="form-field mt-lg">
                        <template #title>
                            <label for="depends_on_field">{{ $t("genie.step_depends_on_field") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.depends_on_field"
                            :error="form.errors.depends_on_field !== undefined"
                            id="depends_on_field"
                            required
                        >
                            <template v-for="(field) in outputFields">
                                <option v-if="field.field_type == 4 || field.is_multiple" :value="field.id">
                                    {{field.code_name }} - {{ field.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.depends_on_field"/>
                        </template>
                    </VerticalGroup>


                    <VerticalGroup v-if="ruleSubType['name'] === 'CHANNELS' && form.depends_on_field !== ''" class="form-field mt-lg">
                        <template #title>
                            <label for="depends_on_option">{{ $t("genie.step_depends_on_option") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.depends_on_option"
                            :error="form.errors.depends_on_option !== undefined"
                            id="depends_on_option"
                            required
                        >
                            <template v-for="(option) in dependsOnOptions().options">
                                <option :value="option.id">
                                    {{option.code_name }} - {{ option.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.depends_on_option"/>
                        </template>
                    </VerticalGroup>

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
                            <label for="instructions">{{ $t("genie.step_instructions") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.instructions"
                                  :error="form.errors.instructions !== undefined"
                                  id="instructions"
                                  class="w-full"
                                  rows="10"
                                  required/>

                        <template #footer>
                            <Error :message="form.errors.instructions"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="ai_model">{{ $t("genie.step_model") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.ai_model"
                            :error="form.errors.ai_model !== undefined"
                            id="ai_model"
                            required
                        >
                            <option
                                v-for="(option) in filteredModels"
                                :value="option.model"
                            >
                                {{option.model}}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.ai_model"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="modelHas['file_search'] ?? true" class="form-field mt-lg">
                        <template #title>
                            <label for="vector_id">{{ $t("genie.step_vector_id") }}</label>
                        </template>

                        <Select
                            v-model="form.vector_id"
                            :error="form.errors.vector_id !== undefined"
                            id="vector_id"
                        >
                            <template v-for="(option) in vectorIds">
                                <option :value="option.id">
                                    {{option.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.vector_id"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="modelHas['temperature_top_p'] ?? true"  class="form-field mt-lg">
                        <template #title>
                            <label for="temperature">{{ $t("genie.step_temperature") }}
                            </label>
                        </template>

                        <template #description>
                            {{ form.temperature }}
                        </template>

                        <Input v-model="form.temperature"
                               default="1"
                               type="range"
                               min="0"
                               max="2"
                               step="0.01"
                               id="temperature"
                               required/>

                        <template #footer>
                            <Error :message="form.errors.temperature"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="modelHas['temperature_top_p'] ?? true" class="form-field mt-lg">
                        <template #title>
                            <label for="top_p">{{ $t("genie.step_top_p") }}
                            </label>
                        </template>

                        <template #description>
                            {{ form.top_p }}
                        </template>

                        <Input v-model="form.top_p"
                               default="1"
                               type="range"
                               min="0"
                               max="2"
                               step="0.01"
                               id="top_p"
                               required/>

                        <template #footer>
                            <Error :message="form.errors.top_p"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="modelHas['reasoning_effort'] ?? true" class="form-field mt-lg">
                        <template #title>
                            <label for="reasoning_effort">{{ $t("genie.step_reasoning_effort") }}</label>
                        </template>

                        <Select
                            v-model="form.reasoning_effort"
                            :error="form.errors.reasoning_effort !== undefined"
                            id="reasoning_effort"
                        >
                            <option value="low">low</option>
                            <option value="medium">medium</option>
                            <option value="high">high</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.reasoning_effort"/>
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

                    <VerticalGroup v-if="rule.rule_type == 1 && ruleSubType['name'] !== 'BRIEFINGS_MULTIPLE'" class="form-field mt-lg">
                        <template #title>
                            <label for="output">{{ $t("genie.step_output") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.output[0]"
                            :error="form.errors.output !== undefined"
                            id="output"
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

                    <VerticalGroup v-if="rule.rule_type == 2 || ruleSubType['name'] === 'BRIEFINGS_MULTIPLE'" class="form-field mt-lg">
                        <template #title>
                            <label for="output">{{ $t("genie.step_output") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <TableCell class="">
                            <template v-if="rule.rule_type == 1" v-for="(output, index) in outputFields" :key="output.code_name">
                                <Flex class="py-sm">
                                    <Checkbox v-model:checked="form.output" :value="output.code_name"/>
                                    {{ output.name }}
                                </Flex>
                            </template>
                            <template v-if="rule.rule_type == 2" v-for="(output, index) in outputIdeaFields" :key="output">
                                <Flex class="py-sm">
                                    <Checkbox v-model:checked="form.output" :value="output"/>
                                    {{ output }}
                                </Flex>
                            </template>
                        </TableCell>

                        <template #footer>
                            <Error :message="form.errors.output"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="response_format">{{ $t("genie.step_response_format") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.response_format"
                            :error="form.errors.response_format !== undefined"
                            id="response_format"
                            :disabled="isMultiple.valueOf()"
                            required
                        >
                            <option value="text">text</option>
                            <option value="json_object">json_object</option>
                            <option v-if="modelHas['file_search'] ?? true" value="json_schema">json_schema</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.response_format"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="form.response_format==='json_schema' && (modelHas['file_search'] ?? true)"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="json_schema">{{ $t("genie.step_json_schema") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.json_schema"
                                  :error="form.errors.json_schema !== undefined"
                                  id="response_format"
                                  class="w-full"
                                  required
                                  rows="10"/>

                        <template #footer>
                            <Error :message="form.errors.json_schema"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field">
                        <template #title>
                            <label for="requires_review">{{ $t("genie.step_requires_review") }}</label>
                        </template>

                        <Switch
                            v-model="form.requires_review"
                            id="requires_review"
                        />

                        <template #footer>
                            <Error :message="form.errors.requires_review"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="form.requires_review" class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_review_message_user") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.review_message_user"
                                  :error="form.errors.review_message_user !== undefined"
                                  id="review_message_user"
                                  class="w-full"
                                  rows="12"
                                  required/>

                        <template #footer>
                            <Error :message="form.errors.review_message_user"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="form.requires_review" class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_review_message_system") }}</label>
                        </template>

                        <Textarea v-model="form.review_message_system"
                                  :error="form.errors.review_message_system !== undefined"
                                  id="review_message_system"
                                  class="w-full"
                                  rows="12"/>

                        <template #footer>
                            <Error :message="form.errors.review_message_system"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field">
                        <template #title>
                            <label for="optional">{{ $t("genie.step_optional") }}</label>
                        </template>

                        <Switch
                            v-model="form.optional"
                            id="optional"
                        />

                        <template #footer>
                            <Error :message="form.errors.optional"/>
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
