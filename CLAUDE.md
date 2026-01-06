# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is ZoneMinder?

ZoneMinder is an integrated Linux-based CCTV surveillance system that provides capture, analysis, recording, and monitoring of video cameras. It consists of C++ capture/analysis/streaming daemons, a PHP web interface with REST API, Perl system management scripts, and a MySQL database.

## Build System

ZoneMinder uses **CMake** as its build system with C++17 standard.

### Basic Build Commands

```bash
# Create build directory (out-of-source build)
mkdir build
cd build

# Configure with CMake
cmake ..

# Or configure with custom options
cmake \
  -DCMAKE_BUILD_TYPE=Debug \
  -DZM_WEBDIR=/usr/share/zoneminder/www \
  -DZM_CONTENTDIR=/var/lib/zoneminder \
  ..

# Build the project
cmake --build .

# Install (requires appropriate permissions)
sudo cmake --build . --target install
```

### Important CMake Configuration Options

- `CMAKE_BUILD_TYPE`: `Release` (default), `Debug`, or `Optimised`
- `BUILD_TEST_SUITE=ON`: Enable unit tests (default: OFF)
- `BUILD_MAN=ON`: Build man pages (default: ON)
- `ZM_TARGET_DISTRO`: Set to `FreeBSD`, `fc`, `el`, or `OS13` for platform-specific paths
- `ASAN=ON`: Build with AddressSanitizer for debugging (default: OFF)
- `TSAN=ON`: Build with ThreadSanitizer for debugging (default: OFF, mutually exclusive with ASAN)
- `ZM_ONVIF=ON`: Enable ONVIF camera support (default: ON)
- `ZM_NO_LIBVLC=ON`: Skip libVLC checks (default: OFF)
- `ZM_NO_CURL=ON`: Skip cURL checks (default: OFF)

### Test Suite

```bash
# Configure with tests enabled
cmake -DBUILD_TEST_SUITE=ON ..

# Build and run tests
cmake --build .
ctest

# Or run the test binary directly
./tests/tests
```

Tests use the Catch2 framework and cover core utilities:
- `zm_box.cpp`: Bounding box tests
- `zm_comms.cpp`: Communication tests
- `zm_crypt.cpp`: Cryptography tests
- `zm_font.cpp`: Font rendering tests
- `zm_poly.cpp`: Polygon tests
- `zm_utils.cpp`: Utility function tests
- `zm_vector2.cpp`: 2D vector tests

## Code Quality

### JavaScript/PHP Linting

```bash
# ESLint is configured for JavaScript in PHP files
# Using Google style guide with custom overrides
# Configuration: eslint.config.js (newer flat config) and .eslintrc.js (legacy)

# Lint JavaScript code (if Node.js/eslint is installed)
npx eslint web/
```

ESLint handles:
- Browser JavaScript in `web/js/`
- Inline JavaScript within PHP files
- Google style guide with relaxed camelCase, max-len, and jsdoc requirements

### C++ Code Standards

- **C++17** standard required
- Compiler flags: `-O2` for release, `-g` for debug
- Large file support: `-D_FILE_OFFSET_BITS=64`
- ARM NEON optimizations auto-detected on ARM platforms

## Architecture Overview

### Core Components

1. **C++ Daemons** (`src/`):
   - `zmc`: Capture daemon (one per monitor) - captures frames from cameras
   - `zma`: Analysis daemon - motion detection and event triggering
   - `zms`: Streaming server - delivers live/recorded video to web clients
   - `zmu`: Utility program - command-line monitor management

2. **Web Interface** (`web/`):
   - PHP-based user interface
   - Bootstrap + jQuery frontend
   - AJAX endpoints in `web/ajax/`
   - View templates in `web/views/`
   - Multiple skins in `web/skins/`

3. **REST API** (`web/api/`):
   - CakePHP-based modern API
   - Controllers in `web/api/app/Controller/`
   - Models in `web/api/app/Model/`
   - JSON responses for programmatic access
   - JWT authentication support

4. **Perl Scripts** (`scripts/`):
   - `zmdc.pl`: Daemon control and supervision
   - `zmaudit.pl`: Database maintenance
   - `zmupdate.pl`: Schema migrations
   - `zmfilter.pl`: Event filtering and automated actions
   - `zmwatch.pl`: Process health monitoring
   - `zmtrigger.pl`: External event triggering
   - `zmonvif-probe.pl`: ONVIF camera discovery

5. **Database** (`db/`):
   - MySQL/MariaDB schema with 31+ tables
   - Incremental migration scripts: `zm_update-*.sql`
   - Core tables: Monitors, Events, Frames, Zones, Users, Storage, Servers

### Key Architectural Patterns

**Shared Memory Architecture**: Capture daemons write frames to shared memory (`/dev/shm` by default). Analysis and streaming daemons read from the same buffers for zero-copy efficiency.

**Monitor-Centric Design**: The `Monitor` class (`zm_monitor.cpp/h`) is the central orchestrator - it manages capture, analysis, recording, and streaming for each camera.

**Pluggable Camera Types**: Abstract `Camera` base class with implementations:
- `LocalCamera`: V4L2 devices
- `RemoteCameraRTSP`: RTSP network streams
- `RemoteCameraHTTP`: HTTP image URLs
- `FFmpegCamera`: FFmpeg-based (supports 100+ formats)
- `LibVLCCamera`: VLC playback
- `LibVNCCamera`: VNC screen capture

**Event-Driven Recording**: Motion detection triggers `Event` objects which manage recording of video segments to disk and database.

