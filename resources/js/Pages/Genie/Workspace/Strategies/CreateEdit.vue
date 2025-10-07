<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, provide} from "vue";
import {cloneDeep} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import usePageMode from "@/Composables/usePageMode";
import useRouter from "@/Composables/useRouter";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import ViewField from "@/Components/DataDisplay/Genie/ViewField.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";

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
    }
})

const workspaceCtx = inject('workspaceCtx')


/*const form = useForm(isEdit.value ? cloneDeep(props.record) :

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
);*/


const backToList = () => {
    router.get(route('genie.strategies.index', {
        workspace: workspaceCtx.id
    }));
}


provide('fieldList', props.fieldList);
provide('fieldTypes', props.fieldTypes);
provide('record', props.record);

</script>
<template>

    <Head :title="$t('genie.view_strategy')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.view_strategy')"/>

        <div class="row-px">

            <Panel>
                <template v-for="(field, index) in props.fieldList.strategies" :key="index">
                    <ViewField
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
