#include "face_detector_plugin.h"



//! Retrieve the engine version we're going to expect
extern "C" int getEngineVersion()
{
  return ZM_ENGINE_VERSION;
}

//! Tells us to register our functionality to an engine kernel
extern "C" void registerPlugin(PluginManager &PlM, string sPluginName)
{
  PlM.getImageAnalyser().addDetector(
    auto_ptr<Detector>(new FaceDetectorPlugin(sPluginName))
  );
}




FaceDetectorPlugin::FaceDetectorPlugin()
  : Detector(),
    m_fScaleFactor(DEFAULT_HAAR_SCALE_FACTOR),
    m_nMinNeighbors(DEFAULT_HAAR_MIN_NEIGHBORS),
    m_nFlag(DEFAULT_HAAR_FLAG),
    m_fMinObjWidth(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_WIDTH),
    m_fMinObjHeight(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_HEIGHT),
    m_cascade(NULL),
    m_sHaarCascadePath(DEFAULT_HAAR_FACE_CASCADE_PATH)
{
    m_sDetectionCause = DETECTED_CAUSE;
    m_sLogPrefix = LOG_PREFIX;

    //openlog(m_sLogPrefix.c_str(), LOG_PID|LOG_CONS, LOG_USER);
    m_pStorage = cvCreateMemStorage(0);
    //_loadHaarCascade(m_sHaarCascadePath);
    log(LOG_NOTICE, "Face Detector Plugin\'s  Object has been created.");
}




FaceDetectorPlugin::FaceDetectorPlugin(string sPluginName)
  : Detector(sPluginName),
    m_fScaleFactor(DEFAULT_HAAR_SCALE_FACTOR),
    m_nMinNeighbors(DEFAULT_HAAR_MIN_NEIGHBORS),
    m_nFlag(DEFAULT_HAAR_FLAG),
    m_fMinObjWidth(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_WIDTH),
    m_fMinObjHeight(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_HEIGHT),
    m_cascade(NULL),
    m_sHaarCascadePath(DEFAULT_HAAR_FACE_CASCADE_PATH)
{
    m_sDetectionCause = DETECTED_CAUSE;
    m_sLogPrefix = LOG_PREFIX;

    //openlog(m_sLogPrefix.c_str(), LOG_PID|LOG_CONS, LOG_USER);
    m_pStorage = cvCreateMemStorage(0);
    //_loadHaarCascade(m_sHaarCascadePath);
    log(LOG_NOTICE, "Face Detector Plugin\'s  Object has been created.");
}



/*! \fn FaceDetectorPlugin::loadConfig(string sConfigFileName)
 *  \param sConfigFileName is path to configuration to load parameters from
 */
