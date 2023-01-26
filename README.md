# chop
a.k.a "cachetool-lite"

Reset the OPcache content from the CLI, without reloading PHP-FPM.

## Installation

    composer require dreadnip/chop

## Usage
The tool attempts to use sane defaults:

 * If no argument is passed, it'll look for a php-fpm socket file in all the common places. If multiple are found, it'll try to select one that contains your PHP version.

 * If no file can be found, it will default to 127.0.0.1:9000.

This is a single-command application, so calling the binary is enough:
    
    php /path/to/your/project/vendor/bin/chop

If the defaults don't work for you, you can also explicitly pass an IP address or a path to a socket file using the --fcgi option:

    php /path/to/your/project/vendor/bin/chop --fcgi=/var/run/php-fpm.sock

By default, the output of the command (true for success, false for failure) will be printed to the console. You can suppress this output with the `--quiet` option (shorthand `-q`)

        php /path/to/your/project/vendor/bin/chop -q

## Deployer integration
Define a task with the command

    task('clear:opcache', function () {
        run('{{bin/php}} {{ release_path }}/vendor/bin/chop');
    });

Call the task after your deployment

    after('deploy:symlink', 'clear:opcache');

## Credit

This tool is of course inspired and based on the awesome [cachetool](https://github.com/gordalina/cachetool). 

Just like cachetool, the FastCGI socket code is handled by [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client).