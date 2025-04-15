<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, onMounted, ref, watch} from "vue";
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
import RuleAction from "@/Components/Genie/Rules/RuleAction.vue";
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
    ruleTypes: {
        type: Object,
        required: true
    },
    ruleSubTypes: {
        ty1pe: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
    ruleType: {
        type: String
    },
    statusTypes: {
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
    version_id: '',
    rule_type: props.ruleType ?? '',
    rule_sub_type: '',
    name: '',
    description: '',
    status: '',
    is_default: false
});

const store = () => {
    form.post(route('genie.admin.versions.rules.store',
        {
            version: props.version.id,
        }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.put(route('genie.admin.versions.rules.update', {
        rule: props.record.id,
        version: props.version.id,
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
        'genie.admin.versions.rules.index',
        {
            version: props.version.id,
            rule_type: form.rule_type
        }
    ));
}

const deleteRule = () => {
    confirmation()
        .title($t("genie.delete_rule"))
        .description($t("genie.delete_rule_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route(
                    'genie.admin.versions.rules.delete',
                    {
                        version: props.version.id,
                        rule: props.record.id
                    }
                )
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
    <Head :title="mode === 'create' ? $t('genie.create_rule') : $t('genie.edit_rule')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_rule') : $t('genie.edit_rule')" />

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
                                  rows="6"/>

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="rule_type">{{ $t("genie.rule_type") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.rule_type"
                            :disabled="isEdit"
                            id="rule_type"
                            required
                        >
                            <option v-for="(option) in ruleTypes" :value="option.value">{{option.title}}</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.rule_type"/>
                        </template>
                    </VerticalGroup>

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
                            @click="deleteRule"
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
