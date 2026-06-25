[&laquo; Back to the README.md](../README.md)

# Installation Documentation

## Docker

-   Install [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)
-   Clone the repo

    ```
    git clone https://github.com/diekantankys/strepen.git
    cd strepen
    ```

-   Copy the example env file and generate an app key

    ```
    cp server/.env.example server/.env
    docker compose run --rm app php artisan key:generate
    ```

-   Start the services

    ```
    docker compose up -d
    ```

-   Run database migrations and seed initial data

    ```
    docker compose exec app php artisan migrate --seed
    ```

-   Goto http://localhost:8000/ and you're done!

-   Optional: run the unit and feature tests

    ```
    docker compose exec app php artisan test --parallel
    ```

## Windows

-   Install [XAMPP](https://www.apachefriends.org/download.html) Apache web server, PHP and MySQL database
-   Install [Composer](https://getcomposer.org/download/) PHP package manager
-   Clone repo in the `C:/xampp/htdocs` folder

    ```
    cd C:/xampp/htdocs
    git clone https://github.com/diekantankys/strepen.git
    cd strepen
    ```

-   Install deps via Composer

    ```
    cd server
    composer install
    ```

-   Copy `server/.env.example` to `server/.env`
-   Generate Laravel security key

    ```
    php artisan key:generate
    ```

-   Link the storage and public folder together

    ```
    php artisan storage:link
    ```

-   Add following lines to `C:/xampp/apache/conf/extra/httpd-vhosts.conf` file

    ```
    # Strepen vhosts

    <VirtualHost *:80>
        ServerName strepen.test
        DocumentRoot "C:/xampp/htdocs/strepen/server/public"
    </VirtualHost>

    <VirtualHost *:80>
        ServerName www.strepen.test
        Redirect permanent / http://strepen.test/
    </VirtualHost>
    ```

-   Add the following lines to `C:/Windows/System32/drivers/etc/hosts` file **with administrator rights**

    ```
    # Strepen local domains
    127.0.0.1 strepen.test
    127.0.0.1 www.strepen.test
    ```

-   Start Apache and MySQL via XAMPP control panel
-   Create MySQL user and database

    ```sql
    CREATE USER 'strepen'@'localhost' IDENTIFIED BY 'strepen';
    CREATE DATABASE `strepen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON `strepen`.* TO 'strepen'@'localhost';
    FLUSH PRIVILEGES;
    ```

-   Fill in MySQL user, password and database information in `server/.env`
-   Create database tables

    ```
    php artisan migrate --seed
    ```

-   Goto http://strepen.test/ and you're done! 🎉
-   Optional: You can import all the data from the [old Strepen System](https://github.com/JohnOnline88/strepensysteem) (Doesn't work anymore)

    ```
    php artisan import-data 'http://stam.diekantankys.nl'
    ```

-   Optional: You could run the automatic PHP linter & fixer

    ```
    php artisan lint
    ```

-   Optional: You could run the unit and feature tests

    ```
    php artisan test --parallel
    ```

## macOS

-   Follow [the first page of this great tutorial](https://getgrav.org/blog/macos-monterey-apache-multiple-php-versions) to setup Homebrew, Apache and PHP 7.4+ on your Mac
-   Install the MySQL database via Homebrew and start it:

    ```
    brew install mysql
    brew services start mysql
    ```

-   Install composer via Homebrew:

    ```
    brew install composer
    ```

-   Clone repo in the `/Users/{username}/Sites` folder

    ```
    cd ~/Sites
    git clone https://github.com/diekantankys/strepen.git
    cd strepen
    ```

-   Install deps via Composer

    ```
    cd server
    composer install
    ```

-   Copy `server/.env.example` to `server/.env`
-   Generate Laravel security key

    ```
    php artisan key:generate
    ```

-   Link the storage and public folder together

    ```
    php artisan storage:link
    ```

-   Add these lines to `/opt/homebrew/etc/httpd/extra/httpd-vhosts.conf`

    ```
    # Strepen vhosts

    <VirtualHost *:80>
        ServerName strepen.test
        DocumentRoot "/Users/{username}/Sites/strepen/server/public"
    </VirtualHost>

    <VirtualHost *:80>
        ServerName www.strepen.test
        Redirect permanent / http://strepen.test/
    </VirtualHost>
    ```

-   And uncomment this line in `/opt/homebrew/etc/httpd/httpd.conf`

    ```
    # Virtual hosts
    Include /opt/homebrew/etc/httpd/extra/httpd-vhosts.conf
    ```

-   Restart apache

    ```
    brew services restart httpd
    ```

-   Add following lines to `/etc/hosts` file **as root**

    ```
    # Strepen local domains
    127.0.0.1 strepen.test
    127.0.0.1 www.strepen.test
    ```

-   Create MySQL user and database

    ```sql
    CREATE USER 'strepen'@'localhost' IDENTIFIED BY 'strepen';
    CREATE DATABASE `strepen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON `strepen`.* TO 'strepen'@'localhost';
    FLUSH PRIVILEGES;
    ```

-   Fill in MySQL user, password and database information in `server/.env`
-   Create database tables

    ```
    php artisan migrate --seed
    ```

-   Goto http://strepen.test/ and you're done! 🎉
-   Optional: You can import all the data from the [old Strepen System](https://github.com/JohnOnline88/strepensysteem) (Doesn't work anymore)

    ```
    php artisan import-data 'http://stam.diekantankys.nl'
    ```

-   Optional: You could run the automatic PHP linter & fixer

    ```
    php artisan lint
    ```

-   Optional: You could run the unit and feature tests

    ```
    php artisan test --parallel
    ```

## Linux

### Ubuntu based distro's

-   Install LAMP stack

    ```
    sudo apt install apache2 php php-dom mysql-server composer
    ```

-   Fix `/var/www/html` Unix rights hell

    ```
    # Allow Apache access to the folders and the files
    sudo chgrp -R www-data /var/www/html
    sudo find /var/www/html -type d -exec chmod g+rx {} +
    sudo find /var/www/html -type f -exec chmod g+r {} +

    # Give your owner read/write privileges to the folders and the files, and permit folder access to traverse the directory structure
    sudo chown -R $USER /var/www/html/
    sudo find /var/www/html -type d -exec chmod u+rwx {} +
    sudo find /var/www/html -type f -exec chmod u+rw {} +

    # Make sure every new file after this is created with www-data as the 'access' user.
    sudo find /var/www/html -type d -exec chmod g+s {} +
    ```

-   Clone repo in the `/var/www/html` folder

    ```
    cd /var/www/html
    git clone https://github.com/diekantankys/strepen.git
    cd strepen
    ```

-   Install deps via Composer

    ```
    cd server
    composer install
    ```

-   Copy `server/.env.example` to `server/.env`
-   Generate Laravel security key

    ```
    php artisan key:generate
    ```

-   Link the storage and public folder together

    ```
    php artisan storage:link
    ```

-   Create the file `/etc/apache2/sites-available/strepen.conf` **as root**

    ```
    # Strepen vhosts

    <VirtualHost *:80>
        ServerName strepen.test
        DocumentRoot "/var/www/html/strepen/server/public"
    </VirtualHost>

    <VirtualHost *:80>
        ServerName www.strepen.test
        Redirect permanent / http://strepen.test/
    </VirtualHost>
    ```

-   Enable the site

    ```
    sudo a2ensite strepen
    ```

-   Edit this line in `/etc/apache2/apache2.conf` at `AllowOverride` from `None` to `All` **as root**

    ```
    <Directory /var/www/>
        ...
        AllowOverride All
        ...
    </Directory>
    ```

-   Enable the Apache rewrite module

    ```
    sudo a2enmod rewrite
    ```

-   Restart apache

    ```
    sudo service apache2 restart
    ```

-   Add following lines to `/etc/hosts` file **as root**

    ```
    # Strepen local domains
    127.0.0.1 strepen.test
    127.0.0.1 www.strepen.test
    ```

-   Create MySQL user and database

    ```sql
    CREATE USER 'strepen'@'localhost' IDENTIFIED BY 'strepen';
    CREATE DATABASE `strepen` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON `strepen`.* TO 'strepen'@'localhost';
    FLUSH PRIVILEGES;
    ```

-   Fill in MySQL user, password and database information in `server/.env`
-   Create database tables

    ```
    php artisan migrate --seed
    ```

-   Goto http://strepen.test/ and you're done! 🎉
-   Optional: You can import all the data from the [old Strepen System](https://github.com/JohnOnline88/strepensysteem) (Doesn't work anymore)

    ```
    php artisan import-data 'http://stam.diekantankys.nl'
    ```

-   Optional: You could run the automatic PHP linter & fixer

    ```
    php artisan lint
    ```

-   Optional: You could run the unit and feature tests

    ```
    php artisan test --parallel
    ```
