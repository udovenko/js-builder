JavaScript Builder
==================

* Current version: 1.1.3
* Requires PHP: 5.3

A simple PHP class you can use to compile multiple JS files into single minified build. Stores minified files in **cache directory** and uses **md5 checksums history** to identify further changes.

> Since Builder uses **.jar** compilers to minify JS code, you need to have Java Runtime Envoronment running on your machine.

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
-----
    
If you've installed Builder with Composer, it should automatically be available under "\Udovenko" namespace. To instantiate class you have to pass an array four parameters:

* **name** _- The name of build. Will be also used as history file name._
* **root** _- The root directory where your created your scripts you now want to compile. Usually it is something like "js/classes"._ 
* **out** _- The full name for output build file, like "js/build/build.js"._ 
* **compiler (optional)** _- Type of compiler you want to use. Can be "closure_compiler" (default) or "yui_compressor"._

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
    
When all files are in place, you have nothing more to do but call "build" method and wait (probably for a long time is you added 50 files, thou):

    $builder->build();
    
When compiling is finished, you should have your new "js/build/classes.js" file in place and updated "classes_build.php" history file in "history" directory of builder package. From now, if you'll update one of your JS classes in JS root, builder will compile only this file, take all others not changed files from cache and rebuild output file enough quickly.