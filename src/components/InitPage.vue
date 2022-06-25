<template>
	<div class="init">
		<div class="background">
			<img src="https://131462.wang/master/assets/img/background.jpg">
		</div>
		<div class="title">
			<p>智慧医院电子导览系统</p>
			<p class="tip">{{mes}}</p>
		</div>
	</div>
</template>

<script>
	export default {
		data() {
			return {
				mes: "欢迎使用本系统进行导航~"
			}
		},
		mounted() {},
		methods: {

		}
	}
</script>

<style scoped>
	*{
		margin: 0;
		padding: 0;
	}
	.init{
		position: fixed;
		width: 100vw;
		height: 100vh;
		z-index: 999;
	}
	.background {
		width: 100vw;
		height: 100vh;
		position: fixed;
		top: 0;
		left: 0;
		z-index: -1;
	}

	.title {
		width: 100%;
		height: 1.875rem;
		font-size: 1.2rem;
		font-family: "YouYuan";
		color: #111;
		position: fixed;
		left: 0;
		bottom: 20%;
	}

	.tip {
		font-size: 0.8rem;
		line-height: 20px;
		margin: 10px;
		color: #7d8686;
		background-color: rgba(0, 0, 0, 0);
	}
</style>
