<script setup>
import {computed, inject} from "vue";
import {find} from "lodash";
import {usePage} from "@inertiajs/vue3";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Select from "@/Components/Form/Select.vue";
import ImageUploadButton from "@/Components/Media/Genie/ImageUploadButton.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Check from "@/Icons/Check.vue";

const props = defineProps({
    field: {
        type: Object,
        required: true,
    }
});

const form = inject('form');

const inputTypeAttributes = {
    TEXT: 'text',
    NUMBER: 'number',
    RANGE: 'range',
    DATE: 'date',
    DATETIME: 'datetime-local',
    EMAIL: 'email',
    URL: 'url'
};

const fieldType = computed(() => {
    return find(usePage().props.fieldTypes, ['value', Number(props.field.field_type)])?.name;
});

const fileType = computed(() => {
    return find(usePage().props.fileTypes, ['value', Number(props.field.file_type)])?.name;
});

const inputType = computed(() => {
    const type = find(usePage().props.inputTypes, ['value', Number(props.field.input_type)])?.name;

    return inputTypeAttributes[type] ?? 'text';
});

const error = computed(() => form.errors[props.field.code_name] ?? null);

const answer = computed(() => form[props.field.code_name]);

/**
 * CHECKBOX answers are a flat array of the selected option codes.
 */
const isChecked = (code) => {
    return Array.isArray(answer.value) && answer.value.includes(code);
};

const toggle = (code) => {
    const index = answer.value.indexOf(code);

    if (index === -1) {
        answer.value.push(code);
        return;
    }

    answer.value.splice(index, 1);
};

/**
 * RADIO and RADIO_GROUP answers are indexed by option group, one answer per group.
 */
const isSelected = (group, code) => {
    return answer.value?.[group] === code;
};

const select = (group, code) => {
    form[props.field.code_name][group] = isSelected(group, code) ? null : code;
};

const cardClass = (active) => {
    return [
        'w-full rounded-xl border-2 px-md py-sm text-left text-sm font-medium transition-colors duration-200 cursor-pointer',
        active
            ? 'border-primary-500 bg-primary-50 text-black'
            : 'border-stone-600 bg-white hover:border-primary-200 hover:bg-primary-50'
    ];
};
</script>
<template>
    <div class="mx-auto w-full">

        <h2 class="font-title text-xl font-bold leading-snug text-black">
            {{ field.name }}
            <LabelSuffix v-if="field.genie_required" :danger="true">*</LabelSuffix>
        </h2>

        <p v-if="field.description" class="mt-xs text-sm text-gray-500">
            {{ field.description }}
        </p>

        <div class="mt-lg">

            <template v-if="fileType === 'IMAGE'">
                <ImageUploadButton
                    v-model="form[field.code_name]"
                    :fieldName="field.code_name"
                    :caption="field.description"
                    :id="field.code_name"
                />
            </template>

            <template v-else-if="fieldType === 'INPUT'">
                <Input
                    v-model="form[field.code_name]"
                    :type="inputType"
                    :id="field.code_name"
                    :error="error !== null"
                    :required="field.required"
                    @keydown.enter.prevent=""
                />
            </template>

            <template v-else-if="fieldType === 'TEXTAREA'">
                <Textarea
                    v-model="form[field.code_name]"
                    :id="field.code_name"
                    :error="error !== null"
                    :required="field.required"
                    :rows="field.rows ?? 4"
                    class="placeholder:text-sm placeholder:italic"
                />
            </template>

            <template v-else-if="fieldType === 'DROP_DOWN'">
                <Select
                    v-model="form[field.code_name]"
                    :id="field.code_name"
                    :error="error !== null"
                    :required="field.required"
                >
                    <option></option>
                    <option v-for="option in field.options[0]" :key="option.code_name" :value="option.code_name">
                        {{ option.name }}
                    </option>
                </Select>
            </template>

            <template v-else-if="fieldType === 'CHECKBOX'">
                <div class="grid gap-xs sm:grid-cols-2">
                    <button
                        v-for="option in field.options[0]"
                        :key="option.code_name"
                        type="button"
                        :class="cardClass(isChecked(option.code_name))"
                        :aria-pressed="isChecked(option.code_name)"
                        @click="toggle(option.code_name)"
                    >
                        <span class="flex items-start justify-between gap-xs">
                            <span>{{ option.name }}</span>
                            <Check v-if="isChecked(option.code_name)" class="!h-5 !w-5 shrink-0 text-primary-500"/>
                        </span>
                    </button>
                </div>
            </template>

            <template v-else-if="fieldType === 'RADIO'">
                <div v-for="(group, groupIndex) in field.options" :key="groupIndex" class="grid gap-xs">
                    <button
                        v-for="option in group"
                        :key="option.code_name"
                        type="button"
                        :class="cardClass(isSelected(groupIndex, option.code_name))"
                        :aria-pressed="isSelected(groupIndex, option.code_name)"
                        @click="select(groupIndex, option.code_name)"
                    >
                        <span class="flex items-center justify-between gap-xs">
                            <span>{{ option.name }}</span>
                            <Check v-if="isSelected(groupIndex, option.code_name)" class="!h-5 !w-5 shrink-0 text-primary-500"/>
                        </span>
                    </button>
                </div>
            </template>

            <template v-else-if="fieldType === 'RADIO_GROUP'">
                <div class="grid gap-xs">
                    <div v-for="(group, groupIndex) in field.options" :key="groupIndex" class="grid grid-cols-2 gap-xs">
                        <button
                            v-for="option in group"
                            :key="option.code_name"
                            type="button"
                            :class="cardClass(isSelected(groupIndex, option.code_name))"
                            :aria-pressed="isSelected(groupIndex, option.code_name)"
                            @click="select(groupIndex, option.code_name)"
                        >
                            <span class="text-center block">{{ option.name }}</span>
                        </button>
                    </div>
                </div>
            </template>

            <div v-if="error" class="mt-xs">
                <Error :message="error"/>
            </div>

        </div>
    </div>
</template>
