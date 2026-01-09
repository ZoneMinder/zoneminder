# AI Agent Development Guide for ZoneMinder

> **Note**: This file guides AI coding agents (Claude Code, GitHub Copilot, Cursor, etc.) working on ZoneMinder.
> CLAUDE.md is a symlink to this file - different agents look for different filenames.

## Quick Reference (MANDATORY RULES)

1. **Testing First**: Write tests BEFORE/DURING implementation - NEVER skip (see Testing Requirements)
2. **Build System**: CMake with C++17, out-of-source builds required
3. **Feature Workflow**: GitHub Issue → Feature Branch → Implement FULLY → Tests Pass → Get Approval → Merge to master
4. **Commits**: Conventional format (`feat:`/`fix:`/`test:`), reference issues (`refs #n` or `fixes #n`)
5. **Pre-Commit Checklist**: Tests pass, build succeeds, linting clean, no warnings
6. **Language-Specific Tests**: C++ (Catch2/ctest), JavaScript (ESLint in CI), PHP (manual + future automation)

---

## What is ZoneMinder?

ZoneMinder is an integrated Linux-based CCTV surveillance system that provides capture, analysis, recording, and monitoring of video cameras. It consists of:

- **C++ capture/analysis/streaming daemons** - Core video processing
- **PHP web interface with REST API** - User interface and API endpoints
- **Perl system management scripts** - Daemon control and maintenance
- **MySQL database** - Configuration and event storage

**Project Scale:**
- 86+ C++ source files
- 31+ database tables
- Multi-language codebase (C++17, PHP, JavaScript, Perl)
- Multi-platform support (Linux, FreeBSD)
- 15+ years of development history

---

## Development Workflow (MANDATORY)

### When the user requests a new feature, follow this workflow:

#### 1. Create GitHub Issue

- Create a GitHub issue for the feature request using `gh issue create`
- Label it as `enhancement`
- Include clear description of what the feature should do
- Example:
  ```bash
  gh issue create --title "Add event favorites feature" \
    --body "Allow users to mark events as favorites and filter by favorites" \
    --label "enhancement"
  ```

#### 2. Create Feature Branch

- Create a new branch from master with descriptive name
- Branch naming: `<issue-number>-<short-description>` (e.g., `456-fix-rtsp-crash`)
- Example:
  ```bash
  git checkout master
  git pull
  git checkout -b 123-event-favorites
  ```

#### 3. Implement Feature Completely

**CRITICAL**: Implement the ENTIRE feature - do not stop in the middle.

**Implementation Steps:**

a. **Write failing test first** (test-driven development)
   - C++: Add Catch2 test in `tests/`
   - PHP/JS: Manual testing checklist (automated tests future enhancement)

b. **Implement code to pass test**
   - Follow existing code patterns
   - Keep changes minimal and focused
   - Don't add unrelated "improvements"

c. **Build verification** (C++ changes):
   ```bash
   cd build
   cmake --build .  # Must succeed with no errors
   ```

d. **Test verification**:
   ```bash
   # C++ unit tests (if BUILD_TEST_SUITE=ON)
   ctest
   # Or run test binary directly
   ./tests/tests

   # JavaScript linting
   npx eslint --ext .js.php,.js .

   # Manual testing for PHP/web changes
   # - Test in browser
   # - Check browser console for errors
   # - Test API endpoints if applicable
   ```

e. **Commit in logical chunks** with conventional commit messages:
   ```bash
   git add <files>
   git commit -m "feat: add favorites toggle to event view refs #123"
   # More commits as needed for different logical components
   ```

**Technology-Specific Notes:**
- **C++ changes** (`src/`): Rebuild required, run ctest if tests enabled
- **PHP/JS changes** (`web/`): No rebuild needed, test manually in browser
- **Database schema** (`db/`): Create migration file `zm_update-X.X.X.sql`
- **Perl scripts** (`scripts/`): Edit `.in` template files, rerun cmake to generate scripts
- **API changes** (`web/api/`): Clear CakePHP cache if needed, test endpoints

#### 4. Request User Feedback

- Once implementation is complete and all tests pass, ask user for feedback
- DO NOT merge or push without user approval
- Example: "Feature implementation complete. All tests passing. Ready for your review."

#### 5. Merge and Cleanup (After User Approval Only)

- Merge feature branch to master
- Delete the feature branch (local and remote)
- Reference the issue in final commit/merge: `fixes #<issue-number>`
- Push to master
- Verify issue is automatically closed

**Example Complete Workflow:**
```bash
# 1. Create issue
gh issue create --title "Add dark mode toggle" \
  --body "Add UI toggle for dark mode theme" \
  --label "enhancement"
# Note the issue number (e.g., #42)

# 2. Create branch
git checkout master
git pull
git checkout -b 42-dark-mode

# 3. Implement + test + commit
# ... write tests first ...
# ... implement feature ...
cd build && cmake --build . && ctest
git add web/skins/classic/css/dark-mode.css web/includes/functions.php
git commit -m "feat: add dark mode CSS and theme switcher refs #42"
git add tests/zm_theme.cpp
git commit -m "test: add dark mode toggle tests refs #42"

# 4. Ask user for approval
# "Feature implementation complete. Tests passing. Ready for review."
# (Wait for user confirmation)

# 5. After approval, merge and cleanup
git checkout master
git merge 42-dark-mode
git push origin master
git branch -d 42-dark-mode
git push origin --delete 42-dark-mode
# Verify issue #42 is closed
```

**Important Notes:**
- Never merge to master without user approval
- Never leave a feature half-implemented
- Always include tests before requesting approval
- Feature branches keep master stable and allow for review

---

## Testing Requirements (MANDATORY - No Exceptions)

### Test-First Development Workflow

**Rule**: Write tests BEFORE or DURING implementation, NEVER skip tests.

**Why**: Tests written "later" are usually never written. Tests verify code actually works.

**Workflow**:
1. Understand the bug/feature requirement
2. Write a failing test that reproduces the issue or validates the feature
3. Implement the fix/feature
4. Run tests - verify they now PASS
5. Run full test suite to check for regressions
6. Only then commit

### C++ Unit Tests (Catch2 Framework)

**Location**: `tests/` directory

**Enable and Build Tests**:
```bash
# From clean build directory
mkdir build && cd build
cmake -DBUILD_TEST_SUITE=ON ..
cmake --build .

# Run all tests
ctest

# Run test binary directly with more output
./tests/tests

# Run specific test by name
./tests/tests "[Box]"

# List all available tests
./tests/tests --list-tests

# Run tests excluding CI-skipped tests
./tests/tests "~[notCI]"
```

