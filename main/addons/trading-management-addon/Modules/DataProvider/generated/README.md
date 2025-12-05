# MT5 gRPC Generated Classes

This directory should contain the generated PHP classes from the MT5 proto file.

## Generating Proto Files

1. Download the MT5 proto file:
   ```bash
   curl -o mt5.proto https://git.mtapi.io/root/grpc-proto/-/raw/main/mt5/protos/mt5.proto
   ```

2. Generate PHP classes using Docker:
   ```bash
   # For Linux/Mac
   docker run -v $(pwd):/defs namely/protoc-all -f ./mt5.proto -l php -o generated
   
   # For Windows PowerShell
   docker run -v ${pwd}:/defs namely/protoc-all -f ./mt5.proto -l php -o generated
   ```

3. Move the generated files to this directory:
   ```bash
   mv generated/GPBMetadata/* ./GPBMetadata/
   mv generated/Mt5grpc/* ./Mt5grpc/
   ```

## Alternative: Download Pre-generated Files

You can download pre-generated PHP classes from:
https://git.mtapi.io/root/grpc-proto/-/archive/main/grpc-proto-main.zip?path=mt5/php

Extract the files to this directory maintaining the structure:
- `GPBMetadata/Mt5.php`
- `Mt5grpc/*.php`

## Required Structure

```
generated/
├── GPBMetadata/
│   └── Mt5.php
└── Mt5grpc/
    ├── ConnectionClient.php
    ├── MT5Client.php
    ├── SubscriptionsClient.php
    ├── StreamsClient.php
    └── ... (other generated classes)
```
