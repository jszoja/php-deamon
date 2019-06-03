# php-deamon
Sample code to run the php as a deamon and monitoring it with monit

Run the bellow command which will figure out that php process is not running and start it automatically
```bash
monit -c .monitrc -I
```

# myprocess.php
Demonstrates the concept of concurrent threads and job queue

- defined number of threads pulls the single job from the queue
- process waits for the all the threads to finish first
- then it start forking new threads to pull the rest of the jobs

# monitrc2
Sample configuration demonstating how to keep multiple processess alive(app.php) with timeout option.
If process is not finished in 20sec it will be restarted.

## Todo
Don't wait for the all threads to finish, but fork as soon as any thread is done
