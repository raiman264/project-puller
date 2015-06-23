<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("error_log", "php-error.log");

include "config.php";

define("CORE_PATH",realpath(dirname(__FILE__)));

define("DB_EVOLUTIONS_FOLDER","db_evolutions");

$request_body = file_get_contents('php://input');


if( validate_request($request_body) ) {
   $json_request = json_decode($request_body);
   #$star = "----------------------\n".print_r($json_request,1)."\n\n";
   #file_put_contents("log.txt",$star,FILE_APPEND);

   echo CORE_PATH."\n";

   $ssh_key = "-i ".CORE_PATH."/".SSH_KEY_FILE;

   if( !file_exists(BASE_DIRECTORY."/".$json_request->repository->name) ){
      echo "proyect doesnt exist, cloning repo\n";

      echo "chdir ".BASE_DIRECTORY."\n";
      chdir(BASE_DIRECTORY);
      $output = array();
      #exec("ssh-keygen -R github.com", $output);

      #use ssh
      #$command = CORE_PATH."/git.sh $ssh_key clone ".escapeshellcmd($json_request->repository->ssh_url);

      #use https
      $command = "git clone ".escapeshellcmd($json_request->repository->clone_url);

      echo "\n".$command."\n";
      exec ( $command, $output, $return_var );

      print_r($output);

      var_dump("result,", $return_var);
      
      if($return_var == 0){
         echo "\nsuccess";
      }
   } else {
       echo "proyect exist, pulling data\n";

      chdir(BASE_DIRECTORY."/".$json_request->repository->name);
      
      $output = array();
      #exec("ssh-keygen -R github.com", $output);

      $prev_evolutions = get_prev_evolutions(DB_EVOLUTIONS_FOLDER);

      echo "\n----prev evos---\n";
      print_r($prev_evolutions);
      
      #$command = CORE_PATH."/git.sh $ssh_key pull";
      $command = "git pull";
      echo "\n".$command."\n";
      exec ( $command, $output, $return_var );

      print_r($output);

      var_dump("result,", $return_var);

      if($return_var == 0){
         echo "\nsuccess";

         run_evolutions(DB_EVOLUTIONS_FOLDER,$prev_evolutions);
      }
   }

   echo "\n\nend script";
} else {
   http_response_code(401);
   die("401 verification failed");
}

function validate_request($payload) {
   return true;
   $algo = "sha1";
   $signature = hash_hmac ( $algo , $payload , GITHUB_SECRET);

   return isset($_SERVER['HTTP_X_HUB_SIGNATURE']) && $_SERVER['HTTP_X_HUB_SIGNATURE'] == $algo."=".$signature;
}

function get_prev_evolutions($dirname) {
   $file_names = array();

   if(file_exists($dirname)){
      $files = scandir($dirname,SCANDIR_SORT_ASCENDING);

      foreach ($files as $file_name) {
         if (preg_match('/^.*\.sql$/',$file_name)) {
            $file_names[] = $file_name;
         }
      }
   }

   return $file_names;
}

function run_evolutions($dirname, $skip = array()) {
   if(file_exists($dirname)){
      echo "\n look for evolutions";
      $files = scandir($dirname,SCANDIR_SORT_ASCENDING);
      $evolutions = array();

      foreach ($files as $file_name) {
         echo "\n testing: ",$file_name;

         if (preg_match('/^.*\.sql$/',$file_name)) {
            echo "\n is in skip list ";
            var_dump(!in_array($file_name, $skip));
            if(!in_array($file_name, $skip)){
               $evolutions[] = $file_name;
            }
         }
      }

      if(count($evolutions) > 0){
         echo "--------------------------------------------\nbackingup db\n-------------------------------------------";
         #@TODO: set just current db
         $command = "mysqldump --all-databases -u".DB_USER." -p".DB_PASS." > db_backup_".date("Ymd-His").".sql";
         exec ( $command, $output);
         print_r($output);

         foreach ($evolutions as $evo) {
            echo "executing ".$evo."\n";

            $command = "mysql -u".DB_USER." -p".DB_PASS." < ".$dirname."/".$evo;

            exec ( $command, $output);
            print_r($output);

         }
         print_r($output);
      }
   }
}