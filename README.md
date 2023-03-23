# chop
Reset the OPcache content from the CLI, without reloading PHP-FPM.

Basically [cachetool](https://github.com/gordalina/cachetool), but without support for the APCu cache, realpath cache and none of the extra opcache commands.

Just opcache:clear, nothing else.

Also installed as a dependency through Composer, for easier coupling with the PHP version of your application.

## Installation

    composer require dreadnip/chop

## Usage

This is a single-command application, so calling the binary is enough:

    php /path/to/your/project/vendor/bin/chop

By default, the command will print a success message or error to the console. You can suppress this output with the `--quiet` option (shorthand `-q`)

    php /path/to/your/project/vendor/bin/chop -q

The tool attempts to use sane defaults:

If no argument is passed, it'll look for a php-fpm socket file in the following places:
```php
private const POSSIBLE_SOCKET_FILE_PATTERNS = [
    '~/.sock/*.sock',
    '/var/run/php*.sock',
    '/var/run/php/*.sock',
    '/var/run/php-fpm/*.sock',
];
```
This should find your socket file on most Linux distributions. If we forgot a common location, please open an issue/PR.

If multiple socket files are found, it'll try to select one by checking the filename for both the major and minor number of your PHP version.

For example: if your server has the followning socket files, and you're running PHP 8.1, it'll select the socket file with both 8 and 1 in the filename.
```js
php_80_fpm_sites.sock
**php_81_fpm_sites.sock**
php_82_fpm_sites.sock
```

When no file socket file can be found, it will default to 127.0.0.1:9000.

If the defaults don't work for you, you can also explicitly pass an IP address or a path to a socket file using the --fcgi option:

    php /path/to/your/project/vendor/bin/chop --fcgi=/var/run/php-fpm.sock

## Deployer integration
Define a task with the command

    task('clear:opcache', function () {
        run('{{bin/php}} {{ release_path }}/vendor/bin/chop');
    });

Call the task after your deployment

    after('deploy:symlink', 'clear:opcache');

Or run it manually

    dep clear:opcache stage=production -vvv

## Credit

This tool is of course inspired and based on the awesome [cachetool](https://github.com/gordalina/cachetool). 

Just like cachetool, the FastCGI socket code is handled by [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client).