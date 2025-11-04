<script setup>
import { useI18n } from "vue-i18n";
import {usePage} from '@inertiajs/vue3';
import {inject, provide, ref} from "vue";
import {find, pick} from "lodash";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Flex from "@/Components/Layout/Flex.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import X from "@/Icons/X.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import StrategySchemaField from "@/Components/Form/Genie/StrategySchemaField.vue";
import Save from "@/Icons/Genie/Save.vue";
import StrategySchemaFieldView from "@/Components/Form/Genie/StrategySchemaFieldView.vue";
import Panel from "@/Components/Surface/Panel.vue";

const {t: $t} = useI18n();

const props = defineProps({
    index: {
        type: Number,
        required: true,
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

const fieldContent = (field, level = 0, keys = '') => {
    if (typeof(field) === "object") {

        let string = '';

        const ordered = pick(field, Object.keys(field).sort());

        Object.keys(ordered).forEach(key => {
            if (typeof(field[key]) === "string") {
                let fieldClass = 'strat_lvl_' + level + '.' + key;
                string += '<span class="' + fieldClass + '">' + field[key] + '</span>';
            } else if (typeof(field[key]) === "object") {
                keys += key + ".";
                string += fieldContent(field[key], level + 1, keys);
            } else {
                string += field[key];
            }
        });
        string = string.replace(/(?:\r\n|\r|\n)/g, '<br>');
        return string;
    }
    return field;
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
<style>

[class^='strat_lvl_'] {
    display: block;
}

[class^='strat_lvl_1'] {
    margin-left: 15px;
}

[class^='strat_lvl_2'] {
    margin-left: 30px;
}

[class^='strat_lvl_3'] {
    margin-left: 40px;
}

[class^='strat_lvl_4'] {
    margin-left: 50px;
}

[class^='strat_lvl_5'] {
    margin-left: 50px;
}

[class^='strat_lvl_5'][class$='_subtopics'] {
    margin-left: 60px;
}

[class^='strat_lvl_3'][class$='_subtopics'] {
    margin-left: 60px;
}

[class*='0_content_pillar_title'] {
    color: red;
}

[class^='strat_lvl_1.'][class$='_title'] {
    color: blue;
    margin: 20px 0 5px 10px;
}

[class^='strat_lvl_0.'][class$='_name']{
    color: green;
    margin: 20px 0 5px 0;
}

[class$='_subtopics'] {
    margin-bottom: 5px;
    color: blueviolet
}

[class$='recommended_digital_channels'] {
    margin-bottom: 10px
}

[class^='strat_lvl_1'][class$='_channels_title'], [class^='strat_lvl_1'][class$='_touchpoints_title'] {
    margin-left: 20px;
}

[class^='strat_lvl_1'][class$='_channels'], [class^='strat_lvl_1'][class$='_touchpoints'] {
    margin-left: 30px;
}

[class^='strat_lvl_0.'][class$='_title'] {
    color: green;
    margin: 20px 0 5px 0;
}
</style>
<template>

    <VerticalGroup class="mt-lg">
        <template #title>
            <Flex class="justify-between">
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

                    {{ field.name }}
                </Flex>
                <Flex>
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
    </VerticalGroup>

    <Panel>

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

            <StrategySchemaFieldView
                v-model="form.content"
                :field="field"
                path=""
                :name="field.code_name"
                :schema="schemas[field.code_name]"
            />

        </template>

    </Panel>

</template>
