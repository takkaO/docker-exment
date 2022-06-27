FROM mcr.microsoft.com/mssql/server:2019-latest

USER root
SHELL ["/bin/bash", "-c"]

RUN mkdir -p /docker-entrypoint-initdb.d
WORKDIR /docker-entrypoint-initdb.d
COPY ./volumes/initdb.d/ /docker-entrypoint-initdb.d/
RUN chmod -R +x /docker-entrypoint-initdb.d
RUN chown -R mssql /docker-entrypoint-initdb.d

EXPOSE 1433
USER mssql
CMD ["/bin/bash", "./entrypoint.sh"]