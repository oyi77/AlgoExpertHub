<?php

namespace App\Contracts;

interface ServiceInterface
{
    /**
     * Create a new resource
     */
    public function create(array $data): array;

    /**
     * Update an existing resource
     */
    public function update(int $id, array $data): array;

    /**
     * Delete a resource
     */
    public function delete(int $id): array;

    /**
     * Get a resource by ID
     */
    public function find(int $id): array;

    /**
     * Get paginated list of resources
     */
    public function list(array $params = []): array;
}