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

Usage
------    
If you've installed Builder with Composer, it should automatically be available under "\Udovenko" namespace. To instantiate class you have to pass an array three parameters:

* **name** _- The name of build. Will be also used as history file name._
* **root** _- The root directory where your created your scripts you now want to compile. Usually it is something like "js/classes"._ 
* **out** _- The full name for output build file, like "js/build/build.js"._ 

So new builder can be created this way:

    $builder = new \Udovenko\Builder(array(
        "name" => "classes_build",
        "root" => "js/classes",
        "out"  => "js/build/classes.js"
    ));
    
Now it's time to tell which files you want to compile. You can do it one-by-one way by using "addFile" method for chaining calls:

    $builder->addFile("contrllers/MenuController.js")
        ->addFile("contrllers/ContentController.js")
        ->addFile("contrllers/NewsController.js")
        ...

Or you can add an array of files:
    
    $builder->addFiles(array(
        "contrllers/MenuController.js",
        "contrllers/ContentController.js",
        "contrllers/NewsController.js"
    ));

You're able to combine both methods together:

    $builder
        ->addFiles(array(
            "contrllers/MenuController.js",
            "contrllers/ContentController.js",
        ))
        ->addFile("contrllers/NewsController.js")
        ...