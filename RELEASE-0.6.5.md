# Stable Consent 0.6.5

Released November 12, 2026. Corrects the confirmed database upgrade collision between legacy event logs and immutable continuity links. New writes use the separate se_consent_event_links table; genuine old links remain readable without rewriting history. Previously failed choices require explicit retry or a new choice. No historical negative submission becomes eligible retrospectively.

## Evidence and limits

Carry forward the native MariaDB before/after reproduction and affected authority/withdrawal/namespace checks at 0ff0d65. The stable payload differs from that verified rc.2 runtime only in version metadata and readme release text. No JavaScript, authority, continuity or diagnostics implementation changed during finalization.

SiteEngine production was already running the exact rc.2 files when this task began. A fresh necessary-only browser rejection reached saved and persisted after reload; direct database comparison showed one added receipt, one link and one chain, with every preexisting row preserved. Stable 0.6.5 was then installed with six-file parity, private targeted file/database backups and unchanged existing data. A necessary-only Save in the same owned browser chain reached saved and persisted after reload. Final counts changed from 22 receipts/3 heads/3 links at the task baseline to 24 receipts/4 heads/5 links, comprising the two owned choices. Every baseline row remains unchanged. The owned head advanced intentionally; audit evidence is retained.

Alma was upgraded from exact accepted rc.1 using the normal version-upgrade bootstrap. The additive event_links table was created. All 13 receipts, eight heads and 12 historical links were preserved, with six-file parity. All execution locks were released. Home-page cache purge requests completed for these two sites. No other client site was changed or is claimed recovered.

Public browser verification used actual shared-panel controls and server-backed saved status, followed by reload, not disappearance of a warning alone. Existing policy/banner identity is retained. Real screen-reader, physical touch and broad fleet compatibility are not newly claimed.

## Delivery

ZIP: siteengine-consent-0.6.5.zip
SHA-256: 38f4b623a4d6a6d60b5eea2cb50f9f5080e9df663b611557db22c63c41e7a992
Members: six; 51,852 bytes. The authoritative existing installation directory contains exactly this one ZIP; historical packages remain archived outside it.

Future release acceptance must include native legacy/hybrid-schema upgrade DDL, capture acknowledgment and preservation, alongside clean installations. An HTTP health response or assertion count alone is insufficient. New table CREATE/index privileges and InnoDB are required; incompatible or ambiguous evidence stays blocked. Do not uninstall or restore an older database as an update procedure.
