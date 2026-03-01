<script setup>
import {inject, ref} from "vue";
import {throttle} from "lodash";
import Option from "./Option.vue";

const props = defineProps({
    modelValue: {
        default: null
    },
    users: {
        type: Array,
        default: []
    },
    exclude: {
        type: Array,
        default: []
    },
    filter: {
        type: Object,
        default: {}
    }
})

const emit = defineEmits(['update:modelValue']);

const options = ref(props.users);

const onSearch = (search, loading) => {
    if (!search) {
        return;
    }

    loading(true);
    fetch(loading, search);
}

const fetch = throttle((loading, search) => {
    axios.get(route(`mixpost_e.users.resources.items`), {
        params: Object.assign({
            keyword: search,
            exclude: props.exclude
        }, props.filter)
    }).then((response) => {
        loading(false);

        options.value = response.data.data.map((item) => {
            return {
                key: item.id,
                label: item.name,
                email: item.email
            }
        });
    });
}, 350)
</script>
<template>
    <div class="relative w-full">
        <v-select
            :modelValue="modelValue"
            @update:modelValue="$emit('update:modelValue', $event)"
            :options="options"
            :filterable="false"
            :close-on-select="true"
            :placeholder="$t('enterprise-theme.type_search')"
            @search="onSearch"
        >
            <template #selected-option="option">
                <Option :option="option"/>
            </template>

            <template #option="option">
                <Option :option="option"/>
            </template>

            <template #no-options="{ search, searching, loading }">
                {{ $t('enterprise-general.list_empty') }}
            </template>
        </v-select>
    </div>
</template>

