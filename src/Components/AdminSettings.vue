<template>
	<div id="text2image_prefs" class="section">
		<h2>
			<Text2ImageHelperIcon class="icon" />
			{{ t('text2image_helper', 'Text2Image Helper') }}
		</h2>
		<div class="line">
			<!--An input for specifying max generation idle time-->
			<label for="text2image-api-max-gen-idle">
				<mdiCalendarClock :size="20" class="icon" />
				{{ t('text2image_helper', 'Max image generation idle time (days)') }}
			</label>
			<input id="text2image-api-max-gen-idle"
				v-model="maxGenIdleTime"
				:title="t('text2image_helper', 'Maximum number of days an image can be idle (not viewed) before it is deleted')"
				type="number">
		</div>
	</div>
</template>

<script>
import mdiCalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import Text2ImageHelperIcon from '../Icons/Text2ImageHelperIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		Text2ImageHelperIcon,
		mdiCalendarClock,
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
		configured() {
			return !!this.state.url || !!this.state.api_key
		},
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

	watch: {
	},

	mounted() {
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
						+ ': ' + error.response?.message,
					)
				})
		},
	},
}
</script>

<style scoped lang="scss">
#text2image_prefs {
	#text2image-content {
		margin-left: 40px;
	}
	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		margin-top: 12px;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.mid-setting-heading {
		margin-top: 32px;
		text-decoration: underline;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
		.spacer {
			display: inline-block;
			width: 32px;
		}
		.quota-table {
			padding: 4px 8px 4px 8px;
			border: 2px solid var(--color-border);
			border-radius: var(--border-radius);
			.text-cell {
				opacity: 0.5;
			}
			th, td {
				width: 300px;
				text-align: left;
			}
		}
	}

	.model-select {
		min-width: 350px;
	}
}
</style>
