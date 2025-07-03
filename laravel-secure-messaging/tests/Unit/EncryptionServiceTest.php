<?php

namespace Acme\SecureMessaging\Tests\Unit;

use Acme\SecureMessaging\Services\EncryptionService;
use Acme\SecureMessaging\Tests\TestCase; // Utiliser notre TestCase de base
use Exception;

class EncryptionServiceTest extends TestCase
{
    protected EncryptionService $encryptionService;

    protected function setUp(): void
    {
        parent::setUp();
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension is not available. Skipping encryption tests.');
        }
        $this->encryptionService = new EncryptionService();
    }

    public function test_it_can_generate_a_valid_key_pair()
    {
        $keyPair = EncryptionService::generateKeyPair();

        $this->assertIsArray($keyPair);
        $this->assertArrayHasKey('publicKey', $keyPair);
        $this->assertArrayHasKey('privateKey', $keyPair);

        $this->assertNotEmpty($keyPair['publicKey']);
        $this->assertNotEmpty($keyPair['privateKey']);

        // Vérifier si les clés sont bien encodées en base64 et ont la bonne longueur une fois décodées
        $publicKeyBytes = base64_decode($keyPair['publicKey']);
        $privateKeyBytes = base64_decode($keyPair['privateKey']);

        $this->assertEquals(SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, strlen($publicKeyBytes));
        $this->assertEquals(SODIUM_CRYPTO_BOX_SECRETKEYBYTES, strlen($privateKeyBytes));
    }

    public function test_it_can_encrypt_and_decrypt_a_message()
    {
        $keyPair = EncryptionService::generateKeyPair();
        $publicKey = $keyPair['publicKey'];
        $privateKey = $keyPair['privateKey'];

        $originalMessage = "This is a secret message for testing.";

        $encryptedMessage = $this->encryptionService->encrypt($originalMessage, $publicKey);
        $this->assertIsString($encryptedMessage);
        $this->assertNotEquals($originalMessage, $encryptedMessage);

        $decryptedMessage = $this->encryptionService->decrypt($encryptedMessage, $publicKey, $privateKey);
        $this->assertEquals($originalMessage, $decryptedMessage);
    }

    public function test_decrypt_fails_with_wrong_key()
    {
        $keyPair1 = EncryptionService::generateKeyPair();
        $publicKey1 = $keyPair1['publicKey'];
        // $privateKey1 = $keyPair1['privateKey']; // Non utilisé pour le déchiffrement avec la mauvaise clé

        $keyPair2 = EncryptionService::generateKeyPair();
        $publicKey2 = $keyPair2['publicKey']; // Clé publique différente
        $privateKey2 = $keyPair2['privateKey']; // Clé privée correspondante à publicKey2

        $originalMessage = "Another secret message.";

        $encryptedMessage = $this->encryptionService->encrypt($originalMessage, $publicKey1);

        // Tentative de déchiffrement avec la mauvaise paire de clés (publicKey1, privateKey2)
        // ou (publicKey2, privateKey2) si on a chiffré avec publicKey1.
        // La méthode decrypt attend la paire de clés complète du destinataire.
        // Ici, on a chiffré pour le détenteur de $keyPair1. On doit déchiffrer avec $keyPair1.
        // Si on tente avec $keyPair2, ça doit échouer.
        $decryptedMessage = $this->encryptionService->decrypt($encryptedMessage, $publicKey2, $privateKey2);
        $this->assertFalse($decryptedMessage, "Decryption should fail with the wrong key pair.");

        // Tentative de déchiffrement avec la bonne clé publique mais une mauvaise clé privée
        $randomPrivateKeyForPair1 = EncryptionService::generateKeyPair()['privateKey'];
        $decryptedWithWrongPrivate = $this->encryptionService->decrypt($encryptedMessage, $publicKey1, $randomPrivateKeyForPair1);
        $this->assertFalse($decryptedWithWrongPrivate, "Decryption should fail with the correct public key but wrong private key.");
    }

    public function test_encrypt_throws_exception_with_invalid_public_key()
    {
        $this->expectException(Exception::class);
        // $this->expectExceptionMessage("Invalid recipient public key format or length."); // Soyez précis si possible

        $this->encryptionService->encrypt("test message", "invalid-base64-public-key");
    }

    public function test_decrypt_throws_exception_with_invalid_keys()
    {
        $keyPair = EncryptionService::generateKeyPair();
        $encryptedMessage = $this->encryptionService->encrypt("test", $keyPair['publicKey']);

        $this->expectException(Exception::class);
        $this->encryptionService->decrypt($encryptedMessage, "invalid-public-key", $keyPair['privateKey']);

        // Reset exception expectation for next test or use @depends or separate tests
        // For simplicity, this test might need splitting if PHPUnit version is older or for clarity
    }

    public function test_decrypt_returns_false_for_tampered_message()
    {
        $keyPair = EncryptionService::generateKeyPair();
        $publicKey = $keyPair['publicKey'];
        $privateKey = $keyPair['privateKey'];

        $originalMessage = "Secret message.";
        $encryptedMessage = $this->encryptionService->encrypt($originalMessage, $publicKey);

        // Tamper the message
        $tamperedEncryptedMessage = substr_replace($encryptedMessage, 'X', 5, 1); // Change a character

        $decryptedMessage = $this->encryptionService->decrypt($tamperedEncryptedMessage, $publicKey, $privateKey);
        $this->assertFalse($decryptedMessage);
    }
}
