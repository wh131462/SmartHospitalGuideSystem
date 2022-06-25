<template>
	<div class="tip" v-if="isTipShow">
		<p>快速双击屏幕以进入医院内部，拖拽以调转视角。</p>
	</div>
	<div id="three" v-show="isShow">
	</div>
</template>
<script>
	import * as THREE from 'three';
	import {
		FBXLoader
	} from 'three/examples/jsm/loaders/FBXLoader';
	import {
		OBJLoader
	} from 'three/examples/jsm/loaders/OBJLoader';
	import {
		MTLLoader
	} from 'three/examples/jsm/loaders/MTLLoader';
	import{
		VRMLLoader
	} from 'three/examples/jsm/loaders/VRMLLoader'
	import {
		RGBELoader
	} from 'three/examples/jsm/loaders/RGBELoader';
	import OrbitControls from 'three-orbitcontrols';
	//全局变量来使用threejs
	let scene = {},
		camera = {},
		renderer = {},
		controls = {},
		tex = {},
		loader = {},
		light={},
		group={},
  ani;
  let THAT;
	export default {
		emits: {
			"finishModel": null,
			"enterMain": null
		},
		data: () => {
			return {
				isTipShow: true,
				isShow:true
			}
		},
		mounted() {
			this.initModel();
		},
		methods: {
			enterMain(){
				console.log("进入之前关闭自己");
				this.isShow=false;
				this.$emit('enterMain')
			},
			initModel() {
        THAT=this;
				let _this = this;
				renderer = new THREE.WebGLRenderer();
				camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 5000);
				camera.position.z =30;
				camera.position.x = 0;
				camera.position.y = 40;
				scene = new THREE.Scene();
				scene.add(camera)
				//鼠标控制镜头
				controls = new OrbitControls(camera, renderer.domElement);
				controls.maxPolarAngle = 1.5;
				controls.minPolarAngle = 0.3;
				controls.enableZoom = false;
				
				var point = new THREE.PointLight(0xffffff, 0.3) //光源设置
				point.position.set(300, 400, 200) //点光源位置
				scene.add(point) //将光源添加到场景中
				
				var ambient = new THREE.AmbientLight(0xffffff, 0.4) //环境光
				ambient.position.set(200, 300, 200) //点光源位置
				scene.add(ambient)
				
				var directionalLight = new THREE.DirectionalLight(0xffffff, 1) //方向光
				directionalLight.position.set(150, 300, 200)
				scene.add(directionalLight)
				
				const pm = new THREE.PMREMGenerator(renderer); //使用hdr作为背景色
				pm.compileEquirectangularShader()

				tex = new RGBELoader();
				tex.load('./assets/hdr/je_gray_park_4k.hdr', function(texture) {
					const envMap = pm.fromEquirectangular(texture).texture;
					pm.dispose()

					scene.environment = envMap;
					scene.background = envMap;
				});
				//渲染器样式
				renderer.setClearColor(new THREE.Color('black'));
				renderer.setSize(window.innerWidth, window.innerHeight);
				renderer.domElement.setAttribute("style", " height:100%; 100%;")
				document.querySelector('#three').appendChild(renderer.domElement);
        document.querySelector('#three').addEventListener('touchend', 	onMouseDblClick, false);
				//FBX
				loader = new FBXLoader();
				loader.load('./assets/model/1.fbx', function ( object ) {
					console.log("FBX");
					object.traverse( function ( child ) {
						if ( child.isMesh ) {
							child.castShadow = true;
							child.receiveShadow = true;
						}
					} );
					scene.add( object );
					_this.$emit('finishModel');
					//模型加载后三秒 关闭提示
					setTimeout(()=>{
						_this.closeTip()
					},5000);
				});

				this.animate();
			},
			animate() {
				ani=requestAnimationFrame(this.animate);
				renderer.render(scene, camera);
			},
			closeTip() {
				this.isTipShow = false;
			},
      closeMe(){
         cancelAnimationFrame(ani);// Stop the animation
         renderer.domElement.addEventListener('touchend', null, false); //remove listener to render
            scene = {};
            camera = {};
            renderer = {};
            controls = {};
            tex = {};
            loader = {};
            light={};
            group={};
         document.querySelector("#three").style.display="none";
      }
		}
	}
  let isTouch=false;
function 	onMouseDblClick(event){
  if(!isTouch){
    isTouch=true;
    console.log(isTouch)
    setTimeout(()=>{isTouch=false;console.log(isTouch)},500)
    return;
  }
  THAT.closeMe();
  THAT.$emit('enterMain');
  // 获取 raycaster 和所有模型相交的数组，其中的元素按照距离排序，越近的越靠前
  //var intersects = getIntersects(event);

  // // 获取选中最近的 Mesh 对象
  // if (intersects.length != 0 && intersects[0].object instanceof THREE.Mesh) {
  //  let selectObject = intersects[0].object;
  //   changeMaterial(selectObject);
  //   console.log(selectObject)
  // } else {
  //   alert("未选中 Mesh!");
  // }
}
  function getIntersects(event) {
    event.preventDefault(); // 阻止默认的点击事件执行, https://developer.mozilla.org/zh-CN/docs/Web/API/Event/preventDefault
    console.log("event.clientX:" + event.clientX);
    console.log("event.clientY:" + event.clientY);

    //声明 rayCaster 和 mouse 变量
    let rayCaster = new THREE.Raycaster();
    let mouse = new THREE.Vector2();

    //通过鼠标点击位置，计算出raycaster所需点的位置，以屏幕为中心点，范围-1到1
    mouse.x = ((event.clientX - document.querySelector('#three').getBoundingClientRect().left) / document.querySelector('#three').offsetWidth) * 2 - 1;
    mouse.y = -((event.clientY - document.querySelector('#three').getBoundingClientRect().top) / document.querySelector('#three').offsetHeight) * 2 +1; //这里为什么是-号，没有就无法点中

    //通过鼠标点击的位置(二维坐标)和当前相机的矩阵计算出射线位置
    rayCaster.setFromCamera(mouse, camera);

    //获取与射线相交的对象数组， 其中的元素按照距离排序，越近的越靠前。
    //+true，是对其后代进行查找，这个在这里必须加，因为模型是由很多部分组成的，后代非常多。
    console.log(group)
    let intersects = rayCaster.intersectObjects(group.children, true);

    //返回选中的对象

    // console.log(intersects)

    return intersects;
  }
</script>

<style scoped>
	.tip {
		width: 60%;
		position: fixed;
		z-index: 2;
		font-size: 0.8rem;
		background-color: aliceblue;
		border-radius: 10px;
		left: 50%;
		transform: translateX(-50%);
		color: #2C3E50;
		background-color: rgba(255, 255, 255, 0.5);
	}
	.background{
		width: 100vw;
		height: 100vh;
		background-color: rgba(0,0,0,0);
		position:fixed;
		top: 0;
		left: 0;
		z-index: 2;
	}
	#three {
		width: 100%;
		height: 100%;
		position: absolute;
		top: 0;
		left: 0;
	}
</style>
