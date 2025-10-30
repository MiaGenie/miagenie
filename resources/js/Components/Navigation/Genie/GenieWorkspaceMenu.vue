<script setup>
import {inject} from "vue";
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import CompetitorIcon from "@/Icons/Genie/Competitor.vue";
import BriefingIcon from "@/Icons/Genie/Briefings.vue";
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
        <MenuItem :url="route('genie.competitors.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Competitors/Index'">
            <template #icon>
                <CompetitorIcon/>
            </template>
            {{ $t('genie.competitors') }}
        </MenuItem>
        <MenuItem :url="route('genie.briefings.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Briefings/Index'">
            <template #icon>
                <BriefingIcon/>
            </template>
            {{ $t('genie.briefings') }}
        </MenuItem>
        <MenuItem :url="route('genie.strategies.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Strategies/Index'">
            <template #icon>
                <StrategyIcon/>
            </template>
            {{ $t('genie.strategies') }}
        </MenuItem>
        <MenuItem :url="route('genie.ideas.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Ideas/Index'">
            <template #icon>
                <LampIcon/>
            </template>
            {{ $t('genie.ideas') }}
        </MenuItem>
        <MenuItem :url="route('genie.drafts.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Drafts/Index'">
            <template #icon>
                <DraftIcon/>
            </template>
            {{ $t('genie.drafts') }}
        </MenuItem>
    </MenuGroupBody>
</template>
