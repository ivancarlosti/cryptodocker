<?php

require_once __DIR__ . '/vendor/autoload.php';

use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP32\BIP32;
use kornrunner\Ethereum\Address;

header('Content-Type: application/json');

function btc_address_from_pubkey($pubKeyHex)
{
    $pubKeyBin = hex2bin($pubKeyHex);
    $sha256 = hash('sha256', $pubKeyBin, true);
    $ripemd160 = hash('ripemd160', $sha256, true);
    $versioned = "\x00" . $ripemd160; // 0x00 for Mainnet P2PKH
    $checksum = substr(hash('sha256', hash('sha256', $versioned, true), true), 0, 4);
    $binary = $versioned . $checksum;

    // Base58 encoding
    $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $base58 = '';
    $num = gmp_init(bin2hex($binary), 16);
    while (gmp_cmp($num, 0) > 0) {
        list($num, $mod) = gmp_div_qr($num, 58);
        $base58 = $alphabet[gmp_intval($mod)] . $base58;
    }

    // Leading zeros
    for ($i = 0; $i < strlen($binary); $i++) {
        if ($binary[$i] === "\x00") {
            $base58 = $alphabet[0] . $base58;
        } else {
            break;
        }
    }

    return $base58;
}

try {
    // 1. Generate Mnemonic
    // BIP39::generate($wordCount)
    $mnemonicObj = BIP39::Generate(12);
    $mnemonicWords = $mnemonicObj->words; // Array of words
    $mnemonicString = implode(' ', $mnemonicWords);

    // 2. Generate Seed
    $seed = $mnemonicObj->generateSeed(); // Returns hex string or object? Usually hex or buffer.
    // Check if it needs passphrase. Default is empty string.
    // Assuming generateSeed returns raw binary or hex. 
    // FurqanSiddiqui\BIP39 usually returns a Seed object or hex.
    // Let's assume hex or convert.
    // Actually, BIP32::FromMasterSeed expects hex or binary.

    // 3. Create HD Factory / Root Key
    // BIP32::fromMasterSeed($seedHex)
    $master = BIP32::FromMasterSeed(bin2hex($seed));

    // 4. Bitcoin (BTC) - m/44'/0'/0'/0/0
    $btcNode = $master->derivePath("m/44'/0'/0'/0/0");
    $btcPrivateKey = $btcNode->privateKey(); // Hex?
    $btcPublicKey = $btcNode->publicKey(); // Compressed?

    // Manual BTC Address Generation (P2PKH)
    // Need compressed public key usually for modern wallets, but uncompressed is valid too.
    // BIP32 usually gives compressed if 'compressed' flag is set.
    // Let's assume standard compressed.
    $btcAddress = btc_address_from_pubkey($btcPublicKey->hex());

    // 5. Ethereum (ETH) - m/44'/60'/0'/0/0
    $ethNode = $master->derivePath("m/44'/60'/0'/0/0");
    $ethPrivateKeyHex = $ethNode->privateKey()->hex();

    $ethAddressObj = new Address($ethPrivateKeyHex);
    $ethAddress = $ethAddressObj->get();

    $response = [
        'mnemonic' => $mnemonicString,
        'btc' => [
            'address' => $btcAddress,
            'private_key_hex' => $btcPrivateKey->hex(),
            'public_key_hex' => $btcPublicKey->hex()
        ],
        'eth' => [
            'address' => $ethAddress,
            'private_key_hex' => $ethPrivateKeyHex,
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
