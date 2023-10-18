<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
	<div class="text2image-helper-reference">
		<div class="image-wrapper">
			<strong v-if="richObject.alt !== null">
				{{ richObject.alt }}
			</strong>
			<div v-if="!isLoaded" class="loading-icon">
				<NcLoadingIcon :size="44"
					:title="t('text2image_helper', 'Loading image')" />
			</div>
			<img v-show="isLoaded"
				class="image"
				:src="richObject.proxied_url"
				@load="isLoaded = true">
			<a v-show="isLoaded"
				class="attribution"
				target="_blank"
				:title="poweredByTitle"
				href="https://memegen.link">
				<div class="content" />
			</a>
		</div>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { imagePath } from '@nextcloud/router'

export default {
	name: 'Text2ImageHelperReferenceWidget',

	components: {
		NcLoadingIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			isLoaded: false,
			poweredByImgSrc: imagePath('text2image_helper', 'app.svg'),
			poweredByTitle: t('text2image_helper', 'Powered by sparkly unicorn dust'),
		}
	},

	computed: {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.text2image-helper-reference {
	width: 100%;
	padding: 12px;
	white-space: normal;

	.image-wrapper {
		width: 100%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		position: relative;

		.image {
			max-height: 300px;
			max-width: 100%;
			border-radius: var(--border-radius);
			margin-top: 8px;
		}

		.attribution {
			position: absolute;
			left: 0;
			bottom: 0;
			height: 33px;
			width: 80px;
			padding: 0;
			border-radius: var(--border-radius);
			background-color: var(--color-main-background);
			.content {
				height: 33px;
				width: 33px;
				background-image: url('../../img/app.svg');
				background-size: 33px 33px;
			}
		}
	}
}
</style>
