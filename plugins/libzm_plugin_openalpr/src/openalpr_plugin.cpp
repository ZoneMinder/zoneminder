/*****************************************************************************
 * Copyright (C) 2014 Emmanuel Papin <manupap01@gmail.com>
 *
 * Authors: Emmanuel Papin <manupap01@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston MA 02110-1301, USA.
 *****************************************************************************/

#include "openalpr_plugin.h"

using namespace alpr;
using namespace boost::program_options;

//! Retrieve the engine version we're going to expect
extern "C" int getEngineVersion()
{
    return ZM_ENGINE_VERSION;
}

//! Tells us to register our functionality to an engine kernel
extern "C" void registerPlugin(PluginManager &PlM, std::string sPluginName)
{
    PlM.getImageAnalyser().addDetector(std::auto_ptr<Detector>(new OpenALPRPlugin(sPluginName)));
}

OpenALPRPlugin::OpenALPRPlugin()
  : Detector(),
    m_sConfigFilePath(DEFAULT_CONFIG_FILE),
    m_sCountry(DEFAULT_COUNTRY_CODE),
    m_sRegionTemplate(DEFAULT_TEMPLATE_REGION),
    m_nMaxPlateNumber(DEFAULT_TOPN),
    m_bRegionIsDet(DEFAULT_DETECT_REGION)
{
    m_sDetectionCause = DEFAULT_DETECTION_CAUSE;
    m_sLogPrefix = DEFAULT_PLUGIN_LOG_PREFIX;

    Info("%s: Plugin object has been created", m_sLogPrefix.c_str());
}

OpenALPRPlugin::OpenALPRPlugin(std::string sPluginName)
  : Detector(sPluginName),
    m_sConfigFilePath(DEFAULT_CONFIG_FILE),
    m_sCountry(DEFAULT_COUNTRY_CODE),
    m_sRegionTemplate(DEFAULT_TEMPLATE_REGION),
    m_nMaxPlateNumber(DEFAULT_TOPN),
    m_bRegionIsDet(DEFAULT_DETECT_REGION)
{
    m_sDetectionCause = DEFAULT_DETECTION_CAUSE;
    m_sLogPrefix = DEFAULT_PLUGIN_LOG_PREFIX;

    Info("%s: Plugin object has been created", m_sLogPrefix.c_str());
}

