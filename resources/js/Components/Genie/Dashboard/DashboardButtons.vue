<script setup>
import { inject } from "vue";
import { useI18n } from "vue-i18n";
import Panel from "@/Components/Surface/Panel.vue";
import Briefings from "@/Icons/Genie/Briefings.vue";
import Strategies from "@/Icons/Genie/Strategies.vue";
import Lamp from "@/Icons/Genie/Lamp.vue";
import Support from "@/Icons/Genie/Support.vue";
import Grid from "@/Icons/Grid.vue";
import DashboardButton from "@/Components/Button/Genie/DashboardButton.vue";
import { router } from "@inertiajs/vue3";

const { t: $t } = useI18n();
const workspaceCtx = inject("workspaceCtx");

const navigate = (page) => {
    switch (page) {
        case "briefing":
            router.get(
                route("genie.briefings.wizard", {
                    workspace: workspaceCtx.id,
                }),
            );
            break;
        case "strategy":
            router.get(
                route("genie.strategies.overview", {
                    workspace: workspaceCtx.id,
                }),
            );
            break;
        case "ideas":
            router.get(
                route("genie.ideas.index", {
                    workspace: workspaceCtx.id,
                }),
            );
            break;
        case "posts":
            router.get(
                route("mixpost.posts.index", {
                    workspace: workspaceCtx.id,
                }),
            );
            break;
    }
};
</script>
<template>
    <div class="w-full max-w-[1200px] mx-auto mb-sm">
        <Panel>
            <div
                class="flex-row items-center justify-around gap-md flex flex-wrap"
            >
<!--                <DashboardButton
                    @click="navigate('briefing')"
                    v-tooltip="$t('genie.briefing')"
                    colorStyle="briefing"
                >
                    {{ $t("genie.briefing") }}

                    <template #icon>
                        <Briefings />
                    </template>
                </DashboardButton>-->

                <DashboardButton
                    @click="navigate('strategy')"
                    v-tooltip="$t('genie.strategy')"
                    colorStyle="strategy"
                >
                    {{ $t("genie.strategy") }}

                    <template #icon>
                        <Strategies />
                    </template>
                </DashboardButton>

                <DashboardButton
                    @click="navigate('ideas')"
                    v-tooltip="$t('genie.ideas')"
                    colorStyle="ideas"
                >
                    {{ $t("genie.ideas") }}

                    <template #icon>
                        <Lamp />
                    </template>
                </DashboardButton>

                <DashboardButton
                    @click="navigate('posts')"
                    v-tooltip="$t('post.posts')"
                    colorStyle="posts"
                >
                    {{ $t("post.posts") }}

                    <template #icon>
                        <Grid />
                    </template>
                </DashboardButton>

                <DashboardButton
                    v-tooltip="$t('genie.support')"
                    colorStyle="support"
                >
                    <template #default>
                        {{ $t("genie.support") }}
                    </template>
                    <template #icon>
                        <Support />
                    </template>
                </DashboardButton>
            </div>
        </Panel>
    </div>
</template>