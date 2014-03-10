#!/bin/sh

# Check for Phalcon, if not, install it
echo -n "Attempt to install Phalcon (Ubuntu only)? [y/n]"
read installPhalcon

if [ $installPhalcon -eq "y" ]; then
    # Ubuntu default php.ini file for apache
    if [ -w /etc/php5/apache2/php.ini ]; then

        # test if already a reference to phalcon
        if grep -q 'phalcon' /etc/php5/apache2/php.ini; then
            echo "Phalcon already detected, moving on..."
            hasPhalcon=true
        else
            hasPhalcon=false
        fi

        # Grab git repo and install
        if [ $hasPhalcon -eq true ]; then
            cd /tmp
            git clone git://github.com/phalcon/cphalcon.git
            cd cphalcon/build
            ./install
            echo 'extension=phalcon.so' >> /etc/php5/apache2/php.ini
            echo "Finished installing phalcon"
        fi
    else
        echo "This does not appear to be Ubuntu, or is a non-standard or non-Apache install. Sorry, this is unsupported at this time."
    fi

fi

# Set up DB
echo -n  "New database name?"
read dbName

echo -n "New database user?"
read dbUser

echo -n "New database user password?"
read dbPass

su - postgres psql -c "CREATE USER $dbuser WITH PASSWORD '$dbPass';"
su - postgres psql -c "CREATE DATABASE $dbName"
su - postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE \"$dbName\" TO \"$dbUser\";"

