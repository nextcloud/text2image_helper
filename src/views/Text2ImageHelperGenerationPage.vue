<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
	<NcContent app-name="text2image_helper">
		<NcAppContent class="page">
			<div class="generation-dialog">
				<h2>
					{{ t('text2image_helper', 'Image generation') }}
				</h2>
				<div v-if="generationUrl !== null" class="image">
					<Text2ImageDisplay :src="generationUrl" :force-edit-mode="forceEditMode" />
				</div>
				<div class="button-wrapper">
					<NcButton
						type="primary"
						@click="onCopy">
						{{ t('text2image_helper', 'Copy link to clipboard') }}
					</NcButton>
				</div>
			</div>
		</NcAppContent>
	</NcContent>
</template>
<script>
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import Text2ImageDisplay from '../Components/Text2ImageDisplay.vue'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import VueClipboard from 'vue-clipboard2'
import Vue from 'vue'

Vue.use(VueClipboard)

export default {
	name: 'Text2ImageHelperGenerationPage',
	components: {
		NcContent,
		NcAppContent,
		NcButton,
		Text2ImageDisplay,
	},
	props: {
		imageGenId: {
			type: String,
			required: true,
		},
		forceEditMode: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			generationUrl: null,
		}
	},
	mounted() {
		this.generateUrl()
	},
	methods: {
		onClose() {
			this.$emit('close')
		},
		generateUrl() {
			this.generationUrl = generateUrl('/apps/text2image_helper/info/' + this.imageGenId)
		},
		async onCopy() {
			try {
				await this.$copyText(window.location.href)
				showSuccess(t('text2image_helper', 'Link copied to clipboard'))
			} catch (error) {
				console.error(error)
				showError(t('text2image_helper', 'Failed to copy link to clipboard'))
			}
		},
	},
}
</script>
<style scoped lang="scss">

.page {
	justify-content: center;
	align-content: center;
	.generation-dialog {
		margin: 12px;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 12px 12px 12px 12px;
		overflow-x: hidden;

		h2 {
			display: flex;
			align-items: center;
		}

		.image {
			display: flex;
			flex-direction: column;
			border-radius: var(--border-radius);
			margin-top: 8px;
		}

		.button-wrapper {
			display: flex;
			flex-direction: column;
			margin-top: 24px;
			margin-bottom: 48px;
		}
	}
}

</style>
