<script setup>
import {isEmpty, filter, size} from "lodash";
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
const rule = usePage().props.rule;
const locales = usePage().props.locales;
const translations = usePage().props.translations;

const getClass = (locale) => {
    let doneTranslations = filter(translations[props.item.id], locale.long);
    if (isEmpty(doneTranslations)) {
        return 'bg-red-100';
    }

    let baseTranslations = filter(translations[props.item.id], 'en-GB');
    if (size(doneTranslations) === size(baseTranslations)) {
        return 'bg-lime-100';
    }
    return 'bg-yellow-100';
}

const getRoute = (locale) => {
    return route('genie.admin.versions.rules.steps.translate', {
        version: version.id,
        rule: rule.id,
        step: props.item.id,
        locale: locale,
    });
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
                        :class="getClass(locale)"
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
        <div class="flex-row items-center justify-end gap-md hidden sm:flex">

            <PureButtonLink
                v-for="locale in locales"
                :class="getClass(locale)"
                class="text-center border-gray-300 border border-spacing-md min-h-6 min-w-7 justify-center rounded"
                :href="getRoute(locale.long)"
                v-tooltip="locale.english + ' (' + locale.long + ')'"
            >
                <template #default>
                    {{ locale.short }}
                </template>
            </PureButtonLink>

        </div>
    </div>
</template>
