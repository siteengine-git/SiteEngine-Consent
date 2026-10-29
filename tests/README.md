# Isolated rc.2 verification

Use the existing local PHP CLI and Node runtime; no installation or site bootstrap:

    php -n tests/authority.php
    php -n tests/withdrawal.php <reviewed-Ads-checkout>/includes/class-siteengine-ads-bridge-consent.php
    node tests/browser.cjs
    node tests/concurrency.cjs <php.exe>
    git show 368c0affe8fe3bb6929222a23164429f3538512e:siteengine-consent.php | php -n tests/preserved-contracts.php

Validated using existing PHP 8.3.36 (-n) and Node 24.20.0. Minimum declared PHP 7.4 was not executed. Lint every PHP file and syntax-check both CJS suites. Paths above are placeholders, not permission to provision runtimes.

- authority.php: actual Consent capture/resolve/filter/AJAX, WP/SQL boundary doubles, attributed Fluent Forms 6.2.16 details serializer excerpt. Independent fixture keys retain earlier event tests; same-chain withdrawal coverage is separate. Core nonce cryptography and installed lifecycle are not exercised.
- withdrawal.php: actual capture/eligibility, permanent withdrawal/regrant barriers, stale/conflicting retry, independent chains, forged/missing state, rollback/failure/engine refusal, expiry, ambiguity and immutable history. Executes actual reviewed Ads historical resolver SHA-256 057c59f58bb55905b2b142339626abf1485dbdb9e5702e0f59712d7fca056798 (reviewed commit ba5aff0a94fd490819aaec91f3125b344bef4687), using real Consent signature/digest methods. No Ads source is vendored or edited. Its historical pass after withdrawal demonstrates missing enforcement; it is not joint acceptance.
- browser.cjs: production embedded JS in a VM with synthetic DOM/Web Locks/storage/fetch. Immediate rejection, pending form authority, delayed/reversed responses, monotonic context, original-base retry, fresh explicit choice, initial shared key, missing locks/storage refusal and retry control. Fixture Web Lock callback is synchronous; actual multi-tab locks/cookie/cache behavior remains unverified.
- concurrency.cjs: separate PHP processes released from a barrier run actual capture. Global OS file lock/JSON transaction model stand in for per-chain MySQL lock/transaction. Covers identical retries, contradictory reuse, concurrent rejection/grant and forced delayed grant. Generated scratch paths are verified inside tests before deletion. This is parallel algorithm evidence, not InnoDB/GET_LOCK/durability or independent-chain throughput evidence.
- preserved-contracts.php: 23 unchanged baseline function bodies, policy/nonce boundaries, original receipts DDL in both upgrade entry points and only the added scope in print_config. New DDL and explicit uninstall cleanup are intentional. Static checks are not installed behavior.

Retained consumer-contract.php is a historical rc.1 fixture for the old 0.6.3 CLI resolver; not rerun or counted for rc.2. Do not repin it to a different consumer. New compatibility evidence is in withdrawal.php.

No actual MySQL/MariaDB, live WordPress/Fluent Forms, real browser/cache, external export or installed acceptance is claimed. STATUS.md records executed counts; ALMA-ACCEPTANCE.md specifies the separate coordinated task.
