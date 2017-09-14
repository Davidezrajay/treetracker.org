## treetracker.org

## Set up a local development environment on MacOSX

1. Install php with mcrypt (  brew install homebrew/php/php56-mcrypt )
1. Install composer ( https://getcomposer.org/doc/00-intro.md )
2. Install laravel using: php composer.phar global require "laravel/installer"
3. Install mysql
	1. MacOSX : brew install mysql
4. Start mysql
5. Create a new myself database with user/pass of your choice
6. Copy setup/database.example.php to app/config/database.php
7. Update app/config/database.php with username/password/name of your database
8. Migrations aren't configured, so don't execute php artisan migrate
7. php artisan serve
