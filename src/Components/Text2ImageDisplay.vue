<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->

<template>
	<div class="display-container">
		<span class="title">
			<Text2ImageHelperIcon :size="20" class="icon" />
			<strong>
				{{ t('text2image_helper', 'Image generation') + ':' }}
			</strong>
			&nbsp;
			<span>
				{{ prompt }}
			</span>
		</span>
		<div v-if="isImageLoading" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('text2image_helper', 'Loading image')" />
		</div>
		<div v-if="imageUrls.length > 0" class="image_container">
			<img v-for="(imageUrl,index) in imageUrls"
				:key="index"
				:v-show="!isImageLoading && !failed"
				class="image"
				:src="imageUrl"
				:aria-label="t('text2image_helper', 'Generated image')"
				@load="isImageLoading = false"
				@error="onError">
		</div>
		<div v-if="!failed && imageUrls.length === 0 && timeUntilCompletion !== null"
			class="processing-notification-container">
			<div class="processing-notification">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('text2image_helper', 'Generation time left (h:m): ' + timeUntilCompletion) + '\n' }}
				{{ t('text2image_helper', 'The generated image is shown once ready.') }}
			</div>
		</div>
		<span v-if="failed" class="error_msg">
			{{ t('text2image_helper', errorMsg) }}
		</span>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import axios from '@nextcloud/axios'
import Text2ImageHelperIcon from '../Icons/Text2ImageHelperIcon.vue'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'Text2ImageDisplay',

	components: {
		NcLoadingIcon,
		InformationOutlineIcon,
		Text2ImageHelperIcon,
	},

	props: {
		src: {
			type: String,
			required: true,
		},
		prompt: {
			type: String,
			required: false,
			default: '',
		},
	},

	data() {
		return {
			isImageLoading: true,
			timeUntilCompletion: null,
			failed: false,
			imageUrls: [],
			isOwner: false,
			errorMsg: t('text2image_helper', 'Image generation failed'),
			closed: false,
		}
	},

	computed: {
	},
	mounted() {
		this.getImageGenInfo()
	},
	unmounted() {
		this.closed = true
	},
	methods: {
		getImages(imageGenId, fileIds) {
			this.imageUrls = []
			// Loop through all the fileIds and get the images:
			fileIds.forEach((fileId) => {
				this.imageUrls.push(generateUrl('/apps/text2image_helper/g/' + imageGenId + '/' + fileId.id))
			})
		},
		getImageGenInfo() {
			let success = false
			axios.get(this.src)
				.then((response) => {
					if (response.status === 200) {
						if (response.data?.files !== undefined) {
							this.isOwner = response.data.is_owner
							success = true
							this.getImages(response.data.image_gen_id, response.data.files)
							this.onGenerationReady()
						} else {
							if (response.data?.processing !== undefined) {
								const completionTimeStamp = response.data.processing
								// If the completionTimeEst (UTC timestamp) is more than 60 seconds in the future,
								// display the time to the user
								if (completionTimeStamp > (Date.now() / 1000) + 60) {
									this.updateTimeUntilCompletion(completionTimeStamp)
								}
							}
						}
					} else {
						console.error('Unexpected response status: ' + response.status)
						this.errorMsg = t('text2image_helper', 'Unexpected server response')
						this.failed = true
						this.isImageLoading = false
					}
				})
				.catch((error) => {
					console.error('WTF, how we got here?')
					this.onError(error)
				})
				// If we didn't succeed in loading the image, try again
			if (!success && !this.failed && !this.closed) {
				setTimeout(this.getImageGenInfo, 3000)
			}
		},
		updateTimeUntilCompletion(completionTimeStamp) {
			const timeDifference = new Date(completionTimeStamp * 1000) - new Date()
			if (timeDifference < 60000) {
				this.timeUntilCompletion = null
				return
			}

			const hours = Math.floor(timeDifference / (1000 * 60 * 60))
			const minutes = Math.floor((timeDifference % (1000 * 60 * 60)) / 60000)
			const seconds = Math.floor((timeDifference % (1000 * 60)) / 1000)
			this.timeUntilCompletion = `${hours}:${minutes.toString().padStart(2, '0')}`

			// Schedule next update at the next minute change:
			if (!this.closed) {
				setTimeout(() => {
					this.updateTimeUntilCompletion(completionTimeStamp)
				}, seconds * 1000 + 1000)
			}
		},
		onError(error) {
			if (error.response?.data !== undefined) {
				this.errorMsg = error.response.data
				this.failed = true
				this.isImageLoading = false
			} else {
				console.error('Could not handle response error: ' + error)
				this.errorMsg = t('text2image_helper', 'Unknown server query error')
				this.failed = true
				this.isImageLoading = false
			}
			this.$emit('failed')

		},
		onGenerationReady() {
			this.$emit('ready')
		},
	},
}
</script>

<style scoped lang="scss">
.display-container {
	display: flex;
	flex-direction: column;
	width: 100%;

	.image_container {
		display: flex;
		flex-direction: column;
		width: 100%;
		justify-content: center;
		.image {
			max-height: 300px;
			border-radius: var(--border-radius-large);
		}
	}

	.title {
		margin-top: 0;
		margin-bottom: 24px;
		.icon {
			display: inline;
			position: relative;
			top: 4px;
		}
	}

	.processing-notification-container {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		.processing-notification {
			display: flex;
			flex-direction: row;
			margin-top: 24px;
			width: 90%;
			align-items: center;
			justify-content: center;
			// Add a border
			border: 3px solid var(--color-border);
			border-radius: var(--border-radius-large);
			padding: 12px;
			// Reduce the font size
			font-size: 0.8rem;
			// Add some space between the icon and the text on the same line
			column-gap: 24px;
		}
	}

	.error_msg {
		color: var(--color-error);
		font-weight: bold;
		margin-bottom: 24px;
		align-self: center;

	}
}

</style>
