#include "zm_detector.h"



/*!\fn Detector::Detector(const Detector& source)
 * \param source is the object to copy
 */
Detector::Detector(const Detector& source)
  : m_sDetectionCause(source.m_sDetectionCause),
    m_fMinAlarmScore(source.m_fMinAlarmScore),
    m_fMaxAlarmScore(source.m_fMaxAlarmScore),
    m_fImageScaleFactor(source.m_fImageScaleFactor),
    m_nNewWidth(source.m_nNewWidth),
    m_nNewHeight(source.m_nNewHeight),
    m_sLogPrefix(source.m_sLogPrefix),
    m_sConfigSectionName(source.m_sConfigSectionName),
    m_vnPluginZones(source.m_vnPluginZones)
{
    m_bIsPluginEnabled = false;
}



/*!\fn Detector& ImageAnalyser::Detector::operator=(const ImageAnalyser::Detector& source)
 * \param source is the object to copy
 */
Detector& Detector::operator=(const Detector& source)
{
    m_sDetectionCause = source.m_sDetectionCause;
    m_fMinAlarmScore = source.m_fMinAlarmScore;
    m_fMaxAlarmScore = source.m_fMaxAlarmScore;
    m_fImageScaleFactor = source.m_fImageScaleFactor;
    m_sLogPrefix = source.m_sLogPrefix;
    m_nNewWidth = source.m_nNewWidth;
    m_nNewHeight = source.m_nNewHeight;
    m_sConfigSectionName = source.m_sConfigSectionName;
    m_vnPluginZones = source.m_vnPluginZones;

    return *this;
}



/*!\fn Detector::getDetectionCause()
 * return detection cause as string
 */
string Detector::getDetectionCause()
{
    return m_sDetectionCause;
}


/*!\fn Detector::getConfigSectionName()
 * return plugin name as string
 */
string Detector::getPluginName()
{
    return m_sConfigSectionName;
}


/*!\fn Detector::EnablePlugin(vector<int> zoneList)
 * \param vnZoneList is the list of enabled zones for the plugin
 */
void Detector::EnablePlugin(vector<unsigned int> vnZoneList)
{
    m_vnPluginZones = vnZoneList;
    m_bIsPluginEnabled = true;
}


/*!\fn Detector::getPluginZones()
 * \return the list of zone which have the plugin enabled
 */
vector<unsigned int> Detector::getPluginZones()
{
    return m_vnPluginZones;
}


/*! \fn Detector::log(int nLogLevel, string sLevel, string sMessage)
 */
void Detector::log(int nLogLevel, string sLevel, string sMessage)
{
    string sMessageToLog = sLevel + string(" [") + m_sLogPrefix + string(": ") + sMessage + string("]");
    syslog(nLogLevel, "%s", sMessageToLog.c_str());
}



/*! \fn int Detector::Detect(const Image &image, Event::StringSet &zoneSet)
 *  \param zmImage is an image to detect faces on
 *  \param zoneSet is set of zone names (see zm_zone.h)
 *  \param score is the detection score
 *  \return true if detection is effective
 */
