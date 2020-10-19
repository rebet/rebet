<?php
namespace Rebet\Mail\Transport;

use Rebet\Inflection\Inflector;
use Rebet\Tools\Exception\LogicException;
use Rebet\Tools\Reflection\Reflector;
use Rebet\Tools\Utility\Arrays;
use Swift_Plugins_PopBeforeSmtpPlugin;
use Swift_SmtpTransport;

/**
 * Smtp Transport class
 *
 * @package   Rebet
 * @author    github.com/rain-noise
 * @copyright Copyright (c) 2020 github.com/rain-noise
 * @license   MIT License https://github.com/rebet/rebet/blob/master/LICENSE
 */
class SmtpTransport extends Swift_SmtpTransport
{
    use PluginAccessible, PluginOptionable;

    /**
     * Default port encription map.
     */
    const DEFAULT_PORT_ENCRYPTION = [
        25   => null,  // SMTP
        110  => null,  // POP3
        465  => 'ssl', // SMTPS (SMTP over SSL)
        587  => 'tls', // SMTP Submission
        995  => 'tls', // POP3S (POP3 over SSL/TLS)
        2525 => 'tls', // SMTP Alternate
    ];

    /**
     * @param string $host (default: 'localhost')
     * @param int $port (default: 25)
     * @param string|null $username (default: null)
     * @param string|null $password (default: null)
     * @param array $options (default: [])
     *   - int          'timeout'
     *   - string       'source_ip'
     *   - string       'local_domain'
     *   - array        'stream_options'
     *   - bool         'disable_ca_check'  : When value is true then set ['ssl' => ['allow_self_signed' => true, 'verify_peer' => false, 'verify_peer_name' => false]] option to 'stream_options'
     *   - string       'sender'            : Use Swift_Plugins_ImpersonatePlugin [?]
     *   - array        'redirecting'       : Use Swift_Plugins_RedirectingPlugin ['recipient' => ?, 'whitelist' => []]
     *   - array        'antiflood'         : Use Swift_Plugins_AntiFloodPlugin ['threshold' => 99, 'sleep' => 0]
     *   - bool         'bandwidth_monitor' : Use Swift_Plugins_BandwidthMonitorPlugin [when value is true]
     *   - array        'throttle'          : Use Swift_Plugins_ThrottlerPlugin ['rate' => ?, 'mode' = Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE]
     *   - bool|string  'logging'           : Use LoggingPlugin [when value is true then use default channel, otherwise give channel name of Log.channels]
     *   - string|array 'always_bcc'        : Use AlwaysBccPlugin [?]
     *   - bool|array   'pop_before_smtp'   : Use Swift_Plugins_PopBeforeSmtpPlugin ['host' => null (null for use SMTP host), 'port' => 110, 'crypto' => null (null, 'tls' or 'ssl' : null for choose default by port number), 'username' => null(null for use SMTP username), 'password' => null(null for use SMTP password), 'timeout' => 10]
     * @param string|null $encryption null, 'tls' or 'ssl' (default: null for choose default by port number)
     */
    public function __construct(string $host = 'localhost', int $port = 25, ?string $username = null, ?string $password = null, array $options = [], ?string $encryption = null)
    {
        parent::__construct($host, $port, $encryption ?? static::DEFAULT_PORT_ENCRYPTION[$port] ?? null);
        $stream_options = [];
        foreach ($options as $option => $value) {
            if ($this->apply($option, $value)) {
                continue;
            }
            switch ($option) {
                case 'stream_options':    $stream_options = $value; break;
                case 'disable_ca_check':  break;
                case 'pop_before_smtp':
                    $crypto = $value['crypto'] ?? static::DEFAULT_PORT_ENCRYPTION[$value['port'] ?? 110] ?? null;
                    $plugin = Reflector::create(Swift_Plugins_PopBeforeSmtpPlugin::class, array_merge(['host' => $host], is_array($value) ? $value : [], ['crypto' => $crypto]));
                    if ($value['username'] ?? $username) {
                        $plugin->setUsername($value['username'] ?? $username);
                        $plugin->setPassword($value['password'] ?? $password);
                    }
                    if (isset($value['timeout'])) {
                        $plugin->setTimeout($value['timeout']);
                    }
                    $plugin->bindSmtp($this);
                    $this->registerPlugin($plugin);
                break;
                default:
                    $setter = 'set'.Inflector::pascalize($option);
                    if (method_exists($this, $setter)) {
                        Reflector::invoke($this, $setter, [$value]);
                        break;
                    }
                    throw new LogicException("Invalid option '{$option}' was given.");
                break;
            }
        }
        if ($options['disable_ca_check'] ?? false) {
            $stream_options = Arrays::override($stream_options, ['ssl' => ['allow_self_signed' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
        }
        if (!empty($stream_options)) {
            $this->setStreamOptions($stream_options);
        }
        if ($username) {
            $this->setUsername($username);
            $this->setPassword($password);
        }
    }
}
