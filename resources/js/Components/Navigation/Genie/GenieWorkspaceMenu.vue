<script setup>
import {inject} from "vue";
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import CogIcon from "@/Icons/Cog.vue";
import LampIcon from "@/Icons/Genie/Lamp.vue";
import DraftIcon from "@/Icons/Genie/Draft.vue";
import StrategyIcon from "@/Icons/Genie/Strategies.vue";

const workspaceCtx = inject('workspaceCtx');
</script>
<template>
    <MenuDelimiter/>
    <MenuGroupHeader>
        {{ $t('genie.genie') }}
    </MenuGroupHeader>
    <MenuGroupBody>
        <MenuItem :url="route('genie.strategies.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Strategies/Index'">
            <template #icon>
                <StrategyIcon/>
            </template>
            {{ $t('genie.strategy') }}
        </MenuItem>
        <MenuItem :url="route('genie.strategies.list', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Strategies/List'">
            <template #icon>
                <StrategyIcon/>
            </template>
            {{ $t('genie.strategies') }}
        </MenuItem>
        <MenuItem :url="route('genie.ideas.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Ideas/Index'">
            <template #icon>
                <LampIcon/>
            </template>
            {{ $t('genie.ideas') }}
        </MenuItem>
        <MenuItem :url="route('genie.drafts.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Drafts/Index'">
            <template #icon>
                <DraftIcon/>
            </template>
            {{ $t('genie.drafts') }}
        </MenuItem>
        <MenuItem :url="route('genie.config.config', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Config'">
            <template #icon>
                <CogIcon/>
            </template>
            {{ $t('genie.setup') }}
        </MenuItem>
    </MenuGroupBody>
</template>
