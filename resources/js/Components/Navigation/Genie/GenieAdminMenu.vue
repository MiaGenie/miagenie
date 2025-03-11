<script setup>
import MenuItem from "@/Components/Sidebar/MenuItem.vue"
import MenuDelimiter from "@/Components/Sidebar/MenuDelimiter.vue"
import MenuGroupHeader from "@/Components/Sidebar/MenuGroupHeader.vue"
import MenuGroupBody from "@/Components/Sidebar/MenuGroupBody.vue"
import VersionIcon from "@/Icons/Genie/Version.vue";
</script>
<template>
    <MenuGroupHeader>
    {{ $t('genie.genie') }}
    </MenuGroupHeader>
    <MenuGroupBody>

    <MenuItem :url="route('genie.admin.versions.index')"
              :active="$page.component === 'Genie/Admin/Versions/Index'">
        <template #icon>
            <VersionIcon/>
        </template>
        {{ $t('genie.versions') }}
    </MenuItem>
    </MenuGroupBody>
    <MenuDelimiter/>
</template>