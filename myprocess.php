#!/usr/bin/php -q
<?php
define( 'NO_JOBS', 20 );
define( 'MIN_JOB_TIME', 3 );
define( 'MAX_JOB_TIME', 8 );
define( 'PID_FILE', './myprocess.pid' );

// stop process gracefully or kill it
if( $argc > 1 && ! empty($argv[1] )  ) {
    $pid = (int)file_get_contents( PID_FILE );
    $signal = strtolower( $argv[1] ) === 'kill'
        ? SIGKILL  // terminate immediately
        : SIGHUP;  // gracefull
    posix_kill( $pid, $signal );
    exit;
}

// fork the process to a separate one with the new $mypid id
$mypid = pcntl_fork();

// parent/main process logic:
// store the childs pid and leave
if( $mypid > 0 ) {
    file_put_contents( PID_FILE, $mypid );
    exit;
}

// ------- FORKED PROCESS LOGIC --------
$logFile = fopen( 'process_output.log', 'a+' );

function logMsg( $msg, $raw=false )
{
    global $logFile;

    if( ! $raw )
        $msg = date('Y-m-d H:i:s ' ).$msg."\n";
    fwrite( $logFile, $msg ); 

}

function getMemUsage()
{
    return number_format( memory_get_usage()/(1024*1024), 1 )."MB";
}

pcntl_signal(SIGHUP,  function($signo) use ( $logFile ) {
    logMsg( "Received SIGHUP - will leave after finishing this job" );
    fclose( $logFile );
    exit;
});
$myarr = [];

// jobs loop
logMsg( "\n", true );
logMsg( "START job queue - Memory usage: ".getMemUsage() );
for( $i=0; $i< NO_JOBS; $i++ )
{
    $time = rand( MIN_JOB_TIME, MAX_JOB_TIME );
    logMsg( date('Y-m-d H:i:s ')."Job #{$i} working for {$time}s:", true );
    for( $j=0; $j<$time; $j++ )
    {
        $myarr = array_merge( $myarr, range( 1, 50000 ) );
        logMsg( '.', true );
        sleep(1);
    }
    logMsg( "- Size ".count($myarr)."; Memory usage: ".getMemUsage()."\n", true );
    pcntl_signal_dispatch();
}
logMsg( "END job queue..." );
fclose($logFile);