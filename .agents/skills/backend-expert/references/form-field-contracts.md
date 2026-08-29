---
name: reactwp-form-field-contracts
description: Shared frontend/backend contracts for formatting, validating, normalizing, storing, and testing ReactWP form fields without treating client-side restrictions as security.
---

# Cross-Layer Form Field Contracts

## When to Use This Reference

Use this reference whenever a ReactWP project creates or changes a user-editable field in an integrated React form, a headless consumer, a WordPress/ACF editor screen, or a custom REST/admin handler.

ReactWP exposes useful WordPress sanitization and escaping helpers, but it does not know a project field's business grammar or automatically keep a React formatter synchronized with PHP validation. Project code owns that contract.

## One Authoritative Contract Per Field

For every submitted or persisted field, keep one authoritative field contract. Backend is the default write custodian because it owns the accepted transport and canonical/storage value; frontend co-approves the same revision because it owns the visible input behavior. Custody may be transferred explicitly, but the roles must not maintain independent regexes or undocumented rules that can drift.

For a client-only field that never crosses a trust boundary, frontend may own the contract. If that field later becomes submitted, persisted, logged, cached, or used in a query, establish the backend contract before shipping the new path.

Record at least:

```text
Field/purpose:
Custodian and revision:
Frontend approval / backend approval:
Raw input sources:
Required, optional, empty, and null behavior:
Visible format and editing behavior:
Allowed characters, positions, grouping, and cross-field rules:
Length/count/range units and limits:
Locale, country, calendar, currency, and timezone assumptions:
Native input attributes and autocomplete semantics:
Accepted transport grammar:
Canonical/storage representation:
Backend validation and normalization:
Stable field error code and localized message owner:
Sensitivity, logging, cache, and retention rules:
Allowed, boundary, malformed, paste/autofill, and bypass fixtures:
```

Prefer a shared schema or generated contract when the existing architecture supports it. When PHP and JavaScript must implement separate validators, keep one shared fixture corpus of accepted and rejected values and run it against both implementations. Matching function names are not evidence that the behavior matches.

## Responsibility Split

| Concern | Frontend | Backend |
| --- | --- | --- |
| Entry experience | Native control, keyboard hint, autocomplete, instructions, formatter/mask, caret, accessible errors | Not applicable to visual editing |
| Early feedback | Prevent or immediately flag characters and structures that can never be valid | Return stable field errors for every invalid request |
| Authoritative acceptance | Helpful only; never a security boundary | Revalidate every request independently of its client |
| Normalization | Produce the documented transport value without hidden semantic changes | Produce the canonical value after validation and before storage/use |
| Business and cross-field rules | Reflect them for timely feedback | Enforce them authoritatively with current server state |
| Security | Avoid unsafe client sinks and accidental sensitive-data exposure | Authorization, CSRF where applicable, resource bounds, safe storage/query/output |
| Evidence | Typing, deletion, selection, paste, drop, autofill, mobile keyboard, IME, errors | Direct requests that bypass React, malformed/oversized/unauthorized cases, storage round trip |

Security reviews the trust boundary; QA verifies that both sides implement the same revision. Frontend formatting improves usability, but backend validation decides whether the value is accepted.

## Frontend Input Behavior

- Start with the correct native control and attributes: `type`, `name`, `autocomplete`, `inputMode`, `required`, `min`, `max`, `step`, `minLength`, `maxLength`, and `pattern` where their semantics match the contract. Attributes such as `type="tel"`, `inputMode`, `pattern`, and `accept` are hints or browser validation, not backend protection.
- Do not implement an allowlist only in `keydown`. Values also arrive through paste, drag/drop, autofill, speech input, mobile keyboards, IME composition, accessibility tools, browser restoration, and programmatic updates. Apply the contract at the value/input boundary and again before submission.
- For characters that can never be meaningful under the approved contract, prevent insertion or normalize them immediately across every input path. Preserve deletion, selection replacement, undo/redo, caret position, and composition behavior. If silent removal could change meaning or surprise the user, retain the value and show a specific validation error instead.
- Distinguish transient editing states from a valid submitted value. Allow an intermediate state needed to construct a valid value—for example, an opening parenthesis before its closing pair—while preventing characters that can never belong and requiring the complete grammar on blur/submission as the contract specifies.
- Keep the editing/display value distinct from the canonical transport value when formatting adds grouping, separators, localized digits, currency symbols, or timezone presentation.
- Explain non-obvious format requirements before the field. Associate errors through `aria-describedby`, set `aria-invalid` after failure, preserve the entered value after a rejected submission, and focus the error summary or first invalid field when appropriate.
- Map stable backend field error codes to the same control. Do not replace a more authoritative server error with a generic success or discard the user's recoverable input.
- Test the formatter with rapid edits and at text boundaries. A field that is valid only when characters are appended at the end is not complete.

## Backend Acceptance and Canonicalization

For every request, including requests that bypass the React application:

