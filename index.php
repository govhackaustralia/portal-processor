<?php
//==========================
// GovHack Portal Processor
//==========================

date_default_timezone_set('Australia/Sydney');
require_once __DIR__ . '/utils.inc.php';

if (preg_match('/_health\/?/', $_SERVER["REQUEST_URI"])) {
    
    // This is a health check
    header('Content-Type: text/plain');
    die('OK');
    
} 


$cfg = parse_ini_file( __DIR__ . '/.processor.ini' );

// Check for post stuff, which should be a stream of all the sponsors
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = file_get_contents('php://input');
    
    $sponsors = json_decode($payload);
    $num = count($sponsors);
    
    header('Content-Type: text/plain');
    // echo json_encode($sponsors, JSON_PRETTY_PRINT);
    
    echo "$num sponsors received", PHP_EOL;
    
    $tempDirRoot = __DIR__ . '/temp/load-' . date('YmdHis');
    mkdir($tempDirRoot, 0755, true);
    echo "Created staging directory $tempDirRoot", PHP_EOL;
    
    $tempDir = "$tempDirRoot/_organisations/sponsors";
    mkdir($tempDir, 0755, true);
    
    // Generate a markdown file for each sponsor
    echo "Starting generation of sponsor .md files...", PHP_EOL;
    foreach ($sponsors as $sponsor){
        $text = '';
        
        ghpp_line('---');
        ghpp_map($sponsor->post_name, 'gid');
        ghpp_map($sponsor->post_title, 'title');
        ghpp_map($sponsor->post_title, 'name');
        ghpp_map($sponsor->meta_sponsor_portal_type, 'type');
        ghpp_map($sponsor->meta_link_sponsor, 'website_url');
        
        // Do a URL domain remapping
        if (isset($sponsor->image) && !empty($cfg['to_domain']) && is_array($cfg['from_domains'])){
            foreach ($cfg['from_domains'] as $fromDomain){
                $sponsor->image = str_replace($fromDomain, $cfg['to_domain'], $sponsor->image);
            }
        }
        ghpp_map($sponsor->image, 'logo_url', '');
        
        // Check if it's tagged as a sponsor
        if (count($sponsor->national_types) > 0){
            $nationalSponsor = $sponsor->national_types[0];
            ghpp_map($nationalSponsor->name, 'sponsor_level');
            ghpp_map($nationalSponsor->slug, 'sponsor_level_id');
            ghpp_map($nationalSponsor->description, 'sponsor_level_desc');
            ghpp_map('australia', 'jurisdiction');
        }
        elseif (count($sponsor->state_types) > 0){
            $stateSponsor = $sponsor->state_types[0];
            ghpp_map($stateSponsor->name, 'sponsor_level');
            ghpp_map($stateSponsor->slug, 'sponsor_level_id');
            ghpp_map($stateSponsor->description, 'sponsor_level_desc');
            ghpp_map($sponsor->region, 'jurisdiction');
        }
        elseif (count($sponsor->local_types) > 0){
            $localSponsor = $sponsor->local_types[0];
            ghpp_map($localSponsor->name, 'sponsor_level');
            ghpp_map($localSponsor->slug, 'sponsor_level_id');
            ghpp_map($localSponsor->description, 'sponsor_level_desc');
            ghpp_map($sponsor->region, 'jurisdiction');
        }
        
        if (isset($sponsor->locations) && is_array($sponsor->locations)){
            ghpp_line('events:');
            foreach ($sponsor->locations as $locationGid){
                ghpp_line("  - $locationGid");                
            }
        }
        
        ghpp_line('is_sponsor: true');
        ghpp_line('---');
        
        if (isset($cfg['show_post_content']) && $cfg['show_post_content']){
            // Not adding the content by default... potential vulnerability
            if (isset($sponsor->post_content)){
                ghpp_line($sponsor->post_content);
            }
        }
        
        file_put_contents("$tempDir/{$sponsor->post_name}.md", $text);
        echo "Written {$sponsor->post_name}.md", PHP_EOL;

    }
    echo "Write done.", PHP_EOL;
    
    // Now, copy these files into a git branch
    if (isset($cfg['repo_root_relative'])){
        echo "Repo root is configured.", PHP_EOL;
        
        $curr = shell_exec('pwd');
        $rrr = __DIR__ . '/' . $cfg['repo_root_relative'];
        
        // Change dir, and checkout a new branch
        chdir($rrr);
        // $branchName = 'auto/sponsors-' . date('Ymd-hia');
        $branchName = 'auto/sponsors';
        echo shell_exec("git checkout master");
        echo shell_exec("git pull origin master");
        echo shell_exec("git checkout -B $branchName"); 
        
        // Now we nuke the branch, shove all the new files in there
        recurse_copy($tempDirRoot, $rrr);
        echo "Copied files to repo.", PHP_EOL;
        echo shell_exec("git status");
        
        echo shell_exec("git add _organisations/sponsors");
        echo "Added files.", PHP_EOL;
        
        echo shell_exec('git commit -m "[Auto] Sponsors regenerated at '.date('h:ia').' AEST '.date('Y-m-d').'"');
        echo shell_exec("git push -uf origin $branchName");
        echo shell_exec("git checkout master");
        
        chdir($curr);
    }
    
    unlinkRecursive($tempDir, true);
    echo "Deleted staging directory $tempDir", PHP_EOL;
    
    die();
    
}

header('Content-Type: text/plain');
die('Nothing received');