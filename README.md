pathfinder
===========

A REST API built using Symfony. This project creates a Graph based on the packages on Packagist and all the github users that have contributed to these packages.
The API has two methods:
1. Calculates the shortest path between the two given github users that contributed to the PHP packages on packsgist.
2. Returns a list of ranked github users who might be a good candidate to contribute to the given package.

You can try the live demo [here](http://52.56.92.140/).

Requirements
------------

  * PHP 5.5.9 or higher;
  * The usual [Symfony application requirements](http://symfony.com/doc/current/reference/requirements.html).
  * [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle).
  * [JMSSerializerBundle](https://github.com/schmittjoh/JMSSerializerBundle).
  * [NelmioCorsBundle](https://github.com/nelmio/NelmioCorsBundle).;
  * [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle).
  * [PHPUnit](https://phpunit.de/) required for running the tests.

Installation
------------

1. Install the [Symfony Installer](https://github.com/symfony/symfony-installer) if you haven't already.
2. Install [mysql](https://www.mysql.com/).
3. Install [composer](http://getcomposer.org/) following the instructions [here](http://getcomposer.org/).
4. Clone this repo `git clone https://github.com/aliminaei/pathfinder.git`
5. Update/Install the project dependencies by executing the command below:
```bash
$ cd pathfinder
$ composer install
```

Usage
-----
Update your database connection settings in `config.yml` and `parameters.yml` and create a database named pathfinder.
Then execute the line below in your terminal to create all tables in your database:

```bash
$ php bin/console doctrine:schema:update --force
```


Before using the APIs you need to build your parse the packages and build your database.
The package parser's documentation can be find [here](https://github.com/aliminaei/pathfinder/tree/master/package_parser).
You can also build your database by using the provided [database backup]().

After you built the graph, you can start using the API.
There is no need to configure a virtual host in your web server to access the application.

Make sure all the tests are green by running:
```bash
$ phpunit
```

Now just use the built-in web server to start the server:

```bash
$ php bin/console server:run
```

This command will start a web server for the Symfony application. Now you can
access the application in your browser at <http://localhost:8000>. You can
stop the built-in web server by pressing `Ctrl + C` while you're in the
terminal.

After you built the graph, you can start using the API methods:

1. <b>GET /api/path/{username1}/{username2}</b>: 
  
  <b>Live demo example:</b> http://52.56.92.140/api/path/pepakriz/zerkalica

  Please note that user names are case sensitive.
  Returns the shortest path between two users in the format below:
  ```
    {
        "ack": "OK",
        "path_len": 2,
        "path": 
            [
                "Package1",
                "Package2"
            ]
    }
  ```

2. <b>GET /api/packages/{vendorName}/{packageName}/potentials:</b>
  
  <b>Live demo example:</b> http://52.56.92.140/api/packages/00f100/uuid/potentials

  Returns a list of potential contributors for the given package. Potential contributors are ranked based on their total contributions to the other PHP packages on packagist.
  Please note that package name is case sensitive.
  The reposne should look like: 
  ```
  {
    "ack": "OK",
    "potential_contributors": 
    [
        {
            "name": "User1",
            "score": 6
        }, {
            "name": "User2",
            "score": 4
        }, {
            "name": "User3",
            "score": 3
        }
    ]
  }
  ```

