<?php

class cwbPDO extends PDO{
    public static $dbtype = 'mysql';
    public static $dbhost = 'localhost';
    public static $dbport = 8889;
    public static $dbname = 'RD1-Assignment';
    public static $dbuser = 'root';
    public static $dbpass = 'root';
    public static $DB = null;
    

    public function __construct(){
        self::connect();
        //buffered: Client緩衝區
        self::$DB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        //預處理
        self::$DB->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        self::execute('SET NAMES utf8');
    }
    public function __destruct(){
        self::close();
    }

    //connect
    public function connect(){
        try{
            self::$DB = new PDO(self::$dbtype . ':host=' . self::$dbhost . ';port=' . self::
            $dbport . ';dbname=' . self::$dbname, self::$dbuser, self::$dbpass, array(PDO::
            ATTR_PERSISTENT => true));
        }
        catch(PDOException $e){
            die("Connect Error Infomation:" . $e->getMessage());
        }
    }
    //close
    public function close(){
        self::$DB = null;
    }

    public function now() {
        return date("Y-m-d H:i:s");
    }

    private function getCode($table, $args){
        $code = '';
        if(is_array($args)){
            foreach ($args as $k => $v){
                // if($v == '')
                //     continue;
                $code .="`$k` = '$v',";
            }
        }
        $code = substr($code, 0, -1);
        return $code;
    }


    //執行sql語句
    public function execute($sql) {
        $prepare = self::$DB->prepare($sql);
        return $prepare->execute();
    }

    // get: fetch_row
    public function fetRowCount($table, $field = '*', $where = false){
        $sql = "SELECT COUNT({$field}) AS num FROM `$table`";
        $sql .= ($where) ? "WHERE $where" : "";
        
        return (self::$DB->query($sql))->rowCount();
    }

    // post: insert
    public function add($table, $args){
        $sql = "INSERT INTO `$table` SET";
        $sql .=self::getCode($table, $args);

        return self::execute($sql);

    }

    // drop: TRUNCATE table
    public function dropTable($table){
        $sql = "TRUNCATE TABLE `$table`";

        return self::execute($sql);
    }

    // get: fetch_all
    public function all($table, $field = '*', $where = false){
        $sql = "SELECT {$field} FROM `{$table}`";
        $sql .= ($where) ? "WHERE $where" : '';

        $stmt = self::$DB->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function get($table, $sql){
        $stmt = self::$DB->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }




}

// $data = [ 'location' => '雲林縣',
//           'elementName' => 'PoP12h',
//           'startTime' => '2020-08-28 12:00:00',
//           'value' => '30'];

// $week_pod = new cwbPDO();
// echo $week_pod->add('weather_week',$data);

?>