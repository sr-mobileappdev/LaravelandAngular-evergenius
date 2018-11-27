<?php

namespace App\Classes;

use App\Classes\BeeFreeAdapter;
use Curl;

class BeeFreeHelper implements BeeFreeAdapter
{

    //Your API Client ID
    private $_client_id = null;

    //Your API Client Secret
    private $_client_secret = null;

    //Url to call when authenicating
    private $_auth_url = 'https://auth.getbee.io/apiauth';

    /**
     * The constructor
     *
     * @param string $key : The key provided by the api
     * @param string $secret : The secret provided by the api
     */
    public function __construct($client_id = null, $client_secret = null)
    {
        $this->setClientID($client_id);
        $this->setClientSecret($client_secret);
    }

    /**
     * Sets the client id that is provided by the API
     *
     * @param string $key
     */
    public function setClientID($client_id)
    {
        $this->_client_id = $client_id;
    }

    /**
     * Set the client secret provided by the API
     *
     * @param string string $secret
     */
    public function setClientSecret($client_secret)
    {
        $this->_client_secret = $client_secret;
    }

    /**
     * Call the API and get the access token, user and other information  required
     * to access the api
     *
     * @param string $grant_type : The grant type used to authenticate the API
     * @param string $json_decode: Return the result as an object or array. Default is object, to return set type to 'array'
     *
     * @return $mixed credentials
     */
    public function getCredentials($grant_type = 'password', $json_decode = 'object')
    {
        //set POST variables
        $fields = array('Client_Id' => urlencode($this->_client_id), 'Client_Secret' => urlencode($this->_client_secret), 'Grant_Type' => urlencode($grant_type));

        $response = Curl::to($this->_auth_url)
            ->withData($fields)
            ->post();
        return json_decode($response);
    }
}
