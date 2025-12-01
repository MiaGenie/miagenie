<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import Panel from "@/Components/Surface/Panel.vue";
import Strategies from "@/Icons/Genie/Strategies.vue";
import Lamp from "@/Icons/Genie/Lamp.vue";
import Support from "@/Icons/Genie/Support.vue";
import Grid from "@/Icons/Grid.vue";
import DashboardButton from "@/Components/Button/Genie/DashboardButton.vue";
import {router} from "@inertiajs/vue3";

const {t: $t} = useI18n()
const workspaceCtx = inject('workspaceCtx');

const navigate = (page) => {
    switch(page) {
        case 'strategy':
            router.get(route('genie.strategies.index', {
                workspace: workspaceCtx.id
            }));
            break;
        case 'ideas':
            router.get(route('genie.ideas.index', {
                workspace: workspaceCtx.id
            }));
            break;
        case 'posts':
            router.get(route('mixpost.posts.index', {
                workspace: workspaceCtx.id
            }));
            break;

    }
}

</script>
<template>
    <div class="row-px mb-2xl">
        <Panel>
            <div class="flex-row items-center justify-between gap-lg hidden sm:flex">

                <DashboardButton
                    @click="navigate('strategy')"
                    v-tooltip="$t('genie.strategy')"
                >
                    {{ $t('genie.strategy') }}

                    <template #icon>
                        <Strategies/>
                    </template>
                </DashboardButton>

                <DashboardButton
                    @click="navigate('ideas')"
                    v-tooltip="$t('genie.ideas')"
                >
                    {{ $t('genie.ideas') }}

                    <template #icon>
                        <Lamp/>
                    </template>
                </DashboardButton>

                <DashboardButton
                    @click="navigate('posts')"
                    v-tooltip="$t('post.posts')"
                >
                    {{ $t('post.posts') }}

                    <template #icon>
                        <Grid/>
                    </template>
                </DashboardButton>

                <DashboardButton
                    v-tooltip="$t('genie.support')"
                >
                    <template #default>
                        {{ $t('genie.support') }}
                    </template>
                    <template #icon>
                        <Support/>
                    </template>
                </DashboardButton>

            </div>
        </Panel>
    </div>
</template>