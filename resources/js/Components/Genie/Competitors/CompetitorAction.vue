<script setup>
import {router} from "@inertiajs/vue3";
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import Trash from "@/Icons/Trash.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import Flex from "@/Components/Layout/Flex.vue";
import useRouter from "@/Composables/useRouter";

const {t: $t} = useI18n();

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    record: {
        type: Object
    }
})

const {onError} = useRouter();

const confirmDeleteCompetitor = () => {
    confirmation()
        .title($t('genie.delete_competitor'))
        .description($t('genie.delete_competitor_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteCompetitorAfterConfirmed(dialog);
        })
        .show();
}

const deleteCompetitorAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(
        route(`${routePrefix}.competitors.delete`, {
            workspace: workspaceCtx.id,
            competitor: props.record.id,
        }),
        {
            preserveScroll: true,
            onError(errors) {
                onError(errors, () => {
                    deleteCompetitorAfterConfirmed(dialog);
                });
            },
            onFinish() {
                dialog.reset();
            }
        }
    );
}
</script>
<template>
    <Flex :responsive="false" class="items-center">

            <DangerButton @click="confirmDeleteCompetitor" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>

    </Flex>
</template>
