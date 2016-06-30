<?php

/** Some ghpp (GovHack Portal Processor) utilities **/

function ghpp_line($line){
    global $text;
    $text .= $line . PHP_EOL;
}
function ghpp_map($exists, $key, $value = false){
    if (!$value){
        $value = $exists;
    }
    if (!empty($exists)){
        if (strpos($value, ':') !== false){
            $value = "'" . addslashes($value) . "'";
        }
        ghpp_line($key . ': ' . $value);
    }
}
function ghpp_s3_upload($url){
    // Assume we have aws s3 cli installed and auth configured
    // A big nice to have
}

/** 
* Recursively delete a directory 
* 
* @param string $dir Directory name 
* @param boolean $deleteRootToo Delete specified top-level directory as well 
*/ 
function unlinkRecursive($dir, $deleteRootToo) 
{ 
    if(!$dh = @opendir($dir)) { 
        return; 
    } 
    while (false !== ($obj = readdir($dh))) { 
        if($obj == '.' || $obj == '..') { 
            continue; 
        } 

        if (!@unlink($dir . '/' . $obj)) { 
            unlinkRecursive($dir.'/'.$obj, true); 
        } 
    } 

    closedir($dh); 
    
    if ($deleteRootToo) { 
        @rmdir($dir); 
    } 
    
    return; 
} 

/**
 * Recursive copy
 * @author <gimmicklessgpt at gmail dot com>
 * http://php.net/manual/en/function.copy.php#91010
 */
function recurse_copy($src,$dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 