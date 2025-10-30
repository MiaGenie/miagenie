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
import TrashIcon from "@/Icons/Trash.vue";
import LampIcon from "@/Icons/Genie/Lamp.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import Eye from "@/Icons/Eye.vue";

const {t: $t} = useI18n()

const routePrefix = inject('routePrefix');
const confirmation = inject('confirmation');
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    itemId: {
        type: String,
        required: true,
    },
    review: {
        type: Boolean,
        default: false,
    },
    complete: {
        type: Boolean,
        default: false,
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
        case 'review':
            return route('genie.strategies.review', {
                workspace: workspaceCtx.id,
                strategy: props.itemId
            });
        case 'delete':
            return route('genie.strategies.delete', {
                workspace: workspaceCtx.id,
                strategy: props.itemId
            });
        case 'generate':
            return route('genie.ideas.generate', {
                workspace: workspaceCtx.id,
                strategy: props.itemId
            });
        default:
            return '';
    }
}

const confirmationDeletion = ref(false);
const confirmationGeneration = ref(false);

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

const confirmGenerate = () => {
    confirmation()
        .title($t('genie.generate_ideas'))
        .description($t('genie.generate_ideas_confirm'))
        .warning()
        .onConfirm((dialog) => {
            router.put(getRoute('generate'));
            dialog.reset();
        })
        .show();
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
                        v-if="review"
                        :href="getRoute('review')">
                        <template #icon>
                            <Eye/>
                        </template>
                        {{ $t('genie.review_strategy') }}
                    </DropdownItem>

                    <DropdownItem
                        :href="getRoute('edit')">
                        <template #icon>
                            <PencilSquare/>
                        </template>
                        {{ $t('genie.view_strategy') }}
                    </DropdownItem>

                    <DropdownItem @click="confirmDeleteStrategy" as="button">
                        <template #icon>
                            <TrashIcon class="text-red-500"/>
                        </template>
                        {{ $t('general.delete') }}
                    </DropdownItem>
                </template>
            </Dropdown>
        </div>
        <div class="flex-row items-center justify-end gap-lg hidden sm:flex">

            <PureButtonLink
                v-if="review"
                :href="getRoute('review')"
                v-tooltip="$t('genie.review_strategy')"
            >
                <PencilSquare/>
            </PureButtonLink>

            <PureButtonLink
                :href="getRoute('edit')"
                v-tooltip="$t('genie.view_strategy')"
            >
                <Eye/>
            </PureButtonLink>

            <PureButton
                @click="confirmDeleteStrategy"
                v-tooltip="$t('general.delete')"
            >
                <TrashIcon class="text-red-500"/>
            </PureButton>

            <PureButton
                v-if="complete"
                @click="confirmGenerate"
                v-tooltip="$t('genie.generate_ideas')"
            >
                <LampIcon class="text-yellow-500"/>
            </PureButton>

        </div>
    </div>
</template>
