# Laravel 5 with WebSockets and Queue Asynchronously

## Install a clean Laravel 5

`composer create-project laravel/laravel --prefer-dist /path/to/your/laravel`

remove compiled.php from vendor folder then update dependencies

`composer update`

`php artisan optimize`

##Create vhost for queue-sockets.dev

`sudo nano /etc/nginx/conf.d/queue-sockets.conf`

and add config

```
server {

    listen  80;
    server_name queue-sockets.dev www.queue-sockets.dev;
    set $root_path '/var/www/queue-sockets/public';
    root $root_path;

    index index.php index.html index.htm;

    try_files $uri $uri/ @rewrite;

    location @rewrite {
        rewrite ^/(.*)$ /index.php?_url=/$1;
    }

    location ~ \.php {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index /index.php;

        include /etc/nginx/fastcgi_params;

        fastcgi_split_path_info       ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO       $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* ^/(css|img|js|flv|swf|download)/(.+)$ {
        root $root_path;
        expires 0;
        break;
    }

    location ~ /\.ht {
        deny all;
    }

}
```

then reload nginx

`sudo service nginx reload`

and test if http://queue-sockets.dev/ is workin` ok!


## Create users

**edit .env file to fill database credentials**

run `php artisan migrate:install` and `php artisan migrate` to create and load default users tables

then go to http://queue-sockets.dev/auth/register and create two users as you want.


## Websockets

**update composer.json with**

```
"illuminate/html": "5.0.*",
"firebase/php-jwt": "1.*",
"cboden/ratchet": "0.3.*",
"textalk/websocket": "1.0.*"
```

then do a `composer update` to install dependencies

**open config/app.php**

at providers to the end add
```
'Illuminate\Html\HtmlServiceProvider',
``` 

at aliases to the end add 
```
'Form'   => 'Illuminate\Html\FormFacade',
'HTML'   => 'Illuminate\Html\HtmlFacade',
```

**open app/Services folder and add these tree services classes from our archive**

```
app/Services/Client.php
app/Services/Websocket.php
app/Services/Jwt.php
```

**open app/Console/Commands and add one command class from our archive**

```
app/Console/Commands/Websocket.php
```

then edit `app/Console/Kernel.php` and add `'App\Console\Commands\Websocket',` in $commands

**again, edit .env file and add WebSocket configuration**

```
#websocket
SOCKET_PORT=8181
SOCKET_ADRESS=localhost
```

*use any port you want, i use 8181 that is an opened port on my vagrant*

then test if WebSocket server is workin`

`php artisan websocket`

if there is no errors then seems that Socket Server is up and running!

**Don`t forget! If you make any changes in app/Services/Websocket.php, you should restart socket server**


## Create routes, controllers and views for our WebSocket Notice System

**open app/Http/routes.php and add**

```
Route::group(['prefix' => 'api', 'middleware' => 'api'], function() {
    Route::post('auth', 'ApiController@Auth');
    Route::get('jwt', 'ApiController@getJwt');
});


Route::get('notice/{type?}/{userId?}/{message?}', 'NoticeController@notice');
Route::get('noticeQueue', 'NoticeController@queue');
```

**open app/Http/Controllers and add these tree controllers from our archive**

```
app/Http/Controllers/Controller.php
app/Http/Controllers/ApiController.php
app/Http/Controllers/NoticeController.php
```

**open app/Http/Middleware/ and add these two middlewares from our arvhive**

```
app/Http/Middleware/Api.php
app/Http/Middleware/Jwt.php
```

then open app/Http/Kernel.php and add them to $routeMiddleware

```
'api' => 'App\Http\Middleware\Api',
'jwt' => 'App\Http\Middleware\Jwt',
```


**open resources/views/ and replace app.blade.php view from our archive**
*be carrefu, you should replace it only if you have a fresh installation, else you could broke your existing file!*

```
resources/views/app.blade.php
```

**open public/ and add js/ folder from our archive**

```
public/js/
```

#Let`s do a test

1) Open two browsers and login with two users you created

`http://queue-sockets.dev/auth/login`

2) Open http://queue-sockets.dev/home

And take a look into terminal. You should see these two connection like:

`New connection! (332)`

3) Fire a Socket Notice to a specified userid, id (1 or 2)

`http://queue-sockets.dev/notice/toast/1/Test Message`

4) Fire a Socket Notice to all connected users

`http://queue-sockets.dev/notice/broadcast/0/Test Message`


#Queue Async with Beanstalkd

`sudo apt-get update`
`sudo apt-get upgrade`

`sudo apt-get install beanstalkd`
`sudo nano /etc/default/beanstalkd`

**Change listen address**

`BEANSTALKD_LISTEN_ADDR=127.0.0.1` to `BEANSTALKD_LISTEN_ADDR=0.0.0.0`

and add at the end

`START=yes`

**Start Beanstalkd!**

`sudo service beanstalkd start`

**Edit .env file and change queue driver with**

`QUEUE_DRIVER=beanstalkd`

**Add pda/pheanstalk laravel package**

`composer require pda/pheanstalk`


##Install Supervisor

*In my vagrant ubuntu 14.04 it was already installed but i prefer to remove the old one and install it in correct path (you should see if you have it installed. Be careful if you want to delete it, you have to see exactly how has been configured)*

`sudo apt-get remove supervisor --purge`
`sudo rm -rf /var/log/supervisor`
`sudo apt-get autoremove`

**Install Supervisor**

`sudo apt-get install supervisor`

Add queue.conf

`sudo nano /etc/supervisor/conf.d/queue.conf`

Add these to this config:

```
[program:queue]
command=php artisan queue:listen --tries=2
directory=/path/to/your/laravel
stdout_logfile=/path/to/your/laravel/storage/logs/queue_supervisord.log
redirect_stderr=true
autostart=true
autorestart=true
```

Open supervisor control 

`sudo supervisorctl`

then run

`reread`
`add queue`
`start queue`

if it's saying that it's already started, just don't panic, it`s ok! :)


##Now Let`s test!

In app/Http/Controllers/NoticeController.php you see that we already have a method to fire a queue async with a callback to user 2 with a message.

From browser where you are logged with user 2, open new tab and fire the queue on 

`http://queue-sockets.dev/noticeQueue`

Then see in tab http://queue-sockets.dev/notice if you received that specific alert.

Then open `storage/logs` and see in laravel-....log if the message is writed.

Now, you could uncomment `//sleep(60);` from queue method, and test it again to see if work in background as you need!
After 60 seconds, you will see again notice on user 2 tab and log updated.

That`s all!















