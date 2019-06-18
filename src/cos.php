<?php namespace Rvwoens\Gompie;

use Config;
use Log;
use Request;

class cos {
	// if v1 is empty, return v1 else v2
	public static function nvl(&$v1,$v2=null) {
		return empty($v1)?$v2:$v1;
	}
	// if v1 is set, return v1 else v2
	public static function ifset(&$v1, $v2 = null) {
		return isset($v1) ? $v1 : $v2;
	}
	// if v1 is empty, return v1 else v2 - v1 must be set
	public static function def($v1,$v2=null) {
		return empty($v1)?$v2:$v1;
	}
	// boolval of a string - Y,y,1,yes,Yes,J,j,T,t are TRUE
	public static function boolval(&$s,$def=false) {
		if (!isset($s))
			return $def;
		$ss=trim($s." ");	// force to string and trim
		if (!$ss)
			return false;	// empty, 0, "0"
		switch(strtolower($ss[0])) {
		case 'j':
		case 'y':
		case '1';
			return true;
		}
		return false;
	}
	
	public static function html_escape($var) {
		if (is_array($var)) {
			return array_map('html_escape', $var);
		}
		else {
			return htmlspecialchars($var, ENT_QUOTES, config_item('charset'));
		}
	}
	public static function muq($value,$cast='') {	// mysql quoter
		// Quote if not a number or a numeric string
		if (!is_numeric($value) || $cast=='string') {
			//$value = "'" . mysql_real_escape_string($value) . "'";
			$value = "'" . addslashes($value) . "'";
		}
		return $value;
	}	
	public static function uq($value) {	// mysql quoter
		$value =  addslashes($value) ;
		return $value;
	}
	// convert a 0=>vala 1=>valb to a vala=>vala valb=>valb array 
	// only if all keys are numbers!
	public static function array_v_to_kv($a) {
		$rv=array();
		foreach($a as $k=>$v) {
			if (is_numeric($k))
				$rv[$v]=$v;
			else
				return $a;	// non-numeric key
		}
		return $rv;
	}
	/**
	 *  array 2 scalar and process variables!
	 */
	public static function a2s(&$arr,$elm=0,$default='') {
		if (!isset($arr))
			return $default;
		if (is_array($arr)) {
			return static::a2s($arr[$elm],0);	// recursive! $a === $a[0]
		}
		return $elm>0 ? $default: vars::v($arr);	// scalar: return scalar or null if not there
	}
	/**
	 *  array key-value 2 scalar but return key and value as extra argument
	 *	example:
	 *		field:	input  
	 *  OR
	 *		field:
	 *			input:	4
	 */
	public static function a2kv(&$arr,&$value,$default='') {
		if (!isset($arr))
			return $default;
		if (is_array($arr)) {
			foreach($arr as $k=>$v) {
				$value=static::a2s($v,0);
				return $k;				// return the KEY of the array
			}
		}
		return vars::v($arr);	// scalar: return scalar or null if not there
	}
	

	public static function bitrotate ( $decimal, $bits) {
	  $binary = decbin($decimal);
	  return (bindec(substr($binary, $bits).substr($binary, 0, $bits)));
	}
	
	//static private $anyindex="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	static private $anyindex="mwMNO456STfg789axyVWklHIXbcE"; //PQnopqYZzAU1de3FG0hijJKLrstBCD2uvR";	
	// Decimal > Custom 
	public static function dec2any( $num, $base=62, $index=false ) {
		$index = self::$anyindex;
	    $base=strlen($index);	// 62
		$i1=$num;
		$num= ($num * 83) + 17 ;	// set check 
		$i2=$num;
		$num=static::obfuscate($num);
		$i3=$num;
	    $out = "";
	    for ( $t = floor( log10( $num ) / log10( $base ) ); $t >= 0; $t-- ) {
	        $a = floor( $num / pow( $base, $t ) );
	        $out = $out . substr( $index, $a, 1 );
	        $num = $num - ( $a * pow( $base, $t ) );
	    }
		\Log::info("dec2any $i1 to $i2 obfuscated $i3 to any $out");
	    return $out;
	}

