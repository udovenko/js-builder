<?php

namespace Udovenko;


/** 
 * Builder for JavaScript file groups. Contains logic for determing changes in
 * initial files and building/rebuilding compiled output.
 * 
 * @author Denis Udovenko
 * @version 1.1.3
 */
class Builder
{
    const CLOSURE_COMPILER_FILE_NAME = "closure_compiler.jar";
    const YUI_COMPRESSOR_FILE_NAME   = "yui_compressor.jar";
    
    const CLOSURE_COMPILER_COMMAND_TEMPLATE = 
        'java -jar ":jar_file_path" --compilation_level=SIMPLE_OPTIMIZATIONS --language_in ECMASCRIPT5_STRICT --js ":js_file_path" --js_output_file ":output_file_path"';
    
    const YUI_COMPRESSOR_COMMAND_TEMPLATE =
        'java -jar ":jar_file_path" --type=js ":js_file_path" -o ":output_file_path"';
   
    private static $_CACHE_DIRECTORY;
    private static $_JAR_DIRECTORY;
        
    private $_name;
    private $_root;
    private $_out;
    private $_compiler;
    private $_historyFileName;
    private $_history = array();
    private $_files = array();
    
    
    /**
     * Public constructor.
     * 
     * @access public
     * @throws \Exception
     * @param {Array} $config Initial settings array
     */
    function __construct($config) 
    {
        static::$_CACHE_DIRECTORY = realpath(__DIR__ . DIRECTORY_SEPARATOR . "cache");
        static::$_JAR_DIRECTORY   = realpath(__DIR__ . DIRECTORY_SEPARATOR . "jar");
        
        if (!isset($config["name"]) || empty($config["name"])) throw new \Exception("Name not found in config");
        if (!isset($config["out"])  || empty($config["out"]))  throw new \Exception("Out file path not found in config");        
        
        if (!isset($config["root"])) throw new \Exception("Root directory not found in config");
        
        $this->_name = $config["name"];
        $this->_root = $config["root"];
        $this->_out  = $config["out"];
        $this->_compiler = isset($config["compiler"]) ? $config["compiler"] : "closure_compiler";
        $this->_historyFileName = __DIR__ . DIRECTORY_SEPARATOR . 'history' . DIRECTORY_SEPARATOR . $config['name'] . ".php";
        
        $this->_loadHistory();
    }// __construct
         
    
    /**
     * Checks wether given file is chaged agains the history or is new. If so,
     * adds it to process queue.
     * 
     * @access public
     * @throws \Exception
     * @param {String} $fileName File name
     * @return {Builder} Current builder instance
     */
    function addFile($fileName)
    {
        if (!file_exists($this->_root . $fileName)) throw new \Exception("File " . $this->_root . $fileName . " not exists");
        if (isset($this->_files[$fileName]))        throw new \Exception("File $fileName is already added");
 
        // If file was changed or not in the history, add it to process queue:
        $this->_files[$fileName] = md5_file($this->_root . $fileName);
        
        return $this;
    }// addFile
    
    
    /**
     * Determines compiler type from config field and compiles changed files to 
     * cache directory with creating subdirectories from file names if 
     * necessary. If at least one file was recompiled, rewrites history and 
     * remerges build file.
     * 
     * @access public
     * @throws \Exception
     */
    function build()
    {
        // Check cache directory is writable:
        if (!is_writable(static::$_CACHE_DIRECTORY)) throw new \Exception("Web service doesn't have write permitions on $cacheDirRealPath");
        
        // Choose command template depends on given compiler type:
        switch($this->_compiler)
        {
            case "closure_compiler":
                $template = static::CLOSURE_COMPILER_COMMAND_TEMPLATE;
                $jarPath = static::$_JAR_DIRECTORY . DIRECTORY_SEPARATOR . static::CLOSURE_COMPILER_FILE_NAME;
                break;
            case "yui_compressor":
                $template = static::YUI_COMPRESSOR_COMMAND_TEMPLATE;
                $jarPath = static::$_JAR_DIRECTORY . DIRECTORY_SEPARATOR . static::YUI_COMPRESSOR_FILE_NAME;
                break;
            default:
                throw new \Exception("Unsupported compiler type");
        }// switch
                
        $compilations = 0;
        
        foreach($this->_files as $fileName => $checksum)
        {
            $checksumEquals = isset($this->_history[$fileName]) && $this->_history[$fileName] === $checksum;
            $fileExistsInCache = file_exists(static::$_CACHE_DIRECTORY . DIRECTORY_SEPARATOR . $fileName);

            // If history already contains this file, its checksum didn't change and minified file exists in cache - skip it:
            if ($checksumEquals && $fileExistsInCache) continue;
                  
            //
            $this->_createCacheDirectory($fileName);
                        
            // Bind parameters to chosen template:          
            $command = str_replace(":jar_file_path", $jarPath, $template);
            $command = str_replace(":js_file_path", realpath($this->_root . $fileName), $command);
            $command = str_replace(":output_file_path", static::$_CACHE_DIRECTORY . DIRECTORY_SEPARATOR . $fileName, $command);
            
            // Execute compiler:
            exec($command, $output);

            //echo $fileName;
            
            $compilations++;
        }//foreach
        
        // If there were any new compilations, rewrite history file:
        if ($compilations > 0) $this->_storeHistory();
        if ($compilations > 0 || !file_exists($this->_out)) $this->_mergeToBuild();
    }// build
        
    
    /**
     * Returns added file names array (filenames are relative to "root" settng).
     * 
     * @access public
     * @return {Array} File names array
     */
    public function getFiles()
    {
        return array_keys($this->_files);
    }// getFiles
    
       
    /**
     * Loads checksums history if appropriate file exists and has correct format.
     * 
     * @access private
     */
    private function _loadHistory()
    {
        if (file_exists($this->_historyFileName) && is_file($this->_historyFileName))
        {
            $this->_history = require_once $this->_historyFileName;
        }// if
    }// _loadHistory
    
    
    /**
     * Rewrites histroy file with current history array.
     * 
     * @access private
     */
    private function _storeHistory()
    {
        file_put_contents($this->_historyFileName, "<?php return " . var_export($this->_files, true) . ";");
    }// _storeHistory
    
    
    /**
     * Creates directory in cache for given file name, if it's not exists.
     * 
     * @access private
     * @throws \Exception
     * @param {String} $fileName File name (relative to "root" setting)
     */
    private function _createCacheDirectory($fileName)
    {
        $dirToCreate= static::$_CACHE_DIRECTORY . DIRECTORY_SEPARATOR . dirname($fileName);
        
        if (!file_exists($dirToCreate))
        {
            if (!mkdir($dirToCreate, 0777, true))
                throw new \Exception("Directory $newDir can be created");
        }// if
    }// _createCacheDirectory    
    
    
    /**
     * Merges all cached compiled files into output build in same order as they
     * were added.
     * 
     * @access private
     */
    private function _mergeToBuild()
    {
        $outputFile = fopen($this->_out, "w");
        
        //Then cycle through the files reading and writing.
        foreach($this->_files as $name => $checksum)
        {
            $inputFile = fopen(static::$_CACHE_DIRECTORY . DIRECTORY_SEPARATOR . $name, "r");
            while ($line = fgets($inputFile))
            {
               fwrite($outputFile, $line);
            }// while
       
            // Add ";" just in case script not closed properly and close file:
            fwrite($outputFile, ";");
            fclose($inputFile);
        }// foreach

        //Then clean up
        fclose($outputFile);
    }// _mergeToBuild
}// Builder
