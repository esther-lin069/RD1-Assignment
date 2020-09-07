<?php 

header("Content-Type:text/html; charset=utf-8");
require('weather_PDO.php');


$table = ['weather_week','weather_72h','rain'];
$url = array(
    0 => 'https://opendata.cwb.gov.tw/api/v1/rest/datastore/F-D0047-091?Authorization=CWB-ED7050C5-A1A2-4409-B999-A4CCC6F4AFBB&elementName=MinT,MaxT,Wx,PoP12h',
    1 => 'https://opendata.cwb.gov.tw/api/v1/rest/datastore/F-D0047-089?Authorization=CWB-ED7050C5-A1A2-4409-B999-A4CCC6F4AFBB&elementName=Wx,T,AT,PoP6h',
    2 => 'https://opendata.cwb.gov.tw/api/v1/rest/datastore/O-A0002-001?Authorization=CWB-ED7050C5-A1A2-4409-B999-A4CCC6F4AFBB&elementName=HOUR_24,RAIN&parameterName=CITY'
);


// get_action
if(isset($_GET['location'])){
    $location = $_GET['location'];
    //echo $location;

    if($_GET['type'] == 'week'){
        getData_Week($table[0],$location);
    }
    else if($_GET['type'] == '72h'){
        getData_72h($table[1],$location);
    }
    else if($_GET['type'] == 'rain'){
        getData_rain($table[2],$location);
    }
    else if($_GET['type'] == 'now'){
        getNow($location);
    }
}
else{
    if(isset($_GET['update'])){
        updateData();
    }
    if(isset($_GET['img-id'])){
        getWxImg($_GET['img-id']);
    }
}


//main function
//updateData();
//getJson($url[0],$table[0]);



function updateData(){
    global $table;
    global $url;

    for($i=0 ; $i<count($table) ;$i++){
        dropTable($table[$i]);
        getJson($url[$i],$table[$i]);
    }    
}


//PDO functions
function addData($table,$data){
    $pod = new cwbPDO();
    $pod->add($table,$data);
}

function dropTable($table){
    $pod = new cwbPDO();
    $pod->dropTable($table);
}

function getData_Week($table,$location){
    $pod = new cwbPDO();
    $sql = <<<sql
    SELECT `Date`,GROUP_CONCAT(concat('"',`elementName`,'"',':"',`value`,'"') separator ',') as 'data' 
    FROM (SELECT `Date`,elementName,value  FROM `$table` WHERE location = '$location' AND `Time` = '06:00' 
    ORDER BY `Date`)as t GROUP BY `Date`
    sql;
    $rows = $pod->get($table,$sql);
    foreach($rows as $k => $v){
        $rows[$k]['data'] = "{".$rows[$k]['data']."}";
    }    

    echo (json_encode($rows));
}

function getWxImg($id){
    $pod = new cwbPDO();
    $row = $pod->all('wx_img','`img`',"`id` = '$id'");

    print_r($row[0]['img']);
}

function getData_72h($table,$location){
    $pod = new cwbPDO();
    $sql = <<<sql
    SELECT DATE_FORMAT(dataTime, '%d日%H時') as 'dataTime', GROUP_CONCAT(concat('"',`elementName`,'"',':"',`value`,'"') separator ',')as 'data' 
    FROM (SELECT * FROM `$table` WHERE location = '$location' and dataTime BETWEEN DATE_SUB(NOW(),INTERVAL 3 HOUR) AND CURDATE() +2 
    ORDER BY `dataTime`,id) as t GROUP BY `dataTime` LIMIT 8
    sql;
    $rows = $pod->get($table,$sql);
    foreach($rows as $k => $v){
        $rows[$k]['data'] = "{".$rows[$k]['data']."}";
    }    

    echo (json_encode($rows));
}

