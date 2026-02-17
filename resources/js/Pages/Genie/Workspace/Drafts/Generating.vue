<script setup>
import {Head, Link, router} from '@inertiajs/vue3';
import {inject, onBeforeUnmount, onMounted, onUpdated, ref} from "vue";
import {useI18n} from "vue-i18n";
import PageHeader from '@/Components/DataDisplay/PageHeader.vue';
import {find, result} from "lodash";
import Panel from "@/Components/Surface/Panel.vue";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n()
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    statusTypes: {
        type: Object,
    },
    record: {
        type: Object
    }
});

const draftStatus = ref('');

const updateStatus = () => {
    return props.record?.status ? find(props.statusTypes, ['value', Number(props.record.status)]).name : '';
}

let refreshStatus;

onMounted(() => {
    draftStatus.value = updateStatus();

    if (draftStatus.value === 'APPROVED') {
        refreshStatus = setInterval(() => {
            router.get(
                route('genie.drafts.generating',
                    {
                        workspace: workspaceCtx.id,
                        draft: props.record.id
                    }
                ), result(), {
                    preserveState: true,
                    only: ['record']
                });
        }, 1000)
    }
})

onUpdated(() => {
    draftStatus.value = updateStatus();

    if (draftStatus.value !== 'APPROVED') {
        clearInterval(refreshStatus);
    }
})

onBeforeUnmount(() => {
    clearInterval(refreshStatus);
})

</script>
<template>

    <div class="w-full max-w-[1200px] mx-auto row-py">

        <Head :title="$t('genie.post')"/>

        <div class="w-full row-px mt-lg whitespace-pre-line text-center">


            <Panel
                class="w-full row-px mt-lg"
            >
                <Flex
                    :col="true"
                    class="items-center"
                >
                    <div class="text-lg">
                        {{ $t('genie.generating_post') }}
                    </div>

                    <div class="fulfilling-bouncing-circle-spinner">
                        <div class="circle"></div>
                        <div class="orbit"></div>
                    </div>

                </Flex>

            </Panel>

        </div>

    </div>
</template>
