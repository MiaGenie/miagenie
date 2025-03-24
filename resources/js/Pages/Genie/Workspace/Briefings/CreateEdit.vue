<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {cloneDeep, find} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import BriefingAction from "@/Components/Genie/Briefings/BriefingAction.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Trash from "@/Icons/Trash.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import Select from "@/Components/Form/Select.vue";
import Radio from "@/Components/Form/Radio.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";

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

const form = useForm(isEdit.value ? cloneDeep(props.record) :

    props.fieldList.briefings.reduce(
        (list, field) => {
            list.content[field.code_name] = props.fieldTypes.find((field_type) => field_type.value === field.field_type  ).hasOptions ? [] : '' ;
            if(Array.isArray(list.content[field.code_name])) {
                field.options.forEach(function(group, indexGroup){
                    const nextGroup = group.filter(option => option.checked === 1);
                    nextGroup.forEach(function(option, indexOption){
                        list.content[field.code_name].push(option.code_name);
                    });
                });
            }

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
    })).post(route(`genie.briefings.store`, {
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
    })).put(route(`genie.briefings.update`, {
        'workspace': workspaceCtx.id,
        briefing: props.record.id
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
    router.get(route('genie.briefings.index', {
        workspace: workspaceCtx.id
    }));
}

const deleteBriefing = () => {
    confirmation()
        .title($t("genie.delete_briefing"))
        .description($t("genie.delete_briefing_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route('genie.briefings.delete',
                    {
                        workspace: workspaceCtx.id,
                        briefing: props.record.id
                    }), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.briefing_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        })
        .show();
}

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_briefing') : $t('genie.edit_briefing')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="mode === 'create' ? $t('genie.create_briefing') : $t('genie.edit_briefing')">
            <template v-if="isEdit">
                <BriefingAction :record="record" />
            </template>
        </PageHeader>

        <div class="row-px">
            <form method="post" @submit.prevent="submit">

                <Panel>

                    <template #title>{{ $t("general.details") }}</template>

                    <template v-for="(field, index) in fieldList.briefings" :key="index">
                        <VerticalGroup class="form-field mt-lg">
                            <template #title>
                                <label :for="field.code_name">{{ field.description }}</label>
                                <LabelSuffix v-if="field.required" :danger="true">*</LabelSuffix>
                            </template>

                            <template v-if="fieldType(field).name === 'INPUT'">

                                <Input v-model="form.content[field.code_name]"
                                       type="text"
                                       :id="field.code_name"
                                       @keydown.enter.prevent=""
                                       :placeholder="field.sub_description"
                                       :error="form.errors[field.code_name] !== undefined"
                                       :required="field.required"
                                />

                            </template>
                            <template v-if="fieldType(field).name === 'TEXTAREA'">

                                <Textarea v-model="form.content[field.code_name]"
                                          :placeholder="field.sub_description"
                                          :error="form.errors[field.code_name] !== undefined"
                                          :id="field.code_name"
                                          class="w-full placeholder:italic placeholder:text-sm"
                                          :rows="field.size ?? 4"
                                />

                            </template>

                            <template v-if="fieldType(field).name === 'CHECKBOX'">


                                <TableCell class="">
                                    <template v-for="(option, index_option) in field.options[0]" :key="option.code_name">
                                        <Flex class="py-sm">
                                            <Checkbox v-model:checked="form.content[field.code_name]" :value="option.code_name"/>
                                            {{ option.name }}
                                        </Flex>
                                    </template>
                                </TableCell>


                            </template>

                            <template v-if="fieldType(field).name === 'DROP_DOWN'">

                                <Flex class="items-start">
                                    <Select v-model="form.content[field.code_name]"
                                            id="testing" >
                                        <option></option>
                                        <template v-for="(option, index) in field.options[0]" :key="index">
                                            <option :value="option.code_name">{{ option.name }}</option>
                                        </template>
                                    </Select>
                                </Flex>

                            </template>

                            <template v-if="fieldType(field).name === 'RADIO'">

                                <TableCell class="">
                                    <template v-for="(group, index) in field.options" :key="index">
                                        <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                                            <Flex class="py-sm">
                                                <Radio v-model:checked="form.content[field.code_name][index]" :value="option.code_name"/>
                                                {{ option.name }}
                                            </Flex>
                                        </template>
                                    </template>
                                </TableCell>

                            </template>

                            <template v-if="fieldType(field).name === 'RADIO_GROUP'">

                                <div >
                                    <template v-for="(group, index) in field.options" :key="index">
                                        <TableRow class="">
                                            <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                                                <TableCell class="">
                                                    <Radio v-model:checked="form.content[field.code_name][index]" :value="option.code_name"/>
                                                    {{ option.name }}
                                                </TableCell>
                                            </template>
                                        </TableRow>
                                    </template>
                                </div>

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
                            @click="deleteBriefing"
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
