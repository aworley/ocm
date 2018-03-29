## Welcome to the Open Case Management (OCM) Project! ##

### What is OCM? ###

Open Case Management is a case management system featuring a browser-based user
interface.  It is user friendly and tailored to meet the specific needs of the
not-for-profit legal services program.  It is freely available under the GPL v2
open source license.  For programs that don't wish to run their own server,
commercial hosting is available from www.pikasoftware.com.

### Installing OCM ###

These instructions are for CentOS 5, 6, and 7.  Instructions for other operating
systems are on the way!

* Install Apache web server, PHP, and mod_ssl.

* Install MySQL or MariaDB.  CentOS 7 ships with MariaDB so it is often simplest
to use that.

* OCM requires the following PHP modules:  curl, DOM, JSON, mbstring, mcrypt,
mysql, SimpleXML, SOAP, and zip.  This command will install the additional
required PHP modules on CentOS 7:
	yum install php-mysqlnd php-cli php-xml php-mcrypt php-mbstring php-soap

* Install ghostscript, it's needed for indexing PDF uploads.

* Create a new MySQL database named 'cms'.

* Download the OCM software from https://github.com/aworley/ocm

* Move the cms directory to /var/www/html/cms and the cms-custom directory to
/var/www/html/cms-custom

* Create a CMS database with the command:
	cat /var/www/html/cms/app/sql/install/new_install.sql | mysql cms

* Edit the file /var/www/html/cms-custom/config/settings.php and enter a valid
MySQL username and password.

* Point your browser to https://"your server IP address"/cms/

* Verify that the log in page appears.

* Set a new Pika CMS account by running this statement in MySQL:
	use cms; update users set username='my.username', password=MD5('my.password');
It's a good idea to replace my.username and my.password with more secure values.
The insecure md5 password will be replaced by a bcrypt value the first time you
log in, as long as the server is running PHP 5.3.3 or higher.

* Optionally, copy the file httpd-config/ocm.conf to /etc/httpd/conf.d.  This
file contains httpd settings that will make the CMS site more secure.  The last
section in ocm.conf should be copied and pasted into the VirtualHost section
in your conf.d/ssl.conf file, then uncommented, for it to take effect.

* If you have made httpd configuration changes in the previous step, restart
httpd and apply the new settings with the command "service httpd graceful".

* The system is now ready to use.  You can log in and set up additional user
accounts for everyone who needs access to the system.
