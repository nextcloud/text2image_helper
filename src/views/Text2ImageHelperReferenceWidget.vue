<!-- SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com> -->
<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<template>
	<div class="text2image-helper-reference">
		<div class="image">
			<Text2ImageDisplay :src="richObject.proxied_url" :prompt="richObject.prompt" />
		</div>
	</div>
</template>

<script>

import Text2ImageDisplay from '../Components/Text2ImageDisplay.vue'

export default {
	name: 'Text2ImageHelperReferenceWidget',

	components: {
		Text2ImageDisplay,
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
	.image {
		max-height: 300px;
		max-width: 100%;
		border-radius: var(--border-radius);
		margin-top: 8px;
	}
}
</style>
