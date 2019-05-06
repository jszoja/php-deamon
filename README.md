# php-deamon
Sample code to run the php as a deamon and monitoring it with monit

Run the bellow command which will figure out that php process is not running and start it automatically
```bash
monit -c .monitrc -I
```
