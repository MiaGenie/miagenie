<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from "vue";
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
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";

defineOptions({layout: AdminLayout});

const props = defineProps({
    configs: {
        required: true,
        type: Object,
    }
});

const {t: $t} = useI18n()

const form = useForm(cloneDeep(props.configs));
const errors = ref({});
const {notify} = useNotifications();

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
            <Panel>
                <template #title>{{ $t('genie.open_ai') }}</template>

                <HorizontalGroup class="form-field">
                    <template #title>
                        <label for="api_key">API Key <LabelSuffix danger>*</LabelSuffix></label>
                    </template>

                    <InputHidden v-model="form.api_key"
                                 :error="errors['api_key'] !== undefined"
                                 id="api_key"
                                 placeholder="sk-..."/>

                    <template #footer>
                        <Error :message="errors['api_key']"/>
                    </template>
                </HorizontalGroup>

                <HorizontalGroup class="form-field">
                    <template #title>
                        <label for="request_timeout">Request Timeout (sec.)</label>
                    </template>

                    <Input v-model="form.request_timeout"
                                 :error="errors['request_timeout'] !== undefined"
                                 id="request_timeout"
                                 placeholder="30"/>

                    <template #footer>
                        <Error :message="errors['request_timeout']"/>
                    </template>
                </HorizontalGroup>

                <PrimaryButton :disabled="form.processing" :isLoading="form.processing" type="submit" class="mt-lg">
                    {{ $t('general.save') }}
                </PrimaryButton>
            </Panel>
        </form>
    </Settings>
</template>
