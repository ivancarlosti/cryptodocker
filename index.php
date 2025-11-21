<?php

require_once __DIR__ . '/vendor/autoload.php';

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use kornrunner\Ethereum\Address;

header('Content-Type: application/json');

try {
    // 1. Generate Mnemonic
    $random = new Random();
    $entropy = $random->bytes(16); // 128 bits for 12 words
    $bip39 = MnemonicFactory::bip39();
    $mnemonic = $bip39->entropyToMnemonic($entropy);
    
    // 2. Generate Seed
    $seedGenerator = new Bip39SeedGenerator();
    $seed = $seedGenerator->getSeed($mnemonic);
    
    // 3. Create HD Factory
    $hdFactory = new HierarchicalKeyFactory();
    $root = $hdFactory->fromEntropy($seed);
    
    // 4. Bitcoin (BTC) - m/44'/0'/0'/0/0
    // Purpose: 44' (BIP44), Coin Type: 0' (Bitcoin), Account: 0', Change: 0, Index: 0
    $btcNode = $root->derivePath("44'/0'/0'/0/0");
    $btcPrivateKey = $btcNode->getPrivateKey();
    $btcPublicKey = $btcPrivateKey->getPublicKey();
    $btcAddress = $btcPublicKey->getAddress()->getAddress();
    $btcWif = $btcPrivateKey->toWif(); // Wallet Import Format
    
    // 5. Ethereum (ETH) - m/44'/60'/0'/0/0
    // Purpose: 44', Coin Type: 60' (Ethereum), Account: 0', Change: 0, Index: 0
    $ethNode = $root->derivePath("44'/60'/0'/0/0");
    $ethPrivateKey = $ethNode->getPrivateKey();
    // kornrunner/ethereum-offline-raw-tx library for address generation
    // We need the private key in hex format
    $ethPrivateKeyHex = $ethPrivateKey->getHex();
    $ethAddressObj = new Address($ethPrivateKeyHex);
    $ethAddress = $ethAddressObj->get();
    
    $response = [
        'mnemonic' => $mnemonic,
        'btc' => [
            'address' => $btcAddress,
            'private_key_wif' => $btcWif,
            'public_key_hex' => $btcPublicKey->getHex()
        ],
        'eth' => [
            'address' => $ethAddress,
            'private_key_hex' => $ethPrivateKeyHex,
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
