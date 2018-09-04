<?php
namespace Rebet\Common;

/**
 * ネット関連 ユーティリティ クラス
 * 
 * ネットワーク関連の簡便なユーティリティメソッドを集めたクラスです。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class NetUtil {
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * バイナリデータをURLに利用可能な文字列に変換します。
	 * ※Base64 の URL Unsafe な文字「+/=」を URL Safe な文字「._-」に置換えた文字列を返します。
	 * 
	 * @param mixed $byte バイナリデータ
	 * @return string URL利用可能文字列
	 */
	public static function encodeBase64Url($byte) : string {
		return strtr(base64_encode($byte), '+/=', '._-');
	}
	
	/**
	 * URLに利用可能な文字列をバイナリデータに変換します。
	 * ※Base64 の URL Unsafe な文字「+/=」を URL Safe な文字「._-」に置換えた文字列からデータを復元します。
	 * 
	 * @param string $encoded 文字列
	 * @return mixed バイナリデータ
	 */
	public static function decodeBase64Url(string $encoded) {
		return base64_decode(strtr($encoded, '._-', '+/='));
	}
	
	/**
	 * ページをリダイレクトします。
	 * ※本メソッドは exit しません。
	 * 
	 * @param  string $url リダイレクトURL
	 * @return void
	 * @todo   パラメータ構築などの機能を追加
	 */
	public static function redirect($url) {
		ob_clean();
		header("HTTP/1.1 302 Found");
		header("Location: {$url}");
	}
	
	/**
	 * データを JSON形式 で書き出します。
	 * ※本メソッドは exit しません。
	 * 
	 * @param obj $data オブジェクト
	 */
	public static function json($data) {
		ob_clean();
		header("HTTP/1.1 200 OK");
		header('Content-Type: application/json; charset=UTF-8');
		echo json_encode($data);
	}
	
	/**
	 * データを JSONP形式 で書き出します。
	 * ※本メソッドは exit しません。
	 * 
	 * @param obj    $data     オブジェクト
	 * @param string $callback コールバック関数
	 */
	public static function jsonp($data, $callback) {
		ob_clean();
		header("HTTP/1.1 200 OK");
		header('Content-Type: application/javascript; charset=UTF-8');
		echo "{$callback}(".json_encode($data).")";
	}

	/**
	 * データを CSV形式 で書き出します。
	 * ※本メソッドは exit を call します。
	 * 
	 * 【コンバーター定義】
	 * $converter = function($line, $col, $val) {
	 *     // return converted value.
	 *     //  - date, number format
	 *     //  - code to label convert
	 *     //  - using not exists col name to new col like 'name' return "{$line->last_name} {$line->first_name}"
	 * }
	 * 
	 * 【使い方】
	 * // ケース1 ： $rs 内の UserDetailDto クラスの全フィールドを出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *  );
	 * 
	 * // ケース2 ： $rs 内の UserDetailDto クラスの全フィールドを出力／日付のフォーマットを指定
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($val instanceof DateTime) { return $val->format('Y年m月d日 H:i'); }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *  );
	 * 
	 * // ケース3 ： 指定のフィールドを任意の列順で出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name')
	 *  );
	 *  
	 * // ケース4 ： 存在しない項目を固定値で追加
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($col == 'fixed_col') { return 1; }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name','fixed_col')
	 *  );
	 *  
	 * // ケース5 ： 複数項目を結合して出力
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) {
	 *         if($col == 'name') { return "{$line->last_name} {$line->first_name}"; }
	 *         return $val;
	 *     }
	 *    ,$rs
	 *    ,array('user_id','mail_address','name')
	 *  );
	 *  
	 * // ケース6 ： ヘッダ行を出力しない
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *    ,false
	 *  );
	 * 
	 * // ケース7 ： ヘッダラベル指定（配列指定）
	 * // ※配列の範囲外の項目はシステムラベルで出力されます
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,array('user_id','mail_address','last_name','first_name')
	 *    ,true
	 *    ,array('会員ID','メールアドレス','姓','名')
	 *  );
	 *  
	 * // ケース8 ： ヘッダラベル指定（連想配列指定）
	 * // ※連想配列に定義の無い項目はシステムラベルで出力されます
	 * Util::csv(
	 *     "user_list_".date('YmdHis').'.csv'
	 *    ,function($line, $col, $val) { return $val; }
	 *    ,$rs
	 *    ,UserDetailDto::class
	 *    ,true
	 *    ,array(
	 *         'user_id'      => '会員ID'
	 *        ,'mail_address' => 'メールアドレス'
	 *        ,'last_name'    => '姓'
	 *        ,'first_name'   => '名'
	 *    )
	 *  );
	 *  
	 * @param string       $fileName  出力ファイル名
	 * @param function     $converter コンバータ
	 * @param array        $rs        結果セット
	 * @param array|string $cols      出力対象列名リスト or DTOクラス名
	 * @param boolean      $hasHeader true : ヘッダ行を出力する／false : ヘッダ行を出力しない - デフォルト true
	 * @param array        $colLabels ヘッダ行のラベル指定(配列又は連想配列)                  - デフォルト []
	 * @param string       $encoding  CSVファイルエンコーディング                             - デフォルト SJIS-win
	 */
	public static function csv($fileName, $converter, array $rs, $cols, $hasHeader = true, $colLabels = [], $encoding = 'SJIS-win') {
		if(is_string($cols)) {
			$reflect = new ReflectionClass($cols);
			$props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
			$cols    = array();
			foreach ($props as $prop) {
				$cols[] = $prop->getName();
			}
		}
		
		// 出力
		ob_clean();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Type: application/force-download");
		header('Content-Disposition: attachment; filename=' . mb_convert_encoding($fileName, "SJIS-win", "UTF-8"));
		header("Content-Transfer-Encoding: binary");
		
		$last = count($rs) - 1;
		
		if($hasHeader) {
			$line = '';
			$isMap = self::isMap($colLabels);
			foreach ($cols AS $i => $col) {
				$val = $isMap ? self::get($colLabels, $col, $col ) : self::get($colLabels, $i, $col) ;
				$line .= '"'.str_replace('"','""', $val).'",';
			}
			$line  = substr($line, 0, -1);
			if(0 <= $last) {
				$line .= "\n";
			}
			echo mb_convert_encoding($line, $encoding, "UTF-8");
		}
		
		foreach ($rs AS $i => $row) {
			$line = '';
			foreach ($cols AS $col) {
				$val = self::get($row, $col) ;
				if($converter) {
					$val = $converter($row, $col, $val);
				}
				$line .= '"'.str_replace('"','""', $val).'",';
			}
			$line  = substr($line, 0, -1);
			if($i != $last) {
				$line .= "\n";
			}
			echo mb_convert_encoding($line, $encoding, "UTF-8");
		}
		exit();
	}
	
	/**
	 * file_get_contents で指定URLのページデータを取得します。
	 * 
	 * @param string $url URL
	 * @return mixed 受信データ
	 */
	public static function urlGetContents($url) {
		return file_get_contents($url, false, stream_context_create([
			'http' => ['ignore_errors' => true],
			'ssl'=> [
				'verify_peer' => false,
				'verify_peer_name' => false
			],
		]));
	}
}