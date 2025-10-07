<script setup>
import {inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
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

const confirmation = inject('confirmation');

const props = defineProps({
    item: {
        type: Object,
        required: true,
    }
})

const version = usePage().props.version;
const rule = usePage().props.rule;

const emit = defineEmits(['onDelete'])

const {onError} = useRouter();

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.admin.versions.rules.steps.edit', {
                version: version.id,
                rule: rule.id,
                step: props.item.id
            });
        case 'delete':
            return route('genie.admin.versions.rules.steps.delete', {
                version: version.id,
                rule: rule.id,
                step: props.item.id
            });
        default:
            return '';
    }
}

const confirmationDeletion = ref(false);

const confirmDeleteStep = () => {
    confirmation()
        .title($t('genie.delete_step'))
        .description($t('genie.delete_step_confirm'))
        .destructive()
        .onConfirm((dialog) => {
            deleteStepAfterConfirmed(dialog);
        })
        .show();
}
const deleteStepAfterConfirmed = (dialog) => {
    dialog.isLoading(true);

    router.delete(getRoute('delete'), {
        onSuccess() {
            confirmationDeletion.value = false;
            emit('onDelete')
            emitter.emit('stepDeleted', props.item.id);
            dialog.reset();
        },
        onError(errors) {
            onError(errors, () => {
                deleteStepAfterConfirmed(dialog);
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
                        @click="confirmDeleteStep"
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
                @click="confirmDeleteStep"
                v-tooltip="$t('general.delete')"
            >
                <Trash class="text-red-500"/>
            </PureButton>

        </div>
    </div>
</template>
