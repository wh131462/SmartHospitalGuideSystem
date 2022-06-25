<template>
		<init-page v-if="state=='init'"></init-page>
		<three-component @finishModel="EnterThree()" @enterMain="EnterMain()"></three-component>
		<main-com v-if="state=='main'"></main-com>
</template>

<script>
	import InitPage from './components/InitPage.vue'
	import ThreeComponent from './components/threeComponent.vue';
	import MainComponent from './components/main.vue'
	export default{
		components:{'init-page':InitPage,'three-component':ThreeComponent,'main-com':MainComponent},
		data: () => {
			return{
				state:"init"//init three main
			}
		},
		mounted() {
			
		},
		methods:{
			EnterThree() {
				this.state= 'three';
				console.log("进入场景")
			},
			EnterMain(){
				this.state='main';
				console.log("进入主功能界面");
			}
		}
	}
</script>

<style>
	#app {
		font-family: Avenir, Helvetica, Arial, sans-serif;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		text-align: center;
		color: #2c3e50;
		margin-top: 60px;
	}
  div{
    margin: 0;
    padding: 0;
  }
</style>
