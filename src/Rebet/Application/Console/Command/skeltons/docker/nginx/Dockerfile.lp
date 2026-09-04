FROM nginx:latest

# Create a self-signed certificate for local development
RUN apt update \
  && mkdir /etc/nginx/ssl \
  && cd /etc/nginx/ssl \
  && openssl genrsa -out server.key 2048 \
  && openssl req -new -key server.key -out server.csr -subj "/C=--/ST=--/L=--/O=--/OU=--/CN={! $site_domain !}" \
  && openssl x509 -in server.csr -days 36500 -req -signkey server.key > server.crt \
  && openssl dhparam -out dhparam.pem 2048