void FaceDetectorPlugin::loadConfig(string sConfigFileName)
{
    options_description config_file("Configuration file options.");
    variables_map vm;
    config_file.add_options()
    // Haar face detector options
        ((m_sConfigSectionName + string(".cascade")).c_str(),
            value<string>()->default_value(DEFAULT_HAAR_FACE_CASCADE_PATH))
        ((m_sConfigSectionName + string(".scale")).c_str(),
            value<double>()->default_value(DEFAULT_HAAR_SCALE_FACTOR))
        ((m_sConfigSectionName + string(".flag")).c_str(),
            value<int>()->default_value(DEFAULT_HAAR_FLAG))
        ((m_sConfigSectionName + string(".min-nbrs")).c_str(),
            value<int>()->default_value(DEFAULT_HAAR_MIN_NEIGHBORS))
        ((m_sConfigSectionName + string(".min-obj-width")).c_str(),
            value<double>()->default_value(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_WIDTH))
        ((m_sConfigSectionName + string(".min-obj-height")).c_str(),
            value<double>()->default_value(DEFAULT_DETECTOR_MIN_OBJECT_SIZE_HEIGHT))
        ((m_sConfigSectionName + string(".min-alarm-score")).c_str(),
            value<double>()->default_value(DEFAULT_MIN_ALARM_SCORE))
        ((m_sConfigSectionName + string(".max-alarm-score")).c_str(),
            value<double>()->default_value(DEFAULT_MAX_ALARM_SCORE))
        ((m_sConfigSectionName + string(".image-scale-factor")).c_str(),
            value<double>()->default_value(DEFAULT_IMAGE_SCALE_FACTOR))
        ((m_sConfigSectionName + string(".det-cause")).c_str(),
            value<string>()->default_value(DETECTED_CAUSE))
        ((m_sConfigSectionName + string(".log-prefix")).c_str(),
            value<string>()->default_value(LOG_PREFIX))
    ;
    ifstream ifs(sConfigFileName.c_str());
    store(parse_config_file(ifs, config_file, true), vm);
    notify(vm);

    m_fScaleFactor = vm[(m_sConfigSectionName + string(".scale")).c_str()].as<double>();
    m_nMinNeighbors = vm[(m_sConfigSectionName + string(".min-nbrs")).c_str()].as<int>();
    m_nFlag = vm[(m_sConfigSectionName + string(".flag")).c_str()].as<int>();
    m_fMinObjWidth = vm[(m_sConfigSectionName + string(".min-obj-width")).c_str()].as<double>();
    m_fMinObjHeight = vm[(m_sConfigSectionName + string(".min-obj-height")).c_str()].as<double>();
    m_fMinAlarmScore = vm[(m_sConfigSectionName + string(".min-alarm-score")).c_str()].as<double>();
    m_fMaxAlarmScore = vm[(m_sConfigSectionName + string(".max-alarm-score")).c_str()].as<double>();
    m_fImageScaleFactor = vm[(m_sConfigSectionName + string(".image-scale-factor")).c_str()].as<double>();

    m_sDetectionCause = vm[(m_sConfigSectionName + string(".det-cause")).c_str()].as<string>();
    m_sLogPrefix = vm[(m_sConfigSectionName + string(".log-prefix")).c_str()].as<string>();

//    if (m_sHaarCascadePath != vm[(m_sConfigSectionName + string(".cascade")).c_str()].as<string>())
//    {
        m_sHaarCascadePath = vm[(m_sConfigSectionName + string(".cascade")).c_str()].as<string>();
        _loadHaarCascade(m_sHaarCascadePath);
//    }
    zmLoadConfig();
    log(LOG_NOTICE, "Face Detector Plugin\'s  Object is configured.");
}



FaceDetectorPlugin::~FaceDetectorPlugin()
{
    cvReleaseMemStorage(&m_pStorage);
    cvReleaseHaarClassifierCascade(&m_cascade);
}


/*! \fn FaceDetectorPlugin::FaceDetectorPlugin(const FaceDetectorPlugin& source)
 *  \param source is the object for copying
 */
FaceDetectorPlugin::FaceDetectorPlugin(const FaceDetectorPlugin& source)
  : Detector(source),
    m_fScaleFactor(source.m_fScaleFactor),
    m_nMinNeighbors(source.m_nMinNeighbors),
    m_nFlag(source.m_nFlag),
    m_fMinObjWidth(source.m_fMinObjWidth),
    m_fMinObjHeight(source.m_fMinObjHeight),
    m_cascade(NULL),
    m_sHaarCascadePath(source.m_sHaarCascadePath)
{
    m_pStorage = cvCreateMemStorage(0);
    if (m_sHaarCascadePath != string())
        _loadHaarCascade(m_sHaarCascadePath);
}



/*! \fn FaceDetectorPlugin:: operator=(const FaceDetectorPlugin& source)
 *  \param source is the object for copying
 */
FaceDetectorPlugin & FaceDetectorPlugin:: operator=(const FaceDetectorPlugin& source)
{
    Detector::operator=(source);
    m_fScaleFactor = source.m_fScaleFactor;
    m_nMinNeighbors = source.m_nMinNeighbors;
    m_nFlag = source.m_nFlag;
    m_fMinObjWidth = source.m_fMinObjWidth;
    m_fMinObjHeight = source.m_fMinObjHeight;
    m_cascade = NULL;
    m_sHaarCascadePath = source.m_sHaarCascadePath;
    
    m_pStorage = cvCreateMemStorage(0);
    
    if (m_sHaarCascadePath != string())
        _loadHaarCascade(m_sHaarCascadePath);
    
    return *this;
}



