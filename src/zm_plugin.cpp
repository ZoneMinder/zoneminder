#include "zm_plugin.h"



/*!\fn Plugin::Plugin(const std::string &sFilename)
 * \param sFilename is the name of plugin file to load
 */
Plugin::Plugin(const std::string &sFilename)
    :   m_sPluginFileName(sFilename),
        m_hDLL(0),
        m_pDLLRefCount(0),
        m_pfnGetEngineVersion(0),
        m_pfnRegisterPlugin(0)
{

    // Try to load the plugin as a dynamic library
    m_hDLL = dlopen(sFilename.c_str(), RTLD_LAZY|RTLD_GLOBAL);

    if(!m_hDLL) // if library hasn't been loaded successfully
    {
        throw runtime_error(string("Could not load '") + sFilename + "'");
    }

    // Locate the plugin's exported functions
    try
    {
        m_pfnGetEngineVersion = reinterpret_cast<fnGetEngineVersion *>(dlsym(m_hDLL, "getEngineVersion"));
        m_pfnRegisterPlugin = reinterpret_cast<fnRegisterPlugin *>(dlsym(m_hDLL, "registerPlugin"));

        // If the functions aren't found, we're going to assume this is
        // a plain simple DLL and not one of our plugins
        if(!m_pfnGetEngineVersion || ! m_pfnRegisterPlugin)
            throw runtime_error(string("'") + sFilename + "' is not a valid plugin");

        // Initialize a new DLL reference counter
        m_pDLLRefCount = new size_t(1);
    }
    catch(runtime_error &ex)
    {
        dlclose(m_hDLL);
        throw ex;
    }
    catch(...)
    {
        dlclose(m_hDLL);
        throw runtime_error(string("Unknown exception while loading plugin '") + sFilename + string("'"));
    }
}



/*!\fn Plugin::Plugin(const Plugin &Other)
 * \param Other is the other plugin instance to copy
 */
Plugin::Plugin(const Plugin &Other)
    :   m_sPluginFileName(Other.m_sPluginFileName),
        m_hDLL(Other.m_hDLL),
        m_pDLLRefCount(Other.m_pDLLRefCount),
        m_pfnGetEngineVersion(Other.m_pfnGetEngineVersion),
        m_pfnRegisterPlugin(Other.m_pfnRegisterPlugin)
{
    // Increase DLL reference counter
    ++*m_pDLLRefCount;
}



/*!\fn Plugin::operator=(const Plugin &Other)
 * \param Other is the other plugin instance to copy
 * return copy of object
 */
Plugin& Plugin::operator=(const Plugin &Other)
{
    m_hDLL = Other.m_hDLL;
    m_pfnGetEngineVersion = Other.m_pfnGetEngineVersion;
    m_pfnRegisterPlugin = Other.m_pfnRegisterPlugin;
    m_pDLLRefCount = Other.m_pDLLRefCount;
    m_sPluginFileName = Other.m_sPluginFileName;
    // Increase DLL reference counter
    ++*m_pDLLRefCount;
    return *this;

}



Plugin::~Plugin()
{
    // Only unload the DLL if there are no more references to it
    if(!--*m_pDLLRefCount)
    {
        delete m_pDLLRefCount;
        dlclose(m_hDLL);
    }
}



/*!\fn Plugin::registerPlugin(PluginManager &K)
 * \param K is the pointer to plugin manager
 */
void Plugin::registerPlugin(PluginManager &K)
{
    m_pfnRegisterPlugin(K, m_sPluginFileName);
}
