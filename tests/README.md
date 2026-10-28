# Isolated candidate tests

Run with PHP 7.4+ and Node supporting the built-in test dependencies (validated with PHP 8.3.36 / Node 24.20.0 on Dell-8940):

```powershell
& <php.exe> -n tests/authority.php '<path-to-original-Ads-0.6.3>/includes/class-siteengine-ads-bridge-cli.php'
node tests/browser.cjs
```

Paths above are command placeholders, not a request to provision a runtime. The existing local test runtime used here is recorded in parent STATUS.md. The Ads source is required for the combined consumer fixtures and is read-only/SHA-256 pinned; omit its argument to run Consent-only PHP checks. Do not repin a changed consumer without review. Standalone `php -n tests/consumer-contract.php <consumer-source>` runs only the five legacy boundary demonstrations.

Tests load actual Consent class/functions/callback registration without executing the constructor/WordPress bootstrap. WordPress APIs, storage/transport and database are doubles. All data/keys are synthetic. The installed Fluent Forms 6.2.16 recordEntryDetails method is preserved as an attributed excerpt; its Arr/Helper/EntryDetails dependencies are doubled. The full unchanged Ads resolver is loaded from a separately provided, hash-verified source file and invoked by reflection against in-memory fixture storage. No real form, account, export, network or database is touched.

The browser harness executes the actual embedded JavaScript in a Node VM, injecting a test-only reference to the private field-population function. It does not replace the production algorithm. DOM/storage/fetch are doubled; this is not accessibility, real browser/cache or live form acceptance.

Expected unsafe legacy-consumer outcomes are labeled COMPATIBILITY BLOCKER. Test success means the limitation was reproduced, not that integration is safe. Database lock serialization is tested at the adapter boundary (busy/acquired/released/conflicting retry), not with real concurrent MySQL connections. WordPress nonce behavior is doubled at the API boundary, not a cryptographic WP nonce acceptance test. Engine lock/autocommit behavior, alternate/payment lifecycle, actual sanitization of hidden fields, cache/nonce expiry and real browser acceptance remain deferred.