	// Custom > Decimal
	public static function any2dec( $num, $base=62, $index=false ) {
		$index = self::$anyindex;
	    $base=strlen($index);
	    $out = 0;
	    $len = strlen( $num ) - 1;
	    for ( $t = 0; $t <= $len; $t++ ) {
			$sp=strpos( $index, substr( $num, $t, 1 ) );
			if ($sp===false) {
				// invalid character
				\Log::info("any2dec $num to $out invalid character");
				return null;	// error
			}
	        $out = $out +  $sp * pow( $base, $len - $t );
	    }
		$out2=static::obfuscate($out,true);	// de-obfuscate
		if ( (($out2-17) % 83) != 0) { 		// check the checkbits
			// check did not work 
			\Log::info("any2dec $num to $out de-obfuscated $out2  not valid");
			return null;	// error
		}
		$out3 = ($out2-17)/83;
		\Log::info("any2dec $num to $out de-obfuscated $out2  returns $out3");
	    return $out3;
	}
	
	public static function obfuscate($x,$restore=false) {
		// *** Shuffle bits (method used here is described in D.Knuth's vol.4a chapter 7.1.3)
		$mask1 = 0x00550055; $d1 = 7;
		$mask2 = 0x0000cccc; $d2 = 14;

		if (!$restore) {
			// Obfuscate
			$t = ($x ^ ($x >> $d1)) & $mask1;
			$u = $x ^ $t ^ ($t << $d1);
			$t = ($u ^ ($u  >> $d2)) & $mask2;
			return $u ^ $t ^ ($t << $d2);
		}
		else {
			// Restore
			$t = ($x ^ ($x >> $d2)) & $mask2;
			$u = $x ^ $t ^ ($t << $d2);
			$t = ($u ^ ($u >> $d1)) & $mask1;
			return $u ^ $t ^ ($t << $d1);
		}
	}

	/**
	 * cos::lang - convert a string with nl::xxxx|en::xxxx into the right language string, otherwise just return string
	 * @param  [type] $s [description]
	 * @return [type]    [description]
	 */
	public static function lang($s) {
		//Log::info("string=$s");
		if (substr($s,2,2)=='::') { // } && in_array(substr($s,0,2),Config::get('app.available_languages'))) {
			// yess! convert
			$langs=explode('|', $s);
			$curlang=strtolower(Config::get('app.locale'));	// or App::getLocale(); ??
			//Log::info("curlang: $curlang");
			foreach($langs as $str) {
				//Log::info("compare $str ");
				if (substr($str,0,2)==$curlang)
					return substr($str,4);
			}
		}
		return $s;
	}


	/**
	 * Return full url but with some querystrings replaced
	 * @param $extra  array of qs values like ['pg'=>'2','offset'=>'4']
	 * @return string
	 */
	public static function fullUrlWithQuery($extra) {
		$url = Request::url(); // url without query
		$query = Request::query(); // query

		//Replace parameter:
		return $url.'?'. http_build_query(array_merge($query, $extra));
	}

	/**
	 *  json decoder: parse and give error as text
	 * @param $json
	 * @param string $err
	 * @return mixed
	 */
	public static function parseJson($json, &$err = '') {
		$err = '';
		$data = json_decode($json, true);    // to assoc array
		if ($data===null) {
			$json_err = array(
				JSON_ERROR_NONE => '',
				JSON_ERROR_DEPTH => 'max stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH => 'underflow or the modes mismatch',
				JSON_ERROR_CTRL_CHAR => 'unexpected control character fount',
				JSON_ERROR_SYNTAX => 'syntax error, malformed json',
				JSON_ERROR_UTF8 => 'malformed utf-8, possibly incorrectly encoded');
			$jse = json_last_error();
			$err = static::ifset($json_err[$jse], 'unknown error');
		}
		return $data;
	}

}