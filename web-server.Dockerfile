ARG HTTPD_VERSION=2.4
FROM httpd:${HTTPD_VERSION}-alpine

# Copy custom configuration files
RUN echo "IncludeOptional conf/custom/*.conf" >> /usr/local/apache2/conf/httpd.conf
COPY ./httpd-docker.conf/ /usr/local/apache2/conf/custom/

# Copy the source files
RUN rm /usr/local/apache2/htdocs/index.html
COPY ./src/ /usr/local/apache2/htdocs/