**Existing Test Modules** (7 files in `tests/`):
- `zm_box.cpp` - Bounding box geometry (Box class)
- `zm_comms.cpp` - Network communication (pipes, sockets, TLS)
- `zm_crypt.cpp` - Cryptography and JWT token validation
- `zm_font.cpp` - Font rendering
- `zm_poly.cpp` - Polygon geometry and intersection
- `zm_utils.cpp` - Utility functions
- `zm_vector2.cpp` - 2D vector mathematics

**When to Add C++ Tests**:
- ✅ New functionality → Write new test file or add to existing
- ✅ Bug fixes → Write test that reproduces bug FIRST
- ✅ Refactoring → Ensure existing tests still pass
- ✅ Changes to existing functionality → Update tests BEFORE changing code
- ✅ New components → Create new test file as you build
- ✅ Utility functions → Test all logic paths and edge cases

**Test Framework Details**:
- Framework: Catch2 (modern C++ test framework)
- Custom header: `tests/zm_catch2.h` with helper macros
- Main entry: `tests/main.cpp` with `CATCH_CONFIG_MAIN`
- Test data: `tests/data/fonts/` directory for fixtures
- CMake integration: `catch_discover_tests()` for automatic test discovery

**What to Test**:
- Happy path (normal usage)
- Edge cases (empty arrays, null pointers, boundary conditions)
- Error cases (network failures, invalid input, missing data)
- State changes (verify before/after behavior)
- ZoneMinder-specific examples:
  - Camera connection edge cases
  - Event recording state transitions
  - Shared memory buffer handling
  - Image format conversions
  - Zone polygon intersections

### JavaScript/PHP Linting

**ESLint Configuration**:
- Config files: `.eslintrc.js` (legacy) and `eslint.config.js` (flat config)
- Style guide: Google JavaScript Style Guide
- Plugins: eslint-plugin-html, eslint-plugin-php-markup
- Handles: Browser JavaScript and inline JS within PHP files

**Run Linting**:
```bash
# Lint all JavaScript (if Node.js/eslint installed)
npx eslint --ext .js.php,.js .

# Lint specific directory
npx eslint web/js/

# Fix auto-fixable issues
npx eslint --fix web/
```

**CI Integration**:
- ESLint runs automatically in GitHub Actions (`.github/workflows/ci-eslint.yml`)
- Triggers on all pushes and PRs to master
- Must pass for PR approval

### PHP/API Testing

**Current State**:
- Framework: CakePHP 2.x test framework (in API)
- Location: `web/api/app/Plugin/Crud/Test/`
- Infrastructure exists but NOT run in CI pipeline
- Manual testing required for API endpoints

**Manual API Testing Checklist**:
- Test endpoints in browser or with curl/Postman
- Verify JSON response structure
- Test authentication (session and JWT)
- Check error handling (invalid input, missing data)
- Verify database changes persist correctly

**Future Enhancement**: Integrate CakePHP tests into CI pipeline

### Database Migration Testing

**Migration Framework**:
- Location: `db/` directory
- Initial schema: `db/zm_create.sql.in`
- Incremental migrations: `db/zm_update-X.X.X.sql` (60+ versions)
- Update script: `scripts/zmupdate.pl.in`

**Testing Database Changes**:
```bash
# Check for available updates
sudo zmupdate.pl --check

# Apply pending migrations
sudo zmupdate.pl

# Force specific version upgrade (testing)
sudo zmupdate.pl -v 1.37.32

# Freshen config in database
sudo zmupdate.pl --freshen
```

**When Creating Migrations**:
- Create new `db/zm_update-X.X.X.sql` file
- Test upgrade path from previous version
- Test fresh install vs. migration (both should result in same schema)
- Document any manual steps required
- Test on supported databases (MySQL, MariaDB)

### CI/CD Testing (GitHub Actions)

**Current CI Pipeline** (`.github/workflows/`):

1. **ESLint** (`ci-eslint.yml`) - JavaScript/PHP linting ✅
2. **Multi-platform builds**:
   - Debian Bookworm (`ci-bookworm.yml`) - 4 config matrix
   - Debian Bullseye (`ci-bullseye.yml`) - 4 config matrix
   - Ubuntu Focal (`ci-focal.yml`) - 2 config matrix
   - CentOS/RHEL 8 (`ci-centos-8.yml`) - Rocky Linux
3. **CodeQL Security Analysis** (`codeql-analysis.yml`) - C++ and JavaScript
4. **Package Building** (`build-native-packages.yml`) - Multi-distro .deb packages

**CI Build Matrix**:
- Crypto backends: GnuTLS vs. OpenSSL
- JWT backends: libjwt vs. jwt_cpp
- Platforms: Debian, Ubuntu, CentOS/RHEL
- Architectures: x86_64 and ARM64 (aarch64)

**Important CI Note**:
- ⚠️ **Unit tests are DISABLED in CI** (`BUILD_TEST_SUITE=0`)
- CI focuses on build verification and linting
- Tests are commented out in workflows (e.g., `# ./tests/tests "~[notCI]"`)
- When you run tests locally, you're going beyond current CI requirements ✅

**CI Configuration Options**:
```bash
# Typical CI build flags
cmake \
  -DCMAKE_BUILD_TYPE=Release \
  -DBUILD_TEST_SUITE=0 \
  -DBUILD_MAN=0 \
  -DENABLE_WERROR=1 \
  ..
```

### Integration Testing

**Current State**: No formal integration test suite

**Manual Integration Testing**:
- Test full workflow: Monitor creation → Event capture → Playback
- Test daemon lifecycle: Start → Stop → Restart
- Test web interface flows: Login → Monitor config → Event filtering
- Test API workflows: Authentication → CRUD operations
- Test multi-server scenarios if applicable

### Pre-Test Checklist

**Before stating "Done" or committing**:
- [ ] ALL applicable tests have been written (not just build)
- [ ] ALL tests have been run (ctest for C++, ESLint for JS)
- [ ] ALL tests PASS (not just "no errors")
- [ ] State which tests were run and their results

**Never Commit or Claim Complete If**:
- ❌ Tests are failing
- ❌ Tests don't exist for new/changed functionality
- ❌ You haven't actually run the tests
- ❌ Build fails
- ❌ You only ran build but not unit/linting tests
- ❌ ESLint reports errors

---

## Build System

ZoneMinder uses **CMake** as its build system with **C++17** standard.

