JavaScript Builder
==================

* Current version: 1.1.3

------------------------
A simple PHP class you can use to compile multiple JS files into single minified build. Stores minified files in **cache directory** and uses **md5 checksums history** to identify further changes.

--------------------------
Installation with Composer
--------------------------
To install Builder class with PHP Composer, first add current GitHub repo into your composer.json file:

    "repositories":[
        ...
        {      
            "type": "vcs",
            "url": "https://github.com/udovenko/js-builder"
        }
    ],
    ...
    
And then add Builder to "require" section:

    "require": {
        ...
        "udovenko/js-builder": "*"
    },
    ...
After that don't forget to run update command in your project directory:

    php composer.phar update