#ifndef ZM_DETECTOR_H
#define ZM_DETECTOR_H


#include <string>
#include <algorithm>
#include <syslog.h>
#include <libgen.h>

#include "zm_image.h"
#include "zm_zone.h"
#include "zm_event.h"

#define DEFAULT_DETECTION_CAUSE "Object Detected"
#define DEFAULT_MIN_ALARM_SCORE 1.0
#define DEFAULT_MAX_ALARM_SCORE 99.0
#define DEFAULT_IMAGE_SCALE_FACTOR 1.0

#define DEFAULT_LOG_PREFIX "ZM PLUGIN"
#define LOG_LEVEL LOG_NOTICE
#define DEFAULT_CONFIGFILE_SECTION "libzm_vscvl_plugin"



//! Base class for object detectors, defined in plugins.
class Detector
{

public:

    //! Destructor
    virtual ~Detector() {}

    //! Default constructor
    Detector()
{
    m_sLogPrefix = DEFAULT_LOG_PREFIX;
    m_sDetectionCause = DEFAULT_DETECTION_CAUSE;
    m_fMinAlarmScore = DEFAULT_MIN_ALARM_SCORE;
    m_fMaxAlarmScore = DEFAULT_MAX_ALARM_SCORE;
    m_fImageScaleFactor = DEFAULT_IMAGE_SCALE_FACTOR;
    m_sConfigSectionName = DEFAULT_CONFIGFILE_SECTION;
    m_nNewWidth = 0;
    m_nNewHeight = 0;
}

    //! Constructor with section name parameter.
    Detector(std::string sPluginFileName)
{
    m_sLogPrefix = DEFAULT_LOG_PREFIX;

    char* szPluginFileName = strdup(sPluginFileName.c_str());

    std::string sPluginFileNameName = std::string(basename(szPluginFileName));

    size_t idx = sPluginFileNameName.rfind('.');

    if (idx == std::string::npos)
        m_sConfigSectionName = sPluginFileNameName;
    else
        m_sConfigSectionName = sPluginFileNameName.substr(0, idx);

    m_sDetectionCause = DEFAULT_DETECTION_CAUSE;
    m_fMinAlarmScore = DEFAULT_MIN_ALARM_SCORE;
    m_fMaxAlarmScore = DEFAULT_MAX_ALARM_SCORE;
    m_fImageScaleFactor = DEFAULT_IMAGE_SCALE_FACTOR;
    m_nNewWidth = 0;
    m_nNewHeight = 0;
}

    //! Copy constructor
    Detector(const Detector& source);

    //! Assignment operator
    Detector& operator=(const Detector& source);

    //! Detect (in an image later)
    bool Detect(const Image &image, Zone** zones, unsigned int &score);

    void _onCreateEvent(Zone** zones, Event *event);
    void _onCloseEvent(Zone** zones, Event *event);

    //! Load detector's parameters.
    virtual int loadConfig(std::string sConfigFileName, std::map<unsigned int,std::map<std::string,std::string> > mapPluginConf) = 0;

    //! Returns detection case string.
    std::string getDetectionCause();

    //! Returns plugin name as string.
    std::string getPluginName();

    //! Enable the plugin for the given zones.
    void EnablePlugin(std::vector<unsigned int> zoneList);

    //! Return the list of enabled zones
    std::vector<unsigned int> getPluginZones();

protected:

    //! Do detection inside one given zone.
    virtual bool checkZone(Zone *zone, unsigned int n_zone, const Image *zmImage) = 0;

    virtual void onCreateEvent(Zone *zone, unsigned int n_zone, Event *event) = 0;
    virtual void onCloseEvent(Zone *zone, unsigned int n_zone, Event *event, std::string &noteText) = 0;

    //! Log messages to the SYSLOG.
    void log(int, std::string sLevel, std::string sMessage);

    //! String to be shown as detection cause for event.
    std::string m_sDetectionCause;

    //! Minimum score value to consider frame as to be alarmed.
    double m_fMinAlarmScore;

    //! Maximum score value to consider frame as to be alarmed.
    double m_fMaxAlarmScore;

    //! Maximum allowed width of frame image.
    double m_fImageScaleFactor;

    //! Width of image to resize.
    int m_nNewWidth;

    //! Height of image to resize.
    int m_nNewHeight;

    //! String prefix for SYSLOG messages.
    std::string m_sLogPrefix;

    //! Name of config file section to search parameters.
    std::string m_sConfigSectionName;

    //! List of zones enabled for the plugin
    std::vector<unsigned int> m_vnPluginZones;

    //! Plugin status regarding zone settings
    bool m_bIsPluginEnabled;

};


#endif // ZM_DETECTOR_H
