<script setup>
import {inject} from "vue";
import {router} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Trash from "@/Icons/Trash.vue";

const {t: $t} = useI18n();

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');

const props = defineProps({
    record: {
        type: Object
    },
    version: {
        type: Object,
        required: true
    },
    group: {
        type: Object,
        required: true
    },
    create: {
        type: Boolean,
        default: true
    },
    edit: {
        type: Boolean,
        default: true
    },
    destroy: {
        type: Boolean,
        default: true
    },
})

const {onError} = useRouter();

const getRoute = (name) => {
    switch (name) {
        case 'create':
            return route('genie.admin.versions.groups.fields.create', {
                version: props.version.id,
                group: props.group.id,
            });
        case 'edit':
            return route('genie.admin.versions.groups.fields.edit', {
                version: props.version.id,
                group: props.group.id,
                field: props.itemId,
            });
        case 'delete':
            return route('genie.admin.versions.groups.fields.delete', {
                field: props.record.id,
            });
        default:
            return '';
    }
}

const confirmDeleteGroup = () => {
    confirmation()
        .title($t('genie.delete_group'))
        .description($t('genie.delete_group_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteGroupAfterConfirmed(dialog);
        })
        .show();
}

const deleteGroupAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        preserveScroll: true,
        onError(errors) {
            onError(errors, () => {
                deleteGroupAfterConfirmed(dialog);
            });
        },
        onFinish() {
            dialog.reset();
        }
    });
}
</script>
<template>
    <Flex :responsive="false" class="items-center">
        <template v-if="destroy">
            <DangerButton @click="confirmDeleteGroup" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>
        </template>
    </Flex>
</template>
