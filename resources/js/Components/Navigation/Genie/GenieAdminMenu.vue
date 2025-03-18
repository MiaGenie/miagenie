<script setup>
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import VersionIcon from "@/Icons/Genie/Version.vue";
import CogIcon from "@/Icons/Cog.vue";
import DocumentIcon from "@/Icons/Document.vue";
import VectorIcon from "@/Icons/Genie/Vector.vue";
</script>
<template>
    <MenuGroupHeader>
    {{ $t('genie.genie') }}
    </MenuGroupHeader>

    <MenuGroupBody>

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