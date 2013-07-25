/***********************************************************************
    This file is part of libzm_anpr_plugin, License Plate REcognition.

    Copyright (C) 2012 Franco (nextime) Lanza <nextime@nexlab.it>

    LiPRec is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    LiPRec is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with LiPRec.  If not, see <http://www.gnu.org/licenses/>.
************************************************************************/


#define LIST LIPREC_LIST // This is a workaround to avoid the conflict 
#include <liprec.h>      // of typedef LIST in both mysql and tesseract headers...
//#include "opencv2/highgui/highgui.hpp"
#undef LIST
#include "anpr_plugin.h"

//! Retrieve the engine version we're going to expect
extern "C" int getEngineVersion()
{
  return ZM_ENGINE_VERSION;
}

//! Tells us to register our functionality to an engine kernel
extern "C" void registerPlugin(PluginManager &PlM, string sPluginName)
{
  PlM.getImageAnalyser().addDetector(
    auto_ptr<Detector>(new ANPRPlugin(sPluginName))
  );
}


using namespace cv;
//using namespace liprec;

ANPRPlugin::ANPRPlugin()
  : Detector(),
    m_nMinObjSize(DEFAULT_DETECTOR_MIN_OBJECT_SIZE),
    m_nMaxObjSize(DEFAULT_DETECTOR_MAX_OBJECT_SIZE)
{
    m_sDetectionCause = DETECTED_CAUSE;
    m_sLogPrefix = LOG_PREFIX;


    log(LOG_NOTICE, "License Plate Recognition  Plugin\'s  Object has been created.");
}




ANPRPlugin::ANPRPlugin(string sPluginName)
  : Detector(sPluginName),
    m_nMinObjSize(DEFAULT_DETECTOR_MIN_OBJECT_SIZE),
    m_nMaxObjSize(DEFAULT_DETECTOR_MAX_OBJECT_SIZE)
{
    m_sDetectionCause = DETECTED_CAUSE;
    m_sLogPrefix = LOG_PREFIX;

    log(LOG_NOTICE, "License Plate Recognition Plugin\'s  Object has been created.");
}



/*! \fn ANPRPlugin::loadConfig(string sConfigFileName)
 *  \param sConfigFileName is path to configuration to load parameters from
 */
void ANPRPlugin::loadConfig(string sConfigFileName)
{
    options_description config_file("Configuration file options.");
    variables_map vm;
    config_file.add_options()
        ((m_sConfigSectionName + string(".min-obj-size")).c_str(),
            value<int>()->default_value(DEFAULT_DETECTOR_MIN_OBJECT_SIZE))
        ((m_sConfigSectionName + string(".max-obj-size")).c_str(),
            value<int>()->default_value(DEFAULT_DETECTOR_MAX_OBJECT_SIZE))
        ((m_sConfigSectionName + string(".alarm-score")).c_str(),
            value<int>()->default_value(DEFAULT_ALARM_SCORE))
        ((m_sConfigSectionName + string(".det-cause")).c_str(),
            value<string>()->default_value(DETECTED_CAUSE))
        ((m_sConfigSectionName + string(".log-prefix")).c_str(),
            value<string>()->default_value(LOG_PREFIX))
    ;
    ifstream ifs(sConfigFileName.c_str());
    store(parse_config_file(ifs, config_file, true), vm);
    notify(vm);

    m_nMinObjSize = vm[(m_sConfigSectionName + string(".min-obj-size")).c_str()].as<int>();
    m_nMaxObjSize = vm[(m_sConfigSectionName + string(".max-obj-size")).c_str()].as<int>();
    m_nAlarmScore = vm[(m_sConfigSectionName + string(".alarm-score")).c_str()].as<int>();

    m_sDetectionCause = vm[(m_sConfigSectionName + string(".det-cause")).c_str()].as<string>();
    m_sLogPrefix = vm[(m_sConfigSectionName + string(".log-prefix")).c_str()].as<string>();
    zmLoadConfig();
    log(LOG_NOTICE, "License Plate Recognition Plugin\'s  Object is configured.");
}



ANPRPlugin::~ANPRPlugin()
{
}


/*! \fn ANPRPlugin::ANPRPlugin(const ANPRPlugin& source)
 *  \param source is the object for copying
 */
ANPRPlugin::ANPRPlugin(const ANPRPlugin& source)
  : Detector(source),
    m_nMinObjSize(source.m_nMinObjSize),
    m_nMaxObjSize(source.m_nMaxObjSize)
{
}



/*! \fn ANPRPlugin:: operator=(const ANPRPlugin& source)
 *  \param source is the object for copying
 */
