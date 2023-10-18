// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

import { registerWidget, registerCustomPickerElement, NcCustomPickerRenderResult } from '@nextcloud/vue/dist/Components/NcRichText.js'

import { linkTo } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { getRequestToken } from '@nextcloud/auth'

__webpack_nonce__ = btoa(getRequestToken()) // eslint-disable-line
__webpack_public_path__ = linkTo('text2image_helper', 'js/') // eslint-disable-line

const features = loadState('text2image_helper', 'features')

if (features.picker_enabled === true || (features.picker_enabled === false && features.is_admin === true)) {
	registerCustomPickerElement('text2image_helper', async (el, { providerId, accessible }) => {
		const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
		Vue.mixin({ methods: { t, n } })
		const { default: Text2ImageHelperCustomPickerElement } = await import(/* webpackChunkName: "reference-picker-lazy" */'./views/Text2ImageHelperCustomPickerElement.vue')
		const Element = Vue.extend(Text2ImageHelperCustomPickerElement)
		const pickerEnabled = features.picker_enabled
		const vueElement = new Element({
			propsData: {
				providerId,
				accessible,
				pickerEnabled,
			},
		}).$mount(el)
		return new NcCustomPickerRenderResult(vueElement.$el, vueElement)
	}, (el, renderResult) => {
		renderResult.object.$destroy()
	})
}

registerWidget('text2image_helper', async (el, { richObjectType, richObject, accessible }) => {
	const { default: Vue } = await import(/* webpackChunkName: "vue-lazy" */'vue')
	Vue.mixin({ methods: { t, n } })
	const { default: Text2ImageHelperReferenceWidget } = await import(/* webpackChunkName: "reference-lazy" */'./views/Text2ImageHelperReferenceWidget.vue')
	const Widget = Vue.extend(Text2ImageHelperReferenceWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})
