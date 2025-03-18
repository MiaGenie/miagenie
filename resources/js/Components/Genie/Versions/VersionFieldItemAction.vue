<script setup>
import {inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import useAuth from "@/Composables/useAuth";
import useNotifications from "@/Composables/useNotifications";
import emitter from "@/Services/emitter";
import {router, usePage} from "@inertiajs/vue3";
import PureButton from "@/Components/Button/PureButton.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Dropdown from "@/Components/Dropdown/Dropdown.vue";
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue";
import DropdownButton from "@/Components/Dropdown/DropdownButton.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Trash from "@/Icons/Trash.vue";

const {t: $t} = useI18n()

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    version: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['onDelete'])

const {notify} = useNotifications();
const {user} = useAuth();
const {onError} = useRouter();

const filter = ref(usePage().props.filter);
watch( () => usePage().props.filter, () => {
    filter.value = usePage().props.filter;
});

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.admin.versions.fields.edit', {
                version: props.version.id,
                field: props.item.id
            });
        case 'delete':
            return route('genie.admin.versions.fields.delete', {
                version: props.version.id,
                field: props.item.id,
                group_type: filter.value.group_type
            });
        default:
            return '';
    }
}

const confirmationDeletion = ref(false);

const confirmDeleteField = () => {
    confirmation()
        .title($t('genie.delete_field'))
        .description($t('genie.delete_field_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteFieldAfterConfirmed(dialog);
        })
        .show();
}
const deleteFieldAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        onSuccess() {
            confirmationDeletion.value = false;
            emit('onDelete')
            emitter.emit('fieldDeleted', props.item.id);
            dialog.reset();
        },
        onError(errors) {
            onError(errors, () => {
                deleteFieldAfterConfirmed(dialog);
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
                        :href="getRoute('edit')"
                    >
                        <template #icon>
                            <PencilSquare/>
                        </template>
                        {{ $t('general.edit') }}
                    </DropdownItem>

                    <DropdownItem
                        @click="confirmDeleteField"
                        as="button"
                    >
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
                @click="confirmDeleteField"
                v-tooltip="$t('general.delete')"
            >
                <Trash class="text-red-500"/>
            </PureButton>

        </div>
    </div>
</template>