### Basic Build Commands

```bash
# Create build directory (out-of-source build required)
mkdir build
cd build

# Configure with CMake (basic)
cmake ..

# Or configure with custom options
cmake \
  -DCMAKE_BUILD_TYPE=Debug \
  -DBUILD_TEST_SUITE=ON \
  -DZM_WEBDIR=/usr/share/zoneminder/www \
  -DZM_CONTENTDIR=/var/lib/zoneminder \
  ..

# Build the project
cmake --build .

# Build with verbose output (see actual commands)
cmake --build . --verbose

# Build specific target
cmake --build . --target zmc

# Install (requires appropriate permissions)
sudo cmake --build . --target install
```

### Important CMake Configuration Options

**Build Types**:
- `CMAKE_BUILD_TYPE=Release` - Optimized build (default), `-O2` optimization
- `CMAKE_BUILD_TYPE=Debug` - Debug symbols, `-g` flag
- `CMAKE_BUILD_TYPE=Optimised` - Custom optimized build

**Testing & Development**:
- `BUILD_TEST_SUITE=ON` - Enable Catch2 unit tests (default: OFF)
- `BUILD_MAN=ON` - Build man pages (default: ON)
- `ENABLE_WERROR=ON` - Treat warnings as errors (used in CI)

**Debugging & Analysis**:
- `ASAN=ON` - Build with AddressSanitizer for memory debugging (default: OFF)
- `TSAN=ON` - Build with ThreadSanitizer for thread debugging (default: OFF, mutually exclusive with ASAN)

**Feature Toggles**:
- `ZM_ONVIF=ON` - Enable ONVIF camera support (default: ON)
- `ZM_NO_LIBVLC=ON` - Skip libVLC checks (default: OFF)
- `ZM_NO_CURL=ON` - Skip cURL checks (default: OFF)

**Platform & Paths**:
- `ZM_TARGET_DISTRO=<distro>` - Set platform: `FreeBSD`, `fc` (Fedora), `el` (RHEL/CentOS), `OS13` (OpenSUSE)
- `ZM_WEBDIR=<path>` - Web root directory
- `ZM_CONTENTDIR=<path>` - Content storage directory
- `ZM_LOGDIR=<path>` - Log file directory
- `ZM_RUNDIR=<path>` - Runtime PID files directory

**Crypto & JWT Backends**:
- `ZM_CRYPTO_BACKEND=<backend>` - `openssl` or `gnutls`
- `ZM_JWT_BACKEND=<backend>` - `libjwt` or `jwt_cpp`

### Build Examples

```bash
# Debug build with tests and AddressSanitizer
cmake -DCMAKE_BUILD_TYPE=Debug -DBUILD_TEST_SUITE=ON -DASAN=ON ..
cmake --build .

# Release build for FreeBSD
cmake -DCMAKE_BUILD_TYPE=Release -DZM_TARGET_DISTRO=FreeBSD ..
cmake --build .

# CI-style build (strict warnings)
cmake -DCMAKE_BUILD_TYPE=Release -DENABLE_WERROR=1 -DBUILD_TEST_SUITE=0 ..
cmake --build .
```

### C++ Code Standards

- **C++17** standard required
- Compiler flags: `-O2` for release, `-g` for debug
- Large file support: `-D_FILE_OFFSET_BITS=64`
- ARM NEON optimizations auto-detected on ARM platforms
- Warning level: High (use `-DENABLE_WERROR=1` to enforce)

---

## Architecture Overview

### Core Components

1. **C++ Daemons** (`src/`):
   - `zmc` - **Capture daemon** (one per monitor) - captures frames from cameras
   - `zma` - **Analysis daemon** - motion detection and event triggering
   - `zms` - **Streaming server** - delivers live/recorded video to web clients
   - `zmu` - **Utility program** - command-line monitor management

2. **Web Interface** (`web/`):
   - PHP-based user interface
   - Bootstrap + jQuery frontend
   - AJAX endpoints in `web/ajax/`
   - View templates in `web/views/`
   - Multiple skins in `web/skins/`

3. **REST API** (`web/api/`):
   - CakePHP 2.x-based modern API
   - Controllers in `web/api/app/Controller/`
   - Models in `web/api/app/Model/`
   - JSON responses for programmatic access
   - JWT authentication support

4. **Perl Scripts** (`scripts/`):
   - `zmdc.pl` - Daemon control and supervision
   - `zmaudit.pl` - Database maintenance and consistency checks
   - `zmupdate.pl` - Schema migrations and upgrades
   - `zmfilter.pl` - Event filtering and automated actions
   - `zmwatch.pl` - Process health monitoring
   - `zmtrigger.pl` - External event triggering
   - `zmonvif-probe.pl` - ONVIF camera discovery

5. **Database** (`db/`):
   - MySQL/MariaDB schema with 31+ tables
   - Incremental migration scripts: `zm_update-*.sql`
   - Core tables: Monitors, Events, Frames, Zones, Users, Storage, Servers

### Key Architectural Patterns

**Shared Memory Architecture**:
- Capture daemons write frames to shared memory (`/dev/shm` by default)
- Analysis and streaming daemons read from the same buffers
- Zero-copy efficiency for high-performance video processing

**Monitor-Centric Design**:
- The `Monitor` class (`src/zm_monitor.cpp/h`) is the central orchestrator
- Manages capture, analysis, recording, and streaming for each camera
- One monitor = one database row = one set of daemons

**Pluggable Camera Types**:
- Abstract `Camera` base class with implementations:
  - `LocalCamera` - V4L2 devices (webcams, capture cards)
  - `RemoteCameraRTSP` - RTSP network streams
  - `RemoteCameraHTTP` - HTTP image URLs (MJPEG, snapshots)
  - `FFmpegCamera` - FFmpeg-based (supports 100+ formats)
  - `LibVLCCamera` - VLC media playback
  - `LibVNCCamera` - VNC screen capture

**Event-Driven Recording**:
- Motion detection triggers `Event` objects
- Events manage recording of video segments to disk and database
- Configurable pre/post alarm recording buffers
- Event lifecycle: Create → Record frames → Close → Archive/Delete

**Multi-Server Clustering**:
- Database-coordinated distributed architecture
- Multiple ZoneMinder servers can share monitors and storage
- Server load balancing and failover support

### Data Flow

```
Camera → zmc (capture) → Shared Memory → zma (analysis) → Event Recording
                            ↓                                    ↓
                         zms (streaming)                   Database + Disk
                            ↓                                    ↓
                      Web Browsers ← Web Interface/API ← MySQL Storage
```

