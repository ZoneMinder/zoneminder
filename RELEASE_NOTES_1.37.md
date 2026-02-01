# ZoneMinder 1.37.x Release Notes

## TL;DR - Key Highlights

- 🔐 **Role-Based Access Control** - Enterprise-grade permission system with user roles
- 🎥 **Modern Streaming** - WebRTC, Go2RTC, RTSP2Web support with hardware acceleration
- ⚙️ **Monitor Function Redesign** - Granular control with separate Capturing, Analysing, and Recording settings
- 📡 **Enhanced Protocols** - ONVIF Events, MQTT, Amcrest API integration
- 🏷️ **Event Tagging** - Flexible labeling and organization system
- 📊 **Server Monitoring** - Real-time CPU, memory, and performance metrics
- 🌍 **Geolocation** - Geographic tracking for events and servers
- ⚡ **Hardware Encoding** - GPU acceleration for video encoding

## Overview

ZoneMinder 1.37.x represents a major evolution from version 1.36.x, introducing significant architectural improvements, new streaming capabilities, enhanced security features, and extensive monitoring enhancements. This release focuses on modernizing the platform with support for contemporary streaming protocols, implementing enterprise-grade access control, and improving performance and scalability.

## Major Feature Areas

### 1. Advanced User Access Control & Security

**Role-Based Access Control (RBAC)**
- Introduced comprehensive role-based permission system with reusable role templates
- New database tables: `User_Roles`, `Role_Groups_Permissions`, `Role_Monitors_Permissions`
- Replaced legacy comma-separated monitor ID strings with normalized permission tables
- Fine-grained permissions for both monitor groups and individual monitors

**Enhanced User Management**
- Added user profile fields: Name, Email, Phone
- User-specific montage layouts for personalized dashboards
- Improved security controls with private and system configuration flags

### 2. Modern Streaming & Multi-Protocol Support

**WebRTC Integration**
- Janus WebRTC Gateway support with audio streaming capabilities
- Go2RTC protocol support for alternative streaming
- RTSP2Web streaming with WebRTC/MSE/HLS options
- Configurable codec selection and stream profiles

**Hardware-Accelerated Encoding**
- Hardware acceleration support for video encoding (GPU encoding)
- New encoder configuration options: `EncoderHWAccelName` and `EncoderHWAccelDevice`
- Human-readable codec names replacing integer-based codec selection
- Optimized encoding for reduced CPU usage

### 3. Monitor Function Redesign

One of the most significant architectural changes in 1.37.x is the redesign of the monitor `Function` field into three independent control parameters. This provides much more granular control over monitor behavior.

**Legacy Function Field (1.36.x and earlier)**

The traditional `Function` field was a single enum that controlled all aspects of a monitor's operation:
- `None` - Monitor disabled
- `Monitor` - Capture video only (no recording, no motion detection)
- `Modect` - Motion detection with recording on motion
- `Record` - Continuous recording without motion detection
- `Mocord` - Continuous recording with motion detection
- `Nodect` - Recording on external trigger, no built-in motion detection

**New Granular Control (1.37.x)**

The `Function` field has been split into three independent settings, allowing fine-grained control:

**`Capturing` enum (None/Ondemand/Always)**
- Controls whether the monitor captures video from the camera
- `None` - No video capture (monitor effectively disabled)
- `Ondemand` - Capture only when needed (e.g., when viewing live or triggered by events)
- `Always` - Continuous video capture from the camera

**`Analysing` enum (None/Always)**
- Controls whether motion detection and analysis is performed
- `None` - No motion detection or analysis (equivalent to Monitor or Record modes)
- `Always` - Perform motion detection and analysis (equivalent to Modect or Mocord modes)

**`Recording` enum (None/OnMotion/Always)**
- Controls when video is saved to disk
- `None` - No recording (live viewing only)
- `OnMotion` - Record only when motion is detected or triggered
- `Always` - Continuous recording

**Migration from Legacy Functions**

The database automatically migrates old Function values to the new settings:
- `None` → Capturing: None, Analysing: None, Recording: None
- `Monitor` → Capturing: Always, Analysing: None, Recording: None
- `Modect` → Capturing: Always, Analysing: Always, Recording: OnMotion
- `Record` → Capturing: Always, Analysing: None, Recording: Always
- `Mocord` → Capturing: Always, Analysing: Always, Recording: Always
- `Nodect` → Capturing: Always, Analysing: None, Recording: OnMotion

