<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { inject, onMounted, ref } from "vue";
import { random } from "lodash";
import MinimalLayout from "@/Layouts/Minimal.vue";
import Panel from "@/Components/Surface/Panel.vue";
import ColorPicker from "@mJs/Components/Package/ColorPicker.vue";
import { COLOR_PALLET_LIST } from "@mJs/Constants/ColorPallet";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import HorizontalGroup from "@/Components/Layout/HorizontalGroup.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import Select from "@/Components/Form/Select.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";

defineOptions({ layout: MinimalLayout });

const routePrefix = "mixpost_e";

const props = defineProps({
    locales: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    name: "",
    hex_color: "",
    locale: "",
});

const changeColorModal = ref(false);
const changeColorHex = ref("");

const selectColor = () => {
    form.hex_color = changeColorHex.value;
    changeColorModal.value = false;
};

const pickRandomColor = () => {
    const colorList = COLOR_PALLET_LIST();

    return colorList[random(0, colorList.length - 1)];
};

onMounted(() => {
    const randomColor = pickRandomColor();

    form.hex_color = randomColor;
    changeColorHex.value = randomColor;
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        login: true,
    })).post(route(`${routePrefix}.workspace.store`));
};
</script>
<template>
    <Head :title="$t('enterprise-dashboard.create_workspace')" />

    <div class="w-full mx-auto">
        <form method="post" @submit.prevent="submit">
            <Panel>
                <template #title>
                    {{ $t("enterprise-dashboard.create_workspace") }}
                </template>

                <div class="sm:max-w-(--container-lg)">
                    <HorizontalGroup>
                        <template #title>
                            <label for="name"
                                >{{ $t("general.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <div class="w-full">
                            <Input
                                v-model="form.name"
                                type="text"
                                id="name"
                                :placeholder="$t('workspace.workspace_name')"
                                class="w-full"
                                autocomplete="off"
                                :autofocus="true"
                                required
                            />
                            <Error :message="form.errors.name" class="mt-1" />
                        </div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            <label for="locale"
                                >{{ $t("genie.language") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <div class="w-full">
                            <Select v-model="form.locale" required>
                                <option
                                    v-for="locale in locales"
                                    :value="locale.long"
                                >
                                    {{ locale.english }} - {{ locale.native }} -
                                    ({{ locale.long }})
                                </option>
                            </Select>
                        </div>
                    </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            {{ $t("enterprise-theme.color") }}
                        </template>

                        <div
                            @click="changeColorModal = true"
                            :style="{ background: form.hex_color }"
                            role="button"
                            type="button"
                            class="w-xl h-xl rounded-md"
                        />
                    </HorizontalGroup>

                    <div class="flex items-center mt-lg">
                        <PrimaryButton
                            type="submit"
                            :is-loading="form.processing"
                            :disabled="form.processing"
                            >{{ $t("general.create") }}</PrimaryButton
                        >
                    </div>
                </div>
            </Panel>
        </form>
    </div>

  <DialogModal :show="changeColorModal" max-width="md" @close="changeColorModal = false">
    <template #header>
      {{ $t('workspace.change_workspace_color') }}
    </template>
    <template #body>
      <template v-if="changeColorModal">
        <ColorPicker v-model="changeColorHex" />
      </template>
    </template>
    <template #footer>
      <SecondaryButton class="mr-xs" @click="changeColorModal = false"
        >{{ $t('general.cancel') }}
      </SecondaryButton>
      <PrimaryButton @click="selectColor">{{ $t('general.done') }}</PrimaryButton>
    </template>
  </DialogModal>
</template>
