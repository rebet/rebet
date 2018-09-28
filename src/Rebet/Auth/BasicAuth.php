<?php
namespace Rebet\Auth;

use Rebet\Common\Utils;
use Rebet\Common\System;

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
class BasicAuth
{
    /**
     * インスタンス化禁止
     */
    private function __construct()
    {
    }

    /**
     * 簡易的な BASIC認証 を提供します。
     *
     * @param array $auth_list 認証リスト
     * @param ?\Closure $to_hash 認証リストのパスワードハッシュ化ロジック（デフォルト：null）
     * @param string $realm 領域テキスト
     * @param string $failed_text 認証失敗時メッセージ
     * @param string $charset 文字コード（デフォルト：UTF-8）
     * @return string
     */
    public static function authenticate(array $auth_list, ?\Closure $to_hash = null, string $realm = "Enter your ID and PASSWORD.", string $failed_text = "Authenticate Failed.", string $charset = 'UTF-8') : string
    {
        if (empty($to_hash)) {
            $to_hash = function ($password) {
                return $password;
            };
        }
        
        $user      = Utils::get($_SERVER, 'PHP_AUTH_USER');
        $pass      = $to_hash(Utils::get($_SERVER, 'PHP_AUTH_PW')) ;
        $auth_pass = Utils::get($auth_list, $user);
        if (!empty($user) && !empty($pass) && $auth_pass === $pass) {
            return $user;
        }

        System::header('HTTP/1.0 401 Unauthorized');
        System::header('WWW-Authenticate: Basic realm="'.$realm.'"');
        System::header('Content-type: text/html; charset='.$charset);

        throw new AuthenticateException($failed_text, 403);
    }
}
