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
import Select from "@/Components/Form/Select.vue";
import Textarea from "@/Components/Form/Textarea.vue";
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
    assistantTypes: {
        type: Object,
        required: true
    },
    models: {
        type: Object,
        required: true
    },
    assistantType: {
        type: String
    },
    vectorIds: {
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

const form = useForm( {
    name: isEdit.value ? props.record.name : '',
    assistant_type: isEdit.value ? props.record.assistant_type : '',
    description: isEdit.value ? props.record.description : '',
    instructions: isEdit.value ? props.record.instructions : '',
    model: isEdit.value ? props.record.model : '',
    vector_id: isEdit.value ? props.record.vector_id ?? '' : '',
    response_format: isEdit.value ? props.record.response_format ?? '' : '',
    json_schema: isEdit.value ? props.record.json_schema ?? '' : '',
    temperature: isEdit.value ? props.record.temperature ?? 1 : 1,
    top_p: isEdit.value ? props.record.top_p ?? 1 : 1,
    reasoning_effort: isEdit.value ? props.record.reasoning_effort ?? '' : '',
});

const filteredVectors = ref({});
const filterVectors = () => {
    filteredVectors.value = cloneDeep(props.vectorIds).filter(
        (vector) => {
            return Number(vector.vector_type) === Number(form.assistant_type);
        }
    )
}

onMounted(() => {filterVectors()})

watch( () => form.assistant_type, () => {
    filterVectors()
})

const store = () => {
    form.post(route('genie.admin.assistants.store'), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.admin.assistants.update', {assistant: props.record.id}), {
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
        'genie.admin.assistants.index',
        {assistant_type: form.assistant_type}
    ));
}

const deleteAssistant = () => {
    confirmation()
        .title($t("genie.delete_assistant"))
        .description($t("genie.delete_assistant_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.assistants.delete',
                    {assistant: props.record.id}
                ), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.assistant_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        }).show();
}

const modelHas = computed(() => {
    return find(props.models, ['model', form.model]) ?? [];
});

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_assistant') : $t('genie.edit_assistant')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_assistant') : $t('genie.edit_assistant')" />

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
                            <label for="assistant_type">{{ $t("genie.assistant_type") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select v-model="form.assistant_type" id="assistant_type" required>
                            <option v-for="(option) in assistantTypes" :value="option.value">{{option.title}}</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.assistant_type"/>
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
                            <label for="instructions">{{ $t("genie.assistant_instructions") }}</label>
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
                            <label for="model">{{ $t("genie.assistant_model") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.model"
                            :error="form.errors.model !== undefined"
                            id="model"
                            required
                        >
                            <option
                                v-for="(option) in props.models"
                                :value="option.model"
                            >
                                {{option.model}}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.model"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="modelHas['file_search'] ?? true" class="form-field mt-lg">
                        <template #title>
                            <label for="vector_id">{{ $t("genie.assistant_vector_id") }}</label>
                        </template>

                        <Select
                            v-model="form.vector_id"
                            :error="form.errors.vector_id !== undefined"
                            id="vector_id"
                        >
                            <template v-for="(option) in filteredVectors">
                                <option :value="option.id">
                                    {{option.name}}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.vector_id"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="response_format">{{ $t("genie.assistant_response_format") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.response_format"
                            :error="form.errors.response_format !== undefined"
                            id="response_format"
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
                            <label for="json_schema">{{ $t("genie.assistant_json_schema") }}</label>
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

                    <VerticalGroup v-if="modelHas['temperature_top_p'] ?? true"  class="form-field mt-lg">
                        <template #title>
                            <label for="temperature">{{ $t("genie.assistant_temperature") }}
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
                            <label for="top_p">{{ $t("genie.assistant_top_p") }}
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
                            <label for="reasoning_effort">{{ $t("genie.assistant_reasoning_effort") }}</label>
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
                            @click="deleteAssistant"
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
