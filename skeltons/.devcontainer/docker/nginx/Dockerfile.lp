FROM nginx:latest

# NOTE: TLS is terminated by the shared reverse proxy (Traefik) launched by
#       '.devcontainer/initialize_command.sh', so this Nginx does not need any certificate.
