# AI Agent Development Guide for ZoneMinder

> **Note**: This file guides AI coding agents (Claude Code, GitHub Copilot, Cursor, etc.) working on ZoneMinder.
> CLAUDE.md is a symlink to this file.

## Quick Reference (MANDATORY RULES)

1. **Testing First**: Write tests BEFORE/DURING implementation - NEVER skip. Tests written "later" never get written.
2. **Build System**: CMake with C++17, out-of-source builds in `build/`. In-source builds pollute the repo.
3. **Feature Workflow**: GitHub Issue -> Feature Branch -> Implement FULLY -> Tests Pass -> Get Approval -> Merge to master. Feature branches keep master stable.
4. **Commits**: Conventional format (`feat:`/`fix:`/`test:`), reference issues (`refs #n` or `fixes #n`). Enables automated changelog.
5. **Pre-Commit**: Tests pass, build succeeds, linting clean, no warnings.
6. **Never merge without user approval. Never leave features half-implemented.**

---

## What is ZoneMinder?

Linux-based CCTV surveillance system: capture, analysis, recording, and monitoring of video cameras.

- **C++ daemons** (`src/`) - Capture (`zmc`), analysis (`zma`), streaming (`zms`), utility (`zmu`)
- **PHP web interface** (`web/`) - Bootstrap + jQuery UI, AJAX endpoints in `web/ajax/`, views in `web/views/`
- **REST API** (`web/api/`) - CakePHP 2.x, controllers in `web/api/app/Controller/`, JWT auth
- **Perl scripts** (`scripts/`) - Daemon control (`zmdc.pl`), migrations (`zmupdate.pl`), filtering (`zmfilter.pl`)
- **MySQL database** (`db/`) - Schema in `db/zm_create.sql.in`, migrations in `db/zm_update-*.sql` (60+ versions)

---

## Architecture

### Data Flow

```
Camera -> zmc (capture) -> Shared Memory -> zma (analysis) -> Event Recording
                              |                                    |
                           zms (streaming)                   Database + Disk
                              |                                    |
                        Web Browsers <- Web Interface/API <- MySQL Storage
```

### Key Patterns

- **Shared Memory**: `zmc` writes frames to `/dev/shm`; `zma` and `zms` read from same buffers. Zero-copy for performance — this is why ZM can handle many cameras on modest hardware.
- **Monitor-Centric**: `Monitor` class (`src/zm_monitor.cpp/h`) is the central orchestrator. One monitor = one DB row = one set of daemons. Most changes to camera handling flow through this class.
- **Pluggable Cameras**: Abstract `Camera` base with: `LocalCamera` (V4L2), `RemoteCameraRTSP`, `RemoteCameraHTTP`, `FFmpegCamera`, `LibVLCCamera`, `LibVNCCamera`. Add new camera types by subclassing Camera.
- **Event-Driven Recording**: Motion detection triggers `Event` objects with pre/post alarm buffers. Lifecycle: Create -> Record -> Close -> Archive/Delete. Events are the core unit of recorded footage.
- **Multi-Server Clustering**: Database-coordinated distributed architecture with shared monitors and storage.

### Directory Structure (Key Paths)

```
src/                      C++ core (86+ source files): zm_monitor.*, zm_camera.*, zm_event.*, zm_zone.*, zm_image.*, zm_ffmpeg*.*
web/                      PHP web interface
  ajax/                   AJAX handlers
  includes/               PHP libraries and functions
  views/                  UI templates
  skins/                  Themes (classic skin)
  js/, css/               Frontend assets
  api/app/Controller/     CakePHP REST API controllers
  api/app/Model/          CakePHP REST API models
scripts/                  Perl system management (.in templates)
db/                       Schema (zm_create.sql.in) and migrations (zm_update-*.sql)
tests/                    Catch2 unit tests + test data
misc/                     Server configs (apache, nginx, systemd)
dep/                      Vendored deps (catch2, jwt-cpp)
.github/workflows/        CI/CD pipelines
```

---

## Development Workflow (MANDATORY)

### For every feature or bug fix:

1. **Create GitHub Issue**: `gh issue create --title "..." --label enhancement|bug`
2. **Create Feature Branch**: `git checkout -b <issue>-<description>` from master
3. **Write failing test first** (TDD)
4. **Implement** - follow existing patterns, keep changes minimal
5. **Build & test** - must all pass (see Build and Testing sections)
6. **Commit** in logical chunks with conventional messages
7. **Request user approval** - never merge without it
8. **After approval**: merge to master, delete branch, push, verify issue closes

### Technology-Specific Notes

