<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class RevenueCat extends ResourceController
{
    use ResponseTrait;

    private $apiKey = 'sk_NVsmNnPJKSCiXhRtUSnydmUGOKDrG'; // Your RevenueCat Secret API Key

    public function getSubscription($userId)
    {
        $client = \Config\Services::curlrequest();

        $response = $client->get("https://api.revenuecat.com/v1/subscribers/{$userId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ]
        ]);

        // print_r($response);
        // exit;
        // Check HTTP status
        if ($response->getStatusCode() === 200) {
            $body = $response->getBody(); // Get JSON string
            $data = json_decode($body, true); // Decode into PHP array

            // Optional: log or process data
            return $this->respond($data); // Return to frontend
        } else {
            return $this->fail('Failed to fetch subscription from RevenueCat', $response->getStatusCode());
        }
    }
}
