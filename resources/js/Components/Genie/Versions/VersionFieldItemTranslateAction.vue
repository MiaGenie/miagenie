<script setup>
import {inject} from "vue";
import {isEmpty, filter, size, reduce} from "lodash";
import {usePage} from "@inertiajs/vue3";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue";
import DropdownButton from "@/Components/Dropdown/DropdownButton.vue";


const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
})

const version = usePage().props.version;
const locales = usePage().props.locales;
const translations = usePage().props.translations;

const currentFilter = inject('currentFilter');
const fieldType = inject('fieldType');

const baseFieldTranslations = filter(translations['fields'][props.item.id], 'en-GB');
const baseOptionsTranslations = reduce(translations['options']?.[props.item.id], function(result, value, key) {
    result = filter(value, 'en-GB');
    return result;
}, {});

const getFieldClass = (locale) => {
    let doneTranslations = filter(translations['fields'][props.item.id], locale.long);
    if (isEmpty(doneTranslations)) {
        return 'bg-red-100';
    }
    if (size(doneTranslations) === size(baseFieldTranslations)) {
        return 'bg-lime-100';
    }
    return 'bg-yellow-100';
}

const getOptionsClass = (locale) => {
    // let doneTranslations = filter(translations['options'][props.item.id], locale.long);

    let doneTranslations = reduce(translations['options'][props.item.id], function(result, value, key) {
        result = filter(value, locale.long);
        return result;
    }, {});
    if (isEmpty(doneTranslations)) {
        return 'bg-red-100';
    }
    if (size(doneTranslations) === size(baseOptionsTranslations)) {
        return 'bg-lime-100';
    }
    return 'bg-yellow-100';
}

const getFieldRoute = (locale) => {
    return route(
        'genie.admin.versions.fields.translate-field',
        {
            version: version.id,
            field: props.item.id,
            locale: locale,
            group_type: Number(currentFilter.value.group_type)
        }
    )
}

const getOptionsRoute = (locale) => {
    return route(
        'genie.admin.versions.fields.translate-options',
        {
            version: version.id,
            field: props.item.id,
            locale: locale,
            group_type: Number(currentFilter.value.group_type)
        }
    )
}

</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-xs sm:hidden">

            <Dropdown placement="bottom-end">
                <template #trigger>
                    <DropdownButton/>
                </template>

                <template #content>

                    <DropdownItem
                        v-for="locale in locales"
                        :class="getFieldClass(locale)"
                        class="text-center justify-center"
                        :href="getRoute(locale.long)"
                        v-tooltip="locale.english"
                    >
                        <template #default>
                            {{ locale.short }}
                        </template>
                    </DropdownItem>

                </template>
            </Dropdown>

        </div>
        <div class="flex-row items-center justify-center gap-md hidden sm:flex">

            <PureButtonLink
                v-for="locale in locales"
                :class="getFieldClass(locale)"
                class="text-center border-gray-300 border border-spacing-md min-h-6 min-w-7 justify-center rounded"
                :href="getFieldRoute(locale.long)"
                v-tooltip="locale.english + ' (' + locale.long + ')'"
            >
                <template #default>
                    {{ locale.short }}
                </template>
            </PureButtonLink>

        </div>
        <template v-if="fieldType().hasOptions">
            <div class="flex-col justify-center py-xs bg-gray-50 mt-xs hidden sm:flex">
                <div class="text-gray-600 justify-center flex">
                    {{ 'options' }}
                </div>
                <div class="mt-xs justify-center gap-md flex">
                    <PureButtonLink
                        v-for="locale in locales"
                        :class="getOptionsClass(locale)"
                        class="text-center border-gray-300 border border-spacing-md min-h-6 min-w-7 justify-center rounded"
                        :href="getOptionsRoute(locale.long)"
                        v-tooltip="locale.english + ' (' + locale.long + ')'"
                    >
                        <template #default>
                            {{ locale.short }}
                        </template>
                    </PureButtonLink>
                </div>
            </div>
        </template>
    </div>
</template>
