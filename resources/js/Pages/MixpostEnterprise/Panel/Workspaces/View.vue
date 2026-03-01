<script setup>
import {inject} from "vue";
import {Head, router} from '@inertiajs/vue3';
import {ACCESS_STATUS_SUBSCRIPTION, ACCESS_STATUS_UNLIMITED, ACCESS_STATUS_LOCKED} from "@meJs/Constants/Workspace";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Panel from "@/Components/Surface/Panel.vue";
import HorizontalGroup from "@/Components/Layout/HorizontalGroup.vue";
import WorkspaceUsers from "@/MixpostEnterprise/Components/Workspace/WorkspaceUsers.vue";
import UserLink from "@meJs/Components/User/UserLink.vue";
import WorkspaceSubscription from "@/MixpostEnterprise/Components/Workspace/WorkspaceSubscription.vue";
import Indicators from "@meJs/Components/Workspace/Indicators.vue";
import Actions from "@meJs/Components/Workspace/Actions.vue";
import PrimaryButton from "@meJs/Components/Button/PrimaryButton.vue";
import PaymentDetails from "@/MixpostEnterprise/Components/Subscription/PaymentDetails.vue";
import ClipboardCard from "@meJs/Components/Util/ClipboardCard.vue";
import EnterpriseLayout from "@/Layouts/MixpostEnterprise/Panel.vue";

defineOptions({layout: EnterpriseLayout});

const routePrefix = 'mixpost_e';
const confirmation = inject('confirmation');

const props = defineProps({
    workspace: {
        type: Object
    },
    subscription: {
        type: [Object, null]
    },
    currency: {
        type: String,
        default: 'USD'
    },
    billing_configs: {
        type: Object,
        required: true
    },
    plans: {
        type: Array,
        required: true
    }
})

const redirectToPortal = () => {
    router.put(route(`${routePrefix}.workspaces.portalPaymentPlatform`, {workspace: props.workspace.uuid}));
}
</script>
<template>
    <Head :title="$t('workspace.view_workspace')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('workspace.view_workspace')">
            <template #description>
                {{ workspace.name }}
            </template>

            <Actions :workspace="workspace" :view="false"/>
        </PageHeader>

        <div class="row-px">
            <Panel>
                <template #title>{{ $t('general.details') }}</template>

                <div class="md:max-w-2xl">
                    <Indicators :workspace="workspace" conditionalClass="mb-lg"/>

                    <HorizontalGroup>
                        <template #title>
                            {{ $t('general.name') }}
                        </template>

                        <div>{{ workspace.name }}</div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            {{ $t('enterprise-general.owner') }}
                        </template>

                        <template v-if="workspace.owner">
                            <UserLink :id="workspace.owner.id" :name="workspace.owner.name"/>
                        </template>
                        <template v-else>
                            -
                        </template>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            {{ $t('enterprise-general.access_status') }}
                        </template>

                        <div v-if="workspace.access_status === ACCESS_STATUS_SUBSCRIPTION">
                            {{ $t('enterprise-subscription.requires_subscription') }}
                        </div>
                        <div v-if="workspace.access_status === ACCESS_STATUS_UNLIMITED">{{
                                $t('enterprise-workspace.unlimited')
                            }}
                        </div>
                        <div v-if="workspace.access_status === ACCESS_STATUS_LOCKED">{{ $t('enterprise-workspace.locked') }}</div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            {{ $t('enterprise-theme.color') }}
                        </template>

                        <div :style="{'background': workspace.hex_color}"
                             class="w-xl h-xl rounded-md"/>
                    </HorizontalGroup>
                </div>
            </Panel>

            <div class="mt-lg">
                <WorkspaceSubscription :workspace="workspace"
                                       :subscription="subscription"
                                       :currency="currency"
                                       :billingConfigs="billing_configs"
                                       :plans="plans"
                />
            </div>

            <template v-if="subscription">
                <Panel class="mt-lg">
                    <template #title>
                        {{ $t('enterprise-dashboard.payment_method') }}
                    </template>

                    <PaymentDetails :payment_method="workspace.payment_method"/>

                    <PrimaryButton @click="redirectToPortal" :class="workspace.payment_method.type ? 'mt-lg' : ''">
                        {{ $t('enterprise-dashboard.update_payment_method') }}
                    </PrimaryButton>
                </Panel>
            </template>

            <div class="mt-lg">
                <WorkspaceUsers :workspace="workspace"/>
            </div>

            <template v-if="$page.props.mixpost.features.api_access_tokens">
                <Panel class="mt-lg">
                    <template #title>
                        {{ $t('system.usage_api') }}
                    </template>

                    <ClipboardCard>{{ workspace.uuid }}</ClipboardCard>
                </Panel>
            </template>
        </div>
    </div>
</template>
