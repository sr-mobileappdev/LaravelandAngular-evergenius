# README #


### EverGenius 2.1

#### System Requirements 

* Composer
* Node js - v6.10.2
* NPM v4.2.0
* PHP v5.6.24 or Higher 
* Redis server v3.2.1
* Gulp v3.9.1


#### Installation
```
$ composer install && npm install
```
Create database and Restore database backup file at /database/ folder
Open ```.env``` and enter necessary config for DB, Oauth Providers Settings and API keys for multiple third party systems.

* Set Addition API keys in .env file (Get from live env file "") 
* Generate app key by running "php artisan key:generate" command
* Run command gulp & gulp watch
* Run command node socke.js in backgroud

```
$ php artisan serve --host=0
```

Open new terminal
```
$ gulp && gulp watch
```

```
$ node socke.js
```

## Built With
* [Laravel] (http://laravel.com)
* [Angularjs] (https://angularjs.org)
* [Twitter Bootstrap] (https://getbootstrap.com)
* [Composer] (https://getcomposer.org/)
* [Gulp.JS] (http://gulpjs.com/)
* [BOWER] (http://bower.io/)
* [NPM] (https://www.npmjs.com/)