**Detailed Flow**:
1. **Capture**: `zmc` connects to camera, writes frames to shared memory buffer
2. **Analysis**: `zma` reads from shared memory, performs motion detection on zones
3. **Event Trigger**: Motion detected → Create Event → Start recording
4. **Storage**: Frames written to disk in event directories, metadata to MySQL
5. **Streaming**: `zms` reads from shared memory or disk to deliver video to browsers
6. **Web UI**: PHP interface queries MySQL, triggers streaming via `zms`, displays events
7. **API**: CakePHP REST API provides programmatic access to all functionality

---

## Directory Structure

```
ZoneMinder/
├── src/                      # C++ core binaries (86+ source files)
│   ├── zmc.cpp              # Capture daemon entry point
│   ├── zma.cpp              # Analysis daemon entry point
│   ├── zms.cpp              # Streaming server entry point
│   ├── zmu.cpp              # Utility program entry point
│   ├── zm_monitor.*         # Monitor orchestration (core class)
│   ├── zm_camera.*          # Camera base classes and implementations
│   ├── zm_event.*           # Event recording management
│   ├── zm_zone.*            # Motion detection zones
│   ├── zm_image.*           # Image processing and manipulation
│   ├── zm_ffmpeg*.*         # FFmpeg integration
│   ├── zm_rtsp*.*           # RTSP server support
│   ├── zm_box.*             # Bounding box geometry
│   ├── zm_poly.*            # Polygon geometry
│   ├── zm_crypt.*           # Cryptography utilities
│   └── zm_utils.*           # General utilities
│
├── web/                      # Web interface
│   ├── index.php            # Main entry point
│   ├── ajax/                # AJAX handlers for async requests
│   ├── includes/            # PHP libraries and functions
│   ├── views/               # UI templates (HTML/PHP)
│   ├── skins/               # Themes (classic, dark, etc.)
│   ├── js/                  # JavaScript files
│   ├── css/                 # Stylesheets
│   └── api/                 # CakePHP REST API
│       ├── app/
│       │   ├── Controller/  # API controllers
│       │   ├── Model/       # API models
│       │   └── Plugin/      # CakePHP plugins
│       └── webroot/         # API entry point
│
├── scripts/                  # System management (Perl)
│   ├── zmdc.pl.in           # Daemon supervisor
│   ├── zmaudit.pl.in        # Database maintenance
│   ├── zmfilter.pl.in       # Event filtering
│   ├── zmupdate.pl.in       # Schema migrations
│   ├── zmwatch.pl.in        # Process monitoring
│   ├── zmtrigger.pl.in      # External triggering
│   └── ZoneMinder/          # Perl modules
│       ├── ConfigData.pm    # Configuration management
│       └── Logger.pm        # Logging utilities
│
├── db/                       # Database schema
│   ├── zm_create.sql.in     # Initial schema template
│   └── zm_update-*.sql      # Incremental migrations (60+ files)
│
├── tests/                    # Unit tests (Catch2)
│   ├── main.cpp             # Test runner entry point
│   ├── zm_catch2.h          # Custom Catch2 helpers
│   ├── zm_box.cpp           # Box geometry tests
│   ├── zm_comms.cpp         # Communications tests
│   ├── zm_crypt.cpp         # Cryptography tests
│   ├── zm_font.cpp          # Font rendering tests
│   ├── zm_poly.cpp          # Polygon tests
│   ├── zm_utils.cpp         # Utils tests
│   ├── zm_vector2.cpp       # Vector math tests
│   └── data/                # Test fixtures
│
├── docs/                     # Sphinx documentation
│   ├── userguide/           # User documentation
│   ├── installationguide/   # Installation docs
│   └── contributing.rst     # Contributor guide
│
├── misc/                     # Server configs
│   ├── apache.conf          # Apache configuration
│   ├── nginx.conf           # Nginx configuration
│   └── zoneminder.service   # systemd unit file
│
├── onvif/                    # ONVIF camera support
│   └── proxy/               # ONVIF SOAP proxy
│
├── utils/                    # Development utilities
│   └── packpack/            # Packaging scripts
│
├── dep/                      # Vendored dependencies
│   ├── catch/               # Catch2 test framework
│   └── jwt-cpp/             # JWT library
│
├── .github/                  # GitHub configuration
│   └── workflows/           # CI/CD pipelines
│
├── CMakeLists.txt           # Root CMake configuration
├── CONTRIBUTING.md          # Contribution guidelines
├── AGENTS.md                # This file (AI agent guide)
└── CLAUDE.md                # Symlink to AGENTS.md
```

---

## Code Quality & Standards

### Language-Specific Guidelines

#### C++ (`src/`)

**Standards**:
- Language: C++17
- Style: Follow existing code patterns in the file/module
- Memory: Use RAII, smart pointers (`std::unique_ptr`, `std::shared_ptr`) where appropriate
- Error handling: Exceptions for exceptional cases, return codes for expected errors
- Logging: Use `Logger` class with levels: Debug, Info, Warning, Error, Fatal

**Example Logging**:
```cpp
Debug(1, "Monitor %s: Capturing frame %d", name.c_str(), frame_count);
Info("Started capture daemon for monitor %d", id);
Warning("Failed to connect, retrying in %d seconds", retry_delay);
Error("Unable to open camera device: %s", strerror(errno));
Fatal("Critical error in shared memory: %s", error_msg);
```

**Testing**: Catch2 framework
```cpp
#include "zm_catch2.h"

TEST_CASE("Box intersection", "[Box]") {
  Box box1(10, 10, 20, 20);
  Box box2(15, 15, 25, 25);

  REQUIRE(box1.Intersects(box2));
  Box intersection = box1.Intersection(box2);
  REQUIRE(intersection.Width() == 5);
}
```

#### PHP (`web/`, `web/api/`)

**Standards**:
- Style: PSR-12 where practical, follow existing conventions
- Security:
  - **Validate ALL user input**
  - Use prepared statements for database queries
  - Sanitize output (htmlspecialchars, json_encode)
  - CSRF protection on forms
- API: CakePHP 2.x framework in `web/api/`
- Sessions: Manage via ZoneMinder session handler

**Example Secure Query**:
```php
// Good - prepared statement
$stmt = $dbConn->prepare('SELECT * FROM Monitors WHERE Id = ?');
$stmt->execute(array($monitorId));

// Bad - SQL injection vulnerable
$sql = "SELECT * FROM Monitors WHERE Id = $monitorId";
```

