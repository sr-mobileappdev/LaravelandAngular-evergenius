<?php
namespace App\Classes;

interface BeeFreeAdapter
{

    public function setClientID($id);
    public function setClientSecret($secret);
    public function getCredentials();
}
