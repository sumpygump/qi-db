# Qi Db Tests

Since this library actually connects to a database, the tests are expected to
run against an actual database.

You may have to install the PDO drivers for your local PHP. Do do that you can (on a linux flavor):

```
sudo apt install php-sqlite php-pgsql php-mysql
```

# Mysql Tests

For MySQL tests, install mysql server locally and create a user and a database to use.

## Postgresql Tests

For Postgresql tests, install postgres locally and then use the following guide
to create a test user and database:

```
sudo -u postgres createuser --login --pwprompt qi_user
```

It will prompt you to create a new password for the user.

```
sudo -u postgres createdb --owner=qi_user qi_test
```

Test that the creation worked by logging in:
```
psql -h localhost -U qi_user qi_test
```

For dropping the users and databases later, run the following as the postgres user:

Login as the postgres user:

```
sudo -u postgres psql
```

Then run these commands:

```
DROP OWNED BY qi_user;
DROP DATABASE qi_test;
DROP USER qi_user;
```

Note: when logged in to postgres, you can enter `\du` to get the list of users
`\l` to list the databases, and `\dt` to list the tables of the current
database.
