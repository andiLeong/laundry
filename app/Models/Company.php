<?php

namespace App\Models;

use Illuminate\Config\Repository;

class Company
{

    public function __construct(protected ?Repository $config = null)
    {
        if(is_null($config)){
            $this->config = config();
        }
    }

    /**
     * it gets all company information from config
     */
    public function all()
    {
        return $this->config->get('company.company');
    }

    /**
     * it inserts a new record to config
     * @param array $company
     * @return bool
     */
    public function insert(array $company): bool
    {
        $this->config->push('company.company', $company);
        return true;
    }

    /**
     * get a corresponding company by its id
     * @param $id
     * @param null $default
     * @return null
     */
    public function find($id, $default = null)
    {
        return collect($this->all())
            ->first(
                fn($company) => $company['id'] === $id,
                $default
            );
    }

    public function users()
    {
        return $this->config->get('company.users');
    }

    public function user($userId,$default = null)
    {
        foreach ($this->users() as $user) {
            if ($user['id'] === $userId) {
                return $user;
            }
        }

        return $default;
    }

    public function getIdByUser($userId)
    {
        return $this->user($userId);
    }

    public function getConfig(): Repository
    {
        return $this->config;
    }

    public function insertUser(array $user) :bool
    {
        $this->config->push('company.users', $user);
        return true;
    }
}
