<?php
namespace Rebet\Auth;

use Rebet\Common\Util;

/**
 * 認証関連 ユーティリティ クラス
 * 
 * 認証関連の簡便なユーティリティメソッドを集めたクラスです。
 * 
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class AuthUtil {
	
	/**
	 * インスタンス化禁止
	 */
	private function __construct() {}

	/**
	 * 簡易的な BASIC認証 を提供します。
	 * 
	 * @param array $auth_list 認証リスト
	 * @param ?callable $to_hash 認証リストのパスワードハッシュ化ロジック（デフォルト：null）
	 * @param string $realm 
	 * @param string $failed_text
	 * @param string $charset
	 * @return type
	 */
	public static function basicAuthenticate(array $auth_list, ?callable $to_hash = null, string $realm = "Enter your ID and PASSWORD.", string $failed_text = "Authenticate Failed.", string $charset = 'utf-8') {
		if(empty($to_hash)) {
			$to_hash = function($password) { return $password; };
		}
		
		$user      = Util::get($_SERVER, 'PHP_AUTH_USER');
		$pass      = $to_hash(Util::get($_SERVER, 'PHP_AUTH_PW')) ;
		$auth_pass = Util::get($auth_list, $user);
		if(!empty($user) && !empty($pass) && $auth_pass === $pass){ return $user; }

		header('HTTP/1.0 401 Unauthorized');
		header('WWW-Authenticate: Basic realm="'.$realm.'"');
		header('Content-type: text/html; charset='.$charset);

		throw new AuthenticateException($failed_text, 403);
	}
}