/*! \fn FaceDetectorPlugin::_loadHaarCascade(string sConfigPath)
 *  \param sConfigPath is path to xml Haar classifier cascade.
 */
void FaceDetectorPlugin::_loadHaarCascade(string sConfigPath)
{
    m_sHaarCascadePath = sConfigPath;
    if (m_cascade != NULL)
        cvReleaseHaarClassifierCascade(&m_cascade);
    
    m_cascade = (CvHaarClassifierCascade*)cvLoad(sConfigPath.c_str());
    if (m_cascade == NULL)
        throw invalid_argument((string("Couldn't load xml data: ") + sConfigPath + ".").c_str());
}




/*! \fn FaceDetectorPlugin::_opencvHaarDetect(const CvMat* pMatImage)
 *  \param pMatImage is an image to perform face detection (in the form of OpenCv' CvMat)
 */
vector<CvRect> FaceDetectorPlugin::_opencvHaarDetect(const CvMat* pMatImage)
{
    CvSize minObjSize = cvSize((int) pMatImage->width * m_fMinObjWidth / 100.0, (int) pMatImage->height * m_fMinObjHeight / 100.0);
    //char szMessage[50];
    //sprintf(szMessage, "IMAGE SIZE IS %d x %d \nMIN OBJ SIZE IS %d x %d", pMatImage->width, pMatImage->height, minObjSize.width, minObjSize.height);
    //log(LOG_WARNING, szMessage);

    CvSeq* haarObjects = cvHaarDetectObjects(pMatImage,
                                             m_cascade,
                                             m_pStorage,
                                             m_fScaleFactor,
                                             m_nMinNeighbors,
                                             m_nFlag,
                                             minObjSize);
    vector<CvRect> objects;
    for (int i = 0; i < haarObjects->total; i++)
    {
        CvRect* pHaarRect = (CvRect*)cvGetSeqElem(haarObjects, i);
        objects.push_back(*pHaarRect);
    }
    return objects;
}


/*! \fn FaceDetectorPlugin::checkZone(Zone *zone, const Image *zmImage)
 *  \param zone is a zone where faces will be detected
 *  \param zmImage is an image to perform face detection (in the form of ZM' Image)
 *  \return true if there were objects detected in given image and
 *          false otherwise
 */
