<?php
define('INFINITY', 9999999);

$path=[];
$dis=[]; // 记录最小值
$finalLine=[];
function CreateGraph($Nv,&$graphArr)
{
    $graphArr = [];
    for ($i = 0; $i < $Nv; $i++) {
        for ($j = 0; $j < $Nv; $j++) {
            if ($i == $j) {
            $graphArr[$i][$j] = 0;
            } else {
             $graphArr[$i][$j] = INFINITY;
             }
        }
    }
 }

//初始化函数
function init(&$arr,$init,$n){
    for($i=0;$i<$n;$i++){
        $arr[$i]=$init;
    }
}

function getRoad($bid,$level){
    $database="SmartHospital";
    $tableRoom="room";
    $tableLine="line";
    $All=findAllRoom($database,$tableRoom,$bid,$level);//获取所有点位信息
    $road=[];
    for($i=0;$i<count($All);$i++){
        if($All[$i]["name"]=="电梯"||$All[$i]["name"]=="楼梯"||$All[$i]["name"]=="大门"){
            array_push($road,$All[$i]);
        }
    }
    return $road;
}
function getLine($bid,$level,$start,$target){
    $database="SmartHospital";
    $tableRoom="room";
    $tableLine="line";
    //只处理一张图的
    $line=[];//最终路径
    $sAll=findAllRoom($database,$tableRoom,$bid,$level);//获取所有点位信息
    $sLines=findAllLines($database,$tableLine,$bid,$level);//获取所有线段

    $sLen=count($sAll);//节点个数
    $slLen=count($sLines);//线的数量 等于 边的数量

    $sPosToIds=array();
    $sPosMap=array();
    //路 出口 门
    $sRoad=[];
    $sExit=[];
    $sDoor=[];

    //数据处理
    for($i=0;$i<$sLen;$i++){
        if($sAll[$i]["name"]=="辅助点"){
            array_push($sRoad,$sAll[$i]);
        }
        if($sAll[$i]["name"]=="电梯"||$sAll[$i]["name"]=="楼梯"){
            array_push($sExit,$sAll[$i]);
        }
        if($sAll[$i]["name"]=="大门"){
            array_push($sDoor,$sAll[$i]);
        }
        if($sAll[$i]["rid"]==$start){
             $startObj=$sAll[$i];
        }
        if($sAll[$i]["rid"]==$target){
             $targetObj=$sAll[$i];
        }
    }
    //合并有效图
    $sMap=array_merge($sRoad,$sExit,$sDoor);
    if($startObj["name"]!="大门"||$startObj["name"]!="辅助点"||$startObj["name"]!="电梯"||$startObj["name"]!="楼梯"){
        array_push($sMap,$startObj);
    }
    if($targetObj["name"]!="大门"||$targetObj["name"]!="辅助点"||$targetObj["name"]!="电梯"||$targetObj["name"]!="楼梯"){
        array_push($sMap,$targetObj);
    }
    //print_r($sMap);
    //获取全部的地址的映射坐标
    for($i=0;$i<$sLen;$i++){
        $sPosToIds[$sAll[$i]["position"]]=$i;
        $sPosAll[$i]=$sAll[$i]["position"];
    }

    $sRealPosToIds=[];
    for($i=0;$i<count($sMap);$i++){
        $sRealPosToIds[$sMap[$i]["position"]]=$i;
        if($startObj["position"]==$sMap[$i]["position"]){
            $startObj["id"]=$i;
        }
        if($targetObj["position"]==$sMap[$i]["position"]){
            $targetObj["id"]=$i;
        }
    }
    $sLineMap=[];
    $count=0;
    for($i=0;$i<count($sMap);$i++){
        for($j=0;$j<$slLen;$j++){
            if($sLines[$j]["startPoint"]==$sMap[$i]["position"]||$sLines[$j]["endPoint"]==$sMap[$i]["position"]){
                $sLineMap[$count++]=array("sp"=>$sLines[$j]["startPoint"],
                "ep"=>$sLines[$j]["endPoint"],
                "w"=>getDistance($sLines[$j]["startPoint"],$sLines[$j]["endPoint"])
                );
            }
        }
    }
    //开始寻路
    $len=count($sMap);
    CreateGraph($len,$graph);//初始化邻接表
    if($graph){
        if($len>0){
            for($i = 0; $i <count($sLineMap); $i++) {
                $v1=$sRealPosToIds[$sLineMap[$i]['sp']];//出
                $v2=$sRealPosToIds[$sLineMap[$i]['ep']];//入
                $weight=$sLineMap[$i]['w'];//权
                if(!isset($sRealPosToIds[$sLineMap[$i]['ep']])||!isset($sRealPosToIds[$sLineMap[$i]['sp']])){
                continue;
                }
                //echo $sLineMap[$i]['sp'].",".$sLineMap[$i]['ep']."->".$v1." ".$v2." ".$weight."\n";
                $graph[$v1][$v2] = $weight;
                // 如果是无向图，还需要插入逆向的边
                $graph[$v2][$v1] = $weight;
            }
        }
    }
    $reverse=Dijkstra($graph, $startObj["id"],$targetObj["id"]);
    $tmp=array_reverse($reverse);
    for($i=0;$i<count($tmp);$i++){
        $line[$i] = $sMap[$tmp[$i]]["position"];
    }
    return $line;
}

