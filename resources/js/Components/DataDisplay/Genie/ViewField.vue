<script setup>
import { useI18n } from "vue-i18n";
import {usePage} from '@inertiajs/vue3';
import {inject, provide, ref} from "vue";
import {find} from "lodash";
import Flex from "@/Components/Layout/Flex.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import X from "@/Icons/X.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import StrategySchemaField from "@/Components/Form/Genie/StrategySchemaField.vue";
import Save from "@/Icons/Genie/Save.vue";
import StrategySchemaFieldView from "@/Components/Form/Genie/StrategySchemaFieldView.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Collapse from "@/Components/Surface/Collapse.vue";
import CollapseSmall from "@/Components/Surface/Genie/CollapseSmall.vue";
import VerticalGroupClass from "@/Components/Layout/Genie/VerticalGroupClass.vue";
import Group from "@/Components/Surface/Genie/Group.vue";
import StrategyFieldIcon from "@/Components/DataDisplay/Genie/StrategyFieldIcon.vue";

const {t: $t} = useI18n();

const props = defineProps({
    index: {
        type: Number,
        required: true,
    },
    id: {
        type: String
    },
    field: {
        type: Object,
        required: true,
    }
});

const workspaceCtx = inject('workspaceCtx')
const confirmation = inject('confirmation');
const schemas = inject('schemas')
const form = inject('form')
provide("fieldForm", form.content[props.field.code_name])
const fieldList = inject('fieldList')
const fieldTypes = inject('fieldTypes')
const record = inject('record')
const editing = ref(inject('editing'));

const fieldType = (field) => {
    return find(fieldTypes, ['value', Number(field.field_type)]);
}

const schemaType = () => {
    return usePage().props.schemas[props.field.code_name]?.type;
}

const sortedFirstKey = (obj) => {
    return Object.keys(obj)
        .sort()
        .slice(0, 1)
        .pop()
}

const titleKey = () => {
    return sortedFirstKey(schemas[props.field.code_name].items.properties)
}

const groupedTitle = (item, key) => {
    if (props.field.display_item_title) {
        return item[titleKey()];
    }
    if (props.field.display_field_title) {
        return (schemas[props.field.code_name]['items']?.title ?? '').replace('#', (key + 1));
    }
    return '# ' + (key + 1);
}

const multipleTitle = (item, key) => {
    const titleKey = sortedFirstKey(schemas[props.field.code_name].properties);
    return props.field.display_item_title ? item[titleKey] : props.field.display_field_title ? schemas[props.field.code_name]?.title ?? '' + ' #' + (key + 1) : '';
}

const update = () => {
    form.put(route('genie.strategies.update', {
        'workspace': workspaceCtx.id,
        'strategy': record.id
    }), {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = '';
        },
        onError: (errors) => {
            onError(errors, update);
        },
    });
}

const attemptClose = () => {
    if (!form.isDirty) {
        editing.value = '';
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm((dialog) => {
            editing.value = '';
            form.reset();
            dialog.reset();
        })
        .show();
}

</script>
<template>

    <VerticalGroupClass bodyClass="text-lg" :forceFullWidth="true" class="mt-lg">
        <template #title>
            <Flex class="justify-between">
                <Flex>
                    <span v-if="field.display_title && !(schemas[field.code_name]?.type === 'object' && field.display_grouped && !field.is_multiple)">
                        {{ field.name }}
                    </span>
                </Flex>
                <Flex>
                    <PureButton
                        v-if="editing !== field.code_name"
                        @click="editing = field.code_name"
                        v-tooltip="$t('general.edit')"
                    >
                        <template #icon>
                            <PencilSquare/>
                        </template>
                    </PureButton>

                    <PureButton
                        v-if="editing === field.code_name"
                        @click="update"
                        v-tooltip="$t('general.save')"
                    >
                        <template #icon>
                            <Save/>
                        </template>
                    </PureButton>

                    <PureButton
                        v-if="editing === field.code_name"
                        @click="attemptClose(editing)"
                        v-tooltip="$t('general.edit')"
                    >
                        <template #icon>
                            <X/>
                        </template>
                    </PureButton>
                </Flex>
            </Flex>
        </template>

        <template #description>
            {{ field.description }}
        </template>
    </VerticalGroupClass>

    <template v-if="field.display_faq_title">

        <CollapseSmall>

            <template #title>{{  field.display_faq_title  }}</template>
            <template #default>
                <span v-html="field.display_faq_text"></span>
            </template>

        </CollapseSmall>

    </template>

    <div :id="props.id">

        <template v-if="editing === field.code_name">

            <StrategySchemaField
                v-model="form.content"
                :field="field"
                path=""
                :name="field.code_name"
                :schema="schemas[field.code_name]"
            />

        </template>

        <template v-else-if="editing !== field.code_name">

            <template v-if="schemas[field.code_name]?.type === 'array'">

                <Collapse v-if="field.display_grouped" v-for="(item, key) in form.content[field.code_name]" :key="key">

                    <template #title>
                        {{ groupedTitle(item, key) }}
                    </template>

                        <StrategySchemaFieldView
                            v-model="form.content[field.code_name]"
                            :field="field"
                            :path="field.code_name + '.' + key"
                            :name="key.toString()"
                            :schema="schemas[field.code_name]['items']"
                            :grouped="true"
                        />

                </Collapse>

                <template v-else v-for="(item, key) in form.content[field.code_name]">

                    <StrategySchemaFieldView
                        v-model="form.content[field.code_name]"
                        :field="field"
                        :path="field.code_name + '.' + key"
                        :name="key.toString()"
                        :schema="schemas[field.code_name]['items']"
                        :grouped="false"
                    />

                </template>

            </template>
            <template v-else-if="Boolean(field.is_multiple)">

                <Collapse v-for="(item, key) in form.content[field.code_name]" :key="key">

                    <template #title>
                        {{ multipleTitle(item, key) }}
                    </template>

                        <StrategySchemaFieldView
                            v-model="form.content[field.code_name]"
                            :field="field"
                            :path="field.code_name + '.' + key"
                            :name="key.toString()"
                            :schema="schemas[field.code_name]"
                            :titled="false"
                        />

                </Collapse>


            </template>
            <template v-else-if="field.display_grouped">

                <Collapse>

                    <template #title>
                        <Flex>
                            <StrategyFieldIcon
                                v-if="schemas[field.code_name]['x-icon']"
                                :field="field.code_name"
                                :icon="schemas[field.code_name]['x-icon']"
                            />
                            {{ schemas[field.code_name]['x-title'] }}
                        </Flex>
                    </template>


                <StrategySchemaFieldView
                    v-model="form.content"
                    :field="field"
                    path=""
                    :name="field.code_name"
                    :schema="schemas[field.code_name]"
                    :grouped="true"
                />


                </Collapse>

            </template>
            <template v-else>

                <StrategySchemaFieldView
                    v-model="form.content"
                    :field="field"
                    path=""
                    :name="field.code_name"
                    :schema="schemas[field.code_name]"
                />

            </template>

        </template>

    </div>

</template>
