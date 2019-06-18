<?php  namespace Rvwoens\Gompie;

use Session;
use Illuminate\Support\Facades\Input;
use DB;
use Cache;
use Log;
/**
 * 	Vars class - stored variables and parsing
 */
class vars {
	private static $form=null;
	private static $vars=array();
	private static $vid='vars.def';	// session var default
	
	// 
	// public function __construct($id='vars.def',$lazy=false) {
	// 	$this->vid=$id;
	// 	if (!$lazy)
	// 		$this->loadvars();	// not lazy? load!
	// }
	public static function setID($vid) {
		static::$vid=$vid;
	}
	
	public static function setvars($vars=array(),$merge=true) {	// merge: add vars and keep old ones
		if ($merge)
			static::$vars=array_merge(static::$vars,$vars);
		else
			static::$vars=$vars;
	}
	public static function setvar($key,$value) {	
		if (is_null($value))
			unset(static::$vars[$key]);
		else
			static::$vars[$key]=$value;
	}
	public static function getvar($key) {	
		return COS::ifset(static::$vars[$key],'');
	}
	public static function hasvar($key) {
		return isset(static::$vars[$key]);
	}
	public static function storevars() {
		// store vars to session
		Session::put(static::$vid,static::$vars);
	}
	public static function loadvars() {
		// load vars from session
		static::$vars=array_merge(static::$vars ?: [], Session::get(static::$vid, []));
	}
	public static function dump() {
		echo "<pre>";
		var_dump(static::$vars);
		exit;
	}
	/**
	 * v - replace $xxx with their values. 
	 * $xx ${xx} $!xx $!{xx}  $$->$  $# = POST  $?= GET  $% = vars $^ = session $!=local $+ = local first, than post  $=xx EncodeID(local)
	 * todo: $#?var $?#var 
	 * and $@{select * from country} by concatenated result
	 * and $.{eval php code} 
	 * ${xx:u} = urlencode
	 * ${xx:e} = force doEscape
	 * ${xx:d} = date convert to YYYY-MM-DD
	 */
	public static function v($st,$doEscape=false,$urlencodevars=false) {
		return static::varreplace($st,$doEscape,$urlencodevars);
	}
	/**
	 * 	varreplace - equivalent of v
	 *	doEscape - true: escape all variables for SQL
	 */
	private static function varreplace($st,$doEscape=false,$urlencodevars=false) {
		//echo "<pre>$st</pre>";
		//$self=$this;
		$vars=static::$vars;
		//var_dump($vars);
		$storg=$st;
		//\Log::info("Varreplace START ".print_r($storg,true));
		$cb=function ($match) use(&$rv,$vars,$doEscape,$urlencodevars) {
			// match[1] -> ! or empty
			// match[3] -> abc or empty (for $xx types)
			// match[4] -> empty or abc (for ${xx} types)
			// match[5] -> empty or abc (for $`xx` types)
			// php EGPCS default: $ENV, $GET $POST $COOKIE $SERVER
			// webdb default: $POST
			$var= isset($match[5])? $match[5] : (isset($match[4]) ? $match[4] : $match[3]);
			//echo "var=$var\n";
			$option='';
			//echo "VAR=$var".print_r($match,true)."<br>";
			// test for var options. Not for sql or php variables!
			if ($match[1]!='@' && $match[1]!='.' && strpos($var,':')!==false) {
				// var:xx format. Extra xx option
				$options=explode(':',$var);
				$var=$options[0];
				$option=strtolower($options[1]);
			}
			$rv='';
			switch($match[1]) {
			case '=':	// local var and than use EncodeID
				if (isset($vars[$var]))
					$rv=$vars[$var];
				elseif (Session::get($var)!==null)
					$rv=Session::get($var);
				$rv=FormerObject::encodeId($rv);
				break;
			case '!':	// local var $!local  (NO GET/POST)
				if (isset($vars[$var]))
					$rv=$vars[$var];	
				elseif (Session::get($var)!==null)
					$rv=Session::get($var);
				break;
			case '#':
				$rv= isset($_POST[$var]) ? $_POST[$var]:'';
				break;
			case '?':
				$rv= isset($_GET[$var]) ? $_GET[$var]:'';
				break;
			case '%':
				$rv= isset($vars[$var])?$vars[$var]:'';
				break;
			case '^':
				$rv=Session::get($var)==null?'':Session::get($var);
				break;
			case '@':
				// sqlreplace.. NEVER escaped!
				$doEscape=false;
				if (substr($var,-3)==':nc') {
					// no cache
					$var=substr($var,0,strlen($var)-3);
					$sql=vars::v($var);
					Log::info("Never cached nc: $sql");
					$qry =DB::select($sql);
				}
				else {
					$sql = vars::v($var);

					$qry = Cache::remember($sql, 1 * 60, function() use ($sql) {
						Log::info("Not cached: $sql");
						$rv = DB::select($sql);

						return $rv;
					});
				}
				//$qry=DB::select(vars::v($var));
				if (!$qry) {
					//$self->log("@-sql query error: $var - ".$ci->db->_error_message());
					return '';
				}
				foreach($qry as $row) {	// all rows
					foreach($row as $v) {			// all columns!  select code,';' from country -> NL;BE saves a concat
						$rv.=$v;
					}
				}
				break;
			case '.':	// php eval
				ob_start();
				$rv=eval($var.';');	// rv only filled if eval uses 'return' statement
										// just add a ; at the end cause 2 ;; dont matter and without ; is an error
				$rv.=ob_get_contents();	// echo'd values are concatted
				ob_end_clean();
				break;
			case '+':
				if (isset($vars[$var]))
					$rv=$vars[$var];	
				elseif (Session::get($var)!==null)
					$rv=Session::get($var);
				elseif (Input::get($var)!==null) 
				 	$rv=Input::get($var);
				break;
			default:
				//if ($var=='id') {
				//	\Log::info('var ID: '.print_r($vars,true));
				//}
				if (Input::get($var)!==null) 
				 	$rv=Input::get($var);
				elseif (isset($vars[$var]))
					$rv=$vars[$var];	
				elseif (Session::get($var)!==null)
					$rv=Session::get($var);
				break;
			}
			if ($doEscape || $option=='e') {

				$rv=addslashes($rv);	//
			}
			if ($urlencodevars || $option=='u')
				$rv=urlencode($rv);
			if ( $option=='d') {
				// dateconvert
				if (preg_match('/([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})/',$rv,$match)) {
					$rv=sprintf("%04d-%02d-%02d",$match[3],$match[2],$match[1]);	// dd-mm-yyyy to yyyy-mm-dd
				}
				else
					$rv=date_format(date_create(),'Y-m-d');	// current date
			}
			return $rv; 
		};
		// match $ optionally followed by !#?%^.@ followed by a-z OR {a-z} OR `a-z`
		$st=preg_replace_callback('/\$([!#?%=\+\^\.@]?)(([a-zA-Z0-9._]+)|\{([^}]+)\}|\`([^`]+)\`)/',$cb,$st);		// greedy is good
		//echo ":".$st."</pre>";
		//\Log::info("Varreplace END ".print_r($storg,true)." ==> result ".$st); //print_r($st,true));
		return $st;
	}
}
