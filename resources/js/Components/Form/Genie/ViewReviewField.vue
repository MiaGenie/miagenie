<script setup>
import { inject } from "vue";
import {find} from "lodash";
import Error from "@/Components/Form/Error.vue";
import Select from "@/Components/Form/Select.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Radio from "@/Components/Form/Radio.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import Flex from "@/Components/Layout/Flex.vue";
import SectionTitle from "@/Components/DataDisplay/SectionTitle.vue";
import CheckIcon from "@/Icons/Check.vue";
import XIcon from "@/Icons/X.vue";

const props = defineProps({
    record: {
        type: Object,
        required: true,
    },
    field: {
        type: Object,
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
});

const form = inject('form');

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

</script>
<template>


        <template
            class="form-field mt-lg"
            v-if="fieldType(field).name === 'INPUT' || fieldType(field).name === 'TEXTAREA'"
            v-for="(value, key, index) in props.record[field.code_name]" :key="key"
        >
            <VerticalGroup class="form-field mt-lg">

                <template #title>
                    {{ field.name + ' ' + (index + 1) }}
                </template>

                <template #default>
                    <span style="white-space: pre-line">
                        {{ value }}
                    </span>
                </template>

            </VerticalGroup>
        </template>

        <template
            class="form-field mt-lg"
            v-if="fieldType(field).name === 'CHECKBOX'"
        >
            <SectionTitle>
                {{ field.name }}
            </SectionTitle>

            <template v-for="(option, key) in field.options" :key="key">

                <VerticalGroup class="form-field mt-sm">
                    <template #default>
                        <CheckIcon class="text-green-900" v-if="record[field.code_name][option.code_name]" />
                        <XIcon class="text-red-700" v-if="!record[field.code_name][option.code_name]" />
                        <span :class="record[field.code_name][option.code_name] ? 'text-green-900, font-semibold' : 'text-red-700'">
                            {{ option.name }}
                        </span>
                    </template>
                </VerticalGroup>

            </template>

        </template>

        <VerticalGroup class="form-field mt-lg">

            <template v-if="fieldType(field).name === 'DROP_DOWN'">

                <Flex class="items-start">
                    <Select v-model="form[field.code_name][index]"
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
                                <Radio v-model:checked="form[field.code_name][index]" :value="option.code_name"/>
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
                                    <Radio v-model:checked="form[field.code_name][index]" :value="option.code_name"/>
                                    {{ option.name }}
                                </TableCell>
                            </template>
                        </TableRow>
                    </template>
                </div>

            </template>

            <template #footer>
                <Error :message="form.errors.content?.field.code_name"/>
            </template>
        </VerticalGroup>

</template>
