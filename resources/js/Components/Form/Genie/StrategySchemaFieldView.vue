<script setup>
import { useI18n } from "vue-i18n";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Error from "@/Components/Form/Error.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Select from "@/Components/Form/Select.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Radio from "@/Components/Form/Radio.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Input from "@/Components/Form/Input.vue";
import { inject } from "vue";
import {cloneDeep, find, get} from "lodash";
import {usePage} from "@inertiajs/vue3";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Collapse from "@/Components/Surface/Collapse.vue";
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";
import DigitalChannel from "@/Components/DataDisplay/Genie/DigitalChannel.vue";

const {t: $t} = useI18n();

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => {},
    },
    field: {
        type: Object,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    path: {
        type: String,
        required: true,
    },
    schema: {
        type: Object,
        required: true,
    },
    grouped: {
        type: Boolean,
        default: false,
    }
});

const form = inject('form');
const fieldForm = inject('fieldForm');

const modelValues = cloneDeep(props.modelValue);

const fieldType = (field) => {
    return find(usePage().props.fieldTypes, ['value', Number(field.field_type)]);
}

const arrayItems = () => {
    return get(cloneDeep(props.modelValue), props.name, []);
}

const sortedFirstKey = (obj) => {
    return Object.keys(obj)
        .sort()
        .slice(0, 1)
        .pop()
}

const titleKey = () => {
    return sortedFirstKey(props.schema.properties)
}

const formValue = () => {
    return get(props.modelValue, props.name);
}

const isObject = (field) => {
    return typeof field === "object";
}

</script>
<template>

    <template v-if="(schema?.type === 'object' || schema?.type === 'array') && fieldType(field).name !== 'CHECKBOX'">

        <div :class="'c_' + name">

        <VerticalGroup :forceFullWidth="true" v-if="!grouped && (schema['x-title'] ?? false) && !(schema['x-group'] ?? false)" class="w-full">
            <template #title>
                <label :for="field.code_name">{{ schema['x-title'] }}</label>
            </template>
        </VerticalGroup>

             <template v-if="schema.type === 'object' && !Boolean(field.is_multiple)" v-for="(property, propKey) in schema.properties" :key="propKey">

                <StrategySchemaFieldView
                    v-if="!grouped || !(props.field.display_grouped && props.field.display_item_title && (propKey === titleKey()))"
                    v-model="modelValue[name]"
                    :field="field"
                    :path="path + '.' + propKey"
                    :name="propKey"
                    :schema="property"
                />

            </template>
            <template
                v-else-if="Boolean(field.is_multiple)"
            >

                <StrategySchemaFieldView
                    v-for="(property, propKey) in schema.properties" :key="propKey"
                    v-model="modelValue[name]"
                    :field="field"
                    :path="path + '.' + propKey"
                    :name="propKey"
                    :schema="property"
                />

            </template>

            <Collapse colorClass="mt-md" v-if="schema.type === 'array' && (schema['x-group'] ?? false)">

                <template #title>
                    {{  schema.title  }}
                </template>


                <div>
                    <StrategySchemaFieldView
                        v-for="(item, key) in arrayItems()" :key="key"
                        v-model="modelValue[name]"
                        :field="field"
                        :path="path + '.' + key"
                        :name="key.toString()"
                        :schema="schema.items"
                    />
                </div>


            </Collapse>

            <StrategySchemaFieldView
                v-else-if="schema.type === 'array'"
                v-for="(item, key) in arrayItems()" :key="key"
                v-model="modelValue[name]"
                :field="field"
                :path="path + '.' + key"
                :name="key.toString()"
                :schema="schema.items"
            />

        </div>

    </template>

    <template v-else>

        <VerticalGroup :forceFullWidth="true" :class="'c_' + name">
            <template v-if="schema['x-title'] ?? false" #title>
                {{ schema['x-title'] }}
            </template>

            <template v-if="fieldType(field).name === 'CHECKBOX'">

                    <template v-for="(value) in modelValue[name]" :key="value">
                        <Flex class="px-lg">
                            <DigitalChannel :provider="value"></DigitalChannel>
                            {{ schema['properties'][value].title }}
                        </Flex>
                    </template>

            </template>

            <template v-else-if="schema?.type === 'string' && (schema.enum)">

                <span>{{ modelValue[name] }}</span>

            </template>

            <template v-else-if="(schema?.type === 'string' && Number(schema?.maxLength) < 120) || schema?.type === 'number' && false">

                <Input v-model="modelValue[name]"
                       :placeholder="schema.description"
                       :id="path"
                       :required="field.required"
                />

            </template>

            <template v-else-if="schema?.type === 'string' || schema?.type === 'integer'">

                <span>{{ modelValue[name] }}</span>

            </template>

            <template v-else>

                <span>SOMETHING SHOULD BE HERE!</span>
                <span>{{ modelValue }}</span>

            </template>


            <template #footer>
                <Error :message="form.errors.content?.field.code_name"/>
            </template>
        </VerticalGroup>

    </template>

</template>
