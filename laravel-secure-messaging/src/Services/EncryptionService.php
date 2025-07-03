<?php

namespace Acme\SecureMessaging\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Generates a new public/private key pair for crypto_box.
     *
     * @return array ['publicKey' => string, 'privateKey' => string] Both keys are base64 encoded.
     * @throws \Exception if sodium extension is not available or key generation fails.
     */
    public static function generateKeyPair(): array
    {
        if (!extension_loaded('sodium')) {
            throw new Exception("Sodium extension is not available. Please install/enable it.");
        }

        try {
            $keyPair = sodium_crypto_box_keypair();
            $publicKey = sodium_crypto_box_publickey($keyPair);
            $privateKey = sodium_crypto_box_secretkey($keyPair);

            return [
                'publicKey' => base64_encode($publicKey),
                'privateKey' => base64_encode($privateKey),
            ];
        } catch (\SodiumException $e) {
            Log::error("Sodium keypair generation failed: " . $e->getMessage());
            throw new Exception("Failed to generate encryption key pair.");
        }
    }

    /**
     * Encrypts a message using the recipient's public key.
     * Uses crypto_box_seal for anonymous encryption (sender is not authenticated by the encryption itself).
     *
     * @param string $message The plaintext message.
     * @param string $recipientPublicKeyBase64 The recipient's public key, base64 encoded.
     * @return string The base64 encoded encrypted message (ciphertext).
     * @throws \Exception If encryption fails.
     */
    public function encrypt(string $message, string $recipientPublicKeyBase64): string
    {
        if (!extension_loaded('sodium')) {
            throw new Exception("Sodium extension is not available.");
        }

        try {
            $recipientPublicKey = base64_decode($recipientPublicKeyBase64);
            if ($recipientPublicKey === false || strlen($recipientPublicKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
                throw new Exception("Invalid recipient public key format or length.");
            }

            $ciphertext = sodium_crypto_box_seal($message, $recipientPublicKey);
            return base64_encode($ciphertext);
        } catch (\SodiumException $e) {
            Log::error("Sodium encryption failed: " . $e->getMessage());
            throw new Exception("Message encryption failed. " . $e->getMessage());
        } catch (Exception $e) {
            Log::error("Encryption process error: " . $e->getMessage());
            throw $e; // Re-throw custom exceptions
        }
    }

    /**
     * Decrypts a message using the recipient's full key pair (public + private).
     * Uses crypto_box_seal_open.
     *
     * @param string $encryptedMessageBase64 The base64 encoded encrypted message.
     * @param string $recipientPublicKeyBase64 The recipient's public key, base64 encoded.
     * @param string $recipientPrivateKeyBase64 The recipient's private key, base64 encoded.
     * @return string|false The decrypted plaintext message, or false if decryption fails.
     * @throws \Exception If decryption setup fails.
     */
    public function decrypt(string $encryptedMessageBase64, string $recipientPublicKeyBase64, string $recipientPrivateKeyBase64)
    {
        if (!extension_loaded('sodium')) {
            throw new Exception("Sodium extension is not available.");
        }

        try {
            $publicKey = base64_decode($recipientPublicKeyBase64);
            if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
                throw new Exception("Invalid recipient public key format or length for decryption.");
            }

            $privateKey = base64_decode($recipientPrivateKeyBase64);
            if ($privateKey === false || strlen($privateKey) !== SODIUM_CRYPTO_BOX_SECRETKEYBYTES) {
                throw new Exception("Invalid recipient private key format or length for decryption.");
            }

            $keyPair = sodium_crypto_box_seed_keypair(sodium_crypto_box_secretkey(sodium_crypto_box_keypair_from_secretkey_and_publickey($privateKey, $publicKey)));


            $encryptedMessage = base64_decode($encryptedMessageBase64);
            if ($encryptedMessage === false) {
                throw new Exception("Invalid base64 encoding for encrypted message.");
            }

            $plaintext = sodium_crypto_box_seal_open($encryptedMessage, $keyPair);

            if ($plaintext === false) {
                // Decryption failed (e.g., wrong key, corrupted message)
                // Log this attempt carefully, avoid logging sensitive data
                Log::warning("Sodium decryption failed. This might be due to incorrect key or corrupted message.");
            }
            return $plaintext; // Returns false on failure

        } catch (\SodiumException $e) {
            Log::error("Sodium decryption failed: " . $e->getMessage());
            // It's common for seal_open to fail without throwing SodiumException, returning false instead.
            // But if an exception is thrown, it's likely a setup issue.
            return false;
        } catch (Exception $e) {
            Log::error("Decryption process error: " . $e->getMessage());
            throw $e; // Re-throw custom exceptions
        }
    }
}
