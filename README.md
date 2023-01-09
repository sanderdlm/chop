# Analogo

Minimal dependency to reset the content of the OPcache through PHP-FPM.

## Installation

    composer require dreadnip/analogo

## Usage
This tool only supports OPcache and fcgi/PHP-FPM.

If no option is passed, it'll look for a php-fpm socket file in all the common places. If multiple are found, it'll try to select one for your PHP version.

If no file can be found, it will default to 127.0.0.1:9000.

Since this is a single-command application, you just need to call the binary:
    
    php /path/to/your/project/vendor/bin/analogo

You can also pass an IP address or a unix socket to the --fcgi option.

    php /path/to/your/project/vendor/bin/analogo --fcgi=/var/run/php-fpm.sock

## Credit

This tool is of course inspired and based on the awesome [cachetool](https://github.com/gordalina/cachetool). All of the FCGI socket code is handled by [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client).