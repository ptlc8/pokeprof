ARG MARIADB_VERSION=10.11
FROM mariadb:${MARIADB_VERSION}

# Copy the configuration file
COPY ./mariadb.cnf /etc/mysql/conf.d/mariadb.cnf

# Copy the initialization script
COPY ./init.sql /docker-entrypoint-initdb.d/init.sql