<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import {cloneDeep, find} from "lodash";
import useNotifications from "@/Composables/useNotifications.js";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import AdminLayout from "@/Layouts/Admin.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Select from "@/Components/Form/Select.vue";
import Switch from "@/Components/Form/Switch.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Flex from "@/Components/Layout/Flex.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import Save from "@/Icons/Genie/Save.vue";

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
    },
    statusTypes: {
        type: Object,
        required: true
    }
})

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');
const {notify} = useNotifications();
const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    name: '',
    description: '',
    status: '',
    is_default: false
});

const store = () => {
    form.post(route('genie.admin.versions.store'), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.admin.versions.update', {version: props.record.id}), {
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
        .onConfirm((dialog) => {
            backToList();
        })
        .show();
}

const backToList = () => {
    router.get(route(
        'genie.admin.versions.index',
    ));
}

const deleteVersion = () => {
    confirmation()
        .title($t("genie.delete_version"))
        .description($t("genie.delete_version_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.versions.delete',
                    {version: props.record.id}
                ), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.version_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        }).show();
}

const currentStatus = () => {
    return find(props.statusTypes, ['value', Number(form.status)]);
}

const statusEnabled = () => {
    return currentStatus()?.name === 'ENABLED';
}



</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_version') : $t('genie.edit_version')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_version') : $t('genie.edit_version')" />

        <div class="row-px">
            <form
                method="post"
                @submit.prevent="submit"
            >

                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="name">{{ $t("genie.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.name"
                            type="text"
                            id="name"
                            :placeholder="$t('general.name')"
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

                        <Textarea
                            v-model="form.description"
                            :error="form.errors.description !== undefined"
                            id="description"
                            rows="6"
                            class="w-full placeholder:italic placeholder:text-sm"
                        />

                        <template #footer>
                            <Error :message="form.errors.description"/>
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
                                    {{ status.title }}
                                </option>
                            </Select>

                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.status"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <Label for="is_default">{{ $t('genie.is_default') }}</Label>
                        </template>

                        <Flex class="items-start">
                            <Switch
                                v-model="form.is_default"
                                id="is_default"
                                :disabled="!statusEnabled()"
                            />
                        </Flex>

                        <template #footer>
                            <Error :message="form.errors.is_default"/>
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
                            @click="deleteVersion"
                            :disabled="form.processing || record.is_default || record.active"
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
