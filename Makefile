install: 
	cp ./php5-tail/php5-tail.php /usr/local/bin/php5-tail
	chmod a+x /usr/local/bin/php5-tail

uninstall: 
	rm /usr/local/bin/php5-tail

setup:
	mkdir -p /etc/php5-tail
	mkdir -p /etc/php5-tail/cache
	mkdir -p /etc/php5-tail/method
	cp ./php5-tail/Script/php5-tail /etc/init.d/
	cp ./php5-tail/Config/php5-tail.conf /etc/php5-tail/
	cp -r ./php5-tail/Method/* /etc/php5-tail/method/
	touch /var/log/php5-tail.log
	chmod a+x /etc/init.d/php5-tail
	chmod a+w /etc/php5-tail/cache/
	chmod a+w /var/log/php5-tail.log

clean:
	rm -rf /etc/php5-tail
	rm /var/log/php5-tail.log
	rm /var/run/php5-tail.pid
	rm /etc/init.d/php5-tail

