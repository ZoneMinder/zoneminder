#ifndef ZM_IMAGE_ANALYSER_H
#define ZM_IMAGE_ANALYSER_H



#include <list>
#include <string>
#include <stdexcept>
#include <iostream>
#include <fstream>
#include <memory>
#include <algorithm>

#include "zm.h"
#include "zm_detector.h"
#include "zm_image.h"
#include "zm_zone.h"
#include "zm_event.h"
#include "zm_db.h"



using namespace std;


//! List of available detectors.
typedef std::list<Detector *> DetectorsList;

//! A structure to store the general configuration of a plugin
struct pGenConf {
    bool Registered;
    bool Configured;
    pGenConf():
        Registered(false),
        Configured(false)
    {}
};

//! A structure to store the zone configuration of a plugin
struct pZoneConf {
    bool Enabled;
    bool RequireNatDet;
    bool IncludeNatDet;
    bool ReInitNatDet;
    pZoneConf():
        Enabled(false),
        RequireNatDet(false),
        IncludeNatDet(false),
        ReInitNatDet(false)
    {}
};

//! Map of zone configuration for a plugin
typedef std::map<unsigned int,pZoneConf> PluginZoneConf;

//! Class for handling image detection.
class ImageAnalyser {
  public:

    //!Default constructor.
    ImageAnalyser( int nMonitorId = 0 );

    //! Destructor.
    ~ImageAnalyser();

    //! Copy constructor.
    ImageAnalyser(const ImageAnalyser& source);

    //! Overloaded operator=.
    ImageAnalyser& operator=(const ImageAnalyser& source);

    //! Adds new plugin's detector to the list of detectors.
    void addDetector(std::auto_ptr<Detector> Det)
    {
        m_Detectors.push_back(Det.release());
    }

    //! Do detection in an image by calling all available detectors.
    bool DoDetection(const Image &comp_image, Zone** zones, Event::StringSetMap& noteSetMap, std::string& det_cause, unsigned int& score);

    //! Configure all loaded plugins using given configuration file.
    void configurePlugins(string sConfigFileName, bool bDoNativeDet = 0);

    //! Check if the configuration file contains the right section name
    bool isValidConfigFile(string sPluginName, string sConfigFileName);

    //! Get index of enabled zones for this monitor (same ordering as in Monitor::Load)
    bool getMonitorZones();

    //! Get plugin configuration from database
    bool getPluginConfig(string sPluginName, vector<unsigned int> vnPluginZones, map<unsigned int,map<string,string> >& mapPluginConf);

    //! Get enabled zones for the plugin
    bool getEnabledZonesForPlugin(string sPluginName, vector<unsigned int>& vnPluginZones);

    //! Get zones configuration from database
    bool getZonesConfig(string sLoadedPlugins);

    //! Get Zone configuration from this class
    bool getZoneConfig(unsigned int nZone, zConf& zoneConf);

    //! Get the general settings of a registered plugin
    bool getRegPluginGenConf(string sPluginName, pGenConf& regPluginGenConf);

    //! Get the zone settings of a registered plugin
    void getRegPluginZoneConf(string sPluginName, PluginZoneConf& regPluginZoneConf);

    //! Remove from db plugins no longer detected
    void cleanupPlugins();

  private:

    //! All available detectors.
    DetectorsList m_Detectors;

    //! The monitor id
    int m_nMonitorId;

    //! Native detection is enabled
    bool m_bIsNativeDetEnabled;

    //! Analyser is enabled
    bool m_bIsAnalyserEnabled;

    //! A structure to store a plugin parameter
    struct zIdName {
        unsigned int zoneId;
        string name;
    };

    //! A vector filled with parameters of zones
    vector<zConf> m_vZonesConfig;

    //! A structure to store basic settings of a zone
    struct zSetting {
        unsigned int id;
        string name;
        string type;
    };

    //! A vector filled with settings of zones enabled for the monitor
    vector<zSetting> m_vMonitorZones;

    //! A map to store the general configuration of registered plugins
    map<string,pGenConf> mapRegPluginGenConf;

    //! A map to store the zone configuration of registered plugins
    map<string,PluginZoneConf> mapRegPluginZoneConf;
};



#endif //ZM_IMAGE_ANALYSER_H
