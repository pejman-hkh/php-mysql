<?php
class DBException extends PDOException {
}

class mysql {

	private  $table_name, $statement, $paginate, $error;

	public $connected = false;

	private $sql = [];
	private $params = [];

	public function get_sql() {

		return $this->sql;
	}

	private function bind( &$params ) {
		try {

			foreach( $params as $k => $v ) {
				
				if( empty( $v ) )
					$v = '';

				$this->statment->bindValue( $k + 1, $v,  is_numeric( $v ) ? PDO::PARAM_INT : PDO::PARAM_STR );
			
			}
			
			return true;

		} catch (PDOException $e) {

			throw new DBException( $this->error = "SQl : '".$this->get_sql()."' BIND Error!:". $e->getMessage()."<br/>" );
			
			return false;
		}					
	}

	private function execute() {
		try {
			
			if( $this->statment->execute() ) {
				return true;	
			} else {
				echo $this->get_sql() ;
				exit();
			}

		} catch (PDOException $e) {

			throw new DBException( $this->error = "SQl : '".$this->get_sql()."' EXECUTE Error!:". $e->getMessage()."<br/>" );
			
			return false;

		}
	}

	private function prepare() {
		try {

			if( $this->statment = $this->_db->prepare( $this->get_sql() ) ) {
				return true;	
			}

	    } catch ( PDOException $e ) {

			throw new DBException( $this->error = "SQl : '".$this->get_sql()."' PREPARE Error!:". $e->getMessage()."<br/>" );

			return false;
	    }

	}

	private function paginate( $data ) {

		$number = $this->paginate["n"];
		$page = $this->paginate["p"];
		

		$this->paginate = [];


		if( ! $number ) return false;

		$exp = explode( "FROM", $this->sql );

		$temp_sql = $this->sql;

		$coq = $exp[ count( $exp ) - 1 ];
		
		$coq = preg_replace("#GROUP\s*BY\s*([\w\.]+)\s*#", "", $coq );


		$this->free = false;
		$fetch = $this->sql( "SELECT COUNT(*) as count FROM " .$coq )->find_one( $data );
		$this->free = true;

		$count = @$fetch["count"];

		$limit = 8;

		$n_p = ceil( $count / $number );

		$start = ( $page - $limit ) <= 0 ? 1 : $page - $limit ; 
		$end = ( $page + $limit >= $n_p ) ? $n_p : $page + $limit;


		$next = $page >= ceil( $count / $number ) ? $page : $page + 1;
		$prev = $page <= 1 ? 1 : $page - 1;


		$pagin = [];
		for( $i = $start; $i <= $end ; $i++ )
		{
			 $pagin[ $i ] = $i;
		}
		
		$this->loop_pagination["loop"] = $pagin; 
		$this->loop_pagination["count"] = $count;
		$this->loop_pagination["next"] = $next;
		$this->loop_pagination["prev"] = $prev;


		$this->sql = $temp_sql." LIMIT ".( $page * $number - $number ).", ".$number;

		return true;
	}

	private function set_attr( $array ) {

		foreach( $array as $k => $v ) {
			
			$this->_db->setAttribute( $k, (int)$v );

		}

	}

	private function make_insert_sql( &$data ) {

		$colum_name = [];
		$values = [];
		
		foreach( $data as $k => $v ) {
			
			$colum_name[ $k ] = "`".$k."`";
			
			$values[ $k ] = "?";

		}

		$this->sql = "INSERT INTO `".$this->table_name."`(".implode( ",", $colum_name ).") VALUES(".implode( ",", $values ).")";

		$this->table_name = "";
	}


