# Same-browser withdrawal contract — candidate 0.6.1-rc.2

Prospective only. Policy remains `2026-05-08-v1`. No deployment or historical repair. Stored stamp contract remains 1; its authenticated continuity extension is `continuity_version: 1`. Current eligibility uses result contract 2. Historical evidence and present permission are different decisions.

## Exact consumer API

```php
SiteEngine_Consent_Authority::current_eligibility(
    array $stamp,
    int $canonical_form_id,
    string $required_policy
): array
```

Pass the original `_se_consent_server` from the canonical persisted Fluent Forms response and the independently resolved form ID. Do not construct evidence from flat fields, entry details, request/CRM data or a receipt ID alone. The method authenticates the form-bound v1 signature, exact unambiguous receipt ID/row/digest, stored choices and metadata, signed event link and signed current chain head. It requires no browser cookie and performs SELECTs only. The final head SELECT uses `LOCK IN SHARE MODE`, a current read rather than a reusable transaction snapshot. Call against the primary WPDB connection without caching; an ambient transaction can retain its shared lock until its caller commits. Actual database privileges, routing, connection and engine behavior remain an acceptance gate. [MySQL locking-read semantics](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html) inform this design; the isolated adapter is not evidence that an installed engine implements it correctly.

Result keys: `contract_version` (2), `available` (bool), `allowed` (bool), `reason` (string), `historical_verified` (bool), `checked_at` (UTC ISO), `event_revision`, `current_revision` (ints), `consent` (four strict booleans), `google_consent` (derived flags). Consumers require **exactly contract 2, available === true, allowed === true**, in addition to their existing gates. Never infer permission from historical_verified, presence of a stamp, an empty reason, or a category alone.

Reasons: `eligible`, `denied`, `withdrawn`, `expired`, `policy_mismatch`, `banner_mismatch`, `invalid_evidence`, `missing_or_ambiguous_receipt`, `continuity_unavailable`, `storage_unavailable`. Authenticated negative/expired/mismatched state is available but disallowed. Missing/legacy/invalid/ambiguous/unavailable evidence is unavailable and disallowed. An exception is disallowed. No newest-row selection or unsigned fallback occurs. Missing heads cannot be recreated with reset barriers when retained event links identify the chain. Historical signature checking ignores browser-ACK expiry; current head preference lifetime is checked separately. Salt rotation invalidates signatures and continuity; no automatic recovery grants.

For each optional category, effective consent is original event true AND current head true AND event revision strictly greater than that category's withdrawal barrier. A false category advances its barrier to the new revision, even if already false. Later grants never lower barriers. Advertising `allowed` depends on effective advertising; personalization remains independent. Existing consumer policy, deterministic submission, DNC, denied/missing evidence and ad_user_data gates remain required.

Revalidate immediately before **each actual browser advertising payload and external export/append dispatch**, including queued/batched retries. A decision made during preview/build is insufficient. Do not cache permission across withdrawal or retroactively upgrade a negative submission. The final head read orders a decision against committed withdrawals; no API can recall a payload already dispatched before a withdrawal committed, or close a network race after that read. Consumers must minimize that gap and test withdrawal between prepare and dispatch.

## Browser continuity and authority

The browser generates a 256-bit CSPRNG bearer secret, retained in site-scoped localStorage and a Secure SameSite=Lax cookie. An origin-wide Web Lock serializes first-use creation across tabs. Without Web Locks, secure randomness or initial durable storage, capture fails closed; local preferences/business forms still work. Context is separate from preference state so a new choice does not replace identity. Site scope combines home URL and table prefix. Changing site identity, deleting all continuity copies or switching device loses linkage; older grants cannot be retroactively matched or withdrawn by the new chain. No fingerprint, CRM identifier or cross-device correlation is introduced.

The server derives chain identity by HMAC of site scope and secret; arbitrary chain/receipt IDs are never accepted as authority. A signed context token binds that chain and the acknowledged server revision. It is an ordering capability, not a grant; initial empty context means revision 0. The key and context are not written into receipt columns, flat form fields or stamps. The stamp contains the opaque derived chain ID, event revision and event-link integrity MAC, covered by its existing form-bound signature.

