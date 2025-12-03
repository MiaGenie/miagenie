<script setup>
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import QueueList from "@/Icons/QueueList.vue";
import RulesIcon from "@/Icons/Genie/Rules.vue";
import Post from "@/Components/PostPreview/Instagram/Post.vue";
import Grid from "@/Icons/Grid.vue";

const {t: $t} = useI18n()
const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    status: {
        type: Object,
        required: true
    }
})

const getRoute = (name) => {
    switch (name) {
        case 'edit':
            return route('genie.drafts.edit', {
                workspace: workspaceCtx.id,
                draft: props.item.id,
            });
        case 'post':
            return route('mixpost.posts.edit', {
                workspace: workspaceCtx.id,
                post: props.item.post,
            });
        default:
            return '';
    }
}

</script>
<template>
    <div>
        <div class="flex flex-row items-center justify-end gap-lg">

            <PureButtonLink
                v-if="status.name !== 'PUBLISHED'"
                :href="getRoute('edit')"
                v-tooltip="$t('general.edit')"
            >
                <template #icon>
                    <PencilSquare/>
                </template>
                <template #default>
                    {{ $t('general.edit') }}
                </template>
            </PureButtonLink>

            <PureButtonLink
                v-if="status.name === 'PUBLISHED'"
                :href="getRoute('post')"
                v-tooltip="$t('post.post')"
            >
                <template #icon>
                    <Grid/>
                </template>
                <template #default>
                    {{ $t('post.post') }}
                </template>
            </PureButtonLink>

        </div>
    </div>
</template>
