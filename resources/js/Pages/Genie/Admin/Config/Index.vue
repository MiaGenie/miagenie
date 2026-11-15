<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import {cloneDeep} from "lodash";
import useNotifications from "@/Composables/useNotifications";
import AdminLayout from "@/Layouts/Admin.vue";
import Settings from "@/Layouts/Genie/Settings.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import HorizontalGroup from "@/Components/Layout/HorizontalGroup.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import InputHidden from "@/Components/Form/InputHidden.vue";

defineOptions({layout: AdminLayout});

const props = defineProps({
    configs: {
        required: true,
        type: Object,
    }
});

const {t: $t} = useI18n()

const form = useForm(cloneDeep(props.configs));
const {notify} = useNotifications();

const providers = [
    {group: 'openai', title: 'genie.open_ai', placeholder: 'sk-...'},
    {group: 'anthropic', title: 'genie.anthropic', placeholder: 'sk-ant-...'},
    {group: 'gemini', title: 'genie.gemini', placeholder: 'AIza...'},
    {group: 'deepseek', title: 'genie.deepseek', placeholder: 'sk-...'},
];

const save = () => {
    form.put(route('genie.admin.configs.update'), {
        preserveScroll: true,
        onSuccess: () => {
            notify('success', $t('general.saved'));
        }
    });
}
</script>
<template>
    <Head title="AI"/>

    <Settings>
        <form @submit.prevent="save">
            <div>
                <Panel v-for="provider in providers" :key="provider.group" class="mb-lg">
                    <template #title>{{ $t(provider.title) }}</template>

                    <HorizontalGroup class="form-field">
                        <template #title>
                            <label :for="`${provider.group}_api_key`">API Key</label>
                        </template>

                        <InputHidden v-model="form[provider.group].api_key"
                                     :error="form.errors[`${provider.group}.api_key`] !== undefined"
                                     :id="`${provider.group}_api_key`"
                                     :placeholder="provider.placeholder"/>

                        <template #footer>
                            <Error :message="form.errors[`${provider.group}.api_key`]"/>
                        </template>
                    </HorizontalGroup>
                </Panel>

                <Panel>
                    <template #title>{{ $t('general.general') }}</template>

                    <HorizontalGroup class="form-field">
                        <template #title>
                            <label for="request_timeout">Request Timeout (sec.)</label>
                        </template>

                        <Input v-model="form.openai.request_timeout"
                               :error="form.errors['openai.request_timeout'] !== undefined"
                               id="request_timeout"
                               placeholder="30"/>

                        <template #footer>
                            <Error :message="form.errors['openai.request_timeout']"/>
                        </template>
                    </HorizontalGroup>

                    <PrimaryButton :disabled="form.processing" :isLoading="form.processing" type="submit" class="mt-lg">
                        {{ $t('general.save') }}
                    </PrimaryButton>
                </Panel>
            </div>
        </form>
    </Settings>
</template>
