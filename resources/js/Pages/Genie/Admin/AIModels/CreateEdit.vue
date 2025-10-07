<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
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
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import Label from "@/Components/Form/Label.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Switch from "@/Components/Form/Switch.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    record: {
        type: Object
    }
})

const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();
const confirmation = inject('confirmation');

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    model: '',
    json_schema: 0,
    temperature_top_p: 0,
    file_search: 0,
    reasoning_effort: 0
});


const store = () => {
    form.post(route('genie.admin.ai_models.store'), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.admin.ai_models.update',
        {
            ai_model: props.record.id
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
    router.get(route('genie.admin.ai_models.index'));
}

const deleteAIModel = () => {
    confirmation()
        .title($t("genie.delete_ai_model"))
        .description($t("genie.delete_ai_model_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.ai_models.delete',
                    {ai_model: props.record.id}
                ), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.ai_model_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        }).show();
}

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_ai_model') : $t('genie.edit_ai_model')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_ai_model') : $t('genie.edit_ai_model')" />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="model">{{ $t("genie.ai_model") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input v-model="form.model"
                               :error="form.errors.model !== undefined"
                               type="text"
                               id="model"
                               :autofocus="isCreate"
                               required
                        />

                        <template #footer>
                            <Error :message="form.errors.model"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <Label for="file_search">{{ $t('genie.ai_model_file_search') }}</Label>
                        </template>

                        <Flex class="items-start">
                            <Switch
                                v-model="form.file_search"
                                id="file_search"
                            />
                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.file_search"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <Label for="json_schema">{{ $t('genie.ai_model_json_schema') }}</Label>
                        </template>

                        <Flex class="items-start">
                            <Switch
                                v-model="form.json_schema"
                                id="json_schema"
                            />
                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.json_schema"/>
                        </template>
                    </VerticalGroup>


                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <Label for="temperature_top_p">{{ $t('genie.ai_model_temperature_top_p') }}</Label>
                        </template>

                        <Flex class="items-start">
                            <Switch
                                v-model="form.temperature_top_p"
                                id="temperature_top_p"
                            />
                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.temperature_top_p"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <Label for="reasoning_effort">{{ $t('genie.ai_model_reasoning_effort') }}</Label>
                        </template>

                        <Flex class="items-start">
                            <Switch
                                v-model="form.reasoning_effort"
                                id="reasoning_effort"
                            />
                        </Flex>

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
                            @click="deleteAIModel"
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
