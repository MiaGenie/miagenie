<script setup>
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import VersionIcon from "@/Icons/Genie/Version.vue";
import CogIcon from "@/Icons/Cog.vue";
import DocumentIcon from "@/Icons/Document.vue";
import VectorIcon from "@/Icons/Genie/Vector.vue";
import AIModelIcon from "@/Icons/Genie/AIModel.vue";
import RunIcon from "@/Icons/Genie/Run.vue";
import LogsIcon from "@/Icons/Genie/Logs.vue";
</script>
<template>
    <MenuGroupHeader>
    {{ $t('genie.genie') }}
    </MenuGroupHeader>

    <MenuGroupBody>

        <MenuItem :url="route('genie.admin.ai_models.index')"
                  :active="$page.component === 'Genie/Admin/AIModels/Index'">
            <template #icon>
                <AIModelIcon/>
            </template>
            {{ $t('genie.ai_models') }}
        </MenuItem>

        <MenuItem :url="route('genie.admin.vectors.index')"
                  :active="$page.component === 'Genie/Admin/Vectors/Index'">
            <template #icon>
                <VectorIcon/>
            </template>
            {{ $t('genie.vectors') }}
        </MenuItem>

        <MenuItem :url="route('genie.admin.files.index')"
                  :active="$page.component === 'Genie/Admin/Files'">
            <template #icon>
                <DocumentIcon/>
            </template>
            {{ $t('genie.files') }}
        </MenuItem>

        <MenuItem :url="route('genie.admin.versions.index')"
                  :active="$page.component === 'Genie/Admin/Versions/Index'">
            <template #icon>
                <VersionIcon/>
            </template>
            {{ $t('genie.versions') }}
        </MenuItem>

        <MenuItem :url="route('genie.admin.runs.index')"
                  :active="$page.component === 'Genie/Admin/Runs/Index'">
            <template #icon>
                <RunIcon/>
            </template>
            {{ $t('genie.runs') }}
        </MenuItem>

        <MenuItem :url="route('genie.admin.logs.index')"
                  :active="$page.component === 'Genie/Admin/Logs/Index'">
            <template #icon>
                <LogsIcon/>
            </template>
            {{ $t('genie.logs') }}
        </MenuItem>

        <MenuDelimiter/>

        <MenuItem :url="route('genie.admin.configs.form')"
                  :active="$page.component === 'Genie/Admin/GenieAIConfig'">
            <template #icon>
                <CogIcon/>
            </template>
            {{ $t('genie.config') }}
        </MenuItem>

    </MenuGroupBody>
    <MenuDelimiter/>
</template>
