<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";
import {usePage} from "@inertiajs/vue3";

const {t: $t} = useI18n()

const version = usePage().props.version;

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.admin.versions.rules.edit', {
                version: version.id,
                rule: props.itemId,
            });
        case 'steps':
            return route('genie.admin.versions.rules.steps.index', {
                version: version.id,
                rule: props.itemId,
            });
        default:
            return '';
    }
}

</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-lg">
            <PureButtonLink
                :href="getRoute('steps')"
                v-tooltip="$t('genie.steps')"
            >
                <QueueList/>
            </PureButtonLink>

            <PureButtonLink
                :href="getRoute('edit')"
                v-tooltip="$t('general.edit')"
            >
                <PencilSquare/>
            </PureButtonLink>
        </div>
    </div>
</template>
