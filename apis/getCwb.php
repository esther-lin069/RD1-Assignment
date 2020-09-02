<?php 

header("Content-Type:text/html; charset=utf-8");
require('weather_PDO.php');


$table = ['weather_week','weather_72h'];
$url = array(
    0 => 'https://opendata.cwb.gov.tw/api/v1/rest/datastore/F-D0047-091?Authorization=CWB-ED7050C5-A1A2-4409-B999-A4CCC6F4AFBB&elementName=MinT,MaxT,Wx,PoP12h',
    1 => 'https://opendata.cwb.gov.tw/api/v1/rest/datastore/F-D0047-089?Authorization=CWB-ED7050C5-A1A2-4409-B999-A4CCC6F4AFBB&elementName=Wx,T,AT,PoP6h'
);


//main function
//updateData();

getData_Week('臺中市');


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

function getData_Week($location){
    $pod = new cwbPDO();
    $rows = $pod->all('weather_week','`elementName`,`value`,`Date`',"`location` = '$location' AND `Time` = '06:00' ORDER BY `Date`");

    foreach($rows as $row){
        echo($row['Date'])." :";
        echo($row['elementName'])." / ";
        echo($row['value'])."<br>";
    }
    echo "<hr>";
    var_dump($rows);
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
            //getWeatherThreeDays($data_location);
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

                foreach($time['elementValue'] as $value){
                    $v = $value['value'];
                    if(is_numeric($v) || $v == " ")
                        $v= intval($v);
                    
                    $list['value'] = $v;

                    //do upload here
                    addData($table[0],$list);
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

function getTaiwan($data){

    foreach($data as $location){

        //$list['location'] = $location['locationName'];
        echo "('".$location['locationName']."')";
        echo ",";
        //$list = array();
        
    }
}

?>