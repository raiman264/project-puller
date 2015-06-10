<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "config.php";

define("CORE_PATH",realpath(dirname(__FILE__)));

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

      
      #$command = CORE_PATH."/git.sh $ssh_key pull";
      $command = "git pull";
      echo "\n".$command."\n";
      exec ( $command, $output, $return_var );

      print_r($output);

      var_dump("result,", $return_var);
      
      if($return_var == 0){
         echo "\nsuccess";
      }
   }

   echo "ok";
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