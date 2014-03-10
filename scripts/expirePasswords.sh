#!/bin/sh

export PGPASSWORD="DB_SECRET_PASS"

psql -U DBUSER -h 127.0.0.1 DBNAME <<THE_END
  DELETE FROM passwords
  WHERE
        ( to_timestamp(expiration) < current_timestamp ) OR
        ( viewcount >= maxviews AND maxviews <> 0 )
THE_END