/*! \fn OpenALPRPlugin::loadConfig(std::string sConfigFileName, std::map<unsigned int,std::map<std::string,std::string> > mapPluginConf)
 *  \param sConfigFileName is path to configuration to load parameters from
 *  \param mapPluginConf is the map of configuration parameters for each zone
*/
int OpenALPRPlugin::loadConfig(std::string sConfigFileName, std::map<unsigned int,std::map<std::string,std::string> > mapPluginConf)
{
    try
    {
        options_description config_file("Configuration file options.");
        variables_map vm;
        config_file.add_options()
            ((m_sConfigSectionName + std::string(".config_file")).c_str(),
                value<std::string>()->default_value(DEFAULT_CONFIG_FILE))
            ((m_sConfigSectionName + std::string(".country_code")).c_str(),
                value<std::string>()->default_value(DEFAULT_COUNTRY_CODE))
            ((m_sConfigSectionName + std::string(".template_region")).c_str(),
                value<std::string>()->default_value(DEFAULT_TEMPLATE_REGION))
            ((m_sConfigSectionName + std::string(".topn")).c_str(),
                value<int>()->default_value(DEFAULT_TOPN))
            ((m_sConfigSectionName + std::string(".detect_region")).c_str(),
                value<bool>()->default_value(DEFAULT_DETECT_REGION))
            ((m_sConfigSectionName + std::string(".det_cause")).c_str(),
                value<std::string>()->default_value(DEFAULT_DET_CAUSE))
            ((m_sConfigSectionName + std::string(".log_prefix")).c_str(),
                value<std::string>()->default_value(DEFAULT_PLUGIN_LOG_PREFIX));
        try
        {
            std::ifstream ifs(sConfigFileName.c_str());
            store(parse_config_file(ifs, config_file, false), vm);
            notify(vm);
        }
        catch(error& er)
        {
            Error("%s: Plugin is not configured (%s)", m_sLogPrefix.c_str(), er.what());
            return 0;
        }
        m_sConfigFilePath = vm[(m_sConfigSectionName + std::string(".config_file")).c_str()].as<std::string>();
        m_sCountry = vm[(m_sConfigSectionName + std::string(".country_code")).c_str()].as<std::string>();
        m_sRegionTemplate = vm[(m_sConfigSectionName + std::string(".template_region")).c_str()].as<std::string>();
        m_nMaxPlateNumber = vm[(m_sConfigSectionName + std::string(".topn")).c_str()].as<int>();
        m_bRegionIsDet = vm[(m_sConfigSectionName + std::string(".detect_region")).c_str()].as<bool>();
        m_sDetectionCause = vm[(m_sConfigSectionName + std::string(".det_cause")).c_str()].as<std::string>();
        m_sLogPrefix = vm[(m_sConfigSectionName + std::string(".log_prefix")).c_str()].as<std::string>();
    }
    catch(std::exception& ex)
    {
        Error("%s: Plugin is not configured (%s)", m_sLogPrefix.c_str(), ex.what());
        return 0;
    }

    pConf pluginConf;
    for (std::map<unsigned int,std::map<std::string,std::string> >::iterator it = mapPluginConf.begin(); it != mapPluginConf.end(); ++it) {
        while ( pluginConfig.size() < (it->first + 1) )
            pluginConfig.push_back(pluginConf);
        // Overwrite default values with database values
        for (std::map<std::string,std::string>::iterator it2 = it->second.begin(); it2 != it->second.end(); ++it2) {
            if (it2->second.empty()) continue;
            if (it2->first == "AlarmScore") {
                pluginConfig[it->first].alarmScore = (unsigned int)strtoul(it2->second.c_str(), NULL, 0);
            } else if (it2->first == "AssumeTargets") {
                if (it2->second == "Yes") {
                    pluginConfig[it->first].assumeTargets = true;
                } else {
                    pluginConfig[it->first].assumeTargets = false;
                }
            } else if (it2->first == "MaxCharacters") {
                pluginConfig[it->first].maxCharacters = (unsigned int)strtoul(it2->second.c_str(), NULL, 0);
            } else if (it2->first == "MinCharacters") {
                pluginConfig[it->first].minCharacters = (unsigned int)strtoul(it2->second.c_str(), NULL, 0);
            } else if (it2->first == "MinConfidence") {
                pluginConfig[it->first].minConfidence = (unsigned int)strtoul(it2->second.c_str(), NULL, 0);
            } else if (it2->first == "OnlyTargets") {
                if (it2->second == "Yes") {
                    pluginConfig[it->first].onlyTargets = true;
                } else {
                    pluginConfig[it->first].onlyTargets = false;
                }
            } else if (it2->first == "StrictTargets") {
                if (it2->second == "Yes") {
                    pluginConfig[it->first].strictTargets = true;
                } else {
                    pluginConfig[it->first].strictTargets = false;
                }
            } else if (it2->first == "TargetList") {
                boost::split(pluginConfig[it->first].targetList, it2->second, boost::is_any_of(","));
            }
        }
    }

    // Create an instance of class Alpr and set basic configuration
    ptrAlpr = new Alpr(m_sCountry, m_sConfigFilePath);
    ptrAlpr->setTopN(m_nMaxPlateNumber);
    if ( m_bRegionIsDet )
        ptrAlpr->setDetectRegion(m_bRegionIsDet);
    if ( !m_sRegionTemplate.empty() )
        ptrAlpr->setDefaultRegion(m_sRegionTemplate);

    // Initialize some lists
    int nb_zones = pluginConfig.size();
    plateList.resize(nb_zones);
    tmpPlateList.resize(nb_zones);

    if ( ptrAlpr->isLoaded() ) {
        Info("%s: Plugin is configured", m_sLogPrefix.c_str());
    } else {
        Error("%s: Plugin is not configured (%s)", m_sLogPrefix.c_str(), strerror(errno));
        delete ptrAlpr;
        return 0;
    }

    return 1;
}


