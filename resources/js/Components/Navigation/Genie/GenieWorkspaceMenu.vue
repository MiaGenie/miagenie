<script setup>
import { inject } from 'vue'
import MenuItem from '@/Components/Sidebar/MenuItem.vue'
import MenuDelimiter from '@/Components/Sidebar/MenuDelimiter.vue'
import MenuGroupHeader from '@/Components/Sidebar/MenuGroupHeader.vue'
import MenuGroupBody from '@/Components/Sidebar/MenuGroupBody.vue'
import LampIcon from '@/Icons/Genie/Lamp.vue'
import StrategyIcon from '@/Icons/Genie/Strategies.vue'

const workspaceCtx = inject('workspaceCtx');
</script>
<template>
    <MenuDelimiter/>
    <MenuGroupHeader>
        {{ $t('genie.genie') }}
    </MenuGroupHeader>
    <MenuGroupBody>
        <MenuItem :url="route('genie.strategies.overview', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Strategies/Overview'">
            <template #icon>
                <StrategyIcon/>
            </template>
            {{ $t('genie.strategy') }}
        </MenuItem>
        <MenuItem :url="route('genie.ideas.index', {workspace: workspaceCtx.id})"
                  :active="$page.component === 'Genie/Workspace/Ideas/Index'">
            <template #icon>
                <LampIcon/>
            </template>
            {{ $t('genie.ideas') }}
        </MenuItem>
    </MenuGroupBody>
</template>
