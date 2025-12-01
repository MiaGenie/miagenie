<script setup>
import {computed, inject, ref} from "vue";
import usePostVersions from "@/Composables/usePostVersions";
import TableRow from "@/Components/DataDisplay/TableRow.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import MediaFile from "@/Components/Media/MediaFile.vue";
import Dropdown from "@/Components/Dropdown/Dropdown.vue"
import DropdownItem from "@/Components/Dropdown/DropdownItem.vue";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue"
import PureButton from "@/Components/Button/PureButton.vue";
import PostPreviewProviders from "@/Components/Post/PostPreviewProviders.vue"
import Account from "@/Components/Account/Account.vue"
import PostItemActions from "@/Components/Post/PostItemActions.vue";
import PostStatus from "@/Components/Post/PostStatus.vue";
import VerticallyScrollableContent from "@/Components/Surface/VerticallyScrollableContent.vue";
import Badge from "@/Components/DataDisplay/Badge.vue";
import usePostURLMeta from "@/Composables/usePostURLMeta";
import Eye from "@/Icons/Eye.vue";
import PureButtonLink from "@/Components/Button/PureButtonLink.vue";
import PencilSquareIcon from "@/Icons/PencilSquare.vue";
import useWorkspace from "@/Composables/useWorkspace.js";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    type: {
        type: String,
        default: ""
    }
})

const {getOriginalVersion, getAccountVersion} = usePostVersions();
const {setupURLMetaForAllVersions} = usePostURLMeta();
const workspaceCtx = inject('workspaceCtx');
const {isWorkspaceEditorRole} = useWorkspace();

const content = computed(() => {
    if (!props.item.versions.length) {
        return {
            excerpt: '',
            media: null,
            media_count: 0,
        }
    }

    let accounts = props.item.accounts;

    const accountVersions = accounts.map((account) => {
        const accountVersion = getAccountVersion(props.item.versions, account.id);

        return accountVersion ? accountVersion.content[0] : getOriginalVersion(props.item.versions).content[0];
    })

    const record = accountVersions.length ? accountVersions[0] : props.item.versions[0].content[0];

    return {
        excerpt: record.excerpt,
        media: record.media.length ? record.media[0] : null,
        media_count: record.media.length
    }
});

const preview = ref(false);

const openPreview = () => {
    preview.value = true;

    setupURLMetaForAllVersions(props.item.versions);
}

const closePreview = () => {
    preview.value = false;
}
</script>
<template>
    <TableRow :hoverable="true">
        <TableCell v-if="type === 'scheduled'" :clickable="true" @click="openPreview" class="!pl-0 w-1/4">
            <div class="text-sm mt-xs">{{
                    item.scheduled_at.human
                }}
            </div>
        </TableCell>
        <TableCell :clickable="true" @click="openPreview" class="!pl-0 w-3/4">
            <div class="text-left break-words">{{ content.excerpt }}</div>
            <div v-if="content.media" class="w-48 flex relative">
                <MediaFile v-if="content.media" :media="content.media" img-height="sm" :imgWidthFull="false" :showCaption="false">
                    <div v-if="content.media_count > 1" class="absolute top-0 -right-5 z-10">
                        <Badge>+{{ content.media_count - 1 }}</Badge>
                    </div>
                </MediaFile>
            </div>
        </TableCell>
        <TableCell v-if="type === 'scheduled'">
            <div class="flex gap-xs">
                <div v-for="(account, index) in item.accounts.slice(0, 3)" :key="account.id"
                     :class="{'-ml-6': index > 0}">
                    <Account :provider="account.provider"
                             :name="account.name"
                             :img-url="account.image"
                             :active="true"
                             v-tooltip="account.name"
                    />
                </div>
                <Dropdown v-if="item.accounts.length > 3" width-classes="w-64" placement="bottom-end">
                    <template #trigger>
                        <PureButton class="mt-4 font-semibold">+{{ item.accounts.slice(3).length }}</PureButton>
                    </template>

                    <template #content>
                        <VerticallyScrollableContent>
                            <template v-for="account in item.accounts.slice(3)">
                                <DropdownItem as="div">
                                    <span class="mr-xs">
                                        <Account :provider="account.provider"
                                                 :name="account.name"
                                                 :img-url="account.image"
                                                 :active="true"/>
                                    </span>
                                    <span class="text-left">{{ account.name }}</span>
                                </DropdownItem>
                            </template>
                        </VerticallyScrollableContent>
                    </template>
                </Dropdown>
            </div>
        </TableCell>
        <TableCell align="right">
            <PureButtonLink :href="route('mixpost.posts.edit', { workspace: workspaceCtx.id, post: item.id })"
                            v-tooltip="$t(!isWorkspaceEditorRole ? 'general.view' : 'general.edit')">
                <template v-if="!isWorkspaceEditorRole">
                    <Eye/>
                </template>
                <template v-else>
                    <PencilSquareIcon/>
                </template>
            </PureButtonLink>
        </TableCell>

        <DialogModal :show="preview" :scrollableBody="true" @close="closePreview">
            <template #body>
                <PostStatus :value="item.status" class="mb-lg"/>

                <PostPreviewProviders v-if="preview"
                                      :accounts="item.accounts"
                                      :versions="item.versions"
                />
            </template>
            <template #footer>
                <template v-if="preview">
                    <div class="mr-xs flex items-center">
                        <PostItemActions :itemId="item.id" :trashed="item.trashed"/>
                    </div>
                    <SecondaryButton @click="closePreview">{{ $t("general.close") }}</SecondaryButton>
                </template>
            </template>
        </DialogModal>
    </TableRow>
</template>