ANPRPlugin & ANPRPlugin:: operator=(const ANPRPlugin& source)
{
    Detector::operator=(source);
    m_nMinObjSize = source.m_nMinObjSize;
    m_nMaxObjSize = source.m_nMaxObjSize;
    return *this;
}




/*! \fn ANPRPlugin::checkZone(Zone *zone, const Image *zmImage)
 *  \param zone is a zone where faces will be detected
 *  \param zmImage is an image to perform face detection (in the form of ZM' Image)
 *  \return true if there were objects detected in given image and
 *          false otherwise
 */
bool ANPRPlugin::checkZone(Zone *zone, const Image *zmImage)
{
   
    double score;
    Polygon zone_polygon = Polygon(zone->GetPolygon()); // Polygon of interest of the processed zone.

    Image *pMaskImage = new Image(zmImage->Width(), zmImage->Height(), ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE );
    pMaskImage->Fill(BLACK);
    // An temporary image in the form of ZM for making from it CvMat.
    // If don't use temp image, after rgb->bgr it will change.
    Image *tempZmImage = new Image(*zmImage);
    int imgtype=CV_8UC1;
    if (tempZmImage->Colours() == ZM_COLOUR_RGB24)
      imgtype=CV_8UC3;
    Mat cvInputImage = Mat(
                           tempZmImage->Height(),
                           tempZmImage->Width(),
                           imgtype, (unsigned char*)tempZmImage->Buffer()).clone();
    //Mat cvInputImage = cvtmpInputImage.reshape(0, tempZmImage->Colours());
    //Mat cvInputImage = cvtmpInputImage.reshape(0, tempZmImage->Height());
    if (tempZmImage->Colours() == ZM_COLOUR_RGB24)
    {
      cvtColor(cvInputImage, cvInputImage, CV_RGB2BGR);
    }
    //imwrite("/tmp/sarca.jpg", cvInputImage);
    //Process image
    liprec::LiPRec plateDetector;
    liprec::PlatesImage plates;
    plateDetector.detectPlates(cvInputImage, &plates);
    score = 0;
    if(plates.plates.size() > 0) {
      log(LOG_INFO, "PLATES WERE DETECTED");
      for(unsigned int i=0;i<plates.plates.size();i++) {

        // Check if object's rectangle is inside zone's polygon of interest.
        int x1 = plates.plates[i].rect.x, x2 = int(plates.plates[i].rect.x+plates.plates[i].rect.width);
        int y1 = plates.plates[i].rect.y, y2 = int(plates.plates[i].rect.y+plates.plates[i].rect.height);
        Coord rectVertCoords[4] = {Coord(x1, y1), Coord(x1, y2), Coord(x2, y1), Coord(x2, y2)};
        int nNumVertInside = 0;
        for (int p = 0; p < 4; p++)
        {
            nNumVertInside += zone_polygon.isInside(rectVertCoords[p]);
        }
        if (nNumVertInside < 3)
        // if at least three rectangle coordinates are inside polygon, 
        // consider rectangle as belonging to the zone
        // otherwise process next object
            continue;
        log(LOG_INFO, plates.plates[i].platetxt);
        // Fill a box with object in the mask
        Box *plateBox = new Box(x1, y1, x2, y2);
        pMaskImage->Fill(WHITE, plateBox);
        score=m_nAlarmScore;
        delete plateBox;
      }
    }


    if (score == 0)
    {
        //log(LOG_DEBUG, "No objects found. Exit.");
        delete pMaskImage;
        delete tempZmImage;

        // XXX We need to delete Mats?

        return( false );
    }
    /*
    else
    {
        zone->SetOverloadCount(zone->GetOverloadFrames());
        delete pMaskImage;
        delete tempZmImage;

        return( false );
    }*/


    zone->SetScore((int)score);

    //Get mask by highlighting contours of objects and overlaying them with previous contours.
    Rgb alarm_colour = RGB_GREEN;
	 Image *hlZmImage = pMaskImage->HighlightEdges(alarm_colour, ZM_COLOUR_RGB24, 
                                                 ZM_SUBPIX_ORDER_RGB, &zone_polygon.Extent());

    if (zone->Alarmed())
    {
        // if there were previous detection and they have already set up alarm image
        // then overlay it with current mask
        Image* pPrevZoneMask = new Image(*(zone->AlarmImage()));
        pPrevZoneMask->Overlay(*hlZmImage);
        zone->SetAlarmImage(pPrevZoneMask);
        delete pPrevZoneMask;
    }
    else
        zone->SetAlarmImage(hlZmImage);

    delete pMaskImage;
    delete hlZmImage;
    delete tempZmImage;

    return true;
}


