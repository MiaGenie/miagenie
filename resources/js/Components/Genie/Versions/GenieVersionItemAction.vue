<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";

const {t: $t} = useI18n()
const routePrefix = inject('routePrefix');
const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.admin.versions.edit', {
                version: props.itemId,
            });
        case 'fields':
            return route('genie.admin.versions.fields.index', {
                version: props.itemId,
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
                :href="getRoute('fields')"
                v-tooltip="$t('genie.fields')"
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
