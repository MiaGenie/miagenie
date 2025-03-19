<script setup>

import PageHeader from "../../Components/DataDisplay/PageHeader.vue";


</script>
<template>
    <div class="row-py w-full mx-auto">
        <PageHeader :title="$t('general.settings')"/>

        <div class="row-px">

            <slot/>
        </div>
    </div>
</template>

