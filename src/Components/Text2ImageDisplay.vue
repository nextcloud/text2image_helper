<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->

<template>
	<div>
		<h3 v-if="prompt !== ''">
			{{ prompt }}
		</h3>
		<div v-if="isImageLoading" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('text2image_helper', 'Loading image')" />
		</div>
		<img v-show="!isImageLoading && !failed"
			class="image"
			:src="src"
			:aria-label="t('text2image_helper', 'Generated image')"
			@load="isImageLoading = false"
			@error="onError">
		<span v-if="failed">
			{{ t('text2image_helper', 'The image cannot be fetched. The image may have been cleaned up due to not being viewed for a while.') }}
		</span>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

export default {
	name: 'Text2ImageDisplay',

	components: {
		NcLoadingIcon,
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
			failed: false,
		}
	},

	computed: {
	},

	mounted() {
	},

	methods: {
		onError(e) {
			this.isImageLoading = false
			this.failed = true
		},
	},
}
</script>

<style scoped lang="scss">
.image {
	max-height: 300px;
	max-width: 100%;
	border-radius: var(--border-radius-large);
}
</style>
