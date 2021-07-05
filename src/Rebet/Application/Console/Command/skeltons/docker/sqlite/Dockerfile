FROM debian:stable-slim

# Install SQLite3
RUN apt-get update \
	&& DEBIAN_FRONTEND=noninteractive apt-get -yq --no-install-recommends install sqlite3=3.* \
    && apt-get clean \
	&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
	&& mkdir -p /tmp/sqlite

COPY . /tmp/
ENTRYPOINT /tmp/docker-entrypoint.sh
