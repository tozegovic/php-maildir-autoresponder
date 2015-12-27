# Php-maildir-autoresponder
Php-maildir-autoresponder is a simple autoresponder script (written in PHP) for Maildir. It consists of only one PHP file which can be started through a cronjob.
It works with any email setup that utilizes Maildir. User settings are stored within each user directory inside 'php-maildir-autoresponder.json' file.

This autoresponder was inspired by excellent Goldfish-Autoresponder project:
https://github.com/dirkgroenen/Goldfish-Autoresponder

## Installation
Download the ``php-maildir-autoresponder.php`` script and place it in a directory on your server. In my case I'm going for ```/usr/local/php-maildir-autoresponder.php```.


```bash
mkdir /usr/local/php-maildir-autoresponder
wget https://github.com/dirkgroenen/Goldfish-Autoresponder/archive/master.tar.gz
tar -xvf master.tar.gz -C /usr/local/goldfish
mv /usr/local/goldfish/Goldfish-Autoresponder-master/* /usr/local/goldfish
rm -r /usr/local/goldfish/Goldfish-Autoresponder-master
```

## Configuration

After you have downloaded and extracted the script you have to open it and update the setting at the top of the file.

```bash
nano /usr/local/php-maildir-autoresponder/php-maildir-autoresponder.php
```

In this file you have to change the configuration values so they match with your setup. 

```php
	$conf['dir_with_users'] = "/home";              // root directory that contains your users directories
	                                                // for virtual accounts it could be /var/mail/vhosts/mydomainname.com
	$conf['default_domain'] = "mydomainname.com";   // your domain name

    /* Logging */
    $conf['log_file_path'] = "/var/log/php-maildir-autoresponder";
    $conf['write_log'] = true;

	$conf['create_user_settings_from_default'] = 0; // if 1, it will create a settings file in users' directories
	                                                // php-maildir-autoresponder.json
	                                                // manually edit the file to enable/disable autoresponse and set message
	$defaultUserSettings = [
			'enabled' => 0,
			'email' => "jdoe@mydomainname.com",
			'descname' => "John Doe",
			'from' => "12/20/2015",
			'to' => "01/03/2016",
			'subject' => "Out of Office: %s",
			'message' => <<<EOT
I will be out of office from 12/20/2015 until 1/3/2016.
I will get back to you as soon as I can.

Thanks

EOT
	];
```

After you have configured php-maildir-autoresponder.php you need to enable it via a cronjob. In my case I want it to be executed every 5 minutes:

```
*/5 * * * *  php /usr/local/php-maildir-autoresponder/php-maildir-autoresponder.php
```


# Credits
This autoresponder was inspired by excellent Goldfish-Autoresponder project:
https://github.com/dirkgroenen/Goldfish-Autoresponder