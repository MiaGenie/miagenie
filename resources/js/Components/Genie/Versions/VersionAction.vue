<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import {router} from "@inertiajs/vue3";
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
            return route('genie.admin.versions.create', {
                version: props.record.id,
                group: props.record.id,
            });
        case 'edit':
            return route('genie.admin.versions.edit', {
                field: props.record.id,
            });
        case 'delete':
            return route('genie.admin.versions.delete', {
                field: props.record.id,
            });
        default:
            return '';
    }
}

const confirmDeleteVersion = () => {
    confirmation()
        .title($t('genie.delete_version'))
        .description($t('genie.delete_version_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteVersionAfterConfirmed(dialog);
        })
        .show();
}

const deleteVersionAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        preserveScroll: true,
        onError(errors) {
            onError(errors, () => {
                deleteVersionAfterConfirmed(dialog);
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
            <DangerButton @click="confirmDeleteVersion" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>
        </template>
    </Flex>
</template>
