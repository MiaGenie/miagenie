<script setup>
import {inject} from "vue";
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import CompetitorIcon from "@/Icons/Genie/Competitor.vue";

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
    </MenuGroupBody>
</template>