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

const confirmation = inject('confirmation');

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    }
})

const emit = defineEmits(['onDelete'])

const {onError} = useRouter();

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route(`genie.admin.ai_models.edit`, {
                ai_model: props.itemId,
            });
        case 'delete':
            return route(`genie.admin.ai_models.delete`, {
                ai_model: props.itemId,
            });
        default:
            return '';
    }
}

const confirmationDeletion = ref(false);

const confirmDeleteAIModel = () => {
    confirmation()
        .title($t('genie.delete_ai_model'))
        .description($t('genie.delete_ai_model_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteAIModelAfterConfirmed(dialog);
        })
        .show();
}
const deleteAIModelAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        onSuccess() {
            confirmationDeletion.value = false;
            emit('onDelete')
            emitter.emit('aIModelDeleted', props.itemId);
            dialog.reset();
        },
        onError(errors) {
            onError(errors, () => {
                deleteAIModelAfterConfirmed(dialog);
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

                    <DropdownItem @click="confirmDeleteAIModel" as="button">
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
                @click="confirmDeleteAIModel"
                v-tooltip="$t('general.delete')"
            >
                <Trash class="text-red-500"/>
            </PureButton>

        </div>
    </div>
</template>
