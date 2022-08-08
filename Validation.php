<?php

namespace FATCHIP\K3\Core;

class Validation
{
    /**
     * Check is secret is valid
     *
     * @param $secret
     * @return bool
     */
    public function isValidSecret($secret): bool
    {
        $headers = $this->getHeaders();
        if ($secret && (!isset($headers['X-Secret']) || $secret != $headers['X-Secret'])) {
            return false;
        }
        return true;
    }

    /**
     * Return headers
     *
     * @return array|false
     */
    protected function getHeaders()
    {
        return getallheaders();
    }
}