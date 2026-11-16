<script setup>
import { computed, inject, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Head, router, useForm } from "@inertiajs/vue3";
import { cloneDeep, random } from "lodash";
import { ACCESS_STATUS_SUBSCRIPTION } from "@meJs/Constants/Workspace";
import usePageMode from "@meJs/Composables/usePageMode";
import useNotifications from "@meJs/Composables/useNotifications";
import PageHeader from "@meJs/Components/DataDisplay/PageHeader.vue";
import Panel from "@meJs/Components/Surface/Panel.vue";
import HorizontalGroup from "@meJs/Components/Layout/HorizontalGroup.vue";
import Error from "@meJs/Components/Form/Error.vue";
import Input from "@meJs/Components/Form/Input.vue";
import PrimaryButton from "@meJs/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@meJs/Components/Button/SecondaryButton.vue";
import DialogModal from "@meJs/Components/Modal/DialogModal.vue";
import ColorPicker from "@mJs/Components/Package/ColorPicker.vue";
import { COLOR_PALLET_LIST } from "@mJs/Constants/ColorPallet";
import SelectUser from "@/MixpostEnterprise/Components/User/SelectUser/SelectUser.vue";
import LabelSuffix from "@meJs/Components/Form/LabelSuffix.vue";
import Actions from "@/MixpostEnterprise/Components/Workspace/Actions.vue";
import Select from "@meJs/Components/Form/Select.vue";
import Alert from "@meJs/Components/Util/Alert.vue";
import EnterpriseLayout from "@/Layouts/MixpostEnterprise/Panel.vue";
import Refresh from "@/Icons/Refresh.vue";

defineOptions({ layout: EnterpriseLayout });

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: "create",
    },
    workspace: {
        type: Object,
    },
    locales: {
        type: Array,
        required: true,
    },
    versions: {
        type: Array,
        required: true,
    },
    strategy: {
        type: Object,
        default: null,
    },
});

const { t: $t } = useI18n()

const { notify } = useNotifications()

const routePrefix = inject('routeEntPrefix')

const confirmation = inject('confirmation')

const pageTitle = computed(() => {
    if (isCreate.value) {
        return $t("enterprise-dashboard.create_workspace");
    }

    return $t("enterprise-workspace.edit_workspace");
});

const { isCreate, isEdit } = usePageMode()

const changeColorModal = ref(false)
const changeColorHex = ref('')

const selectedOwner = ref(
  isEdit.value && props.workspace.owner
    ? {
        key: props.workspace.owner.id,
        label: props.workspace.owner.name,
        email: props.workspace.owner.email
      }
    : null
)

const form = useForm(
    isEdit.value
        ? cloneDeep(props.workspace)
        : {
              name: "",
              hex_color: "",
              access_status: ACCESS_STATUS_SUBSCRIPTION,
              locale: "",
              version: "",
          },
);

const selectColor = () => {
  form.hex_color = changeColorHex.value
  changeColorModal.value = false
}

const pickRandomColor = () => {
  const colorList = COLOR_PALLET_LIST()

  return colorList[random(0, colorList.length - 1)]
}

onMounted(() => {
  if (isCreate.value) {
    const randomColor = pickRandomColor()

    form.hex_color = randomColor
    changeColorHex.value = randomColor
  }

  if (isEdit.value) {
    changeColorHex.value = props.workspace.hex_color
  }
})

const getTransformedForm = () => {
  return form.transform(data => ({
    ...data,
    ...{
      owner_id: selectedOwner.value ? selectedOwner.value.key : null
    }
  }))
}

const store = () => {
  getTransformedForm().post(route(`${routePrefix}.workspaces.store`))
}

const update = () => {
  getTransformedForm().put(
    route(`${routePrefix}.workspaces.update`, { workspace: props.workspace.uuid }),
    {
      onSuccess: () => {
        notify('success', $t('enterprise-workspace.workspace_updated'))
      }
    }
  )
}

/**
 * Regeneration never generates anything itself: it leaves the customer's strategy page offering
 * "Create strategy" again, after a new or cloned briefing when the mode asks for one.
 */
const regenerationModes = [
  { mode: 'strategy', label: 'genie.regenerate_strategy', confirm: 'genie.regenerate_strategy_confirm' },
  { mode: 'new_briefing', label: 'genie.regenerate_strategy_new_briefing', confirm: 'genie.regenerate_strategy_new_briefing_confirm' },
  { mode: 'cloned_briefing', label: 'genie.regenerate_strategy_cloned_briefing', confirm: 'genie.regenerate_strategy_cloned_briefing_confirm' },
]

const isRegenerationAvailable = (mode) => {
  if (!props.strategy || props.strategy.state === 'generating') {
    return false
  }

  return mode !== 'cloned_briefing' || props.strategy.has_briefing
}

