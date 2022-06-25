<template>
  <div class="loading" v-if="isLoading">
    <div class="loading-icon-back"><div class="loading-icon"></div></div>
  </div>
  <div class="search">
    <input v-model="searchContent" placeholder="请输入想要去的地方" @input="closeSearch()" @keyup="search($event)" type="text">
    <span class="icon iconfont icon-sousuo" @click="search()"></span>
  </div>
  <div class="result" v-if="isResult">
    <p style="font-size: 0.7rem;text-align: center;margin: 20px 0;" v-if="resultList.length==0">
      抱歉，未能搜索到您的目的地。
    </p>
    <div class="result-item" v-for="item in resultList">
      <div class="item-info">
        <p class="info-title">{{ item.name }}</p>
        <p class="info-description">{{ item.description }}</p>
        <p class="info-location">{{ item.location }}</p>
      </div>
      <div class="navigation" @click="navigateTo(item.rid)">去导航</div>
    </div>
  </div>
  <div class="nav" v-show="isNav">
    <canvas id="navCanvas"></canvas>
    <p class="nav-tip">{{navTip}}</p>
    <div class="nav-btn"><button @click="nav()">{{navStep==0?"完成导航":"已到达当前目标点"}}</button></div>
  </div>
  <div class="main">
    <canvas id="canvas"></canvas>
  </div>
  <div class="level">
    <!-- 楼层选择 -->
    <el-select v-model="builder" @change="changeBuilder()" clearable placeholder="选择建筑主体">
      <el-option
          v-for="item in builders"
          :key="item.bid"
          :label="item.name"
          :value="item.bid"
      />
    </el-select>
    <!-- 楼层选择 -->
    <el-select v-model="level" @change="changeLevel()" clearable placeholder="选择楼层">
      <el-option
          v-for="item in levels"
          :key="item.level"
          :label="item.level+'层'"
          :value="item.level"
      />
    </el-select>
  </div>
  <div class="controls">
    <div class="control-item"><span class="iconfont icon icon-tuodong" :class="{'active':controlState=='drag'}"
                                    @click="setState('drag')"></span></div>
    <div class="control-item"><span class="iconfont icon icon-suofang" :class="{'active':controlState=='zoom'}"
                                    @click="setState('zoom')"></span></div>
    <div class="control-item"><span class="iconfont icon icon-biaoji" :class="{'active':controlState=='tag'}"
                                    @click="setState('tag')"></span></div>
    <div class="control-item"><span class="iconfont icon icon-zhongzhi"
                                    @click="resetCanvas()"></span></div>
  </div>

  <div class="setStart" v-if="isStart">
    <div v-if="startStep==0">
      <h1 class="startTitle">您是从那栋楼进入的呢？</h1>
      <div class="selectBuilder">
        <div class="builderItem" :class="{'active':this.builder==builder.bid}" v-for="(builder,index) in builders"
             @click="setStartBuilder(index)">
          <p>{{ builder.name }}</p>
        </div>
      </div>
      <div class="startBtn">
        <el-button type="primary" @click="nextStep()">下一步</el-button>
      </div>
    </div>
    <div v-if="startStep==1">
      <h1 class="startTitle">您目前所在的楼层是？</h1>
      <div class="selectLevel">
        <!-- 楼层选择 -->
        <el-select v-model="level" clearable placeholder="选择楼层">
          <el-option
              v-for="item in levels"
              :key="item.level"
              :label="item.level+'层'"
              :value="item.level"
          />
        </el-select>
      </div>
      <div class="startBtn">
        <el-button type="primary" @click="preStep()">上一步</el-button>
        <el-button type="primary" @click="nextStep()">下一步</el-button>
      </div>
    </div>
    <div v-show="startStep==2">
      <h1 class="startTitle">在地图上标记你的起点</h1>
      <div class="btns">
        <div class="btn"><span class="iconfont icon icon-tuodong" :class="{'active':startControlState=='drag'}"
                               @click="setStartState('drag')"></span></div>
        <div class="btn"><span class="iconfont icon icon-biaoji" :class="{'active':startControlState=='tag'}"
                               @click="setStartState('tag')"></span></div>
        <div class="btn"><span class="iconfont icon icon-suofang" :class="{'active':startControlState=='zoom'}"
                               @click="setStartState('zoom')"></span></div>
        <div class="btn"><span class="iconfont icon icon-zhongzhi"
                               @click="resetStart()"></span></div>
      </div>
      <canvas id="startCanvas"></canvas>
      <div class="startBtn">
        <el-button type="primary" @click="setStart">确定</el-button>
        <el-button type="danger" @click="cancelStart">取消</el-button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {},
  data() {
    return {
      isLoading:false,
      controlState: "drag",
      startControlState: "drag",//drag tag zoom
      startStep: 0,
      isStart: true,

      level: "",//当前楼层
      levels: [],//楼层
      builder: "",//建筑
      builders: [],//建筑主体
      //搜索
      searchContent: "",
      //canvas核心对象
      canvas: {},
      objs: [],
      nowBack: {},
      ctx: {},
      size: {width: 0, height: 0},//尺寸

      startCanvas: {},
      startBack: {},//开始地图
      startObjs: [],//所有的对象
      startCtx: {},
      startSize: {width: 0, height: 0},//尺寸

      isNav: false,
      navres:{},
      navTip:"",
      navCanvas: {},
      navBack: {},//开始地图
      navObjs: [],//所有的对象
      navCtx: {},
      navSize: {width: 0, height: 0},//尺寸
      lines:[],
      navStep:0,

      moveStart: {},//移动开始
      moveNow: {},
      moveEnd: {},
      isMove: false,
      zoomStart: 0,
      zoomNow: 0,
      zoomEnd: 0,
      //开始和结束的信息对象
      start: {},
      target: {},

      isResult: false,//搜索结果
      resultList: []
    }
  },
  mounted() {
    this.getAllData()
  },
  methods: {
    setStart() {
      //进入主体
      if (!this.start) {
        this.showMessage("提示", "请选择起始点。")
        return;
      }
      this.initCanvas();
      this.isStart = false;
    },
    cancelStart() {
      //直接remake
      window.location.reload();
    },
    setStartBuilder(index) {
      this.builder = this.builders[index].bid;
      this.levels = this.builders[index].maps;
      console.log("获取到楼层",this.levels)
    },
    preStep() {
      this.startStep--;
    },
    nextStep() {
      this.startStep++;
      if (this.startStep == 2) {
        this.initStart();
      }
    },
    //设置状态
    setStartState(state) {
      this.startControlState = state;
    },
    setState(state) {
      this.controlState = state;
    },
    resetStart() {
      console.log("初始化")
      this.clearCanvas('start')
      this.startCanvas.width = this.startSize.width;
      this.startCanvas.height = this.startSize.height;
      this.setStartImage(this.startBack.src);
    },
    resetCanvas() {
      this.clearCanvas('canvas')
      this.canvas.width = this.size.width;
      this.canvas.height = this.size.height;
      this.setImage(this.nowBack.src);
    },
    resetNav() {
      this.clearCanvas('nav')
      this.navCanvas.width = this.navSize.width;
      this.navCanvas.height = this.navSize.height;
      this.setNavImg(this.navBack.src);
    },
    getAllData() {
      let _this = this;
      //this.showMessage("提示", "请先选择当前的起始点");
      let form = new FormData();
      form.append("type", "readAll")
      fetch('https://131462.wang/master/php/action.php',
          {
            method: "post",
            body: form
          })
          .then(r => r.json())
          .then(res => {
            _this.builders = res.builders;
            _this.builders?.forEach((item,idx)=>{
              if(!item.maps)return;
              _this.builders[idx].maps=JSON.parse(item.maps.replaceAll("'","\""));
            })
            _this.rooms = res.rooms;
            console.log(res)
          }).catch(err => {
        console.log(err)
      });
    },
    initStart() {
      console.log("开始初始化")
      let _this = this;
      //初始化背景图片
      this.startCanvas = document.querySelector("#startCanvas");
      this.startCtx = this.startCanvas.getContext('2d');
      this.startSize = {
        width: document.querySelector(".main").clientWidth,
        height: document.querySelector(".main").clientHeight * 0.8
      };
      //canvas处理 初始化画布
      this.startCanvas.width = this.startSize.width;
      this.startCanvas.height = this.startSize.height;
      let index=0;
      this.levels?.forEach((level,idx)=>{
        if(level.level==this.level){
          index=idx;
        }}
      )
      this.setStartImage(this.levels[index].src);

      //开始 touch
      this.startCanvas.ontouchstart = function (event) {
        switch (_this.startControlState) {
          case 'drag':
            _this.isMove = true;
            let touch = event.touches[0];
            _this.moveStart = {x: touch.pageX, y: touch.pageY};
            console.log(touch, _this.moveStart)
            break;
          case 'tag':

            break;
          case 'zoom':
            console.log(event.touches)
            let touches = event.touches;
            if (touches.length == 2) {
              _this.zoomStart = _this.getDistance(touches[0], touches[1])
              console.log(_this.getDistance(touches[0], touches[1]))
            }

            break;
        }

      }
      this.startCanvas.ontouchmove = function (event) {
        switch (_this.startControlState) {
          case 'drag':
            if (!_this.isMove) {
              return;
            }
            let touch = event.touches[0];
            _this.moveNow = {x: touch.pageX, y: touch.pageY};
            let diff = {x: _this.moveNow.x - _this.moveStart.x, y: _this.moveNow.y - _this.moveStart.y};
            _this.startObjs?.forEach(item => {
              console.log("转换前", item, item?.zoomPro)
              if (item.type == 'image') {
                item.zoomPro.x = item.zoomPro.initX + diff.x;
                item.zoomPro.y = item.zoomPro.initY + diff.y;

              } else {
                item.x = item.initX + diff.x;
                item.y = item.initY + diff.y;
              }
              console.log("转换后", item, item?.zoomPro)
            });
            console.log("move", touch, _this.moveNow, diff)
            _this.printCanvas('start');

            break;
          case 'tag':

            break;
          case 'zoom':
            let touches = event.touches;
            if (touches.length == 2) {
              _this.zoomNow = _this.getDistance(touches[0], touches[1]);
              let diff = _this.zoomNow - _this.zoomStart;
              _this.startObjs?.forEach(item => {
                console.log("转换前", item, item?.zoomPro)
                if (item.type == 'image') {
                  //图像可以通过宽度来设置
                  item.zoomPro.width = item.zoomPro.initW + diff;
                  item.zoomPro.height = item.zoomPro.initH + diff;

                } else {
                  //圆和线  都需要改变xy  而不需要改变宽度
                  //item.width = item.initW + diff;
                  item.x = item.initX + diff / 2;
                  item.y = item.initY + diff / 2;
                }
                console.log("转换后", item, item?.zoomPro)
              });
              console.log("zoom", diff)
              _this.printCanvas('start');
            }
            break;
        }

      }
      this.startCanvas.ontouchend = function (event) {

        console.log(_this.startControlState)
        switch (_this.startControlState) {
          case 'drag':
            _this.isMove = false;
            _this.printCanvas('start')
            break;
          case 'tag':
            let pos = _this.windowToCanvas(event.changedTouches[0].pageX, event.changedTouches[0].pageY, _this.startCanvas)
            console.log(_this.windowToCanvas(event.changedTouches[0].pageX, event.changedTouches[0].pageY, _this.startCanvas));
            //重新标记点 要清除画布 并回复基础
            _this.clearCanvas('start')
            _this.setStartImage(_this.startBack.src);
            setTimeout(() => {


              let tar, tarIdx, min = -1;
              let rooms=_this.rooms.filter((room)=>{
                return room.bid==_this.builder&&room.level==_this.level;
              });
              rooms?.forEach((room, idx) => {
                let position = room.position?.split(',');
                let tx = position[0] / _this.startBack.zoomPro.rate,
                    ty = position[1] / _this.startBack.zoomPro.rate;
                let comx = _this.startBack.zoomPro.x + tx,
                    comy = _this.startBack.zoomPro.y + ty;//目前的位置
                let x = comx - pos.x,
                    y = comy - pos.y;
                //通过距离的对比来获取最近的点
                let distance = Math.sqrt((x * x) + (y * y));
                if (min == -1 || min > distance) {
                  min = distance;
                  tar = {obj: room, x: comx, y: comy};
                  tarIdx = idx;
                }
              });
              _this.start = rooms[tarIdx].rid;
              _this.startCtx.beginPath();
              _this.startCtx.arc(tar.x, tar.y, 5, 0, 360);
              _this.startCtx.closePath();
              _this.startCtx.lineWidth = 5;
              _this.startCtx.strokeStyle = "rgb(0,25,255)";
              _this.startCtx.stroke();
              //存入
              _this.startObjs.push({
                type: 'arc',
                initX: tar.x,
                initY: tar.y,
                x: tar.x,
                y: tar.y,
                width: 5,
                initW: 5,
                startAngle: 0,
                endAngle: 360,
                lineWidth: 5,
                style: "rgb(0,25,255)"
              });
            })
            break;
          case 'zoom':
            console.log('zoom')
            break;
        }
        console.log(event);
      }
    },
    initNav(){
      console.log("开始初始化")
      let _this = this;
      //初始化背景图片
      _this.navCanvas = document.querySelector("#navCanvas");
      _this.navCtx = _this.navCanvas.getContext('2d');
      _this.navSize = {
        width: document.querySelector(".main").clientWidth,
        height: document.querySelector(".main").clientHeight*0.8
      };
      //canvas处理 初始化画布
      this.navCanvas.width = this.navSize.width;
      this.navCanvas.height = this.navSize.height;
      this.builders.filter(item=>{
        return item.bid==this.start;
      })
      //获取起点
      let mapUrl="";
      this.builders?.forEach((item,idx)=>{
        if(item.bid==this.navres.guide[0].bid){
          this.builders[idx].maps?.forEach(map=>{
            if(map.level==this.navres.guide[0].level){
              mapUrl=map.src;
            }
          })
        }
      });

      this.setNavImg(mapUrl);//设置背景图方法
      setTimeout(()=>{
        this.setPoint(...this.navres.line[0]);
        if(this.navres.guide?.length>1){

          let b=this.builders.filter(b=>b.bid==this.navres.guide[1].bid);
          this.navTip=`请沿此路线前往${b[0].name}的第${this.navres.guide[1]?.level}层。`;
          console.log("设置提示语",b,this.navTip)
        }

      },500)
      //开始 touch
      this.navCanvas.ontouchstart = function (event) {
        if(event.touches.length==1){
          _this.isMove = true;
          let touch = event.touches[0];
          _this.moveStart = {x: touch.pageX, y: touch.pageY};
          console.log(touch, _this.moveStart)
        }
        console.log(event.touches)
        let touches = event.touches;
        if (touches.length == 2) {
          _this.zoomStart = _this.getDistance(touches[0], touches[1])
          console.log(_this.getDistance(touches[0], touches[1]))
        }
      }
      this.navCanvas.ontouchmove = function (event) {
        if (event.touches.length==1) {
          let touch = event.touches[0];
          _this.moveNow = {x: touch.pageX, y: touch.pageY};
          let diff = {x: _this.moveNow.x - _this.moveStart.x, y: _this.moveNow.y - _this.moveStart.y};
          _this.navObjs?.forEach(item => {
            console.log("转换前", item, item?.zoomPro)
            if (item.type == 'image') {
              item.zoomPro.x = item.zoomPro.initX + diff.x;
              item.zoomPro.y = item.zoomPro.initY + diff.y;

            } else {
              item.x = item.initX + diff.x;
              item.y = item.initY + diff.y;
            }
            console.log("转换后", item, item?.zoomPro)
          });
          console.log("move", touch, _this.moveNow, diff)
          _this.printCanvas('nav');
        }

        let touches = event.touches;
        if (touches.length == 2) {
          _this.zoomNow = _this.getDistance(touches[0], touches[1]);
          let diff = _this.zoomNow - _this.zoomStart;
          _this.startObjs?.forEach(item => {
            console.log("转换前", item, item?.zoomPro)
            if (item.type == 'image') {
              //图像可以通过宽度来设置
              item.zoomPro.width = item.zoomPro.initW + diff;
              item.zoomPro.height = item.zoomPro.initH + diff;

            } else {
              //圆和线  都需要改变xy  而不需要改变宽度
              //item.width = item.initW + diff;
              item.x = item.initX + diff / 2;
              item.y = item.initY + diff / 2;
            }
            console.log("转换后", item, item?.zoomPro)
          });
          console.log("zoom", diff)
          _this.printCanvas('nav');
        }

      }
      this.navCanvas.ontouchend = function (event) {
        if(event.touches.length==1){
          _this.isMove = false;
          _this.printCanvas('nav')
        }
      }
    },
    //输出整个画布
    printCanvas(type) {
      if (type == "start") {
        console.log("输出整个画布", this.startObjs);
        let _this = this;
        _this.startCtx.clearRect(0, 0, _this.startCanvas.width, _this.startCanvas.height);
        this.startObjs?.forEach((item) => {
          console.log(item)
          if (item.type == "image") {
            _this.startCtx.drawImage(item, item.zoomPro.x, item.zoomPro.y, item.zoomPro.width, item.zoomPro.height);
          } else if (item.type == "arc") {
            _this.startCtx.beginPath();
            _this.startCtx.arc(item.x, item.y, item.width, item.startAngle, item.endAngle);
            _this.startCtx.closePath();
            _this.startCtx.lineWidth = item.lineWidth;
            _this.startCtx.strokeStyle = item.style;
            _this.startCtx.stroke();
          } else if (item.type == "line") {
            _this.startCtx.beginPath();
            _this.startCtx.moveTo(0, 0);
            _this.startCtx.closePath();
            _this.startCtx.lineWidth = item.lineWidth;
            _this.startCtx.strokeStle = item.style;
            _this.startCtx.stroke();
          }
        })
      } else if (type == 'canvas') {
        console.log("输出整个画布", this.objs);
        let _this = this;
        _this.ctx.clearRect(0, 0, _this.canvas.width, _this.canvas.height);
        this.objs?.forEach((item) => {
          console.log(item)
          if (item.type == "image") {
            _this.ctx.drawImage(item, item.zoomPro.x, item.zoomPro.y, item.zoomPro.width, item.zoomPro.height);
          } else if (item.type == "arc") {
            _this.ctx.beginPath();
            _this.ctx.arc(item.x, item.y, item.width, item.startAngle, item.endAngle);
            _this.ctx.closePath();
            _this.ctx.lineWidth = item.lineWidth;
            _this.ctx.strokeStyle = item.style;
            _this.ctx.stroke();
          } else if (item.type == "line") {
            _this.ctx.beginPath();
            _this.ctx.moveTo(0, 0);
            _this.ctx.closePath();
            _this.ctx.lineWidth = item.lineWidth;
            _this.ctx.strokeStle = item.style;
            _this.ctx.stroke();
          }
        })
      }else if(type=="nav"){
        console.log("输出整个画布", this.navObjs);
        let _this = this;
        _this.navCtx.clearRect(0, 0, _this.navCanvas.width, _this.navCanvas.height);
        this.navObjs?.forEach((item) => {
          console.log(item)
          if (item.type == "image") {
            _this.navCtx.drawImage(item, item.zoomPro.x, item.zoomPro.y, item.zoomPro.width, item.zoomPro.height);
          } else if (item.type == "arc") {
            _this.navCtx.beginPath();
            _this.navCtx.arc(item.x, item.y, item.width, item.startAngle, item.endAngle);
            _this.navCtx.closePath();
            _this.navCtx.lineWidth = item.lineWidth;
            _this.navCtx.strokeStyle = item.style;
            _this.navCtx.stroke();
          } else if (item.type == "line") {
            _this.navCtx.beginPath();
            _this.navCtx.moveTo(0, 0);
            _this.navCtx.closePath();
            _this.navCtx.lineWidth = item.lineWidth;
            _this.navCtx.strokeStle = item.style;
            _this.navCtx.stroke();
          }
        })
      }
    },
    //清空画布
    clearCanvas(type = 'canvas') {
      if (type == 'canvas') {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.objs = [];
      } else if (type == 'start') {
        this.startCtx.clearRect(0, 0, this.startCanvas.width, this.startCanvas.height);
        this.startObjs = [];
      }else{
        this.navCtx.clearRect(0, 0, this.navCanvas.width, this.navCanvas.height);
        this.navObjs = [];
      }
    },
    //屏幕点在画布上对应的位置
    windowToCanvas(x, y, canvas) {
      let box = canvas.getBoundingClientRect();  //这个方法返回一个矩形对象，包含四个属性：left、top、right和bottom。分别表示元素各边与页面上边和左边的距离
      return {
        x: x - box.left - (box.width - canvas.width) / 2,
        y: y - box.top - (box.height - canvas.height) / 2
      };
    },
    //缩放 勾股定理方法-求两点之间的距离
    getDistance(p1, p2) {
      var x = p2.pageX - p1.pageX,
          y = p2.pageY - p1.pageY;
      return Math.sqrt((x * x) + (y * y));
    },
    initCanvas() {
      //初始化背景图片
      let _this = this;
      this.canvas = document.querySelector("#canvas");
      this.ctx = this.canvas.getContext('2d');
      this.size = {
        width: document.querySelector(".main").clientWidth,
        height: document.querySelector(".main").clientHeight
      };
      //canvas处理 初始化画布
      this.canvas.width = this.size.width;
      this.canvas.height = this.size.height;
      let index=0;
      this.levels?.forEach((level,idx)=>{
        if(level.level==this.level){
          index=idx;
        }}
      )
      this.setImage(this.levels[index].src);
      //开始 touch
      this.canvas.ontouchstart = function (event) {
        switch (_this.controlState) {
          case 'drag':
            _this.isMove = true;
            let touch = event.touches[0];
            _this.moveStart = {x: touch.pageX, y: touch.pageY};
            console.log(touch, _this.moveStart)
            break;
          case 'tag':

            break;
          case 'zoom':
            console.log(event.touches)
            let touches = event.touches;
            if (touches.length == 2) {
              _this.zoomStart = _this.getDistance(touches[0], touches[1])
              console.log(_this.getDistance(touches[0], touches[1]))
            }

            break;
        }

      }
      this.canvas.ontouchmove = function (event) {
        switch (_this.controlState) {
          case 'drag':
            if (!_this.isMove) {
              return;
            }
            let touch = event.touches[0];
            _this.moveNow = {x: touch.pageX, y: touch.pageY};
            let diff = {x: _this.moveNow.x - _this.moveStart.x, y: _this.moveNow.y - _this.moveStart.y};
            _this.objs?.forEach(item => {
              console.log("转换前", item, item?.zoomPro)
              if (item.type == 'image') {
                item.zoomPro.x = item.zoomPro.initX + diff.x;
                item.zoomPro.y = item.zoomPro.initY + diff.y;

              } else {
                item.x = item.initX + diff.x;
                item.y = item.initY + diff.y;
              }
              console.log("转换后", item, item?.zoomPro)
            });
            console.log("move", touch, _this.moveNow, diff)
            _this.printCanvas('canvas');

            break;
          case 'tag':

            break;
          case 'zoom':
            let touches = event.touches;
            if (touches.length == 2) {
              _this.zoomNow = _this.getDistance(touches[0], touches[1]);
              let diff = _this.zoomNow - _this.zoomStart;
              _this.objs?.forEach(item => {
                console.log("转换前", item, item?.zoomPro)
                if (item.type == 'image') {
                  //图像可以通过宽度来设置
                  item.zoomPro.width = item.zoomPro.initW + diff;
                  item.zoomPro.height = item.zoomPro.initH + diff;

                } else {
                  //圆和线  都需要改变xy  而不需要改变宽度
                  //item.width = item.initW + diff;
                  item.x = item.initX + diff / 2;
                  item.y = item.initY + diff / 2;
                }
                console.log("转换后", item, item?.zoomPro)
              });
              console.log("zoom", diff)
              _this.printCanvas('canvas');
            }
            break;
        }

      }
      this.canvas.ontouchend = function (event) {
        console.log(_this.controlState)
        switch (_this.controlState) {
          case 'drag':
            _this.isMove = false;
            _this.printCanvas('canvas')
            break;
          case 'tag':
            let pos = _this.windowToCanvas(event.changedTouches[0].pageX, event.changedTouches[0].pageY, _this.canvas)
            console.log(_this.windowToCanvas(event.changedTouches[0].pageX, event.changedTouches[0].pageY, _this.canvas));
            //重新标记点 要清除画布 并回复基础
            _this.clearCanvas('canvas')
            _this.setImage(_this.nowBack.src);
            setTimeout(() => {
              let tar, tarIdx, min = -1;
              let rooms=_this.rooms.filter((room)=>{
                return room.bid==_this.builder&&room.level==_this.level;
              });
              rooms?.forEach((room, idx) => {
                let position = room.position?.split(',');
                let tx = position[0] / _this.nowBack.zoomPro.rate,
                    ty = position[1] / _this.nowBack.zoomPro.rate;
                let comx = _this.nowBack.zoomPro.x + tx,
                    comy = _this.nowBack.zoomPro.y + ty;//目前的位置
                let x = comx - pos.x,
                    y = comy - pos.y;
                //通过距离的对比来获取最近的点
                let distance = Math.sqrt((x * x) + (y * y));
                if (min == -1 || min > distance) {
                  min = distance;
                  tar = {obj: room, x: comx, y: comy};
                  tarIdx = idx;
                }
              });
              _this.target = rooms[tarIdx].rid;
              _this.ctx.beginPath();
              _this.ctx.arc(tar.x, tar.y, 5, 0, 360);
              _this.ctx.closePath();
              _this.ctx.lineWidth = 5;
              _this.ctx.strokeStyle = "rgb(0,25,255)";
              _this.ctx.stroke();
              //存入图像
              _this.objs.push({
                type: 'arc',
                initX: tar.x,
                initY: tar.y,
                x: tar.x,
                y: tar.y,
                width: 5,
                initW: 5,
                startAngle: 0,
                endAngle: 360,
                lineWidth: 5,
                style: "rgb(0,25,255)"
              });
            })
            _this.$msgbox({
              title: '提示',
              message:"导航到此处？",
              showCancelButton: true,
              confirmButtonText: '确定',
              cancelButtonText: '取消'
            }).then(action => {
              if(action=="confirm"){
                _this.navigateTo(_this.target);
              }
            });
            break;
          case 'zoom':
            console.log('zoom')

            break;
        }
        console.log(event);
      }
    },
    setStartImage(url) {
      //图片处理
      let _this = this;
      this.startBack = new Image();
      this.startBack.src = url;
      this.startBack.onload = function () {
        let rate = this.width / _this.startSize.width;
        let width = this.width / rate;
        let height = this.height / rate;
        let x = 0;
        let y = 0,
            initW = width,
            initH = height,
            initX = x,
            initY = y;

        _this.startBack.zoomPro = {
          rate,
          initW,
          initH,
          width,
          height,
          initX,
          initY,
          x,
          y
        };
        _this.startCtx.drawImage(this, x, y, width, height);
        _this.startBack.type = 'image';
        //存入图像
        _this.startObjs.push(_this.startBack);
      }

    },
    setImage(url) {
      //图片处理
      let _this = this;
      this.nowBack = new Image();
      this.nowBack.src = url;
      this.nowBack.onload = function () {
        let rate = this.width / _this.size.width;
        let width = this.width / rate;
        let height = this.height / rate;
        let x = 0;
        let y = (_this.size.height - height) / 2,
            initW = width,
            initH = height,
            initX = x,
            initY = y;
        _this.nowBack.zoomPro = {
          rate,
          initW,
          initH,
          width,
          height,
          initX,
          initY,
          x,
          y
        };

        _this.ctx.clearRect(0, 0, _this.canvas.width, _this.canvas.height);
        _this.ctx.drawImage(this, x, y, width, height);
        _this.nowBack.type = 'image';
        //存入图像
        _this.objs.push(_this.nowBack);
      }
    },
    //剩余参数来获取所有的点位
    setPoint(...rest) {
      console.log(rest.length);
       let rate=this.navBack.zoomPro.rate;
      //绘制 起点到终点
      for (let i = 0; i < rest.length; i++) {
        if (i == 0) {
          this.navCtx.beginPath();
          this.navCtx.arc(rest[i].x/rate, rest[i].y/rate, 5, 0, 360);
          this.navCtx.closePath()
          this.navCtx.lineWidth = 5;
          this.navCtx.strokeStyle = "rgba(114,128,252,0.6)";
          this.navCtx.stroke();
          //绘制文字
          this.navCtx.font = 'bold 15px SongTi';
          this.navCtx.textAlign = 'center';
          this.navCtx.textBaseline = 'bottom';
          this.navCtx.fillStyle = '#2c2c2c';
          this.navCtx.fillText("起点", rest[i].x/rate + 5, rest[i].y/rate + 35, 100)

          this.navCtx.beginPath();//开始绘制线条，若不使用beginPath，则不能绘制多条线条
          this.navCtx.lineTo(rest[i].x/rate, rest[i].y/rate);
        } else if (i == rest.length - 1) {
          this.navCtx.lineTo(rest[i].x/rate, rest[i].y/rate);
          this.navCtx.closePath();//结束绘制线条，不是必须的
          this.navCtx.lineWidth = 2;//设置线条宽度
          this.navCtx.strokeStyle = "blue";//设置线条颜色
          this.navCtx.stroke();//用于绘制线条
          //终点
          //画一个圆
          this.navCtx.beginPath();
          this.navCtx.arc(rest[i].x/rate, rest[i].y/rate, 5, 0, 360);
          this.navCtx.closePath()
          this.navCtx.lineWidth = 5;
          this.navCtx.strokeStyle = "rgba(114,128,252,0.6)";
          this.navCtx.stroke();
          //绘制文字
          this.navCtx.font = 'bold 15px SongTi';
          this.navCtx.textAlign = 'center';
          this.navCtx.textBaseline = 'bottom';
          this.navCtx.fillStyle = '#2c2c2c';
          this.navCtx.fillText("终点", rest[i].x/rate + 5, rest[i].y/rate + 35, 100)
        } else {
          this.navCtx.lineTo(rest[i].x/rate, rest[i].y/rate);
          this.navCtx.closePath();//结束绘制线条，不是必须的
          this.navCtx.lineWidth = 2;//设置线条宽度
          this.navCtx.strokeStyle = "blue";//设置线条颜色
          this.navCtx.stroke();//用于绘制线条
          this.navCtx.beginPath();//开始绘制线条，若不使用beginPath，则不能绘制多条线条
          this.navCtx.lineTo(rest[i].x/rate, rest[i].y/rate);
        }
      }
      console.log("线条绘制完成")
    },
    showMessage(Title, Text) {
      this.$alert(Text, Title, {
        confirmButtonText: '确定',
        callback: action => {
          console.log(action)
        }
      });
    },
    closeSearch() {
      if (!this.searchContent) {
        this.isResult = false;
        this.resultList = [];
      }
    },
    search(e = null) {
      if (e != null) {
        if (e.keyCode != 13) {
          return;
        }
      }
      this.isLoading=true;
      let result = this.rooms.filter(item => {
        return item.name.includes(this.searchContent);
      })
      this.isLoading=false;
      this.isResult = true;
      this.resultList = result;
    },
    nav(){
      if(this.navStep==0){
        //结束
        this.navTip="";
        this.isNav=false;
        this.isResult=false;
        this.searchContent="";
      }else{
        this.navStep--;
        //公式为  len-1-step
        let index=this.navres.guide.length - 1 - this.navStep;
        this.clearCanvas('nav');
        let mapUrl="";
        this.builders?.forEach((item,idx)=>{
          if(item.bid==this.navres.guide[index].bid){
              this.builders[idx].maps?.forEach(map=>{
                if(map.level==this.navres.guide[index].level){
                  mapUrl=map.src;
                }
              })
          }
        });
        this.setNavImg(mapUrl);//设置背景图方法
        setTimeout(()=>{
          console.log(index)
          this.setPoint(...this.navres.line[index]);
          if(index<this.navres.guide?.length-1){
            console.log(index+1);
            let b=this.builders?.filter(b=>{
              return b.bid==this.navres?.guide[index+1].bid
            });
            this.navTip=`请沿此路线前往${b[0].name}的第${this.navres.guide[index+1].level}层。`;
          }else{
            this.navTip="";
          }
        },500)
        //输出提示

        this.setNavImg(mapUrl);//设置背景图方法
        setTimeout(()=>{
           this.setPoint(...this.lines[index]);
        },500)

      }

    },
    setNavImg(url){
      //图片处理
      let _this = this;
      this.navBack = new Image();
      this.navBack.src = url;
      this.navBack.onload = function () {
        let rate = this.width / _this.navSize.width;
        let width = this.width / rate;
        let height = this.height / rate;
        let x = 0;
        let y =0,
            initW = width,
            initH = height,
            initX = x,
            initY = y;
        _this.navBack.zoomPro = {
          rate,
          initW,
          initH,
          width,
          height,
          initX,
          initY,
          x,
          y
        };

        _this.navCtx.clearRect(0, 0, _this.navCanvas.width, _this.navCanvas.height);
        _this.navCtx.drawImage(this, x, y, width, height);
        _this.navBack.type = 'image';
        //存入图像
        _this.navObjs.push(_this.navBack);
      }
    },
    navigateTo(target) {
      console.log("导航")
      if(!target){
        return;
      }
      let _this = this;
      _this.target = target;

      let form = new FormData();
      form.append("type", "getLine")
      form.append("start", this.start)
      form.append("target", this.target)
      this.isLoading = true;
      fetch('https://131462.wang/master/php/action.php',
          {
            method: "post",
            body: form
          })
          .then(r => r.json())
          .then(res => {
            if (res.code == 1) {
              _this.isLoading = false;
              _this.isNav = true;
              for (let i = 0; i < res.line?.length; i++) {
                _this.lines[i]=res.line[i].map(item=>_this.toPos(item));
              }
              _this.navres.guide=res.map;
              _this.navres.line=_this.lines;
              _this.navStep=_this.navres.guide.length-1;
              _this.initNav();
              console.log(_this.navres)
            }
          }).catch(err => {
            _this.isLoading = false;
            _this.$alert("遇到未知错误！导航失败！","提示");
            console.log(err)
      })
    },
    toPos(pos){
      let arr=pos?.split(",");
      return {x:arr[0],y:arr[1]};
    },
    changeBuilder() {
      //切换建筑物了
      this.clearCanvas('canvas');//清除画布
      let back;
      this.builders?.forEach(res => {
        if (res.bid == this.builder) {
          let maps = res.maps;
          back = maps[0].src;
          this.level=maps[0].level;
          this.levels = maps;
        }
      })
      this.setImage(back)
    },
    changeLevel() {
      this.clearCanvas('canvas');//清除画布
      this.levels?.forEach(res => {
        if (res.level == this.level) {
          this.setImage(res.src);
        }
      })
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
}
.loading{
  user-select: none;
  width: 100vw;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  background: rgba(0,0,0,0.3);
  z-index: 9999;

}
.loading-icon-back{
  width: 50px;
  height: 50px;
  border: #5d81ff  solid 5px;
  border-radius: 50%;
  position: fixed;
  top: 50%;
  left: 50%;
  transform:translate(-50%,-50%) rotate(0deg);
  animation: rotate 10s infinite linear ;
}
.loading-icon{
  content: "";
  width: 15px;
  height: 15px;
  background:rgba(255,255,255,1);
  border-radius: 50%;
  position: absolute;
  left: 0px;
  top: -5px;
}
@keyframes rotate {
  from{
    transform:translate(-50%,-50%)  rotate(0deg);
  }
  to{
    transform:translate(-50%,-50%)  rotate(3600deg);
  }

}
.setStart {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 100;
  background: url("https://131462.wang/master/assets/img/background.jpg");
}

.startTitle {
  margin: 20px;
}

.btns {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}

.btn {
  width: 30px;
  height: 30px;
  margin: 0 15px;
}

.active {
  color: #5d81ff;
  box-shadow: rgba(255, 255, 255, .8) 0px 0px 2px;
}

.btn span {
  font-size: 30px;
}

.selectBuilder {
  width: 100%;
  margin: 0 auto;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}

.builderItem {
  margin: 20px;
  width: 100px;
  height: 100px;
  background: #b9e9ff;
  box-shadow: rgba(0, 0, 0, 0.3) 0px 0px 1px;
}

.builderItem p {
  line-height: 100px;
  text-align: center;
}

.builderItem:active {
  background: #5d81ff;
}

.startBtn {
  position: fixed;
  bottom: 15px;
  display: flex;
  flex-direction: row;
  justify-content: space-evenly;
  align-items: center;
  margin: 20px auto;
  width: 100%;
}

.main {
  width: 100vw;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  margin: 0;
  padding: 0;
  border: none;
  overflow: hidden;
  z-index: -1;
}

#canvas {
  margin: 0;
  padding: 0;
  border: none;
}