OpenALPRPlugin::~OpenALPRPlugin()
{
    delete ptrAlpr;
}


/*! \fn OpenALPRPlugin::OpenALPRPlugin(const OpenALPRPlugin& source)
 *  \param source is the object for copying
 */
OpenALPRPlugin::OpenALPRPlugin(const OpenALPRPlugin& source)
  : Detector(source),
    m_sConfigFilePath(source.m_sConfigFilePath),
    m_sCountry(source.m_sCountry),
    m_sRegionTemplate(source.m_sRegionTemplate),
    m_nMaxPlateNumber(source.m_nMaxPlateNumber),
    m_bRegionIsDet(source.m_bRegionIsDet)
{
}


/*! \fn OpenALPRPlugin:: operator=(const OpenALPRPlugin& source)
 *  \param source is the object for copying
 */
OpenALPRPlugin & OpenALPRPlugin:: operator=(const OpenALPRPlugin& source)
{
    Detector::operator=(source);
    m_sConfigFilePath = source.m_sConfigFilePath;
    m_sCountry = source.m_sCountry;
    m_sRegionTemplate = source.m_sRegionTemplate;
    m_nMaxPlateNumber = source.m_nMaxPlateNumber;
    m_bRegionIsDet = source.m_bRegionIsDet;
    return *this;
}


/*! \fn OpenALPRPlugin::onCreateEvent(Zone *zone, unsigned int n_zone, Event *event)
 * \param zone is a pointer to the zone that triggered the event
 * \param n_zone is the zone id
 * \param event is a pointer the new event
 */
void OpenALPRPlugin::onCreateEvent(Zone *zone, unsigned int n_zone, Event *event)
{
    Debug(1, "%s: Zone %s - Prepare plugin for event %d", m_sLogPrefix.c_str(), zone->Label(), event->Id());

    /* Note: Do not clear the plate list here because in case of post processing
     *       the plate list is filled with results of first detection before
     *       event creation */

}


/*! \fn OpenALPRPlugin::onCloseEvent(Zone *zone, unsigned int n_zone, Event *event, std::string noteText)
 *  \param zone is a pointer to the zone that triggered the event
 *  \param n_zone is the zone id
 *  \param event is a pointer to the event that will be closed
 *  \param noteText is a string that can be used to output text to the event note
 */
void OpenALPRPlugin::onCloseEvent(Zone *zone, unsigned int n_zone, Event *event, std::string &noteText)
{
    // Set the number of plates to output and exit if nothing to do
    unsigned int topn = ( m_nMaxPlateNumber < plateList[n_zone].size() ) ? m_nMaxPlateNumber : plateList[n_zone].size();
    if (topn == 0) return;

    // Sort plates according confidence level (higher first)
    sort(plateList[n_zone].begin(), plateList[n_zone].end(), sortByConf());

    Info("%s: Zone %s - Add plates to event %d", m_sLogPrefix.c_str(), zone->Label(), event->Id());

    // Output only the first topn plates to the event note
    for(unsigned int i=0; i<topn;i++)
    {
        std::stringstream plate;
        plate << plateList[n_zone][i].num << " (" << plateList[n_zone][i].conf << ")";
        Debug(1, "%s: Zone %s - Plate %s detected", m_sLogPrefix.c_str(), zone->Label(), plate.str().c_str());
        noteText += "    " + plate.str() + "\n";
    }

    // Reset the lists for next use
    plateList[n_zone].clear();
    tmpPlateList[n_zone].clear();
}


/*! \fn OpenALPRPlugin::checkZone(Zone *zone, const Image *zmImage)
 *  \param zone is a zone where license plates will be detected
 *  \param n_zone is the zone id
 *  \param zmImage is an image to perform license plate detection (in the form of ZM' Image)
 *  \return true if there were objects detected in given image and false otherwise
 */
