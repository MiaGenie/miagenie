<script setup>
import {useI18n} from "vue-i18n";
import {Head} from '@inertiajs/vue3';
import {router} from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/Admin.vue";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import Panel from "@/Components/Surface/Panel.vue";
import X from "@/Icons/X.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    log: {
        type: Object,
        require: true
    },
    logType: {
        type: String,
        require: true
    },
    logAction: {
        type: String,
        require: true
    },
});

const backToList = () => {
    router.get(route(
        'genie.admin.logs.index', {

        }));
}

</script>
<template>
    <Head :title="$t('genie.log_data')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.log_data')"/>

        <div class="row-px">

            <Panel :with-padding="true" class="mt-lg">

                <div :class="'pb-2 pl-2'">
                    {{ $t('genie.log_id') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words '">
                    {{ log.id }}
                </div>

                <div :class="'pb-2 pl-2 pt-5'">
                    {{ $t('genie.log_type') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words'">
                    {{ logType }}
                </div>

                <div :class="'pb-2 pl-2 pt-5'">
                    {{ $t('genie.log_action') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words'">
                    {{ logAction }}
                </div>

                <div :class="'pb-2 pl-2 pt-5'">
                    {{ $t('genie.log_request') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words'">
                    {{ log.request ? log.request : '----------' }}
                </div>

                <div :class="'pb-2 pl-2 pt-5'">
                    {{ $t('genie.log_duration') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words'">
                    {{ log.duration }}
                </div>

                <div :class="'pb-2 pl-2 pt-5'">
                    {{ $t('genie.created_at') }}
                </div>
                <div :class="'bg-primary-50 rounded-lg pb-1 pl-2 pt-1 pr-2 w-full break-words'">
                    {{ log.created_at }}
                </div>

            </Panel>

            <div class="flex flex-row items-center justify-between mt-lg">
                <div class="flex gap-6">

                    <PrimaryButton
                        @click="backToList"
                        type="button"
                    >
                        {{ $t("general.close") }}
                        <template #icon>
                            <X/>
                        </template>
                    </PrimaryButton>

                </div>
            </div>
        </div>
    </div>
</template>
