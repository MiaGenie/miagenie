<script setup>
import { useI18n } from "vue-i18n";
import Error from "@/Components/Form/Error.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Input from "@/Components/Form/Input.vue";
import { inject } from "vue";
import {cloneDeep, find, get} from "lodash";
import {usePage} from "@inertiajs/vue3";
import Collapse from "@/Components/Surface/Collapse.vue";
import StrategyGroup from "@/Components/Form/Genie/StrategyGroup.vue";

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

        <StrategyGroup :forceFullWidth="true" v-if="!grouped && (schema['x-title'] ?? false) && !(schema['x-group'] ?? false)" class="w-full">
            <template #title>
                <label :for="field.code_name">{{ schema['x-title'] }}</label>
            </template>
        </StrategyGroup>

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

            <Collapse v-if="schema.type === 'array' && (schema['x-group'] ?? false)">

                <template #title>
                    <span  class="font-medium">{{  schema.title  }}</span>
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

        <StrategyGroup :forceFullWidth="true" :class="'c_' + name">
            <template v-if="schema && Object.keys(schema).includes('x-title')" #title>
                {{ schema['x-title'] ?? '' }}
            </template>

            <template v-if="fieldType(field).name === 'CHECKBOX'">

                    <template v-for="(value) in modelValue[name]" :key="value">
                        <Flex class="px-lg">
                            {{ schema['properties'][value]?.title }}
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
        </StrategyGroup>

    </template>

</template>
