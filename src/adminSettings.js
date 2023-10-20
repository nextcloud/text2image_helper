// SPDX-FileCopyrightText: Sami Finnilä <sami.finnila@nextcloud.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

import Vue from 'vue'
import AdminSettings from './Components/AdminSettings.vue'
Vue.mixin({ methods: { t, n } })

const View = Vue.extend(AdminSettings)
new View().$mount('#text2image_helper_prefs')
