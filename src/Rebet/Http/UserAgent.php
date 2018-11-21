<?php
namespace Rebet\Http;

use Rebet\Config\Configurable;
use Rebet\Common\Strings;

/**
 * User Agent Class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2018 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class UserAgent 
{
	use Configurable;

	public static function defaultConfig() : array 
	{
		return [
			'types' => [
				'smartphone' => [
					function($ua) { return Strings::contains($ua,'iphone'); },
					function($ua) { return Strings::contains($ua,'ipod'); },
					function($ua) { return Strings::contains($ua,'android') && Strings::contains($ua,'mobile'); },
					function($ua) { return Strings::contains($ua,'windows') && Strings::contains($ua,'phone'); },
					function($ua) { return Strings::contains($ua,'firefox') && Strings::contains($ua,'mobile'); },
					function($ua) { return Strings::contains($ua,'blackberry'); },
				],
				'tablet' => [
					function($ua) { return Strings::contains($ua,'ipad'); },
					function($ua) { return Strings::contains($ua,'windows') && Strings::contains($ua,'touch') && Strings::contains($ua,'tablet pc'); },
					function($ua) { return Strings::contains($ua,'android') && !Strings::contains($ua,'mobile'); },
					function($ua) { return Strings::contains($ua,'firefox') && Strings::contains($ua,'tablet'); },
					function($ua) { return Strings::contains($ua,'kindle') || Strings::contains($ua,'silk'); },
					function($ua) { return Strings::contains($ua,'playbook'); },
				],
			],
			'bots' => [
				'googlebot',
				'baiduspider',
				'bingbot',
				'yeti',
				'naverbot',
				'yahoo',
				'tumblr',
				'livedoor',
			],
		];
	}
	
	/**
	 * User-Agent header text.
	 * 
	 * @var string
	 */
	private $user_agent = null;
	
	/**
	 * user agent type
	 * 
	 * @var string
	 */
	private $type = null;
	
	/**
	 * Whether it is crawler or not
	 * 
	 * @var boolean
	 */
	private $is_crawler = false;
	
	/**
	 * Create a user agent
	 *
	 * @param string $user_agent
	 */
	public function __construct(?string $user_agent) 
	{
		if(!$user_agent) {
			$this->type = 'unknown';
			return;
		}

		$this->user_agent = $user_agent;
		foreach (static::config('types') as $type => $conditions) {
			foreach ($conditions as $condition) {
				if($condition($user_agent)) {
					$this->type = $type;
					break 2;
				}
			}
		}
		$this->type = $this->type ?? 'others' ;

		foreach (static::config('bots') as $bot) {
			if(Strings::contains($user_agent, $bot)) {
				$this->is_crawler = true;
				break;
			}
		}
	}

	/**
	 * It checks whether the user agent is mobile (smart phone or tablet).
	 * 
	 * @return bool 
	 */
	public function isMobile() : bool
	{
		return $this->isSmartphone() || $this->isTablet() ;
	}
	
	/**
	 * It checks whether the user agent is smart phone.
	 * 
	 * @return bool
	 */
	public function isSmartphone() : bool 
	{
		return $this->type === 'smartphone';
	}
	
	/**
	 * It checks whether the user agent is tablet.
	 * 
	 * @return bool
	 */
	public function isTablet() : bool 
	{
		return $this->type === 'tablet';
	}
	
	/**
	 * It checks whether the user agent is pc(not mobile).
	 * 
	 * @return bool
	 */
	public function isOthers() : bool 
	{
		return $this->type === 'others';
	}
	
	/**
	 * It checks whether the user agent is unknown.
	 * 
	 * @return bool
	 */
	public function isUnknown() : bool 
	{
		return $this->type === 'unknown';
	}

	/**
	 * It checks whether the user agent is crawler.
	 * 
	 * @return bool
	 */
	public function isCrawler() : bool 
	{
		return $this->is_crawler;
	}
	
	/**
	 * Get user agent type.
	 * 
	 * @return string
	 */
	public function getType() : string {
		return $this->type;
	}
	
	/**
	 * Gets the user agent string.
	 * 
	 * @return string|null
	 */
	public function raw() : ?string
	{
		return $this->user_agent;
	}
}