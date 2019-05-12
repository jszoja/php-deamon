#!/usr/bin/php -q
<?php
define( 'NO_JOBS', 20 );
define( 'MIN_JOB_TIME', 1 );
define( 'MAX_JOB_TIME', 8 );
define( 'PID_FILE', './myprocess.pid' );
define( 'CONCURRENT_THREADS', 6 );
define( 'ERROR_PROBABILITY', 15 ); // 0-100

// stop process gracefully or kill it
if( $argc > 1 && ! empty($argv[1] )  ) {
    $pid = (int)file_get_contents( PID_FILE );
    $signal = strtolower( $argv[1] ) === 'kill'
        ? SIGKILL  // terminate immediately
        : SIGHUP;  // gracefull
    posix_kill( $pid, $signal );
    exit;
}

$logFile = fopen( 'process_output.log', 'a+' );
$startTs = time();

// number of jobs to dispatch among threads
$noJobs = NO_JOBS; // rand( 3, NO_JOBS );
$jobsQueue = range( 1, $noJobs );
// jobs loop
logMsg( "\n", true );
logMsg( "START job queue with {$noJobs} - Memory usage: ".getMemUsage() );

do {
    if( empty($mypid) || $mypid > 0 )
        logMsg("START concurrent process loop");

    for( $t=0; $t<CONCURRENT_THREADS; $t++ )
    {
        if( ! empty($mypid) && $mypid > 0 )
        {
            if( empty($jobsQueue) ) {
                logMsg( "No more jobs. Leaving..." );
                break;
            }
        }
        
        // fork the process to a separate one with the new $mypid id
        $mypid = pcntl_fork();
        
        // store the childs pid
        if( $mypid > 0 ) {
            file_put_contents( PID_FILE.$t, $mypid );
            // remove job from the queue
            $removedJob = array_shift($jobsQueue);
            logMsg("Removed job #{$removedJob} from the queue.");
        }
        
        // child process always exits from the loop
        // and continue with its logic after the loop
        else
            break 2;

    }

    if( $mypid > 0 ) {

        logMsg("Waiting for {$t} children to finish...");

        // parent/main process logic:
        // wait for exit
        for( $t2=0; $t2<$t; $t2++ ) {
            $status = null;
            logMsg("Waiting for child {$t2}...");
            $childPid = pcntl_wait( $status );
            logMsg("Process #{$childPid} finished with status {$status}");  
        }

        logMsg("END concurrent process loop");
    }
    
} while( ! empty( $jobsQueue ) );

if( $mypid > 0 ) {
    $duration = time()-$startTs;
    logMsg( "END job queue - {$duration}s" );

    exit;
}


// ------- FORKED PROCESS LOGIC --------
$jobId = array_shift( $jobsQueue );
$myarr = [];
$time = rand( MIN_JOB_TIME, MAX_JOB_TIME );
logMsg( "Job #{$jobId} working for {$time}s:" );
for( $j=0; $j<$time; $j++ )
{
    $myarr = array_merge( $myarr, range( 1, 50000 ) );
    sleep(1);
}
if( ERROR_PROBABILITY > 0 && rand( 1, 100 ) < ERROR_PROBABILITY )
    throw new Exception("Unknown error occurred...");
logMsg( "Job #{$jobId} finished: Size ".count($myarr)."; Memory usage: ".getMemUsage() );
fclose($logFile);
pcntl_signal_dispatch();

// ===========================================
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