- **C++ changes** (`src/`): Rebuild required, run ctest. Daemons must be restarted to pick up changes.
- **PHP/JS changes** (`web/`): No rebuild needed — PHP is interpreted, changes are live immediately. Test in browser + ESLint.
- **Database schema** (`db/`): Create migration file `zm_update-X.X.X.sql` for existing installs, also update `db/zm_create.sql.in` for fresh installs. Both must result in the same schema.
- **Perl scripts** (`scripts/`): Edit `.in` template files (NOT generated scripts). cmake substitutes `@ZM_*@` variables to produce the final scripts. When testing perl changes, any perl script will try to read /etc/zm/zm.conf and needs to be run as a user with permission to read it. Best to run with sudo -u www-data
- **API changes** (`web/api/`): Clear CakePHP cache (`tmp/cache/`) if you change models or routes. Test endpoints with curl.

### Example Workflow

```bash
gh issue create --title "Add event favorites" --label enhancement
# Note issue number, e.g. #42
git checkout master && git pull && git checkout -b 42-event-favorites

# Write test first, implement, build & test
cd build && cmake --build . && ctest
npx eslint --ext .js.php,.js .

# Commit
git add <files>
git commit -m "feat: add favorites toggle to event view refs #42"

# Ask user for approval, then after approval:
git checkout master && git merge 42-event-favorites
git push origin master
git branch -d 42-event-favorites && git push origin --delete 42-event-favorites
```

---

## Build System

```bash
# Standard build (out-of-source required)
mkdir build && cd build
cmake ..
cmake --build .

# Debug build with tests
cmake -DCMAKE_BUILD_TYPE=Debug -DBUILD_TEST_SUITE=ON ..
cmake --build .

# Build specific target
cmake --build . --target zmc
```

### Key CMake Options

| Option | Values | Default | Notes |
|--------|--------|---------|-------|
| `CMAKE_BUILD_TYPE` | Release/Debug/Optimised | Release | |
| `BUILD_TEST_SUITE` | ON/OFF | OFF | Enables Catch2 tests |
| `ENABLE_WERROR` | ON/OFF | OFF | Warnings as errors (CI uses ON) |
| `ASAN` | ON/OFF | OFF | AddressSanitizer |
| `TSAN` | ON/OFF | OFF | ThreadSanitizer (mutually exclusive with ASAN) |
| `ZM_CRYPTO_BACKEND` | openssl/gnutls | | |
| `ZM_JWT_BACKEND` | libjwt/jwt_cpp | | |
| `ZM_TARGET_DISTRO` | FreeBSD/fc/el/OS13 | (Debian) | Platform-specific paths |
| `ZM_ONVIF` | ON/OFF | ON | ONVIF camera support |

---

## Testing Requirements (MANDATORY)

### Workflow

1. Write a failing test that reproduces the issue or validates the feature
2. Implement the fix/feature
3. Run tests - verify they PASS
4. Run full test suite for regressions
5. Only then commit

### C++ Unit Tests (Catch2)

```bash
# Build with tests
cmake -DBUILD_TEST_SUITE=ON .. && cmake --build .

# Run all tests
ctest
# Or directly with more output
./tests/tests

# Run specific test / list tests
./tests/tests "[Box]"
./tests/tests --list-tests
./tests/tests "~[notCI]"
```

**Test location**: `tests/` directory
**Existing modules**: `zm_box.cpp`, `zm_comms.cpp`, `zm_crypt.cpp`, `zm_font.cpp`, `zm_poly.cpp`, `zm_utils.cpp`, `zm_vector2.cpp`
**Framework**: Catch2 with custom header `tests/zm_catch2.h`, main in `tests/main.cpp`
**Test data**: `tests/data/fonts/`

### JavaScript Linting

```bash
npx eslint --ext .js.php,.js .        # Lint all
npx eslint --fix web/js/              # Auto-fix
```

ESLint config: `eslint.config.js` (ESLint 9 flat config, Google style guide). Runs in CI via `.github/workflows/ci-eslint.yml`.

### PHP/API Testing

Manual testing required. Test endpoints with curl/browser. Verify JSON responses, auth (session + JWT), error handling, DB persistence.

### Database Migration Testing

```bash
sudo zmupdate.pl --check              # Check for updates
sudo zmupdate.pl                      # Apply migrations
```

When creating migrations: test upgrade path AND verify fresh install matches migrated schema.

### CI Notes

- Unit tests are **DISABLED** in CI (`BUILD_TEST_SUITE=0`) — some tests need hardware/network access. Running locally goes beyond CI requirements but is still expected for local development.
- CI does: multi-platform builds (Debian/Ubuntu/CentOS with GnuTLS/OpenSSL + libjwt/jwt_cpp matrix), ESLint, CodeQL security scanning

---

## Code Standards

### C++ (`src/`)

