<?php
$def_csv_options = array();
$def_csv_options['TERMINATED'] 		= ",";
$def_csv_options['ENCLOSED'] 		= "\"";
$def_csv_options['ESCAPED'] 		= "\\\\";
$def_csv_options['L_TERMINATED'] 	= "\n";


class mysql {
	var $name;
 	var $connect_id;
	var $result;
	var $csv_options = array();
	var $debug;
	var $tpl = '_error_db.tpl';
	
	function mysql(){
	        $this->name = "MySQL"; 
		$this->connect_id = mysql_connect("localhost", "root", "hequ3");
                if ( !$this->connect_id ) {
                
                     print '<h1>Доступ к сайту временно заблокирован</h1>' ;
                     print mysql_error();
                     exit;  
                } 
	}
	function free(){
	 	return @mysql_close($this->connect_id);
	}
	function free_result() {
		return @mysql_free_result($this->connect_id);
	}
	function error(){
		return mysql_errno($this->connect_id).": ".@mysql_error($this->connect_id);
	}
	function errno(){
	 	return @mysql_errno($this->connect_id);
	}
        function query($sql){
		if ( $this->result != '' ) {
		      //  mysql_free_result($this->connect_id); 
		}
		$this->result=@mysql_db_query("house", $sql, $this->connect_id);

		if ($this->debug && FALSE === $this->result) {
		    $er['mysql'] = @mysql_error($this->connect_id);
		    $er['sql'] = $sql;
		    $er['src'] = __FILE__ .' '. __LINE__.' '.__CLASS__.' '.__FUNCTION__;
		    
		    print_r($er);
		    
		    $this->error[] = $er;
		    
		}
		
		 
	}
	function db_query($sql, $dbname=''){
		if($dbname == ''){
		    $this->result=@mysql_db_query($this->ini['dbname'], $sql, $this->connect_id);
		    $this->error['mysql'] = @mysql_error($this->connect_id);

		} else {
		    $this->result=@mysql_db_query($dbname, $sql, $this->connect_id);
		    $this->error['mysql'] = @mysql_error($this->connect_id);
		    
		    if ($this->debug) {
			print $this->error['mysql'] .' qwerty ';
		    }
		}

	}
	function getrow($type = MYSQL_ASSOC){
		return @mysql_fetch_array($this->result, $type);
	}
	function numrows(){
		return @mysql_num_rows($this->result);
	}
	function affect(){
		return @mysql_affected_rows($this->connect_id);
	}
	function id(){
		return @mysql_insert_id($this->connect_id);
	}
	function seek($row){
//		@mysql_free_result($this->result);
		return @mysql_data_seek($this->result, $row);
	}
	function fieldname($index){
	 	return mysql_field_name($this->result, $index);
	}
	function numfields(){
	 	return @mysql_num_fields($this->result);
	}
	function fields_array(){
	 	$co = $this->numfields();
		for($i=0; $i<$co; $i++){
		    	$res[] = $this->fieldname($i);
		}
		return $res;

	} 

///////////////////////////////////////////////////////////////////////////////////////////////////
	function is_empty($SQL = ''){
       		if($SQL) { 
			$this->query($SQL); 
		}
		if(0 == $this->numrows()){
			return true;
		} else {
			return false;
		}
	}
	function not_empty($SQL = ''){
	 	if($SQL){
			$this->query($SQL);
		}
		if(0 == $this->numrows()){
			return false;
		} else {
			return true;
		}

	}
	function data_array( $type = MYSQL_ASSOC ) {
		while( $r = $this->getrow($type) ){
			$res[] = $r;
		}
		return @$res;
	}
	function data($SQL, $type = MYSQL_ASSOC){
		$this->query($SQL);
		return $this->data_array($type);	 	
	}
	function data_count($table, $field='', $value=''){

	        $SQL = 'SELECT COUNT(*) as cnt FROM '.$table;
	        if(($field != '') and ($value != '')) { $SQL.= ' WHERE '.esc($field).'="'.esc($value).'"'; } 
		$GLOBALS['db']->query($SQL);
	        $r = $GLOBALS['db']->getrow();
	        
	        return (int)$r['cnt'];
        }

	function loadcsv($file, $table, $columns){
	 	if(is_file($file)){
			if(count($this->csv_options) == 0) {	
				$this->csv_options = $GLOBALS['def_csv_options'];
			}

			$sql = 'LOAD DATA ';
			$sql.= 'LOCAL INFILE \''.esc($file).'\' ';
			$sql.= 'INTO TABLE '.esc($table);
			$sql.= ' FIELDS TERMINATED BY \''.$this->csv_options['TERMINATED'].'\''.' OPTIONALLY ENCLOSED BY \'';
			$sql.= $this->csv_options['ENCLOSED'].'\''.' ESCAPED BY \''.$this->csv_options['ESCAPED'].'\'';
			$sql.= ' LINES TERMINATED BY \''.$this->csv_options['L_TERMINATED'].'\'';
			if($columns!='') { $sql.=' ('.esc($columns).')'; }
			$this->query($sql);
//		print $sql.' '.$this->error();
			 
		}                              
	}
	function savecsv($file, $table, $columns=''){
		if(count($this->csv_options) == 0) {	
			$this->csv_options = $GLOBALS['def_csv_options'];
		}
		$sql = 'SELECT ';
		if($columns!='') { $sql.=esc($columns); } else { $sql.= '*';}
		$sql.= ' INTO OUTFILE \''.$file.'\'';
		$sql.= ' FIELDS TERMINATED BY \''.$this->csv_options['TERMINATED'].'\''.' OPTIONALLY ENCLOSED BY \'';
		$sql.= $this->csv_options['ENCLOSED'].'\''.' ESCAPED BY \''.$this->csv_options['ESCAPED'].'\'';
		$sql.= ' LINES TERMINATED BY \''.$this->csv_options['L_TERMINATED'].'\'';
		$sql.= ' FROM '.$table;
		$this->query($sql);

	}

/// ������� ��� ������������� � �������� /// ������
	function smarty_data(){
		while($r = $this->getrow(MYSQL_NUM)){
			$res[] = $r;
		}
		return $res;
	}
	function smarty_fields(){
		$result = array();
		for($i=0; $i < $this->numfields(); $i++){	 	
			$result[] = $this->fieldname($i);			
		}
		return $result;
	}

}

/// ������� ��������
function esc($str){
  	return mysql_escape_string($str);
}

function sql_insert($table, $column, $value){
	return  "INSERT INTO ".$table." (".$column.") VALUES (".$value.")";
}
function sql_insert_array($table, $data = array()) {
    if (empty($data)) {
	return FALSE;
    }
    $field_name = implode(', '.$table.'.', array_keys($data));
    $field_value = '\'' . implode('\', \'',  array_values($data)).'\''; // . '\', ';

    return sql_insert($table, $field_name, $field_value);
}

?>