The acknowledgement proves that this site persisted this explicit capture request and exact event under the bearer continuity capability. It does **not** prove person identity, ownership, informed intent or legal permission. A holder of the browser secret can act for that browser chain; same-origin script compromise or a stolen bearer capability is outside this identity-free mechanism. A nonce/UUID/signature alone does not prove consent intent. Browser nested/flat fields, categories, Google flags and IDs are untrusted; categories must be strict booleans and server-derived Google flags replace submitted ones.

## Persistence, ordering and retries

Original `se_consent_receipts` columns, indexes, digest and historical rows are unchanged. Two additive InnoDB tables are installed by normal authorized activation/version upgrade:

- `se_consent_chains`: chain primary key, server revision, current categories/policy/banner/expiry, three permanent category barriers and integrity MAC.
- `se_consent_events`: exact receipt row primary key, unique new receipt ID, unique chain/revision, canonical interaction hash, original receipt digest and integrity MAC. These uniqueness constraints apply only to new links, never legacy receipts.

Capture requires all three tables to be InnoDB; it refuses an incompatible existing receipts engine rather than converting it. A per-chain named lock and transaction serialize capture. Receipt, immutable link and new signed head are read back and committed before ACK. Errors roll back and acknowledge nothing. Transaction COMMIT may be uncertain after connection loss; the same interaction retry resolves a committed event, otherwise it remains failed. A replica/read router, transaction-changing drop-in or unsupported privilege must block acceptance rather than silently relax atomicity. No transaction is started by the eligibility API.

Every explicit interaction has a new UUID. Namespaced receipt IDs derive from chain plus UUID. Identical retry retains the original base token and normalized request; changed IP/UA do not change event identity or rewrite stored metadata. Conflicting reuse cannot append. An identical retry of the current head returns the same row/revision; a superseded event returns `superseded` with current signed context, never an old restoring ACK.

Grant or mixed choices require base revision equal to the head. A stale complete rejection (all optional categories false) may still advance the head, so either ordering of concurrent grant/rejection ends rejected. A stale grant or mixed choice returns `continuity_stale` with current context and requires a **new explicit choice**, never automatic rebasing of an old grant. Invalid/missing key/token cannot modify another chain. Rejection is applied locally immediately; network/audit failure is reported as unconfirmed server persistence and is retryable with the same interaction. It cannot claim that server withdrawal succeeded. Permanent storage/ordering failures require a fresh explicit choice after recovery.

The browser learns only monotonically increasing server context and never restores a superseded interaction. Storage events propagate pending rejection across open tabs. ACK failures retain local choice, blank receipt authority and existing failure/retry feedback. A form submitted before successful ACK has a frozen negative stamp and stays negative after a delayed ACK. Existing response sanitation, unrelated fields, post-component Fluent Forms filters and entry-detail sanitation remain in force.

Policy/banner mismatch and legacy/expired state require renewed explicit choice; release identity is separate from policy. Head preference expiry uses the configured cookie lifetime (default 390 days) and server time. Continuity can survive preference expiry to preserve withdrawal barriers. No new cleanup job is introduced; new chain/link state is retained conservatively. Existing receipt retention is unchanged: deleted receipt evidence fails closed. Bounded retention/disposal for continuity requires a separate reviewed task; uninstall explicitly removes these tables with the existing destructive uninstall action, which must never be used for deployment or rollback.

## Acceptance limits

Isolated SQL-boundary/parallel-file/VM tests exercise the real Consent algorithms and the actual pinned reviewed Ads historical resolver. They do not prove InnoDB durability, installed WordPress/Fluent Forms lifecycle, actual browser/Web Locks/cache behavior, minimum PHP 7.4 execution or end-to-end dispatch enforcement. The reviewed Ads commit still reports revocation unavailable and accepts authenticated historical grants; its owning project must enforce this API. See ADS-CODEX-ASSIGNMENT.md and ALMA-ACCEPTANCE.md. No messages or assignments have been sent externally.