**Testing**: Manual testing + future CakePHP test automation

#### JavaScript (`web/js/`, inline in PHP)

**Standards**:
- Style: Google JavaScript Style Guide (configured in ESLint)
- Linting: ESLint with html and php-markup plugins
- Framework: jQuery + Bootstrap (legacy codebase)
- Modern JS: Can use ES6+ features where browser support allows

**ESLint Configuration** (`.eslintrc.js`):
- Extends: google
- Relaxed rules: camelCase, max-len, jsdoc requirements
- Handles: Browser globals, jQuery, inline JS in PHP files

**Linting**:
```bash
npx eslint --ext .js.php,.js .
npx eslint --fix web/js/  # Auto-fix issues
```

**Testing**: ESLint in CI (`.github/workflows/ci-eslint.yml`)

#### Perl (`scripts/`)

**Standards**:
- Edit: `.in` template files, NOT generated scripts
- Modules: Use `ZoneMinder::` modules where appropriate
- Error handling: Follow existing patterns with proper error messages
- Logging: Use `ZoneMinder::Logger`

**Example**:
```perl
use ZoneMinder;
use ZoneMinder::Logger;

Info("Starting daemon control");
if (!daemonControl('start', $daemon)) {
  Error("Failed to start daemon: $!");
}
```

**Rebuild**: After editing `.in` files, rerun cmake to regenerate scripts

---

## Debugging & Performance Tools

### Debugging C++ Code

**Basic Debugging with GDB**:
```bash
# Build with debug symbols
cd build
cmake -DCMAKE_BUILD_TYPE=Debug ..
cmake --build .

# Run under debugger
gdb ./src/zmc
(gdb) break zm_monitor.cpp:123
(gdb) run -m 1
(gdb) backtrace
(gdb) print variable_name
```

**AddressSanitizer (Memory Debugging)**:
```bash
# Build with ASan
cmake -DCMAKE_BUILD_TYPE=Debug -DASAN=ON ..
cmake --build .

# Run normally - ASan will report memory issues
./src/zmc -m 1
# ASan reports: leaks, use-after-free, buffer overflows, etc.
```

**ThreadSanitizer (Thread Debugging)**:
```bash
# Build with TSan (mutually exclusive with ASan)
cmake -DCMAKE_BUILD_TYPE=Debug -DTSAN=ON ..
cmake --build .

# Run normally - TSan will report threading issues
./src/zma -m 1
# TSan reports: data races, deadlocks, thread leaks, etc.
```

### Runtime Logging

**Log Configuration**:
- Web UI: **Options → System → Logging**
  - `LOG_LEVEL_FILE` - File logging level (None to Debug9)
  - `LOG_LEVEL_DATABASE` - Database logging level
  - `LOG_LEVEL_SYSLOG` - Syslog logging level
  - `LOG_DEBUG_TARGET` - Target specific component for debugging

**Log Locations**:
- Default: `/var/log/zm/` (configurable via `ZM_LOGDIR`)
- Files: `zmc_m1.log`, `zma_m2.log`, `zms.log`, etc.
- Syslog: Check `/var/log/syslog` or `journalctl -u zoneminder`

**Debug Specific Components**:
```bash
# Debug specific monitor capture daemon
# Set in web UI: LOG_DEBUG_TARGET = _zmc_m5
# Or via environment:
export ZM_DBG_LEVEL=9
export ZM_DBG_LOG=/tmp/zmc_debug.log
./src/zmc -m 5

# Debug with stdout output
export ZM_DBG_PRINT=1
./src/zmc -m 5
```

**Runtime Log Level Control**:
```bash
# Send USR1 signal to increase log level
kill -USR1 <pid>

# Send USR2 signal to decrease log level
kill -USR2 <pid>
```

### Performance Profiling

**Built-in Performance Stats**:
- Monitor stats available via web UI
- Frame capture rates, analysis times
- Database query timing in debug logs

**External Profiling Tools**:
```bash
# Valgrind for memory profiling
valgrind --leak-check=full ./src/zmc -m 1

# Perf for CPU profiling (Linux)
perf record -g ./src/zmc -m 1
perf report

# Callgrind for call graph analysis
valgrind --tool=callgrind ./src/zmc -m 1
kcachegrind callgrind.out.*
```

### Browser Debugging

**JavaScript Console**:
- Browser Dev Tools (F12)
- Check console for JavaScript errors
- Network tab for AJAX request debugging
- Monitor WebSocket connections for streaming

**PHP Debugging**:
- Enable PHP error display: `php.ini` → `display_errors = On`
- Check PHP error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
- Use `error_log()` function for debug output
- Xdebug for step-through debugging (optional)

---

## Commit Standards

### Commit Message Format

**Use conventional commit format**:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `test:` - Test additions or modifications
- `chore:` - Maintenance tasks (dependencies, config)
- `refactor:` - Code restructuring without behavior change
- `perf:` - Performance improvements
- `style:` - Code style changes (formatting, no logic change)

**Issue References**:
- `refs #123` - Reference issue without closing
- `fixes #123` - Close issue when commit is merged

**Commit Message Guidelines**:
- Be detailed and descriptive (no vague summaries)
- Split unrelated changes into separate commits (one logical change per commit)
- Avoid superlative language (no "comprehensive", "critical", "major", "massive")
- Keep messages factual and objective
- Use imperative mood ("add feature" not "added feature")

**Examples**:

✅ **Good**:
```
feat: add RTSP reconnection logic to RemoteCameraRTSP refs #42

Implements automatic reconnection when RTSP stream drops. Adds
exponential backoff with configurable retry delay. Includes new
Monitor setting for max retry attempts.
```

✅ **Good**:
```
fix: resolve shared memory leak in Monitor class fixes #123

Shared memory segments were not being properly released when monitor
was disabled. Added cleanup in Monitor destructor.
```

✅ **Good**:
```
test: add Catch2 tests for Polygon intersection

Added test cases for polygon overlap detection including edge cases:
- Adjacent polygons (touching edges)
- Nested polygons
- Non-intersecting polygons
```

❌ **Bad**:
```
fix: comprehensive camera improvements

Fixed various issues.
```

❌ **Bad**:
```
feat: massive critical overhaul of event system

Improved everything.
```

### Committing Multiple Changes

