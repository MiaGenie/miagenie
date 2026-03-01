<script setup>
import {inject, ref} from "vue";
import { useI18n } from "vue-i18n";
import {useForm} from "@inertiajs/vue3";
import useNotifications from "@/Composables/useNotifications";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import UserRole from "./UserRole.vue";
import Plus from "@/Icons/Plus.vue";
import SelectUser from "../User/SelectUser/SelectUser.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import CanApprove from "./CanApprove.vue";
import IsOwner from "./IsOwner.vue";

const { t: $t } = useI18n()

const routePrefix = 'mixpost_e';
const {notify} = useNotifications();

const props = defineProps({
    workspace: {
        type: Object
    },
    attachedUsers: {
        type: Array,
        default: []
    }
})

const selectedUser = ref(null);

const formAttach = useForm({
    user_id: null,
    role: 'admin',
    can_approve: false,
    is_owner: false,
});

const attach = () => {
    formAttach.transform((data) => {
        return {
            ...data,
            user_id: selectedUser.value.key,
        }
    }).post(route(`${routePrefix}.workspaces.users.store`, {workspace: props.workspace.uuid}), {
            preserveScroll: true,
            onSuccess() {
                close();
                formAttach.reset();

                notify('success', $t('enterprise-user.user_attached'))
            }
        }
    );
}

const modal = ref(false);

const open = () => {
    modal.value = true;
}

const close = () => {
    modal.value = false;
    selectedUser.value = null;
}
</script>
<template>
    <SecondaryButton @click="open" size="sm">
        <template #icon>
            <Plus/>
        </template>

        {{ $t('enterprise-general.attach') }}
    </SecondaryButton>

    <DialogModal :show="modal"
                 max-width="md"
                 :scrollable-body="true"
                 :closeable="true"
                 @close="close">
        <template #header>
            {{ $t('enterprise-user.attach_user') }}
        </template>

        <template #body>
            <SelectUser v-model="selectedUser" :exclude="attachedUsers"/>

            <UserRole v-model="formAttach.role" class="mt-lg"/>

            <VerticalGroup class="mt-lg">
                <CanApprove v-model="formAttach.can_approve"/>
            </VerticalGroup>

            <VerticalGroup class="mt-lg">
                <IsOwner v-model="formAttach.is_owner"/>
            </VerticalGroup>
        </template>

        <template #footer>
            <SecondaryButton @click="close" class="mr-xs rtl:mr-0 rtl:ml-xs">{{ $t('enterprise-general.cancel') }}</SecondaryButton>
            <PrimaryButton @click="attach" :disabled="!selectedUser || formAttach.processing"
                           :isLoading="formAttach.processing">{{ $t('enterprise-general.attach') }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
