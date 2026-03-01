<script setup>
import {computed, inject} from "vue";
import {find} from "lodash";
import {usePage} from "@inertiajs/vue3";
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
import ImageUploadButton from "@/Components/Media/Genie/ImageUploadButton.vue";
import Label from "@/Components/Form/Label.vue";

const props = defineProps({
    field: {
        type: Object,
        required: true,
    }
});

const form = inject('form');

const fieldType = () => {
    return find(usePage().props.fieldTypes, ['value', Number(props.field.field_type)]);
}

const fileType = () => {
    return find(usePage().props.fileTypes, ['value', Number(props.field.file_type)]);
}

const fieldsErrors = computed ( () => {
    return form.errors.hasOwnProperty('content.' + props.field.code_name) ? form.errors[props.field.code_name] : null;
})

</script>
<template>

        <VerticalGroup class="form-field mx-auto mt-2xl">
            <template #title>
                <Flex :col="true">
                    <Flex :responsive="false">
                        <label :for="field.code_name">{{ field.name }}</label>
                        <LabelSuffix v-if="field.required" :danger="true">*</LabelSuffix>
                    </Flex>
                    <Error :message="form.errors[props.field.code_name] ?? null"/>
                </Flex>
            </template>

            <template v-if="fieldType().name === 'INPUT'">

                <Input
                    v-model="form[field.code_name]"
                    type="text"
                    :id="field.code_name"
                    @keydown.enter.prevent=""
                    :placeholder="field.description"
                    :error="form.errors[field.code_name] !== undefined"
                    :required="field.required"
                />

            </template>

            <template v-else-if="fileType()?.name === 'IMAGE'">

                <ImageUploadButton
                    v-model="form[field.code_name]"
                    :fieldName="field.code_name"
                    :caption="field.description"
                    :id="field.code_name"
                />

            </template>

            <template v-else-if="fieldType().name === 'TEXTAREA'">

                <Textarea v-model="form[field.code_name]"
                          :placeholder="field.description"
                          :error="form.errors[field.code_name] !== undefined"
                          :required="field.required"
                          :id="field.code_name"
                          class="w-full placeholder:italic placeholder:text-sm"
                          :rows="field.size ?? 4"
                />

            </template>

            <template #description v-if="fieldType().name === 'CHECKBOX'">
                {{ field.description }}
            </template>

            <template v-if="fieldType().name === 'CHECKBOX'">

                <Flex :id="field.code_name" :col="true" class="">
                    <fieldset>
                        <template v-for="(option, index_option) in field.options[0]" :key="option.code_name">
                            <Flex :responsive="false" class="py-sm">
                                <Checkbox v-model:checked="form[field.code_name]" :value="option.code_name" :id="`${field.code_name}-${index_option}`"/>
                                <Label :for="`${field.code_name}-${index_option}`" class="!mb-0">{{ option.name }}</Label>
                            </Flex>
                        </template>
                    </fieldset>
                </Flex>

            </template>

            <template #description v-if="fieldType().name === 'DROP_DOWN'">
                {{ field.description }}
            </template>

            <template v-if="fieldType().name === 'DROP_DOWN'">

                <Flex class="items-start">
                    <Select v-model="form[field.code_name]"
                            :error="form.errors[field.code_name] !== undefined"
                            :required="field.required"
                            :id="field.code_name" >
                        <option></option>
                        <template v-for="(option, index) in field.options[0]" :key="index">
                            <option :value="option.code_name">{{ option.name }}</option>
                        </template>
                    </Select>
                </Flex>

            </template>

            <template #description v-if="fieldType().name === 'RADIO'">
                {{ field.description }}
            </template>

            <template v-if="fieldType().name === 'RADIO'">

                <TableCell class="">
                    <template v-for="(group, index) in field.options" :key="index">
                        <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                            <Flex class="py-sm">
                                <Label :for="`${field.code_name}-${index}-${index_option}`" class="!mb-0">
                                    <Radio v-model:checked="form[field.code_name][index]" :value="option.code_name" :id="`${field.code_name}-${index}-${index_option}`"/>
                                    {{ option.name }}
                                </Label>
                            </Flex>
                        </template>
                    </template>
                </TableCell>

            </template>

            <template #description v-if="fieldType().name === 'RADIO_GROUP'">
                {{ field.description }}
            </template>

            <template v-if="fieldType().name === 'RADIO_GROUP'">

                <div >
                    <template v-for="(group, index) in field.options" :key="index">
                        <TableRow class="">
                            <template v-for="(option, index_option) in field.options[index]" :key="option.code_name">
                                <TableCell class="">
                                    <Label :for="`${field.code_name}-${index}-${index_option}`" class="!mb-0">
                                        <Radio v-model:checked="form[field.code_name][index]" :value="option.code_name" :id="`${field.code_name}-${index}-${index_option}`"/>
                                        {{ option.name }}
                                    </Label>
                                </TableCell>
                            </template>
                        </TableRow>
                    </template>
                </div>

            </template>

            <template #footer>

            </template>
        </VerticalGroup>

</template>
