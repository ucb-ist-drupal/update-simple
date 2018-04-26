<?php
$cohort_file = $argv[1];
$release = $argv[2];
$max_procs = $argv[3];

$TEST = TRUE;

if (empty(getenv('S3_UPDATES_BUCKET'))) {
  print "Error: You must export the environment variable 'S3_UPDATES_BUCKET' before using this script.\n";
  exit(1);
}
else {
  $updates_bucket = getenv('S3_UPDATES_BUCKET');
}

if ($TEST) {
  $update_script = 'update_test.sh';
  $s3url = "$updates_bucket/dev";
  $sleep_max_proc = 10;
  $max_proc_attempts = 10;
  $sleep_s3_sync = 10;
  $s3_sync_attempts = 10;
}
else {
  $update_script = 'update.sh';
  $s3url = "$updates_bucket/prod";
  $sleep_max_proc = 120;
  $max_proc_attempts = 30;
  $sleep_s3_sync = 120;
  $s3_sync_attempts = 20;
}


$log_dir = "$release";

require $cohort_file;


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

foreach ($sites as $site) {
  $proc_data = monitor_processes($update_script);
  $i = 0;
  while ($proc_data['number'] == $max_procs && $i < $max_proc_attempts) {

    print "Notice: Max processes ($max_procs) reached.  Sleeping.\n";
    sleep($sleep_max_proc);
    // Sync any new log messages to S3.
    s3_sync($s3url, $log_dir);

    $i++;
    if ($i == $max_proc_attempts) {
      print "Error: " . $proc_data['number'] . " long-running processes have not finished after waiting " . round(($i * $max_proc_attempts) / 60, 2) . " minutes.\n";
      print "Error: Process IDs:\n" . $proc_data['list'] . "\nAborting.\n";
      exit(2);
    }

    // Recheck processes.
    $proc_data = monitor_processes($update_script);
  }

  $cmd = "./$update_script $site $env $release";
  $outputfile = $log_dir . DIRECTORY_SEPARATOR . $site . "_" . time() . '.log';
  exec(sprintf("%s > %s 2>&1 &", $cmd, $outputfile), $output, $return);
  // $return just tells us if the process was successfully initiated in the background.
  // We don't wait on the bg process to find its true status. Rely on log file for that.
  if ($return != 0) {
    $error_msg = "Error: Failed to execute '$cmd'\n";
    print $error_msg;
    print "$output\n";
  }

}

// Wait for all processes to finish and do a final log sync to s3
$max_attempts = $s3_sync_attempts;
print "Notice: Final log sync to S3.\n";
for ($j = 1; $j <= $max_attempts; $j++) {
  $proc_data = monitor_processes($update_script);
  if ($proc_data['number'] == 0) {
    s3_sync($s3url, $log_dir);
    print "Notice: Finished with $cohort_file.\n";
    break;
  }
  if ($j == $max_attempts) {
    print "Warning: Processes still running. Doing final S3 sync and aborting.\n";
    s3_sync($s3url, $log_dir);
  }
  else {
    print "Notice: S3 sync attempt $j: Processes still running.\n";
    sleep($sleep_s3_sync);
    // Sync anyway to upload any new log messages.
    s3_sync($s3url, $log_dir);
  }
}

function monitor_processes($update_script) {

  $values = [];

  $cmd = "pgrep -f $update_script";
  // If pgrep finds no processes it exits with 1, so don't check exit status.
  exec($cmd, $out);

  $values = [
    'number' => count($out),
    'list' => implode("\n", $out)
  ];

  return $values;
}

function s3_sync($s3url, $log_dir) {
  print "Notice: Log sync to s3.\n";
  $s3_cmd = "aws s3 sync $log_dir $s3url/$log_dir";
  $s3_outputfile = DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . "s3_sync.log";
  //sync logs to s3. It's async, so background this.
  exec(sprintf("%s >> %s 2>&1 &", $s3_cmd, $s3_outputfile));
}