const regenerateStrategy = ({ mode, label, confirm }) => {
  confirmation()
    .title($t(label))
    .description($t(confirm) + '<br><br>' + $t('genie.regenerate_strategy_desc'))
    .btnConfirmName($t(label))
    .onConfirm((dialog) => {
      dialog.isLoading(true)

      router.post(
        route(`${routePrefix}.workspaces.regenerate_strategy`, { workspace: props.workspace.uuid }),
        { mode },
        {
          preserveScroll: true,
          onFinish() {
            dialog.reset()
          }
        }
      )
    })
    .show()
}

const submit = () => {
  if (isCreate.value) {
    store()
  }

  if (isEdit.value) {
    update()
  }
}
</script>
<template>
  <Head :title="pageTitle" />

  <div class="row-py w-full mx-auto">
    <PageHeader :title="pageTitle">
      <template v-if="isEdit">
        <Actions :workspace="workspace" :edit="false" />
      </template>
    </PageHeader>

    <div class="row-px">
      <form method="post" @submit.prevent="submit">
        <Panel>
          <template #title>{{ $t('enterprise-general.details') }}</template>

          <HorizontalGroup>
            <template #title>
              <label for="name"
                >{{ $t('enterprise-general.name') }}
                <LabelSuffix danger>*</LabelSuffix>
              </label>
            </template>

            <Input
              id="name"
              v-model="form.name"
              type="text"
              :placeholder="$t('enterprise-workspace.workspace_name')"
              class="w-full"
              autocomplete="off"
              :autofocus="isCreate"
              required
            />

            <template #footer>
              <Error :message="form.errors.name" />
            </template>
          </HorizontalGroup>

          <HorizontalGroup class="mt-lg">
            <template #title>
              <label for="access_status">{{ $t('enterprise-general.access_status') }}</label>
            </template>
            <Select id="access_status" v-model="form.access_status">
              <option value="subscription">{{ $t('enterprise-subscription.requires_subscription') }}</option>
              <option value="unlimited">{{ $t('enterprise-workspace.unlimited') }}</option>
              <option value="locked">{{ $t('enterprise-workspace.locked') }}</option>
            </Select>
            <template #footer>
              <Error :message="form.errors.access_status" />
            </template>
          </HorizontalGroup>

                    <HorizontalGroup class="mt-lg">
                        <template #title>
                            <label for="locale"
                                >{{ $t("genie.language") }}
                                <LabelSuffix danger>*</LabelSuffix>
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
                            <label for="version"
                                >{{ $t("genie.version") }}
                                <LabelSuffix danger>*</LabelSuffix>
                            </label>
                        </template>

                        <div class="w-full">
                            <Select v-model="form.version" required>
                                <option
                                    v-for="version in versions"
                                    :value="version.id"
                                >
                                    {{ version.name }}
                                </option>
                            </Select>
                        </div>
                    </HorizontalGroup>

          <HorizontalGroup class="mt-lg">
            <template #title>
              {{ $t('enterprise-theme.color') }}
            </template>

            <div
              :style="{ background: form.hex_color }"
              role="button"
              type="button"
              class="w-xl h-xl rounded-md"
              @click="changeColorModal = true"
            />
          </HorizontalGroup>
        </Panel>

        <Panel class="mt-lg">
          <template #title>{{ $t('enterprise-general.owner') }}</template>

          <div class="form-field">
            <SelectUser v-model="selectedOwner" :users="selectedOwner ? [selectedOwner] : []" />

            <Alert v-if="isEdit" :closeable="false" class="mt-lg"
              >{{ $t('enterprise-panel.add_admin_role') }}
            </Alert>

            <Error :message="form.errors.owner_id" />
          </div>
        </Panel>

        <PrimaryButton
          type="submit"
          class="mt-lg"
          :disabled="form.processing"
          :is-loading="form.processing"
        >
          {{ isCreate ? $t('general.create') : $t('enterprise-workspace.update') }}
        </PrimaryButton>
      </form>

      <Panel v-if="isEdit && strategy" class="mt-lg">
        <template #title>{{ $t('genie.strategy') }}</template>
        <template #description>
          {{ $t(`genie.strategy_state_${strategy.state}`) }}
          {{ $t('genie.regenerate_strategy_desc') }}
        </template>

        <div class="flex flex-wrap gap-xs">
          <SecondaryButton
            v-for="option in regenerationModes"
            :key="option.mode"
            :disabled="!isRegenerationAvailable(option.mode)"
            @click="regenerateStrategy(option)"
          >
            <template #icon>
              <Refresh />
            </template>
            {{ $t(option.label) }}
          </SecondaryButton>
        </div>
      </Panel>
    </div>
  </div>

  <DialogModal :show="changeColorModal" max-width="md" @close="changeColorModal = false">
    <template #header>
      {{ $t('enterprise-workspace.change_workspace_color') }}
    </template>
    <template #body>
      <template v-if="changeColorModal">
        <ColorPicker v-model="changeColorHex" />
      </template>
    </template>
    <template #footer>
      <SecondaryButton class="mr-xs rtl:mr-0 rtl:ml-xs" @click="changeColorModal = false">
        {{ $t('general.cancel') }}
      </SecondaryButton>
      <PrimaryButton @click="selectColor">{{ $t('general.done') }}</PrimaryButton>
    </template>
  </DialogModal>
</template>
