Django Auth backend for DokuWiki
================================


Use this so that your users don't have to log in to the wiki when they're logged on your django website.

Since it is based on cookies, you need them to run on the same domain.

Installation
------------

Copy django.class.php into 'dokuwiki/inc/auth/'

Configuration
-------------

Add this to your 'conf/local.php'::

	$conf['authtype'] = 'django';
	$conf['auth']['django']['user'] = 'imaginationforpeople';
	$conf['auth']['django']['password'] = 'superpassword';
	$conf['auth']['django']['dsn'] = 'pgsql:host=localhost;dbname=imaginationforpeople';


On the django side, you user must belong to the admin group defined in your local.php if you want it to be admin::

        $conf['superuser'] = '@wikiadmin';


Credits
-------

Based on http://www.dokuwiki.org/auth:django
