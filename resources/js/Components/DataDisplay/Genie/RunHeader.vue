<script setup>
import {usePage} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const workspaceName = usePage().props.workspaceName;
const versionName = usePage().props.versionName;
const ruleName = usePage().props.ruleName;
const ruleType = usePage().props.ruleType;

/**
 * A run outlives its rule: `rule_id` is nulled when one is deleted, so every part of the heading
 * can be missing.
 */
const parts = [workspaceName, versionName, ruleName, ruleType].filter((part) => !!part);

</script>
<template>
    <div class="row-px row-mt">

        <Flex class="justify-between items-start">
            <div>
                <Flex :responsive="false" class="items-center">

                    <h2 class="font-semibold text-lg">
                        {{ parts.join(', ') }}
                    </h2>

                </Flex>

            </div>

            <slot/>

        </Flex>
    </div>
</template>
