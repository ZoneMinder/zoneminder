#ifndef ZM_PLUGIN_H
#define ZM_PLUGIN_H



#include <stdlib.h>
#include <stdio.h>
#include <dlfcn.h>

#include <stdexcept>
#include "zm.h"



using namespace std;



class PluginManager;



//! Signature for the version query function
typedef int  fnGetEngineVersion();

//! Signature for the plugin's registration function
typedef void fnRegisterPlugin(PluginManager &, string);



//! Representation of a plugin.
/*! Use for loading plugin's shared library 
 *  and registration of it to the PluginManager.
 */
class Plugin 
{

public:
    
    //! Initialize and load plugin
    Plugin(const std::string &sFilename);
    
    //! Copy existing plugin instance
    Plugin(const Plugin &Other);
    
    //! Operator =.
    Plugin &operator =(const Plugin &Other);

    //! Unload a plugin
    ~Plugin();

    //! Query the plugin for its expected engine version
    int getEngineVersion() const { return m_pfnGetEngineVersion();}

    //! Register the plugin to a PluginManager
    void registerPlugin(PluginManager &K);
        
private:

    //! Shared file name.
    string m_sPluginFileName;
    
    //! DLL handle
    void* m_hDLL; 
    
    //! Number of references to the DLL
    size_t *m_pDLLRefCount;        

    //! Version query function
    fnGetEngineVersion *m_pfnGetEngineVersion; 

    //! Plugin registration function
    fnRegisterPlugin *m_pfnRegisterPlugin;
};



#endif //ZM_PLUGIN_H