function getNow($location){
    $pod = new cwbPDO();
    $sql = <<<sql
    SELECT DATE_FORMAT(dataTime, '%W %H:%i') as 'dataTime', GROUP_CONCAT(concat('"',`elementName`,'"',':"',`value`,'"') separator ',')as 'data' 
    FROM (SELECT * FROM `weather_72h` WHERE location = '$location' 
    ORDER BY `dataTime`,id) as t GROUP BY `dataTime` limit 1
    sql;
    $row = $pod->get('weather_72h',$sql);

    echo json_encode($row[0]);
}

function getData_rain($table,$location){
    $pod = new cwbPDO();
    $rows = $pod->all($table,'`stationId`,`location`,`hour`,`day`',"`city` = '$location'");

    echo json_encode($rows);
}

// cut api data
function getJson($url,$table){
    $json = file_get_contents($url);
    $data = json_decode($json,true);
    
    switch($table){
        case 'weather_week':
            $data_location = $data['records']['locations'][0]['location'];
            getWeatherWeek($data_location);
        break;

        case 'weather_72h':
            $data_location = $data['records']['locations'][0]['location'];
            getWeatherThreeDays($data_location);
        break;
        case 'rain':
            $data_location = $data['records']["location"];
            getRain($data_location);
        break;
        default:
            echo ("No such function");

    }
        

}


//grab data from api
function getWeatherWeek($data){
    global $table;
    $list = [];

    foreach($data as $location){

        $list['location'] = $location['locationName'];
        foreach($location['weatherElement'] as $name){

            $list['elementName'] = $name['elementName'];
            foreach($name['time'] as $time){

                $date = date_create($time['startTime']);
                $list['Date'] = date_format($date,"m/d");
                $list['Time'] = date_format($date,"H:i");

                if($list['elementName'] == 'Wx'){
                    $v = [];
                    foreach($time['elementValue'] as $value){
                        array_push($v,$value['value']);
                    }
                    $list['value'] = implode(",",$v);
                }
                else{
                    foreach($time['elementValue'] as $value){
                        $v = $value['value'];
                        if($v == " ")
                            $v= '-';
                        $list['value'] = $v;

                    }
                }
                //do upload here
                addData($table[0],$list);
                
                
            }
            
        }
        //echo "<hr>";
        $list = array();
        
    }
}


function getWeatherThreeDays($data){

    global $table;
    $list = [];

    foreach($data as $location){

        $list['location'] = $location['locationName'];
        foreach($location['weatherElement'] as $name){

            $list['elementName'] = $name['elementName'];
            foreach($name['time'] as $time){
                if($list['elementName'] == 'T' || $list['elementName'] == 'AT'){
                    $list['dataTime'] = $time['dataTime'];
                }
                else{
                    $list['dataTime'] = $time['startTime'];
                }                

                foreach($time['elementValue'] as $value){
                    $v = $value['value'];
                    if(is_numeric($v) || $v == " ")
                        $v= intval($v);
                    
                        $list['value'] = $v;

                        //do upload here
                        addData($table[1],$list);
                        //var_dump($list);
                        //echo "<br>";                
                break;
                }
                
            }
            
        }
        //echo "<hr>";
        $list = array();
        
    }
}

function getRain($data){
    global $table;
    $list = [];
    //var_dump($data);

    foreach($data as $location){

        $t = array(
            "stationId" => $location['stationId'],
            "city" => $location['parameter'][0]["parameterValue"],
            "location" => $location['locationName']
        );
        $list = $t;
        
        foreach($location['weatherElement'] as $weather){

            if($weather["elementName"] == 'RAIN'){
                
                $list["hour"] = $weather["elementValue"];
            }
            else{
                $list["day"] = $weather["elementValue"];
            }
            
            
        }
        addData($table[2],$list);
        //print_r($list);
        $list = array();
        //echo "<br>";

    }
}

function getTaiwan($data){

    foreach($data as $location){

        //$list['location'] = $location['locationName'];
        echo "('".$location['locationName']."')";
        echo ",";
        //$list = array();
        
    }
}

?>