bool OpenALPRPlugin::checkZone(Zone *zone, unsigned int n_zone, const Image *zmImage)
{
    Polygon zone_polygon = Polygon(zone->GetPolygon()); // Polygon of interest of the processed zone.

    Image *pMaskImage = new Image(zmImage->Width(), zmImage->Height(), ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE );
    pMaskImage->Fill(BLACK);
    // An temporary image in the form of ZM for making from it CvMat.
    // If don't use temp image, after rgb->bgr it will change.
    Image *tempZmImage = new Image(*zmImage);
    int imgtype=CV_8UC1;
    if (tempZmImage->Colours() == ZM_COLOUR_RGB24)
      imgtype=CV_8UC3;
    cv::Mat cvInputImage = cv::Mat(
                           tempZmImage->Height(),
                           tempZmImage->Width(),
                           imgtype, (unsigned char*)tempZmImage->Buffer()).clone();
    if (tempZmImage->Colours() == ZM_COLOUR_RGB24)
        cvtColor(cvInputImage, cvInputImage, CV_RGB2BGR);

    //Process image
    //std::vector<unsigned char> buffer;
    //cv::imencode(".bmp", cvInputImage, buffer);
    std::vector<AlprRegionOfInterest> regionsOfInterest;
    regionsOfInterest.push_back(AlprRegionOfInterest(0,0, cvInputImage.cols, cvInputImage.rows));
    // Region of interest don't work as expected
    //std::vector<AlprResults> results = ptrAlpr->recognize(buffer, regionsOfInterest);
    AlprResults results = ptrAlpr->recognize(cvInputImage.data, cvInputImage.elemSize(), cvInputImage.cols, cvInputImage.rows, regionsOfInterest);
    double score = 0;

    for (unsigned int i = 0; i < results.plates.size(); i++) {
        int x1 = results.plates[i].plate_points[0].x, y1 = results.plates[i].plate_points[0].y;
        int x2 = results.plates[i].plate_points[1].x, y2 = results.plates[i].plate_points[1].y;
        int x3 = results.plates[i].plate_points[2].x, y3 = results.plates[i].plate_points[2].y;
        int x4 = results.plates[i].plate_points[3].x, y4 = results.plates[i].plate_points[3].y;
        Coord rectVertCoords[4] = {Coord(x1, y1), Coord(x2, y2), Coord(x3, y3), Coord(x4, y4)};
        int nNumVertInside = 0;

        for (int p = 0; p < 4; p++)
            nNumVertInside += zone_polygon.isInside(rectVertCoords[p]);

        // if at least three rectangle coordinates are inside polygon,
        // consider rectangle as belonging to the zone
        // otherwise process next object
        if (nNumVertInside < 3)
        {
            Debug(1, "%s: Zone %s - Skip result (outside detection zone)", m_sLogPrefix.c_str(), zone->Label());
            continue;
        }

        int cntDetPlates = 0;

        for (unsigned int k = 0; k < results.plates[i].topNPlates.size(); k++)
        {
            strPlate detPlate;
            detPlate.num = results.plates[i].topNPlates[k].characters;
            detPlate.conf = results.plates[i].topNPlates[k].overall_confidence;

            bool isTarget = false;

            // Check targeted plates first
            for (std::vector<std::string>::iterator it = pluginConfig[n_zone].targetList.begin(); it != pluginConfig[n_zone].targetList.end(); ++it)
            {
                // If plates match, add targeted plate and continue with next detected plate
                if (*it == detPlate.num)
                {
                    detPlate.conf = 100;
                    addPlate(zone, n_zone, detPlate);
                    cntDetPlates++;
                    isTarget = true;
                    break;
                }
                // Check if targeted plate is a substring of the detected plate
                else if (detPlate.num.find(*it) != std::string::npos)
                {
                    // If yes and strict targeting is on, disqualify targeted plate
                    if (pluginConfig[n_zone].strictTargets)
                    {
                        Debug(1, "%s: Zone %s - Skip targeted plate %s (strict targeting is on)", m_sLogPrefix.c_str(), zone->Label(), detPlate.num.c_str());
                        // And continue with next targeted plate (maybe another in the list will strictly match)
                        continue;
                    }
                    // If yes but detected plate has too much characters, disqualify targeted plate
                    if (detPlate.num.size() > pluginConfig[n_zone].maxCharacters)
                    {
                        Debug(1, "%s: Zone %s - Skip targeted plate %s (number of characters is out of range)", m_sLogPrefix.c_str(), zone->Label(), detPlate.num.c_str());
                        // And continue with next detected plate (this one is not valid)
                        break;
                    }
                    // Overwrite detected plate by target if we assume the matching is correct
                    if (pluginConfig[n_zone].assumeTargets)
                    {
                        detPlate.conf = 100;
                        detPlate.num = *it;
                    }
                    // Add targeted plate to list and continue with next detected plate
                    addPlate(zone, n_zone, detPlate);
                    cntDetPlates++;
                    isTarget = true;
                    break;
                }
            }

            // Skip detected plate if already added or if only targeted plates are accepted
            if (isTarget || pluginConfig[n_zone].onlyTargets)
                continue;

            // Disqualify plate if under the minimum confidence level
            if (detPlate.conf < pluginConfig[n_zone].minConfidence)
            {
                Debug(1, "%s: Zone %s - Skip plate %s (under minimum confidence level)", m_sLogPrefix.c_str(), zone->Label(), detPlate.num.c_str());
                continue;
            }

            // Disqualify plate if not enough characters or too much characters
            if ((detPlate.num.size() < pluginConfig[n_zone].minCharacters)
                    || (detPlate.num.size() > pluginConfig[n_zone].maxCharacters))
            {
                Debug(1, "%s: Zone %s - Skip plate %s (number of characters is out of range)", m_sLogPrefix.c_str(), zone->Label(), detPlate.num.c_str());
                continue;
            }

            // Add plate to list (if already in list, update confidence by adding new value)
            addPlate(zone, n_zone, detPlate);
            cntDetPlates++;
        }

        if (cntDetPlates == 0)
            continue;

        // Raise an alarm if at least one plate has been detected
        score = pluginConfig[n_zone].alarmScore;

        // Fill a polygon with object in the mask
        Polygon platePolygon = Polygon(4, rectVertCoords);
        pMaskImage->Fill(WHITE, 1, platePolygon);
    }

    if (score == 0) {
        delete pMaskImage;
        delete tempZmImage;
        return false;
    }

    zone->SetScore((int)score);

    //Get mask by highlighting contours of objects and overlaying them with previous contours.
    Rgb alarm_colour = RGB_GREEN;
    Image *hlZmImage = pMaskImage->HighlightEdges(alarm_colour, ZM_COLOUR_RGB24,
                                                 ZM_SUBPIX_ORDER_RGB, &zone_polygon.Extent());

    if (zone->Alarmed()) {
        // if there were previous detection and they have already set up alarm image
        // then overlay it with current mask
        Image* pPrevZoneMask = new Image(*(zone->AlarmImage()));
        pPrevZoneMask->Overlay(*hlZmImage);
        zone->SetAlarmImage(pPrevZoneMask);
        delete pPrevZoneMask;
    } else
        zone->SetAlarmImage(hlZmImage);

    delete pMaskImage;
    delete tempZmImage;

    return true;
}


bool OpenALPRPlugin::addPlate(Zone *zone, unsigned int n_zone, strPlate detPlate)
{
    for (std::vector<strPlate>::iterator it = plateList[n_zone].begin(); it != plateList[n_zone].end(); ++it)
    {
        // If plate number exists in the list for this zone
        if ((*it).num == detPlate.num)
        {
            // Add confidence
            (*it).conf += detPlate.conf;
            Debug(1, "%s: Zone %s - Raise confidence of plate %s to %f", m_sLogPrefix.c_str(), zone->Label(), (*it).num.c_str(), (*it).conf);
            return false;
        }
    }

    // Add a new plate for this zone
    Debug(1, "%s: Zone %s - Add plate %s with confidence %f", m_sLogPrefix.c_str(), zone->Label(), detPlate.num.c_str(), detPlate.conf);
    plateList[n_zone].push_back(detPlate);

    return true;
}
