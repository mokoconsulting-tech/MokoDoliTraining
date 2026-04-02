<!--
Copyright (C) 2026 Moko Consulting <hello@mokoconsulting.tech>

This file is part of a Moko Consulting project.

SPDX-License-Identifier: GPL-3.0-or-later

# FILE INFORMATION
DEFGROUP: MokoDoliTraining.Documentation
INGROUP: MokoStandards.Templates
REPO: https://github.com/mokoconsulting-tech/MokoDoliTraining
PATH: /docs/update-server.md
VERSION: 04.04.00
BRIEF: How this module's update server file (update.txt) is managed
-->

# Dolibarr Update Server

[![MokoStandards](https://img.shields.io/badge/MokoStandards-04.04.00-blue)](https://github.com/mokoconsulting-tech/MokoStandards)

This document explains how `update.txt` is automatically managed for this Dolibarr module.

## How It Works

Dolibarr checks for module updates by fetching a plain-text file from the URL in `$this->url_last_version` in the module descriptor (`src/core/modules/mod*.class.php`). The file must contain **only the version string** — no JSON, no XML, no trailing newline.

### Automatic Generation

| Event | Workflow | `update.txt` Content | `$this->version` |
|-------|----------|---------------------|-------------------|
| Merge to `main` | `auto-release.yml` | `XX.YY.ZZ` (real version) | Real version |
| Push to `dev/**` | `deploy-dev.yml` | `development` | `development` |
| Push to `rc/**` | `deploy-dev.yml` | `XX.YY.ZZ-rc` | RC version |

### Module Descriptor

The `url_last_version` in your module descriptor should point to:

```
https://raw.githubusercontent.com/mokoconsulting-tech/MokoDoliTraining/main/update.txt
```

This is set automatically by `version_set_platform.php` during the build pipeline. **Never manually edit `$this->version` or `$this->url_last_version`** — the workflows handle it.

### Branch Lifecycle

```
dev/XX.YY.ZZ  →  rc/XX.YY.ZZ  →  main  →  version/XX.YY
(development)     (release candidate)  (stable release)  (frozen snapshot)
```

1. **Development** (`dev/**`): `update.txt` = `development`, `$this->version` = `development`
2. **Release Candidate** (`rc/**`): `update.txt` = `XX.YY.ZZ-rc`, version set to RC
3. **Stable Release** (merge to `main`): `auto-release.yml` writes real version to `update.txt`, creates GitHub Release + tag, creates `version/XX.YY` branch
4. **Frozen Snapshot** (`version/XX.YY`): immutable, never force-pushed

### Health Checks

The `repo_health.yml` workflow verifies on every commit:

- `update.txt` exists in the repository root
- Module descriptor (`mod*.class.php`) exists in `src/core/modules/`
- `$this->numero` is set and non-zero
- `$this->version` is not hardcoded (should be set by workflow)
- `url_last_version` points to `update.txt` (not `update.json`)
- `url_last_version` references `/main/` branch on the main branch

---

*Managed by [MokoStandards](https://github.com/mokoconsulting-tech/MokoStandards). See [docs/workflows/update-server.md](https://github.com/mokoconsulting-tech/MokoStandards/blob/main/docs/workflows/update-server.md) for the full specification.*
