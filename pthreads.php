#!/usr/bin/php -q
<?php
define( 'NO_JOBS', 20 );
define( 'MIN_JOB_TIME', 1 );
define( 'MAX_JOB_TIME', 8 );
define( 'PID_FILE', './myprocess.pid' );
define( 'CONCURRENT_THREADS', 6 );
define( 'ERROR_PROBABILITY', 18 ); // 0-100
define( 'LOG_FILE', 'pthreads.log' );

$startTs = time();
// number of jobs to dispatch among threads
$noJobs = NO_JOBS; // rand( 3, NO_JOBS );
$jobsQueue = range( 1, $noJobs );
// jobs loop
logMsg( "\n", true );
logMsg( "START job queue with {$noJobs} - Memory usage: ".getMemUsage().print_r( $jobsQueue, 1 ) );

# Create a pool of 4 threads
$pool = new Pool(CONCURRENT_THREADS);

foreach( $jobsQueue as $job ) 
{
    $pool->submit( new Task( $job ) );
}

while ($pool->collect());

$pool->shutdown();

$duration = time()-$startTs;
logMsg( "END job queue - {$duration}s" );

# ---------------------------------------

class Task extends Threaded
{
    private $value;

    public function __construct(int $i)
    {
        $this->value = $i;
    }

    public function run()
    {
        $myarr = [];
        $time = rand( MIN_JOB_TIME, MAX_JOB_TIME );
        logMsg( "Job #{$this->value} working for {$time}s:" );
        for( $j=0; $j<$time; $j++ )
        {
            $myarr = array_merge( $myarr, range( 1, 50000 ) );
            sleep(1);
        }
        if( ERROR_PROBABILITY > 0 && rand( 1, 100 ) < ERROR_PROBABILITY ) {
            //throw new Exception("Unknown error occurred...");
            callingnonexisting();
        }
        logMsg( "Job #{$this->value} finished: Size ".count($myarr)."; Memory usage: ".getMemUsage() );
        pcntl_signal_dispatch();
    }
}

function logMsg( $msg, $raw=false )
{
    static $logFile;

    if( empty( $logFile ) )
        $logFile = fopen( LOG_FILE, 'a+' );

    $pid = getmypid();

    if( ! $raw )
        $msg = date('Y-m-d H:i:s ' ).'['.$pid.'] '.$msg."\n";
    fwrite( $logFile, $msg ); 

}

function getMemUsage()
{
    return number_format( memory_get_usage()/(1024*1024), 1 )."MB";
}

pcntl_signal(SIGHUP,  function($signo) {
    logMsg( "Received SIGHUP - will leave after finishing this job" );
    exit;
});