bool FaceDetectorPlugin::checkZone(Zone *zone, const Image *zmImage)
{
    //log(LOG_DEBUG, "Entering checkZone.");
    double score;
    Polygon zone_polygon = Polygon(zone->GetPolygon()); // Polygon of interest of the processed zone.
    //char szMessage[50];
    //sprintf(szMessage, "Polygon of the zone has %d vertices.", zone_polygon.getNumCoords());
    //log(LOG_WARNING, szMessage);

	//zone->ResetStats();

    /*
    log(LOG_WARNING, "precheck");
    if ( !zone->CheckOverloadCount() )
    {
        log(LOG_WARNING, "CheckOverloadCount() return false, we'll return false.");
        return(false);
    }
    */
    //zmLoadConfig();
    // An image for highlighting detected objects.                                
    Image *pMaskImage = new Image(zmImage->Width(), zmImage->Height(), ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE );
    pMaskImage->Fill(BLACK);

    //log(LOG_WARNING, "FILLBLACK.");
    // An temporary image in the form of ZM for making from it CvMat.
    // If don't use temp image, after rgb->bgr it will change.
    Image *tempZmImage = new Image(*zmImage);
    CvMat* cvInputImage = NULL;
    CvMat* pScaledImage = NULL;

    bool bDoResizing = (m_fImageScaleFactor != 1.0);    // resize image or not

    if (tempZmImage->Colours() == ZM_COLOUR_GRAY8)
    {
        // if image is not colored, create an one-channel CvMat.
        cvInputImage = cvCreateMat(tempZmImage->Height(), tempZmImage->Width(), CV_8UC1);
        unsigned char *buffer = (unsigned char*)tempZmImage->Buffer();
        cvSetData(cvInputImage, buffer, tempZmImage->Width());
    }
    // NEXTIME XXX TODO: manage also 32 bit images!
    else
    {
        // otherwise create a three-channel CvMat and then convert colors from RGB to BGR.
        cvInputImage = cvCreateMat(tempZmImage->Height(), tempZmImage->Width(), CV_8UC3);
        unsigned char *buffer = (unsigned char*)tempZmImage->Buffer();
        cvSetData(cvInputImage, buffer, tempZmImage->Width() * 3);
        cvCvtColor(cvInputImage, cvInputImage, CV_RGB2BGR);
    }

    if (bDoResizing)
    {
        int nNewWidth = int (m_fImageScaleFactor * zmImage->Width());
        int nNewHeight = int (m_fImageScaleFactor * tempZmImage->Height());
        int nImageElemType = cvGetElemType(cvInputImage);
        pScaledImage = cvCreateMat(nNewHeight, nNewWidth, nImageElemType);
        cvResize(cvInputImage, pScaledImage, CV_INTER_LINEAR);
    }


    //Process image

    vector<CvRect> foundObjects;
    if (bDoResizing)
        foundObjects = _opencvHaarDetect(pScaledImage);
    else
        foundObjects = _opencvHaarDetect(cvInputImage);

    if (foundObjects.size() > 0)
        log(LOG_INFO, "OBJECTS WERE DETECTED");

    score = 0;
    for (vector<CvRect>::iterator it = foundObjects.begin(); it < foundObjects.end(); it++)
    {
        // Process found objects.

        // Scale object's coordinates back if image has been scaled.
        int x1 = int(it->x/m_fImageScaleFactor), x2 = int((it->x + it->width)/m_fImageScaleFactor), y1 = int(it->y/m_fImageScaleFactor), y2 = int((it->y + it->height)/m_fImageScaleFactor);
        
        // Check if object's rectangle is inside zone's polygon of interest.
        Coord rectVertCoords[4] = {Coord(x1, y1), Coord(x1, y2), Coord(x2, y1), Coord(x2, y2)};
        int nNumVertInside = 0;
        for (int i = 0; i < 4; i++)
        {
            nNumVertInside += zone_polygon.isInside(rectVertCoords[i]);
        }
        if (nNumVertInside < 3)
        // if at least three rectangle coordinates are inside polygon, consider rectangle as belonging to the zone
        // otherwise process next object
            continue;

        // Fill a box with object in the mask
        Box *faceBox = new Box(x1, y1, x2, y2);
        pMaskImage->Fill(WHITE, faceBox);
        // Calculate score as portion of object area in the image
        score += (100.0*(it->width)*(it->height)/m_fImageScaleFactor/m_fImageScaleFactor)/zone_polygon.Area();
        delete faceBox;
    }


    if (score == 0)
    {
        //log(LOG_DEBUG, "No objects found. Exit.");
        delete pMaskImage;
        delete tempZmImage;

        if (cvInputImage)
            cvReleaseMat(&cvInputImage);
        if (pScaledImage)
            cvReleaseMat(&pScaledImage);

        return( false );
    }



    if ( m_fMinAlarmScore && ( score < m_fMinAlarmScore) )
    {
        delete pMaskImage;
        delete tempZmImage;

        if (cvInputImage)
            cvReleaseMat(&cvInputImage);
        if (pScaledImage)
            cvReleaseMat(&pScaledImage);

        return( false );
    }
    if ( m_fMaxAlarmScore && (score > m_fMaxAlarmScore) )
    {
        zone->SetOverloadCount(zone->GetOverloadFrames());
        delete pMaskImage;
        delete tempZmImage;

        if (cvInputImage)
            cvReleaseMat(&cvInputImage);
        if (pScaledImage)
            cvReleaseMat(&pScaledImage);

        return( false );
    }


    zone->SetScore(max(1, (int)score));

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

    if (cvInputImage)
        cvReleaseMat(&cvInputImage);
    if (pScaledImage)
        cvReleaseMat(&pScaledImage);

    //log(LOG_DEBUG, "Leaving checkZone.");
    return true;
}


