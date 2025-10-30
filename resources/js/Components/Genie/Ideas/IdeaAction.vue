<script setup>
import {router} from "@inertiajs/vue3";
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Trash from "@/Icons/Trash.vue";


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
            return route('genie.ideas.create', {
            });
        case 'edit':
            return route('genie.ideas.edit', {
                idea: props.record.id,
            });
        case 'delete':
            return route('genie.ideas.delete', {
                idea: props.record.id,
            });
        default:
            return '';
    }
}

const confirmDeleteIdea = () => {
    confirmation()
        .title($t('genie.delete_idea'))
        .description($t('genie.delete_idea_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteIdeaAfterConfirmed(dialog);
        })
        .show();
}

const deleteIdeaAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        preserveScroll: true,
        onError(errors) {
            onError(errors, () => {
                deleteIdeaAfterConfirmed(dialog);
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
            <DangerButton @click="confirmDeleteIdea" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>
        </template>
    </Flex>
</template>
