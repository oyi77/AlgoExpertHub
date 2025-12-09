#!/bin/bash
# Wrapper script to start Octane inside Docker container

CONTAINER_NAME="1Panel-php8-mrTy"
ARTISAN_PATH="/www/sites/aitradepulse.com/index/main/artisan"

# Start Octane in the container
docker exec -i ${CONTAINER_NAME} php ${ARTISAN_PATH} octane:start \
    --server=swoole \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=4 \
    --max-requests=1000
