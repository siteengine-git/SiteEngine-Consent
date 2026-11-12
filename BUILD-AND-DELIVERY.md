# Accepted release build and installation delivery

Canonical checkout: `D:\SiteEngine\Plugins\.PLUGIN — SiteEngine Consent\source`.
Build/stage/archive home: its existing parent `deliverables` directory. Preserve every accepted source pin, historical package and Git history. Never build from an installed site or create a competing source master.

The filesystem-resolved authoritative installation destination is exactly:

`D:\SiteEngine\Plugins\..Deliverables\PLUGIN — SiteEngine Consent`

`..Deliverables` is a literal child directory of `Plugins`. A written path missing the separator after `Plugins` is not an alternate destination; do not create `D:\SiteEngine\Plugins..Deliverables` or another lookalike. Resolve/verify the existing directory before any promotion.

## Build and publication

1. Start from the Helper-accepted runtime and preserve subsequent work. Stable finalization changes release metadata only unless an explicit successor repair is authorized. Policy/banner identity does not follow plugin release versions.
2. Build the existing six-member `siteengine-consent/` payload from canonical source bytes. PHP begins exactly at `<?php`. Exclude secrets, configuration, operational data, tests, source docs and logs. Record byte counts/SHA-256 in PACKAGE-MANIFEST.json and an archive checksum sidecar outside installation delivery.
3. Verify the ZIP member set, hashes, version and exact committed bytes. For metadata finalization, compare every member to the accepted candidate using only explicitly enumerated version/readme release-status substitutions. Do not substitute a repeated integration campaign for this proof.
4. Complete only the installed verification authorized for the assignment, using existing execution locks and targeted file recovery. Preserve consent decisions/history/barriers and unrelated plugins. Release ownership promptly before publication/reporting. Carry completed functional evidence and explicit coverage limits forward.
5. Commit/push without rewriting history; verify remote refs and pinned contents. Publish a public release only when explicitly authorized, with ZIP, manifest, checksum and concise notes through existing procedures. An accepted RC can be promoted unchanged for installation without a stable rebuild/version rename or public release. A package/checkpoint does not imply installation, acceptance, or public Stable release.

## Promotion: exactly one file

Promote only after the owning Helper's applicable acceptance and required installed checks. Retain the accepted delivery until its successor qualifies. Archive superseded ZIPs **and every ancillary file** outside the authoritative directory, with hash readback; never discard historical versions.

For the current Helper-accepted0.6.5-rc.1 the destination must contain **one total file**, exactly:

`siteengine-consent-0.6.5-rc.1.zip`

No checksum, manifest, notes, second ZIP or temporary file may remain there. Copy the verified archive bytes without rebuilding during promotion, verify the destination SHA-256 and enumerate all directory entries, including hidden entries. Retain publication and archive/delivery readbacks in the owning project's evidence. Future accepted versions follow the same exact destination/one-file rule, with only the approved filename/version changing. Never promote another project's package or infer client deployment.

Current accepted release: stable 0.6.5. This supersedes the historical rc.1 filename above. Authoritative delivery contains exactly siteengine-consent-0.6.5.zip. Native legacy/hybrid-schema upgrade and acknowledgment preservation are release gates; see RELEASE-0.6.5.md.