**When you have multiple logical changes, create separate commits**:
```bash
# Change 1: Add feature
git add src/zm_monitor.cpp src/zm_monitor.h
git commit -m "feat: add auto-reconnect for camera failures refs #42"

# Change 2: Add tests
git add tests/zm_monitor.cpp
git commit -m "test: add monitor reconnection tests refs #42"

# Change 3: Update docs
git add docs/userguide/monitors.rst
git commit -m "docs: document auto-reconnect feature refs #42"
```

---

## Pre-Commit Checklist

### ALL Changes (MANDATORY - No Exceptions)

- [ ] **Tests written/updated** BEFORE or DURING implementation
- [ ] **Build succeeds**: `cmake --build .` (for C++ changes)
- [ ] **Tests pass**:
  - [ ] C++ unit tests: `ctest` or `./tests/tests`
  - [ ] JavaScript linting: `npx eslint --ext .js.php,.js .`
  - [ ] Manual testing (for PHP/web changes)
- [ ] **No warnings** from compiler or linter
- [ ] **Code follows existing patterns** in the file/module
- [ ] **Commit messages** follow conventional format
- [ ] **Issue referenced** in commit message (`refs #n` or `fixes #n`)

### Before Stating "Done" or Requesting Approval

- [ ] ALL applicable tests have been run (not just build)
- [ ] ALL tests PASS (not just "no errors")
- [ ] State which tests were run and their results
- [ ] Feature is COMPLETE (not half-implemented)
- [ ] Documentation updated if adding new features

### Never Commit or Claim Complete If

- ❌ Tests are failing
- ❌ Tests don't exist for new/changed functionality
- ❌ You haven't actually run the tests
- ❌ Build fails
- ❌ Compiler warnings exist
- ❌ ESLint reports errors
- ❌ You only ran build but not unit/linting tests
- ❌ Feature is incomplete or partially implemented

---

## Common Development Tasks

### Adding a New C++ Feature

```bash
# 1. Create issue and branch
gh issue create --title "Add motion mask feature" --label enhancement
git checkout -b 123-motion-mask

# 2. Write failing test first
# Edit tests/zm_zone.cpp - add test for new feature

# 3. Implement feature
# Edit src/zm_zone.cpp, src/zm_zone.h

# 4. Build and test
cd build
cmake --build .
./tests/tests "[Zone]"  # Run zone tests specifically

# 5. Commit
git add tests/zm_zone.cpp src/zm_zone.cpp src/zm_zone.h
git commit -m "feat: add motion mask support to Zone class refs #123"

# 6. Request approval
# Ask user for review

# 7. After approval, merge
git checkout master
git merge 123-motion-mask
git push origin master
git branch -d 123-motion-mask
```

### Fixing a Bug

```bash
# 1. Create issue and branch
gh issue create --title "RTSP stream freezes after 1 hour" --label bug
git checkout -b 456-rtsp-freeze-fix

# 2. Write test that reproduces bug
# Edit tests/zm_camera.cpp

# 3. Verify test fails (reproduces bug)
cd build && ./tests/tests "[Camera]"

# 4. Fix the bug
# Edit src/zm_remote_camera_rtsp.cpp

# 5. Verify test now passes
./tests/tests "[Camera]"

# 6. Commit
git add tests/zm_camera.cpp src/zm_remote_camera_rtsp.cpp
git commit -m "fix: resolve RTSP stream timeout after prolonged use fixes #456"

# 7. Merge after approval
git checkout master && git merge 456-rtsp-freeze-fix
git push origin master
```

### Adding a Web UI Feature

```bash
# 1. Create issue and branch
gh issue create --title "Add event export button" --label enhancement
git checkout -b 789-event-export

# 2. Implement UI changes
# Edit web/views/event.php, web/ajax/export.php, web/js/event.js

# 3. Test manually in browser
# - Navigate to event view
# - Test export button
# - Check browser console for errors
# - Verify exported file downloads correctly

# 4. Run JavaScript linting
npx eslint web/js/event.js

# 5. Commit
git add web/views/event.php web/ajax/export.php web/js/event.js
git commit -m "feat: add event export to MP4 button refs #789"

# 6. Merge after approval
git checkout master && git merge 789-event-export
git push origin master
```

### Adding a Database Migration

```bash
# 1. Determine next version number
ls db/zm_update-*.sql | tail -1
# Shows: db/zm_update-1.37.32.sql
# Next version: 1.37.33

# 2. Create migration file
cat > db/zm_update-1.37.33.sql << 'EOF'
--
-- Add favorites column to Events table
--

ALTER TABLE Events ADD COLUMN Favorite BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE Events ADD INDEX Favorite_idx (Favorite);
EOF

# 3. Update version in ConfigData
# Edit db/zm_create.sql.in - update version to 1.37.33

# 4. Test migration
sudo zmupdate.pl --version=1.37.33
# Verify column exists
mysql -u zmuser -p zm -e "DESCRIBE Events;"

# 5. Test fresh install has same schema
# (Build from source, run cmake install, check schema matches)

# 6. Commit
git add db/zm_update-1.37.33.sql db/zm_create.sql.in
git commit -m "feat: add favorites support to Events table refs #101"
```

### Debugging a Daemon

```bash
# 1. Stop running daemon
sudo systemctl stop zoneminder
# Or kill specific daemon
sudo pkill -f "zmc -m 1"

# 2. Build debug version
cd build
cmake -DCMAKE_BUILD_TYPE=Debug ..
cmake --build .

# 3. Run under debugger
gdb ./src/zmc
(gdb) set args -m 1
(gdb) break zm_monitor.cpp:PreCapture
(gdb) run

# Or with AddressSanitizer
cmake -DCMAKE_BUILD_TYPE=Debug -DASAN=ON ..
cmake --build .
./src/zmc -m 1  # ASan will report memory issues

# 4. Enable debug logging
export ZM_DBG_LEVEL=9
export ZM_DBG_LOG=/tmp/zmc_debug.log
./src/zmc -m 1
tail -f /tmp/zmc_debug.log
```

### Running Specific Tests

```bash
# Run all tests
cd build
ctest

# Run test binary with verbose output
./tests/tests

# Run specific test by name
./tests/tests "[Box]"

# Run all tests in a category
./tests/tests "[Box]" "[Poly]"

# List all available tests
./tests/tests --list-tests

# Run tests excluding CI-skipped tests
./tests/tests "~[notCI]"

# Run with success messages (normally only shows failures)
./tests/tests --success
```

---

## Dependencies

### Required Dependencies

**Build Tools**:
- CMake 3.5+
- C++17 compiler (GCC 6+, Clang 3.4+)
- Make or Ninja

