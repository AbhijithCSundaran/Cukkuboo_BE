<?php
namespace App\Models;

use CodeIgniter\Model;

class CoreModel extends Model
{

    function simpleEncrypt($plaintext, $key)
    {
        // Validate plaintext is string
        if (!is_string($plaintext)) {
            throw new \Exception('Plaintext must be a string');
        }

        // Ensure the key is exactly 32 bytes for AES-256
        $key = hash('sha256', $key, true);

        $cipher = "AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);

        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext_raw === false) {
            throw new \Exception('Encryption failed');
        }

        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }


}