1. read and unslash at the correct WordPress boundary;
2. reject the wrong scalar/object/list type and bound byte/code-point length before expensive parsing;
3. validate allowed characters, their positions, grouping, range, locale and business/cross-field rules;
4. authenticate, authorize, and verify CSRF/nonces independently where the operation requires them;
5. normalize an already accepted representation into the documented canonical value;
6. store/query through the correct WordPress or project API;
7. return stable field-specific errors and the canonical public value when appropriate;
8. escape or encode later for each final output sink.

Do not silently turn an invalid semantic value into a different accepted one merely because a sanitizer can remove characters. For example, accepting `123abc` as the number `123` conceals invalid input. Benign display separators may be removed only when the contract explicitly accepts them and the remaining value is unambiguous.

For custom REST endpoints, declare argument types and bounds and use validation plus sanitization/normalization callbacks as appropriate; perform cross-field and current-state rules in the endpoint/service. For ACF editor submissions, use field settings and `acf/validate_value` for applicable editor validation, then normalize through a deliberate project hook when needed. Project-owned REST/admin/programmatic writes still require their own validation rather than assuming an ACF browser rule ran.

`rwp::sanitize()` normalizes supported types; it does not define the business grammar, authorize the request, or prove that normalization preserved intent. `rwp::escape()` protects a later output context; it does not validate input.

## Telephone Example

Do not impose one worldwide phone presentation without a product decision about supported countries, extensions, significant-digit limits, and whether a country selector exists. For the user's strict example—or any field whose approved requirements are equivalent—the contract must specify:

- the contract states whether digits mean ASCII `0`-`9` or another deliberate Unicode/locale representation, and they are the only ordinary characters;
- a single `+` is allowed only at position zero;
- parentheses are the only other allowed punctuation, must be balanced in the submitted value, and may surround only the approved digit group;
- letters, misplaced or repeated `+`, unmatched parentheses, and every other punctuation character are invalid unless a different business grammar was explicitly approved;
- the canonical value is the documented country-aware digits form, such as `+15145550123`, while the visible value may be `+1(514)5550123`;
- an extension, when supported, has its own explicit field or grammar rather than an undocumented suffix.

Frontend should use a labelled `type="tel"` control with suitable `inputMode` and `autocomplete="tel"`, enforce the approved grammar for typing and every alternate input path, keep caret/editing behavior usable, and submit the agreed transport representation. `type="tel"` alone does not restrict characters.

Backend must independently reject strings outside the accepted transport grammar, validate the supported numbering plan and length, normalize only documented display characters, and store the canonical representation. If reliable international validation is required, choose a maintained numbering-plan parser deliberately and keep its region/version assumptions in the contract; do not replace that decision with one universal regex.

## Other Common Field Decisions

| Field kind | Contract decisions that must remain aligned |
| --- | --- |
| Email | Trimming and case policy, length, syntax validation, internationalized domains, verification workflow; browser `type="email"` is not server validation |
| Human name/address | Unicode and locale-aware punctuation; do not reject real names with ASCII-only or letters-only assumptions |
| Postal/region code | Supported country/region, spacing/case display, canonical form, country-dependent grammar |
| Integer/decimal/currency | Locale display versus canonical number, decimal/group separators, precision, rounding, sign and min/max; represent money without unsafe floating-point assumptions |
| Date/time | Calendar, accepted date range, timezone, daylight-saving ambiguity, visible locale format, canonical ISO representation |
| URL | Absolute/local rule, permitted schemes and hosts, normalization, redirect/fetch/output use; a syntactically valid URL may still be forbidden |
| Enum/identifier | Exact allowlist, case policy, length and existence/current-state validation; never pass a client identifier directly to SQL or authorization |
| Password/secret | Preserve exact value unless the authentication protocol explicitly says otherwise; do not silently trim, normalize, log, or truncate; allow password managers and paste |
| File | `accept` is a picker hint; backend validates size, content/MIME, extension, count, permissions and safe storage |

## Verification Matrix

For each field, verify as applicable:

- the shortest/longest valid value and every important boundary;
- empty, null, missing, wrong type, too long, unsupported Unicode, and mixed valid/invalid input;
- invalid character at the beginning, middle, and end;
- misplaced/repeated prefix or separators and malformed grouping;
- typing, deletion, selection replacement, paste, drag/drop, autofill, mobile keyboard, IME, undo/redo, and programmatic submission;
- a direct REST/admin request that bypasses frontend checks;
- frontend and backend results against the same fixture corpus;
- canonical storage and a read/render round trip;
- localized accessible instructions and errors without sensitive data leakage;
- unauthorized, replay/duplicate, oversized, and failure behavior when the operation is state-changing or exposed.

## Do Not

- Do not treat an input mask, `pattern`, `type`, `inputMode`, `accept`, disabled button, or JavaScript validator as a security boundary.
- Do not let frontend and backend invent separate field grammars or normalize to different canonical values.
- Do not reject legitimate Unicode, locale, assistive-technology, password-manager, or paste behavior through an overly broad keyboard filter.
- Do not silently strip characters when doing so can change the submitted meaning.
- Do not store display punctuation, localized formatting, or pre-escaped text as canonical data unless that representation is itself the explicit domain value.
- Do not expose server internals or echo unsafe input inside field errors.