function getDistance($a,$b){
//x,y
    $aArr=explode(',',$a);
    $bArr=explode(',',$b);
    $x=$aArr[0]-$bArr[0];
    $y=$aArr[1]-$bArr[1];
    $res=round(sqrt($x*$x + $y*$y));
    return $res;
}
function Dijkstra($graph,$s,$d){
    $row = count($graph);
    $col =  count($graph[0]);
    init($dist,INFINITY,$row);
    init($Blackened ,0,$row);
    init($pathlength ,0,$row);
    init($parent,-1,$row);
    $dist[$s]= 0;

    for($count = 0;$count<$row - 1;$count++){
        $u=MinDistance($dist, $Blackened);
        if($u==INFINITY){
             break;
        }else{
            $Blackened[$u]= 1;
        }
        for($v=0;$v < $row;$v++){
             if($Blackened[$v] == 0&&$graph[$u][$v]&& $dist[$u]+$graph[$u][$v]<$dist[$v]){
                $parent[$v]=$u;
                $pathlength[$v]=$pathlength[$parent[$v]]+1;
                $dist[$v]=$dist[$u]+$graph[$u][$v];
             }else if($Blackened[$v]== 0 && $graph[$u][$v] &&$dist[$u]+$graph[$u][$v]== $dist[$v] && $pathlength[$u]+1<$pathlength[$v]){
                $parent[$v]=$u;
                $pathlength[$v]=$pathlength[$u] + 1;
             }
        }
    }

    if($dist[$d]!= INFINITY){
        //print_r($parent);
        return Path($parent,$d);
        //PrintPath($parent, $d);
    }else{
    return [];
        //echo $s."到".$d."没有路径";
    }
}
function Path($parent,$d){
    $res=[];
    while($parent[$d]!=-1){
        array_push($res,$d);
        $d=$parent[$d];
    }
    array_push($res,$d);
    return $res;
}
function PrintPath($parent, $d){
     if($parent[$d]==-1){
        echo $d." ";
        return;
     }
     PrintPath($parent, $parent[$d]);
     echo "->".$d." ";
}

function MinDistance($dist, $Blackened){
    $min=INFINITY;
    for($i=0; $i < count($dist);$i++){
        if(!$Blackened[$i]&&$dist[$i]<$min){
             $min = $dist[$i];
             $Min_index = $i;
        }
    }
    if($min==INFINITY){
        return INFINITY;
    }else{
        return $Min_index;
    }
}
?>