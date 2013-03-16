#ifndef ZM_PLUGIN_MANAGER_H
#define ZM_PLUGIN_MANAGER_H



#include <stdio.h>
#include <stdlib.h>
#include <string>
#include <map>
#include <sys/types.h>
#include <sys/dir.h>
#include <sys/param.h>
#include <unistd.h>

#include "zm.h"
#include "zm_image_analyser.h"
#include "zm_detector.h"
#include "zm_plugin.h"



using namespace std;


#define ZM_ENGINE_VERSION 24
#define DEFAULT_PLUGIN_EXT ".zmpl"



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
    
    //! Access the image analyser.
    ImageAnalyser &getImageAnalyser() {return m_ImageAnalyser;}
    
    //! Loads a plugin.
    void loadPlugin(const string &sFilename);

    //! Find and load all plugins from given directory, returns number of found plugins.
    int findPlugins(const string &sPath);

    //! Configure all loaded plugins using given configuration file.
    void configurePlugins(string sConfigFileName);

    //! Set plugin extension.
    void setPluginExt(string sPluginExt) { m_sPluginExt = sPluginExt; }

    //! Extension for zm plugins.
    static string m_sPluginExt;
    
private:
    
    //! All plugins currently loaded.
    PluginMap m_LoadedPlugins;

    //! The image analyser.
    ImageAnalyser m_ImageAnalyser;
};



#endif //ZM_PLUGIN_MANAGER_H
