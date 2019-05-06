#!/usr/bin/php -q
<?php

define( 'NO_JOBS', 20 );
define( 'MIN_JOB_TIME', 3 );
define( 'MAX_JOB_TIME', 8 );
define( 'PID_FILE', './myprocess.pid' );

if( $argc > 1 && strtolower($argv[1]) === 'stop' ) {
    $pid = (int)file_get_contents( PID_FILE );
    exec( "kill -9 {$pid}" );
}

$mypid = pcntl_fork();
if( $mypid > 0 ) {
    file_put_contents( PID_FILE, $mypid );
    exit;
}


$logFile = fopen( 'process_output.log', 'a+' );

$myarr = range(1,100000);

for( $i=0; $i< NO_JOBS; $i++ )
{
    $time = rand( MIN_JOB_TIME, MAX_JOB_TIME );
    fwrite( $logFile, date('Y-m-d H:i:s ')."Job #{$i} working for {$time}s:" );
    for( $j=0; $j<$time; $j++ )
    {
        $myarr += $myarr;
        fwrite(  $logFile, '.' );
        sleep(1);
    }
    fwrite( $logFile, "\n" );
}
fclose($logFile);