<script setup>
import emitter from "@/Services/emitter";
import {router} from "@inertiajs/vue3";
import {inject, ref} from "vue";
import {useI18n} from "vue-i18n";
import useAuth from "@/Composables/useAuth";
import useNotifications from "@/Composables/useNotifications";
import useRouter from "@/Composables/useRouter";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import DropdownButton from "@/Components/Dropdown/DropdownButton.vue";
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Trash from "@/Icons/Trash.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";

const {t: $t} = useI18n()

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const emit = defineEmits(['onDelete'])

const {notify} = useNotifications();
const {user} = useAuth();
const {onError} = useRouter();

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.strategies.edit', {
                workspace: workspaceCtx.id,
                strategy: props.itemId
            });
        case 'delete':
            return route('genie.strategies.delete', {
                workspace: workspaceCtx.id,
                strategy: props.itemId
            });
        default:
            return '';
    }
}

const confirmationDeletion = ref(false);

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

    router.delete(getRoute('delete'), {
        onSuccess() {
            confirmationDeletion.value = false;
            emit('onDelete')
            emitter.emit('strategyDeleted', props.itemId);
            dialog.reset();
        },
        onError(errors) {
            onError(errors, () => {
                deleteStrategyAfterConfirmed(dialog);
            });
        },
        onFinish() {
            dialog.isLoading(false)
        }
    })
}
</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-xs sm:hidden">

            <Dropdown placement="bottom-end">
                <template #trigger>
                    <DropdownButton/>
                </template>

                <template #content>
                    <DropdownItem
                        :href="getRoute('edit')">
                        <template #icon>
                            <PencilSquare/>
                        </template>
                        {{ $t('general.edit') }}
                    </DropdownItem>

                    <DropdownItem @click="confirmDeleteStrategy" as="button">
                        <template #icon>
                            <Trash class="text-red-500"/>
                        </template>
                        {{ $t('general.delete') }}
                    </DropdownItem>
                </template>
            </Dropdown>
        </div>
        <div class="flex-row items-center justify-end gap-lg hidden sm:flex">

            <PureButtonLink
                :href="getRoute('edit')"
                v-tooltip="$t('general.edit')"
            >
                <PencilSquare/>
            </PureButtonLink>

            <PureButton
                @click="confirmDeleteStrategy"
                v-tooltip="$t('general.delete')"
            >
                <Trash class="text-red-500"/>
            </PureButton>

        </div>
    </div>
</template>
