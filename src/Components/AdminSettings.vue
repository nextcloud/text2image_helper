<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSettingsSection :name="t('text2image_helper', 'Image generation - Text2Image Helper')"
			:title="t('text2image_helper', 'Image generation - Text2Image Helper')"
			:description="t('text2image_helper','Here you can define the settings for the Text2Image helper smart picker.')">
			<div class="line">
				<!--An input for specifying max generation idle time-->
				<label for="text2image-api-max-gen-idle">
					<mdiCalendarClock :size="20" class="icon" />
					{{ t('text2image_helper', 'Max generation idle time (days): ') }}
				</label>
				<NcTextField id="text2image-api-max-gen-idle"
					class="input-field"
					:value.sync="maxGenIdleTime"
					:title="t('text2image_helper', 'Maximum number of days an image can be idle (not viewed) before it is deleted')"
					type="number" />
			</div>
		</NcSettingsSection>
	</div>
</template>

<script>
import mdiCalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		mdiCalendarClock,
		NcSettingsSection,
		NcTextField,
	},

	props: [],

	data() {
		return {
			state: loadState('text2image_helper', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			models: null,
			selectedModel: null,
			apiKeyUrl: 'https://platform.text2image.com/account/api-keys',
			quotaInfo: null,
		}
	},

	computed: {
		formattedModels() {
			if (this.models) {
				return this.models.map(m => {
					return {
						id: m.id,
						value: m.id,
						label: m.id
							+ (m.owned_by ? ' (' + m.owned_by + ')' : ''),
					}
				})
			}
			return []
		},
		maxGenIdleTime: {
			get() {
				return this.state.max_generation_idle_time / 60 / 60 / 24
			},
			set(newValue) {
				this.state.max_generation_idle_time = newValue * 60 * 60 * 24
				this.onInput()
			},
		},
	},

	methods: {
		capitalizedWord(word) {
			return word.charAt(0).toUpperCase() + word.slice(1)
		},
		onInput() {
			delay(() => {
				this.saveOptions({
					max_generation_idle_time: this.state.max_generation_idle_time,
				})
			}, 2000)()
		},
		saveOptions(values, notify = true) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/text2image_helper/admin-config')
			return axios.put(url, req)
				.then((response) => {
					if (notify) {
						showSuccess(t('text2image_helper', 'Text2Image Helper admin options saved'))
					}
				})
				.catch((error) => {
					showError(
						t('text2image_helper', 'Failed to save Text2Image Helper admin options')
						+ ': ' + error.response?.data?.message,
					)
				})
		},
	},
}
</script>

<style scoped lang="scss">

.line,
.settings-hint {
	display: flex;
	align-items: center;
	margin-top: 12px;
	.icon {
		margin-right: 4px;
	}
}

.line {
	> label {
		width: 300px;
		display: flex;
		align-items: center;
	}
	.input-field {
		width: 300px;
	}
}

</style>
