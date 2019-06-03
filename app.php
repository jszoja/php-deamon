<?php
$appId = 1;
if( !empty( $argv[1] ) )
    $appId = $argv[1];

$pidFile = 'app'.$appId.'.pid';
if( file_exists( $pidFile ) )
    $pid = file_get_contents( $pidFile );

// stop process
if( ! empty( $argv[2] ) && $argv[2] === 'stop' && ! empty($pid) ) {
    exec( 'kill -9 '.$pid );
    exit;
}

// fork new process
$newPid = pcntl_fork();
if( $newPid ) {
    file_put_contents( $pidFile, $newPid );
    exit;
}

$slipTime = 15;
if( $appId == 2 )
    $slipTime = 35;
sleep($slipTime);