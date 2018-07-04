<?php
declare(strict_types=1);

namespace SamIT\Yii2\StaticAssets;

use Docker\Context\Context;
use Docker\Context\ContextBuilder;
use yii\base\InvalidConfigException;
use yii\console\Application;

class Module extends \yii\base\Module
{
    /**
     * @var string The base URL for the assets. This can include a hostname.
     */
    public $baseUrl;

    /**
     * @var string The name of the created image.
     */
    public $image;

    /**
     * @var string The tag of the created image.
     */
    public $tag = 'latest';

    /**
     * @var bool wheter to push successful builds.
     */
    public $push = false;

    /**
     * @var string Location of composer.json / composer.lock
     */
    public $composerFilePath = '@app/../';

    /**
     * @var string The class of the default asset bundle. This will be used to look for files like /favicon.ico
     */
    public $defaultBundle;

    /**
     * @var string The location if your entry script inside your PHP-FPM container / server
     * Does not support aliases, must be absolute.
     */
    public $entryScript = '/project/public/index.php';

    /** @var array List of fnmatch patterns with file names to skip. */
    public $excludedPatterns = [];

    public $nginxConfig = [
        'daemon' => 'off',
        'user' => 'nginx',
        'worker_processes' => 2,
        'error_log' => '/dev/stderr warn',
        'pid' => '/var/run/nginx.pid',
        'events' => [
            'worker_connections' => 1024
        ],
        'http' => [
            'include' => '/etc/nginx/mime.types',
            'client_max_body_size' => '20m',
            'client_body_buffer_size' => '128k',
            'default_type' => 'application/octet-stream',
            'log_format' => <<<NGINX
main '\$remote_addr - \$remote_user [\$time_local] "\$request" '
     '\$status \$body_bytes_sent "\$http_referer" '
     '"\$http_user_agent" "\$http_x_forwarded_for"'
NGINX
            ,
            'access_log' => '/dev/stdout main',
            'sendfile' => 'on',
            'keepalive_timeout' => 65,
            'gzip_static' => 'on',
            'resolver' => '${RESOLVER} valid=10s ipv6=off',
            'server' => [
                'set' => '$upstream_endpoint ${PHPFPM}',
                'listen' => '80 default',
                'root' => '/www',
                'try_files' => '$uri @php',
                'location ^~ /assets' => [
                    'try_files' => '$uri =404'
                ]

            ]
        ]


    ];

    public $fastcgiParams = [
        'connect_timeout' => '5s',
        'read_timeout' => '300s',
        'content_length' => '$content_length',
        'content_type' => '$content_type',
        'gateway_interface' => 'CGI/1.1',
        'query_string' => '$query_string',
        'remote_addr' => '$remote_addr',
        'request_method' => '$request_method',
        'server_name' => '$server_name',
        'server_port' => '$server_port',
        'server_protocol' => '$server_protocol',
        'server_software' => 'nginx/$nginx_version',

        // Not in spec, but required by Yii
        'request_uri' => '$request_uri',



    ];

    public $environmentVariables = [
        'RESOLVER', 'PHPFPM'
    ];

    public function init(): void
    {
        parent::init();
        $assetManagerConfig = $this->module->getComponents(true)['assetManager'] ?? [];
        $assetManagerConfig['hashCallback'] = self::hashCallback();
        if ($this->module instanceof Application) {
            if (!isset(\Yii::$aliases['@webroot'])) {
                \Yii::setAlias('@webroot', \sys_get_temp_dir());
            }
            $assetManagerConfig['basePath'] = \sys_get_temp_dir();
        }
        $this->set('assetManager', $assetManagerConfig);

    }

    public static function hashCallback(): \Closure
    {
        return function($path) {

            $dir = \is_file($path) ? \dirname($path) : $path;
            $relativePath = \strtr($dir, [
                \realpath(\Yii::getAlias('@app')) => 'app',
                \realpath(\Yii::getAlias('@vendor')) => 'vendor'
            ]);
            return \strtr(\trim($relativePath, '/'), ['/' => '_']);
        };
    }