.search {
  width: 100%;
  height: 30px;
  position: fixed;
  top: 15px;
  left: 0;
  z-index: 100;
}

.search input {
  width: 95%;
  height: 100%;
  border: none;
  border-radius: 10px;
  box-shadow: rgba(0, 0, 0, 0.2) 0px 0px 5px;
  outline: none;
  text-indent: 1em;
}

.search span {
  position: absolute;
  right: 15px;
  font-size: 25px;
}

.result {
  width: 100vw;
  height: calc(100% - 45px);
  position: fixed;
  top: 45px;
  z-index: 101;
  overflow-y: scroll;
  background: #F7F7F7;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
}

.result .result-item {
  width: 90%;
  margin: 10px auto;
  box-shadow: rgba(0, 0, 0, 0.5) 0px 0px 5px;
  background: rgba(255, 255, 255, 0.3);
}

.result .item-info {
  width: 100%;
  height: auto;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: flex-start;
}

.result .info-title {
  font-size: 1.2rem;
  font-weight: bold;
  color: #5d81ff;
  margin: 10px 10px 0px;
}

.result .info-description {
  color: #005eb8;
  font-size: 0.8rem;
  margin: 10px 10px;
}

.result .info-location {
  color: #2c3e50;
  font-size: 0.7rem;
  margin: 0 10px;

}

.result .navigation {
  margin: 0px 10px 10px;
  text-align: right;
  color: #5d81ff;
}

.nav{
  width:100vw;
  height: 100vh;
  background: #EEEEEE;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 200;
}
nav-tip{
  width: 90%;
  margin: 0;
  position: fixed;
  top: 40%;
  color: #5d81ff;
  font-size: 0.9rem;
}
.nav-btn{
  position: fixed;
  bottom: 0;

  width: 100%;
  height: 40px;
  text-align: center;
}
.nav-btn button{
  border: none;
  font-size: 1.2rem;
  font-family: "YouYuan";
  color: #FFFFFF;
  background: #0000ff;
  width: 95%;
  height: 40px;
  border-radius: 20px;

}

.level {
  width: 150px;
  position: fixed;
  bottom: 15px;
  left: 15px;
  z-index: 99;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
}

.controls {
  width: 160px;
  position: fixed;
  bottom: 15px;
  right: 15px;
  z-index: 99;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
}

.control-item {
  width: 30px;
  height: 30px;
  margin: 0 5px;
  font-size: 30px;
  line-height: 30px;
  text-align: center;
}
.icon{
  font-size: 30px;
}
</style>
