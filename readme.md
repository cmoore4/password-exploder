Password Exploder
=================

Share passwords online, with restricted access options and auto-deletion.
-------------------------------------------------------------------------

This project was born from our need to send passwords to clients while avoiding email and phone calls.  This is not strictly secure (in fact, it's not secure at all), but generally suits our needs.

Creating a new Exploding Password requires a password and an expiration date.  The app will generate a unique URL that you can send out over email or IM.  When the expiration date is hit, the password is completely removed from the database via Cron.  Only some anonymous usage statistics are collected.

To provide a hint of security, you can limit the maximum amount of times the password can be viewed (after which point it will be deleted), and you can limit access to the password by IP address or range.

As long as you only serve the site over SSL, the unique URL will never be sent in plaintext over the wire, so will remain anonymous (as SSL establishes the encrypted connection first, before requesting the page).


Technical
---------

The project "api" is built on PHP using the [Phalcon Framework/Extension](http://phalconphp.com/) (It's not truly restful, barely even rest-ish, it just implements the basic functionality needed for this site).  The front-end is a simple [AngularJS](http://angularjs.org/) app.  Postgres is required for the UUID generation, thought it would be easy to replace that with a different UUID generator if you wanted a different database engine.  Cron is required to run the job that deletes the passwords.


Installation
------------

I'm working on an installer script (I could use Puppet or Chef for this, but I haven't yet).  In the meantime, here's the basic steps for the technically inclined:

-  Make sure you have PHP 5.3 installed with the Phalcon extension enabled (linked above)
-  Make sure you have PostgreSQL 9.1 or up
-  Make sure you have Apache 2.x (Nginx or anything else will also work, you'll jsut need to convert the htaccess files)
-  Create the database and tables using the sql/pwx-init.sql script.
-  Edit public/api/config.ini and input all of your site's information
-  chmod +x scripts/expirePasswords.sh and then add a cron job to execute it once a minute
-  Point your webserver's root to public/


Upcoming Features
-----------------

At some point, I'd like to implement a basic user account, where you could set defaults for your restriction fields, and also manage all your open passwords in one location (easy delete, see viewcount, etc).  Other than that, I consider this largely feature complete, though I'm willing to entertain suggestions.
