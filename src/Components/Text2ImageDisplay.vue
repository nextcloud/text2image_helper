<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->

<template>
	<div class="display-container">
		<div class="title">
			<div class="icon-and-text">
				<div v-if="isLoaded.length === 0 && ((success && hasVisibleImages)||!success === false) && !failed">
					<NcLoadingIcon :size="20" class="icon" />
				</div>
				<div v-else>
					<Text2ImageHelperIcon :size="20" class="icon" />
				</div>
				<strong class="app-name">
					{{ t('text2image_helper', 'Image generation') + ':' }}
				</strong>
				{{ prompt }}
			</div>
			<div v-if="isOwner && !forceEditMode"
				class="edit-icon"
				:class="{ 'active': editModeEnabled}"
				:title="t('text2image_helper', 'Edit visible images')"
				@click="toggleEditMode">
				<Cog :size="30" class="icon" />
			</div>
		</div>
		<div v-if="editModeEnabled && isOwner">
			<div v-if="imageUrls.length > 0 && !failed" class="image-list">
				<div v-for="(imageUrl, index) in imageUrls"
					:key="index"
					class="image-container"
					@mouseover="hoveredIndex = index"
					@mouseout="hoveredIndex = -1">
					<div class="checkbox-container" :class="{ 'hovering': hoveredIndex === index }">
						<input v-model="fileVisStatusArray[index].visible"
							:v-show="!isLoaded[index]"
							type="checkbox"
							:title="t('text2image_helper', 'Click to toggle generation visibility')"
							@change="onCheckboxChange()">
					</div>
					<div class="image-wrapper" :class="{ 'deselected': !fileVisStatusArray[index].visible }">
						<img
							class="image-editable"
							:src="imageUrl"
							:title="t('text2image_helper', 'Click to toggle generation visibility')"
							@load="isLoaded[index] = true"
							@click="toggleCheckbox(index)"
							@error="onError">
					</div>
				</div>
			</div>
		</div>
		<div v-else>
			<div v-if="imageUrls.length > 0 && !failed"
				class="image-list">
				<div v-for="(imageUrl, index) in imageUrls"
					:key="index"
					class="image-container">
					<div v-if="!isOwner || fileVisStatusArray[index].visible" class="image-wrapper">
						<img
							class="image-non-editable"
							:src="imageUrl"
							:title="t('text2image_helper', 'Generated image')"
							@load="isLoaded[index] = true"
							@error="onError">
					</div>
					<div v-if="!hasVisibleImages" class="error_msg">
						{{ t('text2image_helper', 'This generation has no visible images') }}
					</div>
				</div>
			</div>
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
import Cog from 'vue-material-design-icons/Cog.vue'
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
		Cog,
	},

	props: {
		src: {
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
			prompt: '',
			isLoaded: [],
			timeUntilCompletion: null,
			failed: false,
			imageUrls: [],
			isOwner: false,
			errorMsg: t('text2image_helper', 'Image generation failed'),
			closed: false,
			success: false,
			fileVisStatusArray: [],
			hoveredIndex: -1,
			hovered: false,
			editModeEnabled: false,
		}
	},

	computed: {
		hasVisibleImages() {
			return this.fileVisStatusArray.some(status => status.visible)
		},
	},
	mounted() {
		this.getImageGenInfo()
		this.editModeEnabled = this.forceEditMode
	},
	unmounted() {
		this.closed = true
	},
	methods: {
		getImages(imageGenId, fileIds) {
			this.imageUrls = []
			this.fileVisStatusArray = fileIds
			// Loop through all the fileIds and get the images:
			fileIds.forEach((fileId) => {
				this.imageUrls.push(generateUrl('/apps/text2image_helper/g/' + imageGenId + '/' + fileId.id))
			})
		},
		getImageGenInfo() {
			axios.get(this.src)
				.then((response) => {
					if (response.status === 200) {
						if (response.data?.files !== undefined) {

							if (response.data.files.length === 0) {
								this.errorMsg = t('text2image_helper', 'This generation has no visible images')
								this.failed = true
								this.isLoaded = []
							} else {
								this.prompt = response.data.prompt
								this.isOwner = response.data.is_owner
								this.success = true
								this.getImages(response.data.image_gen_id, response.data.files)
								this.onGenerationReady()
							}
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
						this.isLoaded = []
					}
				})
				.catch((error) => {
					console.error('Could not get image generation info: ' + error)
					this.onError(error)
				})
			// If we didn't succeed in loading the image, try again
			if (!this.success && !this.failed && !this.closed) {
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
				this.isLoaded = []
			} else {
				console.error('Could not handle response error: ' + error)
				this.errorMsg = t('text2image_helper', 'Unknown server query error')
				this.failed = true
				this.isLoaded = []
			}
			this.$emit('failed')

		},
		onGenerationReady() {
			this.$emit('ready')
		},
		onCheckboxChange() {
			const url = generateUrl('/apps/text2image_helper/v/' + this.src.split('/').pop())

			axios.post(url, {
				fileVisStatusArray: this.fileVisStatusArray,
			})
				.then((response) => {
					if (response.status === 200) {
						// console.log('Successfully updated visible images')
					} else {
						console.error('Unexpected response status: ' + response.status)
					}
				})
				.catch((error) => {
					console.error('Could not update visible images: ' + error)
				})
		},
		toggleCheckbox(index) {
			this.fileVisStatusArray[index].visible = !this.fileVisStatusArray[index].visible
			this.onCheckboxChange()
		},
		toggleEditMode() {
			this.editModeEnabled = !this.editModeEnabled
		},
	},
}
</script>

<style scoped lang="scss">
.display-container {
	display: flex;
	flex-direction: column;
	width: 100%;
	align-items: center;
	justify-content: center;
	.edit-icon {
		position: static;
		z-index: 1;
		opacity: 0.2;
		transition: opacity 0.2s ease-in-out;
		cursor: pointer;
	}

	.edit-icon.active {
		opacity: 1;
	}

	.image-list {
		display: flex;
		flex-direction: column;
		flex-wrap: wrap;
		justify-content: center;
	}

	.image-container {
		display: flex;
		flex-direction: column;
		position: relative;
		justify-content: center;
		max-width: 90%;
	}

	.checkbox-container {
		position: absolute;
		top: 5%;
		left: 95%;
		z-index: 1;
		opacity: 0.2;
		transition: opacity 0.2s ease-in-out;
	}

	.checkbox-container.hovering {
		opacity: 1;
	}

	.image-wrapper {
		display: flex;
		flex-direction: column;
		position: relative;
		max-width: 100%;
		height: 100%;
		margin-top: 12px;
		filter: grayscale(100%);
		transition: filter 0.2s ease-in-out;
	}

	.image-wrapper.deselected {
		filter: grayscale(100%) brightness(50%);
	}

	.image-editable {
		display: flex;
		width: 100%;
		height: 100%;
		min-width: 400px;
		object-fit:contain;
		cursor: pointer;
		border-radius: var(--border-radius);
	}

	.image-non-editable {
		display: flex;
		width: 100%;
		height: 100%;
		min-width: 400px;
		object-fit:contain;
	}

	.title {
		max-width: 600px;
		width: 100%;
		display: flex;
		flex-direction: row;
		margin-top: 0;

		.icon-and-text {
			width: 100%;
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: start;
			margin-right: 8px;

			.app-name {
				margin-right: 8px;
				white-space: nowrap;
			}
		}

		.icon {
			display: inline;
			position: relative;
			top: 4px;
		}
	}

	.loading-icon {
		position: absolute;
		top: 5;
		z-index: 1;

	}

	.processing-notification-container {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		margin-top: 24px;

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
