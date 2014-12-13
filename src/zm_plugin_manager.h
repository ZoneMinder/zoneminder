#ifndef ZM_PLUGIN_MANAGER_H
#define ZM_PLUGIN_MANAGER_H



#include <stdio.h>
#include <stdlib.h>
#include <string>
#include <fstream>
#include <map>
#include <sys/types.h>
#include <sys/dir.h>
#include <sys/param.h>
#include <unistd.h>

#include "zm_image_analyser.h"
#include "zm_detector.h"
#include "zm_plugin.h"



using namespace std;


#define ZM_ENGINE_VERSION 24


//! Map of plugins by their associated file names.
typedef std::map<std::string, Plugin> PluginMap;


//! External function for sorting of files in directory.
extern int alphasort();


//! Function to select files with plugins by extension.
int file_select(const struct direct *entry);


//! Join two path strings.
string join_paths(const string& p1, const string& p2);



//! Class for managing all loaded plugins.
class PluginManager
{
public:
    //! Default constructor.
    PluginManager();

    //! Constructor with parameters
    PluginManager(int nMonitorId);

    //! Access the image analyser.
    ImageAnalyser &getImageAnalyser() {return m_ImageAnalyser;}

    //! Loads a plugin.
    bool loadPlugin(const string &sFilename);

    //! Find all plugins from given directory, load them if required and
    //! return the number of found plugins and the number of loaded plugins
    int findPlugins(const string sPath, bool loadPlugins, unsigned int& nNumPlugLoaded);

    //! Get general settings of plugins
    unsigned long getPluginsGenConf(map<string,pGenConf>& mapPluginGenConf);

    //! Get zone settings of a plugin
    void getPluginZoneConf(string sPluginName, PluginZoneConf& mapPluginZoneConf);

    //! Configure all loaded plugins using given configuration file.
    void configurePlugins(string sConfigFileName, bool bDoNativeDet);

    //! Set plugin extension.
    void setPluginExt(string sPluginExt) { m_sPluginExt = sPluginExt; }

    //! Extension for zm plugins.
    static string m_sPluginExt;

private:

    //! All plugins currently loaded.
    PluginMap m_LoadedPlugins;

    //! The image analyser.
    ImageAnalyser m_ImageAnalyser;

    //! Plugin list
    map<string,bool> mapPluginReg;
};



#endif //ZM_PLUGIN_MANAGER_H
