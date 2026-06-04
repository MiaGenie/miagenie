<script setup>
import {inject, ref} from "vue";
import {useI18n} from "vue-i18n";
import {Head, useForm} from '@inertiajs/vue3';
import useNotifications from "@meJs/Composables/useNotifications";
import WorkspaceLayout from "@/Layouts/MixpostEnterprise/Workspace.vue";
import Panel from "@meJs/Components/Surface/Panel.vue";
import ColorPicker from "@mJs/Components/Package/ColorPicker.vue";
import PrimaryButton from "@meJs/Components/Button/PrimaryButton.vue";
import HorizontalGroup from "@meJs/Components/Layout/HorizontalGroup.vue";
import Error from "@meJs/Components/Form/Error.vue";
import DialogModal from "@meJs/Components/Modal/DialogModal.vue";
import SecondaryButton from "@meJs/Components/Button/SecondaryButton.vue";
import Input from "@meJs/Components/Form/Input.vue";
import PageHeader from "@meJs/Components/DataDisplay/PageHeader.vue";
import ClipboardCard from "@meJs/Components/Util/ClipboardCard.vue";
import Select from "@meJs/Components/Form/Select.vue";

defineOptions({layout: WorkspaceLayout});

const {t: $t} = useI18n()

const {notify} = useNotifications();

const props = defineProps({
    workspace: {
        required: true,
        type: Object,
    },
    locales: {
        type: Array,
        required: true,
    }
})

const form = useForm({
    name: props.workspace.name,
    hex_color: props.workspace.hex_color,
    locale: props.workspace.locale
});

const changeColorModal = ref(false);
const changeColorHex = ref(props.workspace.hex_color);

const selectColor = () => {
    form.hex_color = changeColorHex.value
    changeColorModal.value = false;
}

const save = () => {
    form.put(route('mixpost_e.workspace.settings.update', {'workspace': props.workspace.uuid}), {
        preserveScroll: true,
        onSuccess: () => {
            notify('success', $t('enterprise-dashboard.settings_saved'));
        }
    })
}
</script>
<template>
    <Head :title="$t('general.settings')"/>

    <PageHeader :title="$t('general.settings')" :with-padding-x="false"/>

    <div class="w-ful">
        <form method="post" @submit.prevent="save">
            <Panel>
                <template #title>{{ $t('general.details') }}</template>
                <template #description>{{ $t('enterprise-general.basic_info_desc') }}</template>

                <div>
                    <HorizontalGroup>
                        <template #title>
                            <label for="name">{{ $t('general.name') }}</label>
                        </template>

                        <div class="w-full">
                            <Input v-model="form.name"
                                   type="text"
                                   id="name"
                                   :placeholder="$t('workspace.workspace_name')"
                                   class="w-full"
                                   autocomplete="off"
                                   required/>
                            <Error :message="form.errors.name" class="mt-1"/>
                        </div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            <label for="locale">{{ $t('genie.language') }}</label>
                        </template>

                        <div class="w-full">
                            <Select
                                v-model="form.locale"
                                required
                                :disabled="true"
                            >
                                <option v-for="locale in locales" :value="locale.long">
                                    {{ locale.english }} - {{ locale.native }} - ({{ locale.long }})
                                </option>
                            </Select>
                        </div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            {{ $t('enterprise-theme.color') }}
                        </template>

                        <div @click="changeColorModal = true"
                             :style="{'background': form.hex_color}"
                             role="button"
                             type="button"
                             class="w-xl h-xl rounded-md"/>
                    </HorizontalGroup>

                    <div class="flex items-center mt-lg">
                        <PrimaryButton type="submit" :disabled="form.processing">{{
                                $t('general.save')
                            }}
                        </PrimaryButton>
                    </div>
                </div>
            </Panel>
        </form>

        <template v-if="$page.props.mixpost.features.api_access_tokens && false">
            <Panel class="mt-lg">
                <template #title>
                    {{ $t('system.usage_api') }}
                </template>

                <ClipboardCard>{{ workspace.uuid }}</ClipboardCard>
            </Panel>
        </template>
    </div>

    <DialogModal :show="changeColorModal" max-width="md" @close="changeColorModal = false">
        <template #header>
            {{ $t('workspace.change_workspace_color') }}
        </template>
        <template #body>
            <template v-if="changeColorModal" class="flex flex-col">
                <ColorPicker v-model="changeColorHex"/>
            </template>
        </template>
        <template #footer>
            <SecondaryButton @click="changeColorModal = false" class="mr-xs">
                {{ $t('general.cancel') }}
            </SecondaryButton>
            <PrimaryButton @click="selectColor">{{ $t('general.done') }}</PrimaryButton>
        </template>
    </DialogModal>
</template>
