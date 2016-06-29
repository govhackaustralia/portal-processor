<?php
//==========================
// GovHack Portal Processor
//==========================

if (preg_match('/_health\/?/', $_SERVER["REQUEST_URI"])) {
    
    // This is a health check
    die('OK');
    
} 
else { 

    echo "<p>Welcome to PHP</p>";
    
}