    public function createBuildContext(): Context
    {
        $builder = new ContextBuilder();

        /**
         * BEGIN COMPOSER
         */
        $builder->from('composer');
        $builder->addFile('/build/composer.json', \Yii::getAlias($this->composerFilePath) .'/composer.json');
        if (\file_exists(\Yii::getAlias($this->composerFilePath) . '/composer.lock')) {
            $builder->addFile('/build/composer.lock', \Yii::getAlias($this->composerFilePath) . '/composer.lock');
        }

        $builder->run('composer global require hirak/prestissimo');

        $builder->run('cd /build && composer install --no-dev --no-autoloader --ignore-platform-reqs --prefer-dist && rm -rf /root/.composer');


        // Add the actual source code.
        $root = \Yii::getAlias('@app');
        if (!\is_string($root)) {
            throw new \Exception('Alias @app must be defined.');
        }
        $builder->addFile('/build/' . \basename($root), $root);
        $builder->run('cd /build && composer dumpautoload -o');
        $builder->run('/usr/local/bin/php /build/' . $this->getConsoleEntryScript() . ' staticAssets/asset/publish /build/assets');
        /**
         * END COMPOSER
         */


        $builder->from('alpine:edge');
        $packages = [
            'nginx',
            'gettext',
            'tini'
        ];

        $builder->run('apk add --update --no-cache ' . \implode(' ', $packages));
//        $builder->volume('/runtime');
        $builder->copy('--from=0 /build/assets', '/www/assets');
        $builder->add('/entrypoint.sh', $this->createEntrypoint());
        $builder->run('chmod +x /entrypoint.sh');
        $builder->add('/nginx.conf.template', $this->createNginxConfig());
        $builder->run('PHPFPM=test RESOLVER=127.0.0.1 envsubst "\$PHPFPM \$RESOLVER" < /nginx.conf.template > /tmp/nginx.conf');
        $builder->run("nginx -t -c /tmp/nginx.conf");
        $builder->entrypoint('["/sbin/tini", "--", "/entrypoint.sh"]');
        $builder->expose(80);
        return $builder->getContext();
    }

    private function createNginxBlock(?string $name, array $directives): array
    {
        $result = [];
        if (isset($name)) {
            $result[] = "$name {";
            $indent = 4;
        }


        $prefix = \str_repeat(' ', $indent ?? 0);
        foreach($directives as $key => $value) {
            if (\is_int($key)) {
                $result[] = $value . ';';
                continue;
            }

            if (!\is_array($value)) {
                $result[] = "{$prefix}{$key} {$value};";
                continue;
            }

            foreach ($this->createNginxBlock($key, $value) as $line) {
                $result[] = $prefix . $line;
            }

        }
        if (isset($name)) {
            $result[] = "}";
        }
        return $result;
    }

    protected function createNginxConfig(): string
    {
        $nginxConfig = $this->nginxConfig;

        $fastcgiConfig = [
            'fastcgi_pass' => '$upstream_endpoint',
            'fastcgi_param SCRIPT_FILENAME' => $this->entryScript,
            'fastcgi_param SCRIPT_NAME' => '/' . \basename($this->entryScript)
        ];

        foreach($this->fastcgiParams as $name => $value) {
            if (\in_array($name, ['read_timeout', 'connect_timeout'], true)) {
                $fastcgiConfig["fastcgi_$name"] = $value;
            } else {
                $fastcgiConfig['fastcgi_param ' . \strtoupper($name)] = $value;
            }
        }


        $nginxConfig['http']['server']['location @php'] = $fastcgiConfig;




        $flattenedConfig = $this->createNginxBlock(null, $nginxConfig);



        return \implode("\n", $flattenedConfig);
    }

    /**
     * @return string A shell script that checks for existence of (non-empty) variables and runs php-fpm.
     */
    protected function createEntrypoint(): string
    {
        $result = [];
        $result[] = '#!/bin/sh';

        $variables = [];
        // Check for variables.
        foreach($this->environmentVariables as $name) {
            $result[] = \strtr('if [ -z "${name}" ]; then echo "Variable \${name} is required."; exit 1; fi', [
                '{name}' => $name
            ]);
            $variables[] = '\\$' . $name;

        }

        $result[] = 'envsubst "' . \implode(' ', $variables) . '" < /nginx.conf.template > /nginx.conf';
        $result[] = 'cat nginx.conf';
        $result[] = 'exec nginx -c /nginx.conf';
        return \implode("\n", $result);
    }

    /**
     * @throws InvalidConfigException in case the app is not configured as expected
     * @return string the relative path of the (console) entry script with respect to the project (not app) root.
     */
    private function getConsoleEntryScript(): string
    {
        $full = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), -1)[0]['file'];
        $relative = \strtr($full, [\dirname(\Yii::getAlias('@app')) => '']);
        if ($relative === $full){
            throw new InvalidConfigException("The console entry script must be located inside the @app directory.");
        }
        return \ltrim($relative, '/');
    }
}