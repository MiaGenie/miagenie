<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {computed, inject, provide, ref} from "vue";
import {useI18n} from "vue-i18n";
import useVersionField from "@/Composables/Genie/useVersionField.js";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ViewField from "@/Components/DataDisplay/Genie/ViewField.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import {cloneDeep, find} from "lodash";
import StrategyField from "@/Components/Form/Genie/StrategyField.vue";
import StrategyOutputField from "@/Components/Form/Genie/StrategyOutputField.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Save from "@/Icons/Genie/Save.vue";
import ConfirmationModal from "@/Components/Modal/ConfirmationModal.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";

const {t: $t} = useI18n();

const props = defineProps({
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
    },
    schemas: {
        type: Object,
        required: true,
    }
})

const workspaceCtx = inject('workspaceCtx')
const form = useForm(cloneDeep(props.record));
const editing = ref('');
provide('editing', editing);
provide('form', form);
provide('schemas', props.schemas);
provide('fieldList', props.fieldList);
provide('fieldTypes', props.fieldTypes);
provide('record', props.record);
const strategyStatus = inject('strategyStatus');
const strategyApproved = ref(false);

const backToList = () => {
    router.get(route('genie.strategies.index', {
        workspace: workspaceCtx.id
    }));
}

const ideasIndex = () => {
    router.get(route('genie.ideas.index', {
        workspace: workspaceCtx.id
    }));
}

const approve = () => {
    form.put(route('genie.strategies.approve', {
        'workspace': workspaceCtx.id,
        'strategy': props.record.id
    }), {
        preserveScroll: true,
        onError: (errors) => {``
            onError(errors, update);
        },
        onSuccess: () => {``
            strategyApproved.value = true;
        },
    });
}

const fieldHasContent = (field) => {
    return  (field.code_name in form['content']) && form['content'][field.code_name];
}

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

</script>
<template>

    <div class="row-px">

        <Panel :withPadding="false">

            <template v-for="(field, index) in props.fieldList" :key="index">
                <StrategyOutputField
                    v-if="fieldType(field).isOutput"
                    :field="field"
                    :index="index"
                    :id="field.code_name"
                />
                <ViewField
                    v-else-if="!field.hidden && fieldHasContent(field)"
                    :field="field"
                    :index="index"
                    :id="field.code_name"
                />
            </template>

        </Panel>

        <Flex
            v-if="strategyStatus === 'PENDING_APPROVAL'"
            class="flex-row items-center justify-between mt-lg gap-6"
            :responsive="false"
        >

            <PrimaryButton
                @click="approve"
                type="button"
            >
                {{ $t("genie.approve_strategy") }}
                <template #icon>
                    <Save/>
                </template>
            </PrimaryButton>

            <SecondaryButton
                @click="backToList"
                type="button"
            >
                {{ $t("general.close") }}
                <template #icon>
                    <X/>
                </template>
            </SecondaryButton>

        </Flex>

    </div>

    <DialogModal :show="strategyApproved" @close="strategyApproved = false">
        <template #header>
            {{ $t("genie.strategy_approved") }}
        </template>
        <template #body>
            {{ $t("genie.strategy_approved_msg") }}
        </template>
        <template #footer>
            <PrimaryButton @click="ideasIndex" class="mr-xs rtl:mr-0 rtl:ml-xs">
                {{  $t("genie.ideas")  }}
            </PrimaryButton>
        </template>
    </DialogModal>

</template>
