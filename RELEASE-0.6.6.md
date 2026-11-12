# Consent 0.6.6 - quiet saves and remembered choices

Removes the floating confirmation notification implementation and its styles. Pending saves show a neutral Saving message only inside preferences. An acknowledged Save closes the panel; late acknowledgment does not discard newer unsaved edits or close an inactive Consent tab. Explicit dismissal is respected.

A valid local choice is remembered on return whether server acknowledgment is saved, pending or failed. Returning visitors are not repeatedly prompted solely because acknowledgment failed. This does not establish server authority: absent/invalid signed acknowledgments remain ineligible. Genuine failures from an explicit attempt reveal an inline failure with the original same-event Retry inside preferences. Opening/returning does not create or automatically retry choices. Storage refusal, expiration and changed policy/banner can still require a choice; persistence is not promised when browser storage is unavailable.

No database schema, signatures, authority, continuity, diagnostics, policy/banner identity or withdrawal protection changed from 0.6.5. The three authoritative include files and uninstall implementation are byte-identical. This release addresses presentation and repeated prompting; it does not claim the previously reported persistent failure in Aubrey's other browser has been reproduced or resolved.

Verification: 33 focused browser/transport assertions, 40 standalone preference assertions and 74 shared-panel assertions passed. They cover delayed acknowledgment, real failure feedback in the modeled host, same-event Retry, no floating notification, remembered pending/failed/saved choices, no return-time capture and preservation of newer drafts. These are isolated boundary checks, not native-browser network-failure simulation.

Installed on Alma, siteengine.io and climatrolairga.com under execution locks with targeted file/database backups. All six installed files match the final archive and each site's preexisting rows were preserved during installation. Site-scoped LiteSpeed purge requests completed. On Climatrol the actual shared panel displayed only inline Saving while pending, then saved with an empty inline message and a closed panel. Reload retained the acknowledged choice without banner or floating notification. No normal-save notification is merely hidden: its DOM-creation code is absent.

All locks released. Other client websites were not changed. Public availability and authoritative one-file delivery are verified at publication.
