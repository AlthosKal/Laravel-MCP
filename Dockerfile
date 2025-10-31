FROM pgvector/pgvector:pg17

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    make \
    gcc \
    postgresql-server-dev-17 \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Compilar e instalar pg_http
RUN git clone https://github.com/pramsey/pgsql-http.git \
    && cd pgsql-http && make && make install

# Compilar e instalar pg_cron
RUN git clone https://github.com/citusdata/pg_cron.git \
    && cd pg_cron && make && make install

# Copiar archivo de variables de entorno
COPY .env.docker /.env.docker

# Crear script de entrada personalizado que carga .env.docker
RUN echo '#!/bin/bash' > /docker-entrypoint-custom.sh && \
    echo 'set -a' >> /docker-entrypoint-custom.sh && \
    echo 'source /.env.docker' >> /docker-entrypoint-custom.sh && \
    echo 'set +a' >> /docker-entrypoint-custom.sh && \
    echo 'exec /usr/local/bin/docker-entrypoint.sh "$@"' >> /docker-entrypoint-custom.sh && \
    chmod +x /docker-entrypoint-custom.sh

# Exponer puerto de PostgreSQL
EXPOSE 5432

ENTRYPOINT ["/docker-entrypoint-custom.sh"]
CMD ["postgres", "-c"]