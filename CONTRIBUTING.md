# Contributing to ReactWP

Thank you for helping improve ReactWP. Contributions should keep WordPress familiar for editors, preserve backward compatibility where practical, and remain secure in client, static, server, and headless modes.

## Before You Start

- Search existing issues and pull requests before opening a duplicate.
- Use a GitHub issue for a reproducible bug or a focused feature proposal.
- Report vulnerabilities privately to [security@reactwp.com](mailto:security@reactwp.com), never in a public issue.
- Keep changes scoped. Separate unrelated refactors from behavioral fixes.

## Local Setup

Requirements and full installation instructions are available in the [documentation](https://reactwp.com/docs/getting-started/).

```bash
git clone https://github.com/studiochampgauche/ReactWP.git
cd ReactWP/configs
npm ci
npm run get:core
npm run build
```

ACF Free is sufficient for standard fields. ACF PRO requires your own valid license and licensed site URL. Never commit license keys, authenticated download URLs, `wp-config.php`, `.env` files, archives, or generated `dist/` content.

## Development

Run commands from `configs/`:

```bash
npm run watch
npm run test:security
npm run test:render
npm run prod
```

When PHP is not on `PATH`, set `PHP_BINARY` to a compatible PHP executable before running the security suite.

Changes should:

- support PHP 8.1 or later and Node.js 22.22.0 or later;
- keep `client` rendering backward compatible;
- treat public and private payloads, caches, previews, and routes as separate security boundaries;
- include focused regression coverage for changed behavior;
- update `README.md`, `CHANGELOG.md`, and the documentation when a public contract changes.

## Pull Requests

1. Create a focused branch from the default branch.
2. Add or update tests.
3. Run the security suite, render suite, production build, npm audit, and Composer audit.
4. Explain the user-visible behavior, compatibility impact, security considerations, and verification performed.
5. Keep generated WordPress output and local credentials out of the commit.

By contributing, you agree that your contribution is licensed under GPL-2.0-or-later and that you have the right to submit it.
