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

const confirmDeleteBriefing = () => {
    confirmation()
        .title($t('genie.delete_briefing'))
        .description($t('genie.delete_briefing_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteBriefingAfterConfirmed(dialog);
        })
        .show();
}

const deleteBriefingAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(
        route(`${routePrefix}.briefings.delete`, {
            workspace: workspaceCtx.id,
            competitor: props.record.id,
        }),
        {
            preserveScroll: true,
            onError(errors) {
                onError(errors, () => {
                    deleteBriefingAfterConfirmed(dialog);
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