bool Detector::Detect(const Image &zmImage, Zone** zones, Event::StringSet &zoneSet, unsigned int &score)
{
    bool alarm = false;
    char szMessage[100];
    score = 0;

    if (!m_bIsPluginEnabled) return (alarm);

    // Check preclusive zones first
    for(std::vector<unsigned int>::iterator it = m_vnPluginZones.begin(); it != m_vnPluginZones.end(); ++it)
    {
        Zone *zone = zones[*it];
        if (!zone->IsPreclusive())
            continue;
        if (zone->IsPostProcEnabled() && !zone->IsPostProcInProgress())
            continue;
        sprintf(szMessage, "Checking preclusive zone %s", zone->Label());
        log(LOG_DEBUG, "DEBUG", szMessage);
        if (checkZone(zone, *it, &zmImage))
        {
            alarm = true;
            score += zone->Score();
            zoneSet.insert(zone->Text());
            if (zone->IsPostProcEnabled())
            {
                zone->StopPostProcessing();
                sprintf(szMessage, "Zone is alarmed, zone score = %d (post-processing)", zone->Score());
            }
            else
            {
                sprintf(szMessage, "Zone is alarmed, zone score = %d", zone->Score());
            }
            log(LOG_DEBUG, "DEBUG", szMessage);
        }
    }

    if ( alarm )
    {
        alarm = false;
        score = 0;
    }
    else
    {
        // Find all alarm pixels in active zones
        for(std::vector<unsigned int>::iterator it = m_vnPluginZones.begin(); it != m_vnPluginZones.end(); ++it)
        {
            Zone *zone = zones[*it];
            if (!zone->IsActive())
                continue;
            if (zone->IsPostProcEnabled() && !zone->IsPostProcInProgress())
                continue;
            if (checkZone(zone, *it, &zmImage))
            {
                alarm = true;
                score += zone->Score();
                zoneSet.insert(zone->Text());
                if (zone->IsPostProcEnabled())
                {
                    zone->StopPostProcessing();
                    sprintf(szMessage, "Zone is alarmed, zone score = %d (post-processing)", zone->Score());
                }
                else
                {
                    zone->SetAlarm();
                    sprintf(szMessage, "Zone is alarmed, zone score = %d", zone->Score());
                }
                log(LOG_DEBUG, "DEBUG", szMessage);
            }
        }

        if ( alarm )
        {
            // Checking inclusive zones
            for(std::vector<unsigned int>::iterator it = m_vnPluginZones.begin(); it != m_vnPluginZones.end(); ++it)
            {
                Zone *zone = zones[*it];
                if (!zone->IsInclusive())
                    continue;
                if (zone->IsPostProcEnabled() && !zone->IsPostProcInProgress())
                    continue;
                sprintf(szMessage, "Checking inclusive zone %s", zone->Label());
                log(LOG_DEBUG, "DEBUG", szMessage);
                if (checkZone(zone, *it, &zmImage))
                {
                    alarm = true;
                    score += zone->Score();
                    zoneSet.insert(zone->Text());
                    if (zone->IsPostProcEnabled())
                    {
                        zone->StopPostProcessing();
                        sprintf(szMessage, "Zone is alarmed, zone score = %d (post-processing)", zone->Score());
                    }
                    else
                    {
                        zone->SetAlarm();
                        sprintf(szMessage, "Zone is alarmed, zone score = %d", zone->Score());
                    }
                    log(LOG_DEBUG, "DEBUG", szMessage);
                }
            }
        }
        else
        {
            // Find all alarm pixels in exclusive zones
            for(std::vector<unsigned int>::iterator it = m_vnPluginZones.begin(); it != m_vnPluginZones.end(); ++it)
            {
                Zone *zone = zones[*it];
                if (!zone->IsExclusive())
                    continue;
                if (zone->IsPostProcEnabled() && !zone->IsPostProcInProgress())
                    continue;
                sprintf(szMessage, "Checking exclusive zone %s", zone->Label());
                log(LOG_DEBUG, "DEBUG", szMessage);
                if (checkZone(zone, *it, &zmImage))
                {
                    alarm = true;
                    score += zone->Score();
                    zoneSet.insert(zone->Text());
                    if (zone->IsPostProcEnabled())
                    {
                        zone->StopPostProcessing();
                        sprintf(szMessage, "Zone is alarmed, zone score = %d (post-processing)", zone->Score());
                    }
                    else
                    {
                        zone->SetAlarm();
                        sprintf(szMessage, "Zone is alarmed, zone score = %d", zone->Score());
                    }
                    log(LOG_DEBUG, "DEBUG", szMessage);
                }
            }
        }
    }

    return alarm;
}
