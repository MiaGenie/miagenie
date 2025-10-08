<script setup>
import { useI18n } from "vue-i18n";
import Select from "@/Components/Form/Select.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Radio from "@/Components/Form/Radio.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import Flex from "@/Components/Layout/Flex.vue";
import {inject, provide} from "vue";
import {find, pick} from "lodash";

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

const fieldList = inject('fieldList')
const fieldTypes = inject('fieldTypes')
const record = inject('record')

const fieldType = (field) => {
    return find(fieldTypes, ['value', Number(field.field_type)]);
}

const fieldContent = (field, level = 0) => {
    if (typeof(field) === "object") {
        let string = '';

        const ordered = pick(field, Object.keys(field).sort());

        Object.keys(ordered).forEach(key => {
            if (typeof(field[key]) === "string") {
                if (level > 0){
                    string += " ".repeat(level * 2);
                }
                string += field[key] + "\n";
            } else if (typeof(field[key]) === "object") {
                string += fieldContent(field[key], level + 1) + "\n";
            } else {
                string += field[key] + "\n";
            }
        });
        return string;
    }
    return field;
}

</script>
<template>
    <template slot="title">{{ $t("general.details") }}</template>


        <VerticalGroup class="form-field mt-lg">
            <template #title>
                {{ field.name }}
            </template>

            <template v-if="fieldType(field).name === 'INPUT' || fieldType(field).name === 'TEXTAREA'">
                <span class="bg-gray-100" style="white-space: pre-wrap">
                    {{ fieldContent(record.content[field.code_name]) }}
                </span>
            </template>

            <template v-if="fieldType(field).name === 'CHECKBOX'">

                <span class="bg-gray-100" style="white-space: pre-wrap">
                    <template v-for="(value, key) in record.content[field.code_name]":key="key">
                      {{ key }}: {{ value }} {{ "\n" }}
                    </template>
                </span>

            </template>

            <template v-if="fieldType(field).name === 'DROP_DOWN'">

                <Flex class="items-start">
                    <Select v-model="record.content[field.code_name][index]"
                            id="testing" >
                        <option></option>
                        <template v-for="(option, index) in field.options[0]" :key="index">
                            <option :value="option.code_name">{{ option.name }}</option>
                        </template>
                    </Select>
                </Flex>

            </template>

            <template v-if="fieldType(field).name === 'RADIO'">

                <TableCell class="">
                    <template v-for="(group, index) in field.options" :key="index">
                        <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                            <Flex class="py-sm">
                                <Radio v-model:checked="record.content[field.code_name][index]" :value="option.code_name"/>
                                {{ option.name }}
                            </Flex>
                        </template>
                    </template>
                </TableCell>

            </template>

            <template v-if="fieldType(field).name === 'RADIO_GROUP'">

                <div >
                    <template v-for="(group, index) in field.options" :key="index">
                        <TableRow class="">
                            <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                                <TableCell class="">
                                    <Radio v-model:checked="record.content[field.code_name][index]" :value="option.code_name"/>
                                    {{ option.name }}
                                </TableCell>
                            </template>
                        </TableRow>
                    </template>
                </div>

            </template>

        </VerticalGroup>

</template>