**Core Libraries**:
- MySQL/MariaDB client library (`libmysqlclient-dev`)
- FFmpeg 55.34.100+ libraries:
  - libavcodec - Video/audio codec support
  - libavformat - Container format support
  - libavutil - Utility functions
  - swscale - Image scaling and format conversion
  - swresample - Audio resampling
- libjpeg (`libjpeg-dev`) - JPEG image support
- pthread - POSIX threads
- zlib - Compression library

**Runtime (Perl)**:
- Perl 5.6+
- Perl modules:
  - DBI - Database interface
  - DBD::mysql - MySQL driver
  - Sys::Syslog - System logging
  - Date::Manip - Date parsing
  - LWP::UserAgent - HTTP client
  - MIME::Lite - Email generation
  - Class::Std::Fast - Object-oriented programming

**Runtime (PHP)**:
- PHP 7.2+ with extensions:
  - mysql or mysqli - Database access
  - gd - Image manipulation
  - json - JSON encoding/decoding
  - session - Session management
  - sockets - Network communication

**Web Server**:
- Apache 2.4+ with mod_php or php-fpm
- OR Nginx with php-fpm

### Optional Dependencies

**Video & Streaming**:
- libVLC (`libvlc-dev`) - VLC media playback
- libvncclient (`libvnclient-dev`) - VNC server monitoring
- libcurl (`libcurl4-dev`) - HTTP camera support

**Security & Crypto**:
- OpenSSL (`libssl-dev`) - Cryptography backend (alternative to GnuTLS)
- GnuTLS (`libgnutls28-dev`) - Cryptography backend (alternative to OpenSSL)
- jwt-cpp OR libjwt (`libjwt-dev`) - JWT token support

**Advanced Features**:
- PCRE2 (`libpcre2-dev`) - Regular expression support
- GSOAP (`gsoap`) - ONVIF camera support
- Mosquitto (`libmosquitto-dev`) - MQTT event publishing
- nlohmann_json (`nlohmann-json3-dev`) - JSON parsing for AI results

**Testing & Development**:
- Catch2 (`catch2`) - Unit testing framework
- ESLint (npm) - JavaScript linting
- Valgrind - Memory profiling
- GDB - Debugging

**Documentation**:
- Sphinx (`python3-sphinx`) - Documentation generator
- Doxygen - API documentation (optional)

### Dependency Installation Examples

**Debian/Ubuntu**:
```bash
# Essential build dependencies
sudo apt install build-essential cmake git

# Core dependencies
sudo apt install libmysqlclient-dev \
  libavcodec-dev libavformat-dev libavutil-dev \
  libswscale-dev libswresample-dev \
  libjpeg-dev zlib1g-dev

# Perl dependencies
sudo apt install libdbi-perl libdbd-mysql-perl \
  libsys-syslog-perl libdate-manip-perl \
  liblwp-useragent-determined-perl

# PHP dependencies
sudo apt install php php-mysql php-gd

# Optional dependencies
sudo apt install libvlc-dev libvncserver-dev \
  libcurl4-openssl-dev libssl-dev \
  libjwt-dev libpcre2-dev

# Testing dependencies
sudo apt install catch2
npm install -g eslint
```

**CentOS/RHEL**:
```bash
# Enable EPEL repository
sudo yum install epel-release

# Build tools
sudo yum groupinstall "Development Tools"
sudo yum install cmake git

# Core dependencies
sudo yum install mariadb-devel \
  ffmpeg-devel libjpeg-turbo-devel

# Perl dependencies
sudo yum install perl-DBI perl-DBD-MySQL \
  perl-Sys-Syslog perl-Date-Manip

# PHP dependencies
sudo yum install php php-mysqlnd php-gd
```

---

## Platform-Specific Notes

### FreeBSD

**CMake Configuration**:
```bash
cmake -DZM_TARGET_DISTRO=FreeBSD ..
```

**Platform Defaults**:
- Web user/group: `www`
- Config dir: `/usr/local/etc/zm`
- Web dir: `/usr/local/share/zoneminder/www`
- Content dir: `/usr/local/var/lib/zoneminder`
- Run dir: `/var/run/zm`

**Package Manager**:
- Use `pkg` to install dependencies
- ZoneMinder available in ports: `/usr/ports/multimedia/zoneminder`

### RHEL/CentOS/Fedora

**CMake Configuration**:
```bash
# CentOS/RHEL
cmake -DZM_TARGET_DISTRO=el ..

# Fedora
cmake -DZM_TARGET_DISTRO=fc ..
```

**Platform Defaults**:
- Uses `/run/zoneminder` for runtime files
- Config: `/etc/zm`
- Content: `/var/lib/zoneminder`
- Web: `/usr/share/zoneminder/www`

**SELinux Considerations**:
- May need to adjust SELinux policies
- Check audit logs: `ausearch -m avc -ts recent`
- Generate policy: `audit2allow`

### Debian/Ubuntu

**Platform Defaults** (default when `ZM_TARGET_DISTRO` not set):
- Web user: `www-data`
- Config: `/etc/zm`
- Content: `/var/cache/zoneminder`
- Web: `/usr/share/zoneminder/www`
- Run: `/var/run/zm`

**Package Installation**:
```bash
# Install from repository
sudo apt install zoneminder

# Or build from source
cmake .. && make && sudo make install
```

---

## Contributing

### Communication Channels

**For Bug Reports & Feature Requests**:
- GitHub Issues: https://github.com/ZoneMinder/zoneminder/issues
- Read first: [GitHub Posting Rules](https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules)
- Issues are for bugs and features ONLY (not general support)

**For General Support & Questions**:
- User Forum: https://forums.zoneminder.com
- Slack: https://zoneminder-chat.slack.com
- Discord: https://discord.gg/tHYyP9k66q

### Pull Request Process

1. **Fork the repository** on GitHub
2. **Create feature branch** from master: `git checkout -b 123-feature-name`
3. **Make changes** following guidelines in this document
4. **Test thoroughly** - all tests must pass
5. **Commit** with conventional format and issue references
6. **Push** to your fork: `git push origin 123-feature-name`
7. **Create Pull Request** on GitHub
8. **Address review feedback** from maintainers
9. **Merge** will be done by maintainers after approval

### Contribution Guidelines

**Branch Naming**:
- Format: `<issue-number>-<brief-description>`
- Examples: `456-fix-rtsp-crash`, `789-add-event-export`
- Always create branch from latest master