**Multi-Server Clustering**: Database-coordinated distributed architecture allows multiple ZoneMinder servers to share monitors and storage.

### Data Flow

```
Camera → zmc (capture) → Shared Memory → zma (analysis) → Event Recording
                            ↓                                    ↓
                         zms (streaming)                   Database + Disk
                            ↓                                    ↓
                      Web Browsers ← Web Interface/API ← MySQL Storage
```

## Directory Structure

```
src/                      # C++ core binaries (86 source files)
├── zmc.cpp              # Capture daemon entry point
├── zms.cpp              # Streaming server entry point
├── zm_monitor.*         # Monitor orchestration
├── zm_camera.*          # Camera base classes
├── zm_event.*           # Event recording
├── zm_zone.*            # Motion detection zones
├── zm_image.*           # Image processing
├── zm_ffmpeg*.*         # FFmpeg integration
└── zm_rtsp*.*           # RTSP server support

web/                      # Web interface
├── index.php            # Main entry
├── ajax/                # AJAX handlers
├── includes/            # PHP libraries
├── views/               # UI templates
├── skins/               # Themes
├── js/                  # JavaScript
├── css/                 # Stylesheets
└── api/                 # CakePHP REST API

scripts/                  # System management (Perl)
├── zmdc.pl.in           # Daemon supervisor
├── zmaudit.pl.in        # DB maintenance
├── zmfilter.pl.in       # Event filtering
└── ZoneMinder/          # Perl modules

db/                       # Database schema
├── zm_create.sql.in     # Initial schema
└── zm_update-*.sql      # Migrations

tests/                    # Unit tests (Catch2)
docs/                     # Sphinx documentation
utils/                    # Development utilities
dep/                      # Vendored dependencies
onvif/                    # ONVIF camera support
misc/                     # Server configs (Apache, Nginx, systemd)
```

## Development Workflow

### Making Changes

1. **C++ Changes**: Edit files in `src/`, rebuild with `cmake --build .` in build directory
2. **Web Changes**: Edit PHP/JS/CSS in `web/`, changes are typically live (no rebuild)
3. **API Changes**: Edit controllers/models in `web/api/app/`, clear CakePHP cache if needed
4. **Database Schema**: Create new `db/zm_update-X.X.X.sql` migration file
5. **Perl Scripts**: Edit `.in` files in `scripts/`, re-run cmake to generate final scripts

### Debugging

**C++ Debugging**:
```bash
# Build with debug symbols
cmake -DCMAKE_BUILD_TYPE=Debug ..
cmake --build .

# Run under gdb
gdb ./src/zmc
```

**AddressSanitizer/ThreadSanitizer**:
```bash
cmake -DCMAKE_BUILD_TYPE=Debug -DASAN=ON ..
cmake --build .
# Run binaries normally, ASan will report memory issues
```

**Logging**: ZoneMinder uses centralized logging:
- C++: `Logger` class with levels: Debug, Info, Warning, Error, Fatal
- Check logs at `/var/log/zm/` (configurable via `ZM_LOGDIR`)
- Enable debug logging via web interface: Options → System → LOG_LEVEL_*

### Common Tasks

**Run a single test**:
```bash
# Build tests
cmake -DBUILD_TEST_SUITE=ON ..
cmake --build .

# Run specific test
./tests/tests "[test name]"

# List all tests
./tests/tests --list-tests
```

**Update database schema**:
```bash
# After creating a migration file
sudo zmupdate.pl
```

**Package maintainer configuration**:
```bash
# Modify ConfigData.pm defaults before cmake
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
```

## Key Dependencies

**Required**:
- CMake 3.5+
- C++17 compiler (GCC 6+, Clang)
- MySQL/MariaDB client library
- FFmpeg 55.34.100+ (avcodec, avformat, avutil, swscale, swresample)
- libjpeg
- pthread
- Perl 5.6+ with modules: DBI, DBD::mysql, Sys::Syslog, Date::Manip, LWP::UserAgent
- PHP with MySQL support
- Apache/Nginx web server

**Optional**:
- libVLC: VLC video playback
- libvncclient: VNC server monitoring
- libcurl: HTTP camera support
- PCRE2: Regular expression support
- OpenSSL/GnuTLS: Cryptography backend
- jwt-cpp or libjwt: JWT token support
- GSOAP: ONVIF camera support
- Mosquitto: MQTT event publishing
- nlohmann_json: AI results parsing
- Catch2: Unit testing

## Platform-Specific Notes

### FreeBSD
Set `ZM_TARGET_DISTRO=FreeBSD` to use appropriate paths:
- Web user/group: `www`
- Config dir: `/usr/local/etc/zm`
- Web dir: `/usr/local/share/zoneminder/www`
- Content dir: `/usr/local/var/lib/zoneminder`

### RHEL/CentOS/Fedora
Set `ZM_TARGET_DISTRO=el` or `ZM_TARGET_DISTRO=fc`:
- Uses `/run/zoneminder`, `/etc/zm`, `/var/lib/zoneminder`

## Contributing

- Branch naming: `<issue-number>-<brief-description>` (e.g., `456-fix-rtsp-crash`)
- Commit early and often rather than single large commits
- Follow existing code style in each language
- All database changes require migration scripts
- See full guidelines: https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules

## Documentation

Full documentation at: https://zoneminder.readthedocs.org

Support channels:
- GitHub Issues: Bug reports and feature requests only
- User Forum: https://forums.zoneminder.com
- Slack: https://zoneminder-chat.slack.com
- Discord: https://discord.gg/tHYyP9k66q
