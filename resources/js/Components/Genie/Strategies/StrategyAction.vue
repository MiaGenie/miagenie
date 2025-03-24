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

const confirmDeleteStrategy = () => {
    confirmation()
        .title($t('genie.delete_strategy'))
        .description($t('genie.delete_strategy_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteStrategyAfterConfirmed(dialog);
        })
        .show();
}

const deleteStrategyAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(
        route(`${routePrefix}.strategies.delete`, {
            workspace: workspaceCtx.id,
            strategy: props.record.id,
        }),
        {
            preserveScroll: true,
            onError(errors) {
                onError(errors, () => {
                    deleteStrategyAfterConfirmed(dialog);
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

            <DangerButton @click="confirmDeleteBriefing" size="sm">
                <template #icon>
                    <Trash/>
                </template>
            </DangerButton>

    </Flex>
</template>
