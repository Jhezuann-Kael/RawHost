<?php
require_once __DIR__ . '/RepositoryInterface.php';

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByUsername($username);
    public function exists($username, $email);
    public function findByCode($code);
    public function clearCode($userId);
    public function findByTelegramId($telegramId);
}
