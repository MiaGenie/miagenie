<script setup>
import {provide} from "vue";
import DefaultLayout from "@/Layouts/MixpostEnterprise/Default.vue";
import Sidebar from "@meJs/Components/Sidebar/SidebarPanel.vue";
import Navigation from "@meJs/Components/Navigation/NavBar.vue";

provide('routePrefix', 'mixpost_e')

</script>
<template>
    <DefaultLayout>
        <template #sidebar>
            <Sidebar/>
        </template>

        <template #navigation>
            <Navigation/>
        </template>

        <slot/>
    </DefaultLayout>
</template>

