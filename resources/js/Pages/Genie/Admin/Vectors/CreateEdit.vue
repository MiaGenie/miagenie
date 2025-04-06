<script setup>
import {provide, inject} from "vue";
import {Head, router, useForm} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import {cloneDeep, remove} from "lodash";
import useRouter from "@/Composables/useRouter";
import usePageMode from "@/Composables/usePageMode";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import VectorAction from "@/Components/Genie/Vectors/VectorAction.vue";
import VectorFile from "@/Components/Genie/Vectors/VectorFile.vue";
import Panel from "@/Components/Surface/Panel.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Error from "@/Components/Form/Error.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Input from "@/Components/Form/Input.vue";
import Select from "@/Components/Form/Select.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VectorAddFile from "@/Components/Genie/Vectors/VectorAddFile.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import SuccessButton from "@/Components/Button/SuccessButton.vue";
import X from "@/Icons/X.vue";
import Plus from "@/Icons/Plus.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: 'create',
    },
    mimeTypes: {
        type: Object,
        required: true
    },
    vectorTypes: {
        type: Object,
        required: true
    },
    record: {
        type: Object
    }
})

const confirmation = inject('confirmation');

provide('mimeTypes', props.mimeTypes);

const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    name: '',
    description: '',
    files: {},
    vector_type: ''
});

const store = () => {
    form.post(route(`genie.admin.vectors.store`), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route(`genie.admin.vectors.update`, {vector: props.record.id}), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
}

const submit = () => {
    if (Object.keys(form.files).length === 0) {
        form.errors.files = $t('genie.vector_file_required');
        return;
    }

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
        'genie.admin.vectors.index',
    ));
}

const deleteVector = () => {
    confirmation()
        .title($t("genie.delete_vector"))
        .description($t("genie.delete_vector_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.vectors.delete',
                    {vector: props.record.id}
                ), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.vector_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        }).show();
}

const updateContent = (values) => {
    values.forEach((value) => {
        form.files[value.id] = form.files[value.id] ?? value;
    })
}

const removeFile = (item) => {
    delete(form.files[item.id]);
}

</script>
<template>
    <Head :title="mode === 'create' ? $t('genie.create_vector') : $t('genie.edit_vector')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_vector') : $t('genie.edit_vector')" />

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
                               type="text"
                               id="name"
                               :placeholder="$t('general.name')"
                               :autofocus="isCreate"
                               required/>

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
                                  class="w-full placeholder:italic placeholder:text-sm"/>

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            {{ $t("genie.vector_files") }}
                        </template>

                        <template #description>

                            <template v-for="(item) in form.files">
                                <VectorFile
                                    :item="item"
                                    @removeFile="removeFile(item)"
                                >
                                </VectorFile>
                            </template>

                            <VectorAddFile
                                @insert="updateContent([...$event.items])"
                            >
                                <SuccessButton
                                    size="xs"
                                >
                                    <template #icon>
                                        <Plus/>
                                    </template>
                                    {{ $t('genie.add_file') }}
                                </SuccessButton>
                            </VectorAddFile>

                        </template>

                        <template #footer>
                            <Error :message="form.errors.files"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="vector_type">{{ $t("genie.vector_type") }}</label>
                        </template>

                        <Select
                            v-model="form.vector_type"
                            id="vector_type"
                            required
                        >
                            <option
                                v-for="option in vectorTypes"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.vector_type"/>
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
                            @click="deleteVector"
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
