<?php

interface RepositoryInterface
{
    public function getAll();
    public function getById($id);
    public function create(array $data);
}