	public function connect( $host = "localhost", $dbname, $user, $pass ) {
		try {
			
			$this->_db = new PDO( "mysql:host=".$host.";dbname=".$dbname.";charset=utf8", $user, $pass );

			$this->set_attr( [
					[  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'" ],
					[ PDO::ATTR_PERSISTENT => false ],
					[ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ],
					[ PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true ],
				] );
			
			$this->connected = true;

		} catch(DBException $e) {
			
			throw new DBException( "Error Connect !<br/>" );

			return false;
		}
	}


	public function OrderKey( $orders, $get ) {

        foreach( $orders as $k => $v ) {
        	if( @$get[ 'order' ] === $k )
            	$this->orderBy( $v, $get[ 'by' ] );
        }

        return $this;

	}

	private $iter_where = 0;

	public function select( $table, $field = "*" ) {
		$this->sql .= " SELECT $field FROM $table";
		return $this;
	}

	public function groupBy( $field ) {
		$this->sql .= " GROUP BY ".$field." ";
		return $this;
	}

	public function orderBy( $field, $aod = "ASC" ) {
		$this->sql .= " ORDER BY ".$field." ".$aod;
		return $this;
	}

	public function limit( $from, $count ) {
		$this->sql .= " LIMIT ".$from.",".$count;
		return $this;
	}

	public function order( $field, $aod = "ASC" ) {
		$this->orderBy( $field, $aod );
		return $this;
	}

	private function whereIt( $field, $op = "", $value = [], $aoo = "AND" ) {
		if( $this->iter_where === 0 ) {
			//$this->sql .= " WHERE 1 = 1 ";
			$aoo = " WHERE ";
			$this->iter_where++;	
		}

		if( $op !== "") {
			$qmark = " ? ";

			if( is_array( $value ) ) {
				$op = "IN";
				$qmark = " (".str_repeat("?,", count($value)-1) . "?) ";
				foreach ($value as $v ) {
					$this->params[] = $v;
				}

			} else {
				$this->params[] = $value;
			}

		
			$this->sql .= $aoo." ".$field." ".$op.$qmark;

		} else {
			$this->sql .= $aoo." ".$field;			
		}
		

		return $this;
	}

	public function whereAccess( $field, $ids, $user ) {
		if( (int)$user['main'] === 0 ) {
			return $this->whereIt( $field, "=", $ids );
		}

		return $this;
	}

	public function where( $field, $op = "", $value = [] ) {
		return $this->whereIt( $field, $op, $value );
	}

	public function orWhere( $field, $op, $value ) {
		return $this->whereIt( $field, $op, $value, "OR" );
	}

	public function search( $search, $keyword)
	{

	    if (  $keyword != '' ) {
			$i = 0;
        	foreach( $search as $k => $v ) {
        		$fn = $i === 0 ? "where" : "orWhere";
        		$this->{$fn}( $v, "LIKE", "%$keyword%" );
        		$i++;
        	} 
	    }

	    return $this;
	}


	public function searchByKey( $search_keys, $get ) {
        $j = 0;
        foreach( $search_keys as $k => $v ) {
            foreach( $v as $k1 => $v1 ) {
            	if( isset( $get[ $k1 ] ) && !empty( $get[ $k1 ]  )  ) {
           
			        if( $v1[1] == "like" ) {
			            $search_value = "%".$get[ $k1 ]."%";
			        } else if( $v1[1] == "%like" ) {
			            $search_value = "%".$get[ $k1 ]; 
			        } else if( $v1[1] == "like%" ) {
			            $search_value = $get[ $k1 ]."%";
			        } else {
			           $search_value = $get[ $k1 ]; 
			        }        	

		           	$this->where( $v1[0], str_replace( "%", "", $v1[1] ), $search_value );
		        }
		
            }
        }

        return $this;
	}

	private $loop_pagination;

	public function get_pagination() {
		return $this->loop_pagination;
	}

	public function begin() {

		return $this;
	}

	public function commit() {

		return $this;
	}

	public function bind_bulk( $data ) {

	}

	public function get_error() {
		return $this->error;
	}

	public function pagination( $get, $in_page = 5 ) {

		$this->paginate["n"] = ( isset( $get["npage"] ) && ! empty( $get['npage'] ) ) ? $get["npage"] : $in_page;
		$this->paginate["p"] = isset( $get["page"] ) ? $get["page"] : 1;

		return $this;
	}

	public function table( $table_name ) {
		$this->table_name = $table_name;
		return $this;
	}

	public function sql( $sql ) {

		$this->sql = $sql;

		$this->loop_pagination = [];

		return $this;
	}

	private $free = true;

	public function exec( $params ) {


		if( $this->prepare() ) {

			if( $this->bind( $params ) ) {
				if( $this->execute() ) {
					if( $this->free ){
						$this->iter_where = 0;
						$this->params = [];
						$this->sql = "";						
					}


					return true;
				}
			}
		}

		return false;
	}

	public function find( $params = [] ) {

        if( count( $this->paginate ) > 0 ) {

            $this->paginate( $params );
        }

        $pas = [];
		foreach ($this->params as $value) {
			$pas[] = $value;
		}

		foreach ($params as $value) {
			$pas[] = $value;
		}


	    if( $this->exec( $pas ) ) {

	    	return $this->statment->fetchAll( PDO::FETCH_ASSOC );

	    }

	    $this->sql = "";
	    
	    return false;

	}

	public function find_one( $params = [] ) {

		$this->sql .= " LIMIT 0,1";
		$find = $this->find( $params );
		return isset( $find[0] ) ? $find[0] : [];

	}

	public function insert( $data ) {

		$this->make_insert_sql( $data );

		$params = [];
		foreach ( $data as $k => $v ) {
			$params[] = $v;
		}


		if( $this->exec( $params ) ) {
			return $this->_db->lastInsertId();
		}

		return false;

	}

	public function update( $update, $query = "", $value = [] ) {

		$set = [];
		$update1 = [];
		foreach( $update as $k => $v ) {
			$set[] = "`".$k."` = ?";
			$update1[] = $v;
		}

		foreach( $value as $k => $v ) {
			$update1[] = $v;
		}

		foreach ($this->params as $value) {
			$update1[] = $value;
		}

		return $this->sql("UPDATE ".$this->table_name." SET ".implode( ",", $set )." ".$query." ".$this->sql )->exec( $update1 );

	}

	function __destruct() {
		echo( $this->get_error() );

	 	$this->_db = NULL;
	}

};

?>