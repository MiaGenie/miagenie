<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import {router} from "@inertiajs/vue3";
import Trash from "@/Icons/Trash.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import useRouter from "@/Composables/useRouter";

const {t: $t} = useI18n();

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
            return route(`genie.admin.vectors.create`, {
                vector: props.record.id,
            });
        case 'edit':
            return route(`genie.admin.vectors.edit`, {
                vector: props.record.id,
            });
        case 'delete':
            return route(`genie.admin.vectors.delete`, {
                vector: props.record.id,
            });
        default:
            return '';
    }
}

const confirmDeleteVector = () => {
    confirmation()
        .title($t('genie.delete_vector'))
        .description($t('genie.delete_vector_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteVectorAfterConfirmed(dialog);
        })
        .show();
}

const deleteVectorAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        preserveScroll: true,
        onError(errors) {
            onError(errors, () => {
                deleteVectorAfterConfirmed(dialog);
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
            <DangerButton @click="confirmDeleteVector" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>
        </template>
    </Flex>
</template>
