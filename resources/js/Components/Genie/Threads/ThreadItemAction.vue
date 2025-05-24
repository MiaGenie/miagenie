<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";
import {usePage} from "@inertiajs/vue3";

const {t: $t} = useI18n()

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'view':
            return route('genie.admin.threads.view', {
                thread: props.itemId,
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
                :href="getRoute('view')"
                v-tooltip="$t('general.view')"
            >
                <PencilSquare/>
            </PureButtonLink>
        </div>
    </div>
</template>
