import { createApp } from 'vue'

import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import '../public/assets/icon/iconfont.css'
import '../public/assets/icon/iconfont.js'
import App from './App.vue'

createApp(App).use(ElementPlus).mount('#app')
