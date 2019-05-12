#!/usr/bin/php -q
<?php
define( 'NO_JOBS', 8 );
define( 'MIN_JOB_TIME', 3 );
define( 'MAX_JOB_TIME', 8 );
define( 'PID_FILE', './myprocess.pid' );
define( 'CONCURRENT_THREADS', 2 );

// stop process gracefully or kill it
if( $argc > 1 && ! empty($argv[1] )  ) {
    $pid = (int)file_get_contents( PID_FILE );
    $signal = strtolower( $argv[1] ) === 'kill'
        ? SIGKILL  // terminate immediately
        : SIGHUP;  // gracefull
    posix_kill( $pid, $signal );
    exit;
}

for( $t=0; $t<CONCURRENT_THREADS; $t++ )
{
    // fork the process to a separate one with the new $mypid id
    $mypid = pcntl_fork();

    // parent/main process logic:
    // store the childs pid and leave
    if( $mypid > 0 ) {
        file_put_contents( PID_FILE.$t, $mypid );
    }

    else
        break;

}

$logFile = fopen( 'process_output.log', 'a+' );
if( $mypid > 0 )
{
    // wait for exit
    logMsg("START concurrent process loop");
    for( $t=0; $t<CONCURRENT_THREADS; $t++ ) {
        $status = null;
        pcntl_wait( $status );
        logMsg("Process #{$mypid} finished with status {$status}");  
    }
    logMsg("END concurrent process loop");
}

// ------- FORKED PROCESS LOGIC --------

function logMsg( $msg, $raw=false )
{
    global $logFile;
    $pid = getmypid();

    if( ! $raw )
        $msg = date('Y-m-d H:i:s ' ).'['.$pid.'] '.$msg."\n";
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
$noJobs = rand( 1, NO_JOBS );
logMsg( "START job queue with {$noJobs} - Memory usage: ".getMemUsage() );
for( $i=0; $i<$noJobs; $i++ )
{
    $time = rand( MIN_JOB_TIME, MAX_JOB_TIME );
    logMsg( "Job #{$i} working for {$time}s:" );
    for( $j=0; $j<$time; $j++ )
    {
        $myarr = array_merge( $myarr, range( 1, 50000 ) );
        sleep(1);
    }
    if( rand( 1, 100 ) < 16 )
        throw new Exception("Unknown error occurred...");
    logMsg( "Job #{$i} finished: Size ".count($myarr)."; Memory usage: ".getMemUsage()."\n" );
    pcntl_signal_dispatch();
}
logMsg( "END job queue..." );
fclose($logFile);