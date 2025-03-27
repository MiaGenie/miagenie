<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, provide} from "vue";
import {cloneDeep} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import StrategyAction from "@/Components/Genie/Strategies/StrategyAction.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Trash from "@/Icons/Trash.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import VersionFieldsForm from "@/Components/Form/Genie/FieldsForm.vue";

const {t: $t} = useI18n();

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

    props.fieldList.strategies.reduce(
        (list, field) => {
            field.name = field.description;
            field.description = field.sub_description;
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
    })).post(route(`genie.strategies.store`, {
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
    })).put(route(`genie.strategies.update`, {
        'workspace': workspaceCtx.id,
        strategy: props.record.id
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
    router.get(route('genie.strategies.index', {
        workspace: workspaceCtx.id
    }));
}

const deleteStrategy = () => {
    confirmation()
        .title($t("genie.delete_strategy"))
        .description($t("genie.delete_strategy_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route('genie.strategies.delete',
                    {
                        workspace: workspaceCtx.id,
                        strategy: props.record.id
                    }), {
                    preserveScroll: true,
                    onSuccess() {
                        notify('success', $t('genie.strategy_deleted'))
                    },
                    onFinish() {
                        dialog.reset();
                    }
                }
            );
        })
        .show();
}

provide(/* key */ 'form', /* value */ form);

</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_strategy') : $t('genie.edit_strategy')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="mode === 'create' ? $t('genie.create_strategy') : $t('genie.edit_strategy')">
            <template v-if="isEdit">
                <StrategyAction :record="record" />
            </template>
        </PageHeader>

        <div class="row-px">
            <form method="post" @submit.prevent="submit">

                <Panel>
                    <VersionFieldsForm :fieldTypes="props.fieldTypes" :fieldList="props.fieldList.strategies"></VersionFieldsForm>
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
                            @click="deleteStrategy"
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
