# Crypto Docker

This is a Dockerized PHP application that generates BIP39 mnemonics and derives Bitcoin (BTC) and Ethereum (ETH) keys.

## Usage

### Build with Docker

```bash
docker build -t cryptodocker .
```

### Run

```bash
docker run -d -p 8080:80 cryptodocker
```

### API

Make a GET request to the root endpoint:

```bash
curl http://localhost:8080/
```

**Response:**

```json
{
    "mnemonic": "word1 word2 ... word12",
    "btc": {
        "address": "1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2",
        "private_key_wif": "...",
        "public_key_hex": "..."
    },
    "eth": {
        "address": "0x...",
        "private_key_hex": "..."
    }
}
```

## GitHub Actions

This repository includes a GitHub Action workflow that automatically builds the Docker image on every push to the `main` branch.
