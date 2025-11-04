<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, provide, ref} from "vue";
import {useI18n} from "vue-i18n";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ViewField from "@/Components/DataDisplay/Genie/ViewField.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import {cloneDeep} from "lodash";

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

const backToList = () => {
    router.get(route('genie.strategies.index', {
        workspace: workspaceCtx.id
    }));
}
const fieldHasContent = (field) => {
    let foo =  (field.code_name in form['content']) && form['content'][field.code_name];
    return foo;
}


const editing = ref('');
provide('editing', editing);

provide('form', form);
provide('schemas', props.schemas);
provide('fieldList', props.fieldList);
provide('fieldTypes', props.fieldTypes);
provide('record', props.record);

</script>
<template>

    <Head :title="$t('genie.view_strategy')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.view_strategy')"/>

        <div class="row-px">

            {{ editing.value }}

            <Panel>
                <template v-for="(field, index) in props.fieldList.strategies" :key="index">
                    <ViewField
                        v-if="!field.hidden && fieldHasContent(field)"
                        :field="field"
                        :index="index"
                    />
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
                        @click="backToList"
                        type="button"
                    >
                        {{ $t("general.close") }}
                        <template #icon>
                            <X/>
                        </template>
                    </PrimaryButton>

                </Flex>
            </Flex>

        </div>
    </div>
</template>
