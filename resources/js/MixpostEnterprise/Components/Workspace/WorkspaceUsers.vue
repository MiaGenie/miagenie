<script setup>
import {inject, ref} from "vue";
import {useI18n} from "vue-i18n";
import {useForm, router} from "@inertiajs/vue3";
import Badge from "@/Components/DataDisplay/Badge.vue";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Trash from "@/Icons/Trash.vue";
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue";
import NoResult from "@/Components/Util/NoResult.vue";
import DropdownButton from "@/Components/Dropdown/DropdownButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Table from "@/Components/DataDisplay/Table.vue";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Avatar from "@/Components/DataDisplay/Avatar.vue";
import UserRole from "@/Components/Workspace/UserRole.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import Eye from "@/Icons/Eye.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import AttachUser from "./AttachUser.vue";
import useNotifications from "@/Composables/useNotifications";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import CanApprove from "@/MixpostEnterprise/Components/Workspace/CanApprove.vue";
import Flex from "@/Components/Layout/Flex.vue";
import IsOwner from "@/MixpostEnterprise/Components/Workspace/IsOwner.vue";

const {t: $t} = useI18n()

const routePrefix = 'mixpost_e'
const confirmation = inject('confirmation');

const props = defineProps({
    workspace: {
        type: Object
    }
})

const {notify} = useNotifications();

const modalRole = ref(null);

const formRole = useForm({
    user_id: null,
    role: 'admin',
    can_approve: false,
    is_owner: false,
});

const openModalRole = (user) => {
    formRole.user_id = user.id;
    formRole.role = user.pivot.role;
    formRole.can_approve = user.pivot.can_approve;
    formRole.is_owner = user.id === props.workspace.owner_id;
    modalRole.value = user;
}

const closeModalRole = () => {
    modalRole.value = null;
    formRole.reset();
}

const detachUser = (user) => {
    confirmation()
        .title($t('enterprise-user.detach_user'))
        .description($t('enterprise-user.detach_user_desc', {user: user.name, workspace: props.workspace.name}))
        .destructive()
        .btnConfirmName($t('enterprise-general.detach'))
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(route(`${routePrefix}.workspaces.users.delete`, {workspace: props.workspace.uuid}), {
                    data: {
                        user_id: user.id
                    },
                    preserveScroll: true,
                    onSuccess() {
                        dialog.reset();
                        notify('success', $t('enterprise-user.user_detached'))
                    },
                    onFinish() {
                        dialog.isLoading(false);
                    }
                }
            );
        }).show();
}

const updateRole = () => {
    formRole.put(route(`${routePrefix}.workspaces.users.update`, {workspace: props.workspace.uuid}), {
        preserveScroll: true,
        onSuccess() {
            formRole.reset();
            closeModalRole();

            notify('success', $t('enterprise-team.role_updated'))
        }
    });
}
</script>
<template>
    <Panel>
        <template #title>{{ $t('enterprise-user.users') }}</template>
        <template #action>
            <AttachUser :workspace="workspace" :attachedUsers="workspace.users.map((item) => item.id)"/>
        </template>

        <template v-if="workspace.users.length">
            <Table>
                <template #head>
                    <TableRow>
                        <TableCell component="th" scope="col"></TableCell>
                        <TableCell component="th" scope="col">{{ $t('enterprise-general.name') }}</TableCell>
                        <TableCell component="th" scope="col">{{ $t('enterprise-general.email') }}</TableCell>
                        <TableCell component="th" scope="col">{{ $t('enterprise-team.role') }}</TableCell>
                        <TableCell component="th" scope="col">{{ $t('enterprise-team.can_approve') }}</TableCell>
                        <TableCell component="th" scope="col">{{ $t('enterprise-general.joined_at') }}</TableCell>
                        <TableCell component="th" scope="col"/>
                    </TableRow>
                </template>
                <template #body>
                    <template v-for="user in workspace.users" :key="user.id">
                        <TableRow :hoverable="true">
                            <TableCell align="left">
                                <div class="flex justify-start">
                                    <Avatar :name="user.name"/>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Flex>
                                    <div>{{ user.name }}</div>
                                    <template v-if="user.id === workspace.owner_id">
                                        <Badge variant="warning"> {{ $t('enterprise-general.owner') }}</Badge>
                                    </template>
                                </Flex>
                            </TableCell>
                            <TableCell>
                                {{ user.email }}
                            </TableCell>
                            <TableCell>
                                <Badge>{{ $t(`enterprise-team.${user.pivot.role}`) }}</Badge>
                            </TableCell>
                            <TableCell>
                                <template v-if="!user.pivot.can_approve">
                                    <Badge>{{ $t('enterprise-general.no') }}</Badge>
                                </template>
                                <template v-else>
                                    <Badge variant="info">{{ $t('enterprise-general.yes') }}</Badge>
                                </template>
                            </TableCell>
                            <TableCell>
                                {{ user.pivot.joined_at }}
                            </TableCell>
                            <TableCell align="right">
                                <div class="flex justify-end">
                                    <PureButtonLink
                                        :href="route(`${routePrefix}.users.view`, {user: user.id})"
                                        v-tooltip="$t('enterprise-general.view')" class="mr-xs">
                                        <Eye/>
                                    </PureButtonLink>

                                    <Dropdown placement="bottom-end">
                                        <template #trigger>
                                            <DropdownButton/>
                                        </template>

                                        <template #content>
                                            <DropdownItem @click="openModalRole(user)" as="button">
                                                <PencilSquare class="mr-xs"/>
                                                {{ $t('enterprise-user.edit_role') }}
                                            </DropdownItem>

                                            <DropdownItem @click="detachUser(user)" as="button">
                                                <Trash class="text-red-500 mr-xs"/>
                                                {{ $t('enterprise-general.detach') }}
                                            </DropdownItem>
                                        </template>
                                    </Dropdown>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </template>
            </Table>
        </template>

        <template v-else>
            <NoResult/>
        </template>
    </Panel>

    <DialogModal :show="modalRole !== null"
                 max-width="md"
                 :scrollable-body="true"
                 :closeable="true"
                 @close="closeModalRole">
        <template #header>
            <i18n-t keypath="team.edit_role_for" tag="span" scope="global">
                <template #user>
                    <span class="font-medium">{{ modalRole.name }}</span>
                </template>
            </i18n-t>
        </template>

        <template #body>
            <UserRole v-model="formRole.role" class="mt-xs"/>

            <VerticalGroup class="mt-lg">
                <CanApprove v-model="formRole.can_approve"/>
            </VerticalGroup>

            <VerticalGroup class="mt-lg">
                <IsOwner v-model="formRole.is_owner"/>
            </VerticalGroup>
        </template>

        <template #footer>
            <SecondaryButton @click="closeModalRole" class="mr-xs">{{ $t('general.cancel') }}</SecondaryButton>
            <PrimaryButton @click="updateRole" :disabled="formRole.processing" :isLoading="formRole.processing">
                {{ $t('workspace.update') }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
