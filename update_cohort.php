<?php
$cohort_file = $argv[1];
$release = $argv[2];
$max_procs = $argv[3];

require $cohort_file;

$log_dir = "logs_$release";
if (!is_dir($log_dir)) {
  if (!mkdir($log_dir)) {
    print "ERROR: Failed to create log directory.\n";
    exit(1);
  }
}

$env = 'live';
if (strpos($cohort_file, '-test')) {
  $env = 'test';
}

$update_script = 'update_test.sh';

foreach ($sites as $site) {

  $num_procs = trim(exec("pgrep -f $update_script | wc -l"));
  if ($num_procs < $max_procs) {
    $cmd = "./$update_script $site $env $release";
    $outputfile = $log_dir . DIRECTORY_SEPARATOR . $site . "_" . time() . '.log';
    exec(sprintf("%s > %s 2>&1 &", $cmd, $outputfile), $output, $return);
    if ($return != 0) {
      $error_msg = "ERROR: Failed to execute '$cmd'\n";
      $fh = fopen($outputfile);
      fwrite($fh, $error_msg);
      fclose($fh);
      print $error_msg;
      print "$output\n";
    }
  }
  else {
    //give procs time to finish
    print "Max processes ($max_procs) reached.  Sleeping.\n";
    sleep(300);
  }
}