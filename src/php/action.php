<?php
header("Content-type:text/html;charset=utf-8");
header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); // 允许请求的类型
header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
header('Access-Control-Allow-Headers: Content-Type,Content-Length,Authorization,Accept-Encoding,X-Requested-with, Origin'); // 设置允许自定义请求头的字段

include_once("mysql.php");
include_once("sql_log.php");
include_once("line.php");
//两个表
$database="SmartHospital";
$tableBuilder="builder";
$tableRoom="room";
$tableLine="line";
//$post=file_get_contents('php://input');//只能传递json字符串
//var_dump(json_decode($post,true));//true为数组
if($_SERVER["REQUEST_METHOD"]=="POST"){
	connect($Mysql_host,$Mysql_username,$Mysql_password);//连接
    $case=$_POST['type'];
    switch($case){
            case "readAll":
                $resBuilder=getAll($database,$tableBuilder);
                $resRoom=getAll($database,$tableRoom);
                if(is_array($resBuilder) && is_array($resRoom)){
                    $return = array("code" => 1);
                    $return["builders"] = $resBuilder;
                    $return["rooms"] = $resRoom;
                    $json=json_encode($return);
                    echo $json;
                }else{
                    $return=array("code" => 0);
                    $json=json_encode($return);
                    echo $json;
                }
            break;
            case "search":
                 $query=$_POST['query'];
                 $resSearch= searchData($database,$tableRoom,'name',$query);
                if(is_array($resSearch)){
                    $return=array("code"=>1);
                    $return['data']=$resSearch;
                    $json=json_encode($return);
                    echo $json;
                }else{
                    $return=array("code"=>0);
                    $return['err']=$resSearch;
                    $json=json_encode($return);
                    echo $json;
                }
            break;
            case "getLines":
            $bid=$_POST["bid"];
            $level=$_POST["level"];
            $res=findAllLines($database,$tableLine,$bid,$level);
            if(is_array($res)){
                $return["code"]=1;
                $return["data"]=$res;
                $json=json_encode($return);
                echo $json;
            }else{
                $return["code"]=0;
                $json=json_encode($return);
                echo $json;
            }
            break;
            case "getLine":
            //获取路线
            $start=$_POST['start'];//传入rid
            $target=$_POST['target'];//传入rid
            $sObj=findData($database,$tableRoom,'rid',$start);//开始对象
            $tObj=findData($database,$tableRoom,'rid',$target);//结束对象
            //判断情况即可 出三张或者四张图 通过
            if($sObj["bid"]==$tObj["bid"]){
                //1.同一栋楼
                if($sObj["level"]==$tObj["level"]){
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"],$tObj["rid"]);
                }else{
                    $sRoad=getRoad($sObj["bid"],$sObj["level"]);
                    $tRoad=getRoad($tObj["bid"],$tObj["level"]);
                    $min=INFINITY;
                    for($i=0;$i<count($sRoad);$i++){
                        for($j=0;$j<count($tRoad);$j++){
                            if($sRoad[$i]["name"]=="大门"){
                                break;
                            }
                            if($tRoad[$j]["name"]=="大门"){
                                continue;
                            }
                            if($min>getDistance($sRoad[$i]["position"],$tRoad[$j]["position"])){
                                $SR=$sRoad[$i];
                                $TR=$tRoad[$j];
                            }
                        }
                    }
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]],["bid"=>$tObj["bid"],"level"=>$tObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"], $SR["rid"]);
                    $return["line"][1]=getLine($tObj["bid"],$tObj["level"],$TR["rid"], $tObj["rid"]);
                }
            }else{
            //不在同一栋楼
                $sLevel=$sObj["level"];
                $tLevel=$tObj["level"];
                if($sLevel=="1"&&$tLevel=="1"){
                    //都在一楼的话 直接通过大门到达
                    $sRoad=getRoad($sObj["bid"],$sObj["level"]);
                    $tRoad=getRoad($tObj["bid"],$tObj["level"]);
                    for($i=0;$i<count($sRoad);$i++){
                        if($sRoad[$i]["name"]=="大门"){
                            $SE=$sRoad[$i];//S exit
                            break;
                        }
                    }
                     for($j=0;$j<count($tRoad);$j++){
                        if($tRoad[$j]["name"]=="大门"){
                            $TS=$tRoad[$j];
                            break;
                        }
                    }
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]],
                    ["bid"=>$tObj["bid"],"level"=>$tObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"], $SE["rid"]);
                    $return["line"][1]=getLine($tObj["bid"],$tObj["level"],$TS["rid"], $tObj["rid"]);
                }else if($sLevel=="1"&&$tLevel!="1"){
                    $sRoad=getRoad($sObj["bid"],$sObj["level"]);//1
                    $mRoad=getRoad($tObj["bid"],"1");//中转 1
                    $tRoad=getRoad($tObj["bid"],$tObj["level"]);//n
                    for($i=0;$i<count($sRoad);$i++){
                        if($sRoad[$i]["name"]=="大门"){
                            $SE=$sRoad[$i];//S exit
                            break;
                        }
                    }
                    for($j=0;$j<count($mRoad);$j++){
                        if($mRoad[$j]["name"]=="大门"){
                            $MS=$mRoad[$j];//m start
                            continue;
                        }
                        if($mRoad[$j]["name"]!="大门"){
                            $ME=$mRoad[$j];//m end
                            continue;
                        }
                    }
                     for($k=0;$k<count($tRoad);$k++){
                        if($tRoad[$k]["name"]!=="大门"){
                            $TE=$tRoad[$k];
                            break;
                        }
                    }
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]],
                    ["bid"=>$tObj["bid"],"level"=>"1"],
                    ["bid"=>$tObj["bid"],"level"=>$tObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"], $SE["rid"]);
                    $return["line"][1]=getLine($tObj["bid"],"1",$MS["rid"],$ME["rid"]);
                    $return["line"][2]=getLine($tObj["bid"],$tObj["level"],$TE["rid"], $tObj["rid"]);
                }else if($sLevel!="1"&&$tLevel=="1"){
                    $sRoad=getRoad($sObj["bid"],$sObj["level"]);//1
                    $mRoad=getRoad($sObj["bid"],"1");//中转 1
                    $tRoad=getRoad($tObj["bid"],$tObj["level"]);//n
                    for($i=0;$i<count($sRoad);$i++){
                        if($sRoad[$i]["name"]=="电梯"||$sRoad[$i]["name"]=="楼梯"){
                            $SE=$sRoad[$i];//S exit
                            break;
                        }
                    }
                    for($j=0;$j<count($mRoad);$j++){
                        if($mRoad[$j]["name"]=="大门"){
                            $ME=$mRoad[$j];//m e
                            continue;
                        }
                        if($mRoad[$j]["name"]!=="大门"){
                            $MS=$mRoad[$j];//m s
                            continue;
                        }
                    }
                     for($j=0;$j<count($tRoad);$j++){
                        if($tRoad[$j]["name"]=="大门"){
                            $TS=$tRoad[$j];
                            break;
                        }
                    }
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]],
                    ["bid"=>$sObj["bid"],"level"=>"1"],
                    ["bid"=>$tObj["bid"],"level"=>$tObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"], $SE["rid"]);
                    $return["line"][1]=getLine($sObj["bid"],1,$MS["rid"], $ME["rid"]);
                    $return["line"][2]=getLine($tObj["bid"],$tObj["level"],$TS["rid"], $tObj["rid"]);
                }else{
                    //不同楼体  四张图
                    $sRoad=getRoad($sObj["bid"],$sObj["level"]);//n
                    $smRoad=getRoad($sObj["bid"],"1");//中转 1
                    $tmRoad=getRoad($tObj["bid"],"1");//中转 1
                    $tRoad=getRoad($tObj["bid"],$tObj["level"]);//n
                    for($i=0;$i<count($sRoad);$i++){
                        if($sRoad[$i]["name"]=="电梯"||$sRoad[$i]["name"]=="楼梯"){
                            $SE=$sRoad[$i];//S exit
                            break;
                        }
                    }
                    for($j=0;$j<count($smRoad);$j++){
                        if($smRoad[$j]["name"]=="大门"){
                            $SME=$smRoad[$j];//m e
                            continue;
                        }
                        if($mRoad[$j]["name"]!=="大门"){
                            $SMS=$smRoad[$j];//m s
                            continue;
                        }
                    }
                    for($j=0;$j<count($tmRoad);$j++){
                        if($tmRoad[$j]["name"]=="大门"){
                            $TMS=$tmRoad[$j];//m e
                            continue;
                        }
                        if($tmRoad[$j]["name"]!=="大门"){
                            $TME=$tmRoad[$j];//m s
                            continue;
                        }
                    }
                     for($j=0;$j<count($tRoad);$j++){
                        if($tRoad[$j]["name"]!=="大门"){
                            $TE=$tRoad[$j];
                            break;
                        }
                    }
                    $return["map"]=[["bid"=>$sObj["bid"],"level"=>$sObj["level"]],
                    ["bid"=>$sObj["bid"],"level"=>"1"],
                    ["bid"=>$tObj["bid"],"level"=>"1"],
                    ["bid"=>$tObj["bid"],"level"=>$tObj["level"]]];
                    $return["line"][0]=getLine($sObj["bid"],$sObj["level"],$sObj["rid"], $SE["rid"]);
                    $return["line"][1]=getLine($sObj["bid"],"1",$SMS["rid"], $SME["rid"]);
                    $return["line"][2]=getLine($tObj["bid"],"1",$TMS["rid"], $TME["rid"]);
                    $return["line"][3]=getLine($tObj["bid"],$tObj["level"],$TE["rid"], $tObj["rid"]);
                }
            }
            $return["code"]=1;
            $json=json_encode($return);
             echo $json;
            //outputPath($startObj["id"],$targetObj["id"]);
            break;
            case "setPoint":
                 $data=array("name"=>$_POST["name"],"description"=>$_POST["description"],"bid"=>$_POST["bid"],"builderName"=>$_POST["builderName"],"level"=>$_POST["level"],"position"=>$_POST["position"],"location"=>$_POST["location"]);
                 $res=addData($database,$tableRoom,$data);
                if($res=='success'){
                    $return=array("code"=>1);
                    $json=json_encode($return);
                    echo $json;
                }else{
                    $return=array("code"=>0);
                    $return['err']=$res;
                    $json=json_encode($return);
                    echo $json;
                }
            break;
            case 'setLine':
             $data=array("bid"=>$_POST["bid"],"level"=>$_POST["level"],"bid"=>$_POST["bid"],"startPoint"=>$_POST["startPoint"],"endPoint"=>$_POST["endPoint"]);
             $res=addData($database,$tableLine,$data);
            if($res=='success'){
                $return=array("code"=>1);
                $json=json_encode($return);
                echo $json;
            }else{
                $return=array("code"=>0);
                $return['err']=$res;
                $json=json_encode($return);
                echo $json;
            }
            break;
     }
}
?>
