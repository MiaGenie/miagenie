<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, provide, ref, watch} from "vue";
import {cloneDeep, find} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import CompetitorAction from "@/Components/Genie/Competitors/CompetitorAction.vue";
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

const {notify} = useNotifications();
const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

const form = useForm(isEdit.value ? cloneDeep(props.record) :
    cloneDeep(props.fieldList.competitors).reduce(
        (list, field) => {
            list.content[field.code_name] = fieldType(field).hasOptions ? [] : '';
            return list;
        }, {
            'content': {}
        }
    )
);

const removePreventNavigation = ref(null);
const reloadPreventNavigation = ref(false);

watch( () => [form.isDirty, reloadPreventNavigation.value], () => {
    if (form.isDirty && removePreventNavigation.value === null) {
        removePreventNavigation.value = router.on('before', (event) => {
            if (!form.isDirty) {
                removePreventNavigation.value();
                return;
            }
            if (!confirm($t('genie.are_you_sure') + "\n" + $t('genie.unsaved_will_lost'))) {
                event.preventDefault();
                removePreventNavigation.value();
                removePreventNavigation.value = null;
                reloadPreventNavigation.value = true;
            } else {
                removePreventNavigation.value();
            }
        })
    } else if (reloadPreventNavigation.value) {
        if (removePreventNavigation.value) {
            removePreventNavigation.value();
            removePreventNavigation.value = null;
        }
    }
    reloadPreventNavigation.value = false;
})

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
    if (removePreventNavigation.value) {
        removePreventNavigation.value();
        removePreventNavigation.value = null;
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
        backToConfig();
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm(() => {
            backToConfig();
        })
        .show();
}

const backToConfig = () => {
    if (removePreventNavigation.value) {
        removePreventNavigation.value();
    }
    router.get(route('genie.config.config', {
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

            if (removePreventNavigation.value) {
                removePreventNavigation.value();
                removePreventNavigation.value = null;
            }

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

provide(/* key */ 'form', /* value */ form);

</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_competitor') : $t('genie.edit_competitor')"/>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_competitor') : $t('genie.edit_competitor')"/>

        <div class="row-px">
            <form method="post" @submit.prevent="submit">

                <Panel class="mx-auto">
                    <template v-for="(field) in fieldList.competitors">

                        <VersionFieldsForm :field="field"></VersionFieldsForm>

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
