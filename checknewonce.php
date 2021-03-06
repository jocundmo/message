<?php /****** Simple Instant Message ******/ ?>
<?php $version = "2.2"; ?>
<?php header("Cache-Control: no-cache"); ?>
<?php require "base64url.php"; ?>
<?php
if (isset($_GET['doc'])){
  //$doc = rawurlencode($_GET['doc']);
  $doc = $_GET['doc'];
  $file = "docs/" . base64urlencode($doc);
  $pos = intval($_GET['pos']);
  if (function_exists("inotify_init")){
    $fd = inotify_init();
    
    while (!file_exists($file))usleep(200000);
    $fp = fopen($file, "r");
    flock($fp, LOCK_SH);
    
    clearstatcache();
    if (filesize($file) > $pos){
      fseek($fp, $pos);
      $data = "";
      while (!feof($fp)){
        $data .= fread($fp, 8192);
      }
      $pos = ftell($fp);
      flock($fp, LOCK_UN);
      fclose($fp);
      die(rawurlencode($data) . "\n" . $pos);
    } else if (filesize($file) < $pos){
      die("\n0");
    }
    
    $watch = inotify_add_watch($fd, $file, IN_ALL_EVENTS);
    flock($fp, LOCK_UN);
    fclose($fp);
    while (true){
      $events = inotify_read($fd);
      foreach ($events as $event => $details){
        if ($details["mask"] & IN_CLOSE_WRITE){
          $fp = fopen($file, "r");
          flock($fp, LOCK_SH);
          fseek($fp, $pos);
          $data = "";
          while (!feof($fp)){
            $data .= fread($fp, 8192);
          }
          $pos = ftell($fp);
          flock($fp, LOCK_UN);
          fclose($fp);
          die(rawurlencode($data) . "\n" . $pos);
        }
        if ($details["mask"] & IN_IGNORED){
          die("\n0");
        }
      }
    }
  } else {
    while (true){
      while (!file_exists($file))usleep(200000);
      
      clearstatcache();
      if (filesize($file) > $pos){
        $fp = fopen($file, "r");
        flock($fp, LOCK_SH);
        fseek($fp, $pos);
        $data = "";
        while (!feof($fp)){
          $data .= fread($fp, 8192);
        }
        $pos = ftell($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        die(rawurlencode($data) . "\n" . $pos);
      } else if (filesize($file) < $pos){
        die("\n0");
      }
      usleep(200000);
    }
  }
}
?>