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
    <template slot="title">{{ $t("general.details") }}</template>


        <VerticalGroup class="form-field mt-lg">
            <template #title>
                {{ field.name }}
            </template>

            <template v-if="fieldType(field).name === 'INPUT' || fieldType(field).name === 'TEXTAREA'">
                <span class="bg-gray-100" v-html="fieldContent(record.content[field.code_name])">
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