**Benefits of the New Design**
- More flexible monitor configurations (e.g., analyze but don't record, or record without analysis)
- Better resource management by independently controlling capture, analysis, and storage
- Clearer separation of concerns for troubleshooting
- Foundation for future enhancements like conditional recording policies
- Easier to understand monitor behavior at a glance

### 4. Enhanced Camera Integration

**Extended Protocol Support**
- ONVIF Event Listener for direct camera event notifications
- ONVIF alarm text integration
- Amcrest API support for Amcrest-branded cameras
- MQTT integration for IoT device communication and message handling

**Camera Management**
- Camera manufacturer and model database integration
- Device-specific control presets linked to camera models
- SOAP WS-Addressing compliance for enterprise cameras
- Configurable ONVIF events endpoints

### 5. Advanced Event Management

**Event Tagging System**
- Flexible event labeling with custom tags
- Many-to-many relationship between events and tags
- Improved event organization and filtering

**Event Automation & Triggers**
- Event start and end command execution
- Multiple event close modes: system, time, duration, idle, alarm-based
- Section length warnings for long recordings
- Filter execution intervals for scheduled automation

**Event Metadata & Analytics**
- Event data extensibility with custom metadata storage
- Geolocation tracking (latitude/longitude) for events
- Maximum score frame tracking to identify peak detection moments
- Enhanced event reporting system with historical analytics

### 6. Performance & Infrastructure

**Server Monitoring**
- Comprehensive server statistics tracking
- CPU usage monitoring (User, Nice, System, Idle percentages)
- Memory and swap utilization metrics
- Timestamped performance data collection

**Optimization Features**
- Monitor startup delay to stagger initialization and reduce system load
- Enhanced status tracking with update timestamps
- Analysis image channel optimization (Full Color vs. Y-Channel)
- Improved monitor soft delete with logical deletion flags

### 7. Display & User Interface

**Montage Enhancements**
- Expanded grid layouts: 1/2/4/5/6/7/8/9/10/12/16 Wide configurations
- User-specific montage layouts with personalized views
- Monitor importance levels (Normal/Less/Not) for stream prioritization
- Wall clock timestamp synchronization

**Playback Improvements**
- Default player selection for preferred streaming client
- Enhanced video playback controls
- Improved event viewing experience

### 8. Storage & Recording

**Granular Recording Control**

With the redesign of the monitor Function field (see section 3), recording behavior is now controlled independently through the `Recording`, `Capturing`, and `Analysing` settings. This provides much greater flexibility:

- **Recording modes**: None/OnMotion/Always
- **Capturing modes**: None/Ondemand/Always  
- **Analysis modes**: None/Always

This allows configurations that weren't possible with the old Function field, such as:
- Capture and analyze without recording (for alerting only)
- Record without analysis (for compliance/archival)
- On-demand capture with triggered recording

**Email Notifications**
- Email format options: Individual or Summary
- Improved event notification formatting
- Configurable alert delivery

### 9. Geographic & Metadata Features

**Geolocation Support**
- Geographic coordinates for monitors, events, and servers
- High-precision decimal storage (11,8) for latitude/longitude
- Location-based event tracking and analysis

### 10. Development & Integration

**API Improvements**
- Enhanced RESTful API capabilities
- Improved monitor control via API
- Better integration support for third-party applications

**Codebase Modernization**
- Database schema normalization
- Type consistency improvements (e.g., cURL to FFmpeg standardization)
- Enhanced error handling and logging

## Database Schema Evolution

The 1.37.x series includes approximately **79 database schema updates** from 1.37.1 through 1.37.79, each introducing targeted improvements and new capabilities. Key architectural changes include:

- Migration from string-based configuration to normalized relational tables
- Introduction of extensible metadata storage systems
- Enhanced foreign key relationships for referential integrity
- Improved indexing for query performance

## Upgrade Considerations

### Breaking Changes
- Permission system migration from comma-separated strings to normalized tables
- Some configuration parameters have been renamed or restructured
- Monitor soft delete may require updates to custom scripts that query monitor data

### Compatibility
- Version 1.36.x is still supported alongside 1.37.x (see SECURITY.md)
- Database upgrades are handled automatically via update scripts
- Backup your database before upgrading

### System Requirements
- Hardware acceleration features require compatible GPU and drivers
- WebRTC streaming may require additional server configuration
- MQTT integration requires MQTT broker setup

## Community & Support

For detailed documentation, visit: https://zoneminder.readthedocs.org

- **Issue Tracker**: https://github.com/ZoneMinder/ZoneMinder/issues
- **Forums**: https://forums.zoneminder.com
- **Slack**: https://zoneminder-chat.slack.com/
- **Discord**: https://discord.gg/tHYyP9k66q

## Acknowledgments

This release represents the collaborative effort of the ZoneMinder development team and community contributors. Special thanks to all who submitted patches, reported bugs, provided testing, and contributed to making ZoneMinder better.

---

**Note**: This is a high-level overview of changes from version 1.36.0 to 1.37.x. For a complete list of all changes, bug fixes, and minor improvements, please refer to the git commit history and closed issues on GitHub.
