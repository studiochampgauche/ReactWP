=== Universal SMTP ===
Contributors: studiochampgauche
Tags: smtp, email, mail, phpmailer, wp-mail
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Route WordPress email through a configured SMTP server without replacing WordPress's wp_mail() API.

== Description ==

Universal SMTP connects WordPress's existing `wp_mail()` flow to an SMTP server through WordPress's bundled PHPMailer instance. It is a portable plugin: it can be used with ReactWP or copied to another WordPress installation.

Open Settings > SMTP to enable SMTP delivery and configure:

* the SMTP host and port;
* STARTTLS (`tls`), implicit TLS (`ssl`), or no transport encryption;
* optional SMTP authentication with automatic, LOGIN, PLAIN, or CRAM-MD5 authentication;
* an SMTP username and write-only password;
* the default From email address and From name, with independent controls to force either value;
* an optional Return-Path;
* a connection timeout from 5 to 60 seconds.

The password can be stored encrypted in the WordPress database or supplied through the `UNIVERSAL_SMTP_PASSWORD` constant in `wp-config.php`. The constant takes precedence when it is defined. Do not commit an SMTP password to source control. Encryption at rest reduces raw database exposure; it does not protect the password when WordPress, PHP, or the server runtime is compromised. The encryption key is derived from the WordPress authentication salts. If those salts change, the stored password becomes unreadable and Universal SMTP stops applying the SMTP configuration until an administrator saves the password again, unless a valid runtime constant supplies it.

The administration screen saves through authenticated WordPress AJAX when JavaScript is available and keeps the native WordPress form submission as a fallback. Only users with `manage_options` can view or change the configuration, send a test email, or view the most recent delivery status.

The test-email tool uses the same `wp_mail()` path as the site and is limited, per administrator, to five attempts in ten minutes. Its quota lock is stored in the WordPress database; every web instance must share that database for the limit to be enforced strictly across the deployment. The last-delivery status contains only the outcome, time, a generic result code, and recipient count. Universal SMTP does not log message recipients, subjects, bodies, or SMTP credentials.

Universal SMTP does not disable TLS certificate verification and does not prevent administrators from using an authorized internal SMTP host. Configuration and test access must remain limited to trusted administrators.

This plugin is not an email delivery service. The site operator remains responsible for valid provider credentials, provider sending limits, mailbox or relay configuration, and the domain's SPF, DKIM, and DMARC records. A successful connection or test does not guarantee inbox placement.

Universal SMTP is experimental and has not yet undergone exhaustive testing. While this notice remains, use it with caution and verify the complete configuration and delivery path for each project before relying on it in production.

== Installation ==

1. Copy the `universal-smtp` directory to `wp-content/plugins/`.
2. Activate "Universal SMTP" in the WordPress Plugins screen.
3. Open Settings > SMTP.
4. Enter the SMTP connection and sender details, then enable SMTP delivery.
5. Save the settings and send a test email to an address you control.
6. Confirm the message was accepted by the intended mailbox and review the provider's activity when available.

To keep the SMTP password out of the database, define it in `wp-config.php` before loading WordPress:

```php
define('UNIVERSAL_SMTP_PASSWORD', getenv('UNIVERSAL_SMTP_PASSWORD'));
```

Provide the environment variable through the hosting platform or server configuration. Do not place the secret in a tracked project file.

== Configuration notes ==

= Encryption =

Use the mode required by the SMTP provider:

* STARTTLS (`tls`) starts as a normal connection and upgrades it to TLS. It is commonly used on port 587.
* Implicit TLS (`ssl`) establishes TLS when the connection opens. It is commonly used on port 465.
* None sends the SMTP session without transport encryption. Use it only for a deliberately trusted network and server configuration.

The port examples are conventions, not automatic provider settings. Use the exact host, port, encryption mode, and authentication method documented by the SMTP provider.

= Authentication =

Leave the authentication type on Automatic unless the SMTP provider explicitly requires LOGIN, PLAIN, or CRAM-MD5. When authentication is disabled, the username and password are not used.

= Sender identity =

The From email address and From name provide the sender identity. Their force controls override values supplied by themes or other plugins. Some SMTP providers reject or rewrite a sender address that is not authorized for the connected account.

When Return-Path is enabled, Universal SMTP uses the configured From email as PHPMailer’s envelope-sender address. Enable it only when that exact address is accepted by the SMTP provider and domain policy.

= Delivery status =

The status panel is a small operational signal, not a mail log or audit trail. It records no message content and cannot confirm that a receiving mailbox placed a message in the inbox.

== Data retention ==

Deactivating or uninstalling Universal SMTP preserves its settings, encrypted password, and last-delivery status so a temporary removal does not silently destroy an operational mail configuration. Remove stored values deliberately before uninstalling when a project requires data erasure.

== Changelog ==

= 1.0.0 =
* Initial experimental release with SMTP routing, sender controls, protected credentials, test delivery, and bounded delivery status.
