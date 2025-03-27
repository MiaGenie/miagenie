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
import Label from "@/Components/Form/Label.vue";
import Input from "@/Components/Form/Input.vue";
import { inject } from "vue";
import {find} from "lodash";

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

});

const form = inject('form');

const fieldType = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
}

</script>
<template>
    <template slot="title">{{ $t("general.details") }}</template>

    <template v-for="(field, index) in props.fieldList" :key="index">
        <VerticalGroup class="form-field mt-lg">
            <template #title>
                <label :for="field.code_name">{{ field.name }}</label>
                <LabelSuffix v-if="field.required" :danger="true">*</LabelSuffix>
            </template>

            <template v-if="fieldType(field).name === 'INPUT'">

                <Input v-model="form.content[field.code_name]"
                       type="text"
                       :id="field.code_name"
                       @keydown.enter.prevent=""
                       :placeholder="field.description"
                       :error="form.errors[field.code_name] !== undefined"
                       :required="field.required"
                />

            </template>

            <template v-if="fieldType(field).name === 'TEXTAREA'">

                <Textarea v-model="form.content[field.code_name]"
                          :placeholder="field.description"
                          :error="form.errors[field.code_name] !== undefined"
                          :id="field.code_name"
                          class="w-full placeholder:italic placeholder:text-sm"
                          :rows="field.size ?? 4"
                />

            </template>

            <template v-if="fieldType(field).name === 'CHECKBOX'">

                <TableCell class="">
                    <template v-for="(option, index_option) in field.options[0]" :key="option.code_name">
                        <Flex class="py-sm">
                            <Checkbox v-model:checked="form.content[field.code_name]" :value="option.code_name"/>
                            {{ option.name }}
                        </Flex>
                    </template>
                </TableCell>

            </template>

            <template v-if="fieldType(field).name === 'DROP_DOWN'">

                <Flex class="items-start">
                    <Select v-model="form.content[field.code_name]"
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
                                <Radio v-model:checked="form.content[field.code_name][index]" :value="option.code_name"/>
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
                                    <Radio v-model:checked="form.content[field.code_name][index]" :value="option.code_name"/>
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
</template>
