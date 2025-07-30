<script setup>
import {usePage} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const workspaceName = usePage().props.workspaceName;
const versionName = usePage().props.versionName;
const ruleName = usePage().props.ruleName;
const ruleType = usePage().props.ruleType;

</script>
<template>
    <div class="row-px row-mt">

        <Flex class="justify-between items-start">
            <div>
                <Flex :responsive="false" class="items-center">

                    <h2 class="font-semibold text-lg">
                        {{ workspaceName }},
                    </h2>

                    <h2 class="font-semibold text-lg">
                        {{ versionName }},
                    </h2>

                    <h2 class="font-semibold text-lg">
                        {{ ruleName }},
                    </h2>

                    <h2 class="font-semibold text-lg">
                        {{ ruleType }}
                    </h2>

                </Flex>

            </div>

            <slot/>

        </Flex>
    </div>
</template>
