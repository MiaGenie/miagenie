<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import {inject, provide, ref, watch} from "vue";
import {cloneDeep} from "lodash";
import {useI18n} from "vue-i18n";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import VersionFieldsForm from "@/Components/Form/Genie/FieldsForm.vue";
import useValidateVersionField from "@/Composables/Genie/useValidateVersionField.js";

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
    fileTypes: {
        type: Object,
        required: true,
    },
    inputTypes: {
        type: Object,
        required: true,
    },
    record: {
        type: Object
    }
})

const workspaceCtx = inject('workspaceCtx');
const confirmation = inject('confirmation');
const {isCreate, isEdit} = usePageMode();
const {onError} = useRouter();

const briefingFields =  cloneDeep(props.fieldList.briefings).reduce(
    (list, field) => {
        field.name = field.description;
        field.description = field.sub_description;
        list.push(field);
        return list;
    }, []
)

const form = useForm(isEdit.value ? cloneDeep(props.record.content) :

    cloneDeep(props.fieldList.briefings).reduce(
        (list, field) => {
            const hasOptions = (
                props.fieldTypes.find((field_type) => field_type.value === field.field_type).hasOptions ||
                props.fieldTypes.find((field_type) => field_type.value === field.field_type).name === "FILE"
            )
            list[field.code_name] = hasOptions ? [] : '' ;
            if(Array.isArray(list[field.code_name])) {
                field.options.forEach(function(group, indexGroup){
                    const nextGroup = group.filter(option => option.checked === 1);
                    nextGroup.forEach(function(option, indexOption){
                        list[field.code_name].push(option.code_name);
                    });
                });
            }

            return list;
        }, {}
    )
);

const {checkForm, divRefs} = useValidateVersionField(form);
provide("form", form);

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
        content: {...data},
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })).post(route(`genie.briefings.store`, {
        workspace: workspaceCtx.id
    }), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
}

const update = () => {
    form.transform((data) => ({
        content: {...data},
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })).post(route(`genie.briefings.update`, {
        workspace: workspaceCtx.id,
        briefing: props.record.id
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
}

const submit = () => {
    if (!checkForm('briefings')) return;

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

</script>
<template>

    <Head :title="mode === 'create' ? $t('genie.create_briefing') : $t('genie.edit_briefing')"/>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <PageHeader :title="mode === 'create' ? $t('genie.create_briefing') : $t('genie.edit_briefing')"/>

        <div class="row-px">
            <form method="post" @submit.prevent="submit()">

                <Panel class="mx-auto">
                    <template v-for="(field) in briefingFields">
                        <div :ref="el => (divRefs[field.code_name] = el)" >

                        <VersionFieldsForm :field="field"></VersionFieldsForm>

                        </div>
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
                </Flex>
            </form>
        </div>
    </div>
</template>