- **C++17** standard, follow existing patterns in file/module
- **Memory**: RAII, smart pointers where appropriate
- **Error handling**: Exceptions for exceptional cases, return codes for expected errors
- **Logging**: Use printf-style `Debug(1, "msg %s", val)`, `Info(...)`, `Warning(...)`, `Error(...)`, `Fatal(...)` — NOT iostream style. The number in Debug() is verbosity level (1-9).

### PHP (`web/`, `web/api/`)

- PSR-12 where practical, follow existing conventions
- **Security**: Validate ALL user input, use prepared statements (NOT string interpolation — legacy code has SQL injection bugs we're fixing), sanitize output with htmlspecialchars/json_encode, CSRF protection on forms
- CakePHP 2.x framework in `web/api/` — we're stuck on 2.x due to migration cost, don't try to upgrade it

### JavaScript (`web/js/`, inline in PHP)

- Google JavaScript Style Guide (ESLint configured)
- jQuery + Bootstrap (legacy codebase), ES6+ where browser support allows

### Perl (`scripts/`)

- Edit `.in` template files, NOT generated scripts — cmake substitutes variables like `@ZM_LOGDIR@` to produce the final scripts
- Use `ZoneMinder::` modules, `ZoneMinder::Logger` for logging
- Rerun cmake after editing `.in` files to regenerate the actual scripts

---

## Commit Standards

**Conventional format** with issue references:

```
feat|fix|test|docs|chore|refactor|perf|style: description refs #N
```

- `refs #N` references issue; `fixes #N` closes it
- Imperative mood ("add feature" not "added feature")
- Be detailed and descriptive, no vague summaries
- One logical change per commit
- **No superlative language** ("comprehensive", "critical", "major", "massive") — AI agents tend to use these; they make commit logs unreadable
- Split unrelated changes into separate commits — makes bisecting and reverting possible

---

## Pre-Commit Checklist (Single Source of Truth)

Before committing or claiming complete:

- [ ] Tests written/updated BEFORE or DURING implementation
- [ ] Build succeeds: `cmake --build .` (C++ changes)
- [ ] C++ tests pass: `ctest` or `./tests/tests`
- [ ] JavaScript linting passes: `npx eslint --ext .js.php,.js .`
- [ ] Manual testing done (PHP/web changes)
- [ ] No compiler warnings or linter errors
- [ ] Code follows existing patterns
- [ ] Commit messages follow conventional format with issue reference
- [ ] Feature is COMPLETE (not half-implemented)
- [ ] State which tests were run and their results

**NEVER commit if**: tests failing, tests missing for new code, build fails, warnings exist, feature incomplete.

---

## Debugging Quick Reference

```bash
# Debug build
cmake -DCMAKE_BUILD_TYPE=Debug .. && cmake --build .

# AddressSanitizer
cmake -DCMAKE_BUILD_TYPE=Debug -DASAN=ON .. && cmake --build .

# Debug logging
export ZM_DBG_LEVEL=9 ZM_DBG_LOG=/tmp/debug.log
./src/zmc -m 1

# Log locations: /var/log/zm/ (zmc_m1.log, zma_m2.log, etc.)
# Runtime log level: kill -USR1 <pid> (increase) / kill -USR2 <pid> (decrease)
```

---

## Contributing (For Humans)

- **Bug reports & features**: [GitHub Issues](https://github.com/ZoneMinder/zoneminder/issues) (read [posting rules](https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules) first)
- **Support**: [Forums](https://forums.zoneminder.com), [Slack](https://zoneminder-chat.slack.com), [Discord](https://discord.gg/tHYyP9k66q)
- **PRs**: Fork -> feature branch -> implement + test -> PR. Maintainers merge after review.
- **Docs**: https://zoneminder.readthedocs.org (source in `docs/`)

---

## grepai - Semantic Code Search

**Use grepai as PRIMARY tool for code exploration and search.**

### When to Use grepai (REQUIRED)

Use `grepai search` INSTEAD OF Grep/Glob for:
- Understanding what code does or where functionality lives
- Finding implementations by intent ("authentication logic", "error handling")
- Exploring unfamiliar parts of the codebase

Only use Grep/Glob for exact text matching (variable names, imports, specific strings) or file path patterns.

If grepai fails (not running, index unavailable), fall back to Grep/Glob.

### Usage

```bash
grepai search "user authentication flow" --json --compact
grepai search "JWT token validation" --json --compact
```

### Call Graph Tracing

```bash
grepai trace callers "HandleRequest" --json
grepai trace callees "ProcessOrder" --json
grepai trace graph "ValidateToken" --depth 3 --json
```

### Workflow

1. `grepai search` to find relevant code
2. `grepai trace` to understand function relationships
3. `Read` tool to examine files from results
4. Grep only for exact string searches if needed
