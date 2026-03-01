<script setup>
import {inject} from "vue";
import DefaultLayout from "@/Layouts/MixpostEnterprise/Default.vue";
import Sidebar from "@/MixpostEnterprise/Components/Sidebar/SidebarWorkspace.vue";
import Navigation from "@meJs/Components/Navigation/NavBar.vue";

const routePrefix = 'mixpost_e';
</script>
<template>
    <DefaultLayout>
        <template #sidebar>
            <Sidebar/>
        </template>

        <template #navigation>
            <Navigation/>
        </template>

        <div class="row-py row-px w-full mx-auto">
            <slot/>
        </div>
    </DefaultLayout>
</template>
