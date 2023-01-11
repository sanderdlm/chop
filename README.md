# Analogo
a.k.a "cachetool-lite"

Minimal CLI tool to reset the content of OPcache without reloading PHP-FPM.

## Installation

    composer require dreadnip/analogo

## Usage
This tool only supports OPcache and fcgi/PHP-FPM.

If no option is passed, it'll look for a php-fpm socket file in all the common places. If multiple are found, it'll try to select one for your PHP version.

If no file can be found, it will default to 127.0.0.1:9000.

This is a single-command application, so you don't have to specify a command.
    
    php /path/to/your/project/vendor/bin/analogo

You can also pass an IP address or a unix socket to the --fcgi option.

    php /path/to/your/project/vendor/bin/analogo --fcgi=/var/run/php-fpm.sock

## Deployer integration
Define a task with the command

    task('clear:opcache', function () {
        run('{{bin/php}} {{ release_path }}/vendor/bin/analogo');
    });

Call the task after your deployment

    after('deploy:symlink', 'clear:opcache');

## Credit

This tool is of course inspired and based on the awesome [cachetool](https://github.com/gordalina/cachetool). All the FCGI socket code is handled by [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client).