**Commit Practices**:
- Commit early and often (rather than single large commits)
- Each commit should be a logical unit of change
- Reference issues in every commit message
- Follow conventional commit format

**Code Review**:
- Be responsive to review feedback
- Be open to suggestions and improvements
- Maintain professional and respectful communication
- Understand that reviews help maintain code quality

**What NOT to Do**:
- ❌ Paste code in GitHub issues expecting others to integrate it
- ❌ Make PRs without associated issue (except trivial fixes)
- ❌ Combine unrelated changes in one PR
- ❌ Submit PRs with failing tests
- ❌ Ignore review feedback

### Knowledge Requirements

**Recommended Knowledge**:
- Git and GitHub workflow
- Relevant programming language (C++, PHP, JavaScript, Perl)
- ZoneMinder architecture basics (read this document)
- Unit testing concepts

**Learning Resources**:
- [Understanding GitHub and Pull Requests](https://github.com/ZoneMinder/ZoneMinder/wiki/Understanding-Github-and-Pull-Requests)
- [GitHub Posting Rules](https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules)
- ZoneMinder Docs: https://zoneminder.readthedocs.org

---

## Documentation

### User Documentation

**Primary Docs**: https://zoneminder.readthedocs.org

**Local Docs** (Sphinx):
- Source: `docs/` directory
- User guide: `docs/userguide/`
- Installation guide: `docs/installationguide/`
- Build docs: `cd docs && make html`

### API Documentation

**REST API**:
- Interactive docs available at: `http://your-zm-server/zm/api/`
- Swagger/OpenAPI specification
- Authentication: Session cookies or JWT tokens

### Developer Documentation

**This File** (`AGENTS.md` / `CLAUDE.md`):
- Architecture overview
- Build instructions
- Development workflow
- Testing requirements

**Code Comments**:
- C++ headers document class interfaces
- PHPDoc comments in PHP files
- JSDoc encouraged in JavaScript

---

## Quick Command Reference

```bash
# ============================================
# BUILD & INSTALL
# ============================================

# Standard build
mkdir build && cd build
cmake ..
cmake --build .
sudo cmake --build . --target install

# Debug build with tests
cmake -DCMAKE_BUILD_TYPE=Debug -DBUILD_TEST_SUITE=ON ..
cmake --build .

# Build with sanitizers
cmake -DCMAKE_BUILD_TYPE=Debug -DASAN=ON ..
cmake --build .

# ============================================
# TESTING
# ============================================

# Run all C++ unit tests
ctest

# Run test binary directly
./tests/tests

# Run specific test
./tests/tests "[Box]"

# List all tests
./tests/tests --list-tests

# JavaScript linting
npx eslint --ext .js.php,.js .

# ============================================
# GIT WORKFLOW
# ============================================

# Create feature branch
gh issue create --title "Feature name" --label enhancement
git checkout -b 123-feature-name

# Commit changes
git add <files>
git commit -m "feat: description refs #123"

# Merge after approval
git checkout master
git merge 123-feature-name
git push origin master
git branch -d 123-feature-name

# ============================================
# DEBUGGING
# ============================================

# Debug with GDB
gdb ./src/zmc
(gdb) set args -m 1
(gdb) run

# Enable debug logging
export ZM_DBG_LEVEL=9
export ZM_DBG_LOG=/tmp/debug.log
./src/zmc -m 1

# View logs
tail -f /var/log/zm/zmc_m1.log

# ============================================
# DATABASE
# ============================================

# Check for migrations
sudo zmupdate.pl --check

# Apply migrations
sudo zmupdate.pl

# Freshen config
sudo zmupdate.pl --freshen

# ============================================
# DAEMON CONTROL
# ============================================

# Start/stop/restart ZoneMinder
sudo systemctl start zoneminder
sudo systemctl stop zoneminder
sudo systemctl restart zoneminder

# Check status
sudo systemctl status zoneminder

# View daemon logs
journalctl -u zoneminder -f
```

---

## Identified Gaps & Future Improvements

### Testing Gaps

**Current State**:
- ✅ C++ unit tests exist (Catch2) but disabled in CI
- ⚠️ PHP/CakePHP tests exist but not run
- ❌ No integration tests
- ❌ No E2E tests for web UI
- ❌ No performance/load testing
- ❌ No test coverage reporting

**Recommended Future Work**:
1. Enable unit tests in CI pipeline (`BUILD_TEST_SUITE=ON`)
2. Add CakePHP API test execution to CI
3. Create integration test suite (daemon → database → web)
4. Add E2E tests with Selenium/Playwright for critical web workflows
5. Implement performance benchmarking for video processing
6. Add test coverage reporting (gcov/lcov)

### Documentation Gaps

**Current State**:
- ✅ Excellent Sphinx user documentation
- ✅ Good architecture overview (this file)
- ⚠️ Inconsistent code comments
- ❌ No API reference documentation (beyond REST API)
- ❌ No architecture diagrams

**Recommended Future Work**:
1. Generate Doxygen documentation for C++ classes
2. Add architecture diagrams (data flow, class relationships)
3. Document all Perl modules with POD
4. Create troubleshooting guide for common issues
5. Document performance tuning best practices

### CI/CD Gaps

**Current State**:
- ✅ Multi-platform build verification
- ✅ JavaScript linting automated
- ✅ CodeQL security scanning
- ⚠️ Tests disabled in CI
- ❌ No automated deployment beyond packages
- ❌ No performance regression detection

**Recommended Future Work**:
1. Enable and require unit tests in CI
2. Add automated smoke tests after package build
3. Performance benchmark tracking across commits
4. Automated security scanning beyond CodeQL (dependency scanning)
5. Automated Docker image builds and publishing

---

## Summary for AI Agents

**When the user asks you to work on ZoneMinder**:

1. **Understand first**: Read architecture, understand component relationships
2. **Plan**: Create GitHub issue, feature branch, test-first approach
3. **Implement**: Write failing test → Implement → Pass test → Commit
4. **Verify**: Build, test (ctest + ESLint), manual testing
5. **Review**: Ask user for approval before merging
6. **Merge**: Only after approval, reference issue with `fixes #n`

**Remember**:
- Testing is MANDATORY (not optional)
- Feature branches keep master stable
- Conventional commits enable automated changelog
- User approval required before merging
- Complete features fully (don't stop halfway)

**This file is your guide - refer back to it throughout development.**

---

*Last updated: 2026-01-09*
*This document guides AI coding agents working on ZoneMinder*
*For human contributors: See also CONTRIBUTING.md and GitHub wiki*
