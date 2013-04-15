install: 
	cp ./phpTail/php5-tail.php /usr/local/bin/php5-tail
	chmod a+x /usr/local/bin/php5-tail

uninstall: 
	rm /usr/local/bin/php5-tail

setup:
	mkdir /etc/php5-tail
	mkdir /etc/php5-tail/cache
	mkdir /etc/php5-tail/method
	cp ./phpTail/Script/php5-tail /etc/init.d/
	cp ./phpTail/Config/config.php /etc/php5-tail/
	cp -r ./phpTail/Method/* /etc/php5-tail/method/
	touch /var/log/php5-tail.log
	chmod a+x /etc/init.d/php5-tail
	chmod a+w /etc/php5-tail/cache/
	chmod a+w /var/log/php5-tail.log

clean:
	rm -rf /etc/php5-tail
	rm /var/log/php5-tail.log
	rm /var/run/php5-tail.pid
	rm /etc/init.d/php5-tail

