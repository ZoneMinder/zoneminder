#ifndef HAAR_DETECTOR_PLUGIN_H
#define HAAR_DETECTOR_PLUGIN_H


#include <cv.h>
#include <stdexcept>
#include <fstream>
#include <algorithm>


#include <boost/program_options.hpp>
#include <boost/program_options/option.hpp>
#include <boost/program_options/options_description.hpp>
#include <boost/program_options/errors.hpp>

#include "zm_plugin_manager.h"
#include "zm_detector.h"
#include "zm_rgb.h"



#define DETECTED_CAUSE "Face Detected"
#define LOG_PREFIX "ZM FACEDET PLUGIN"


#define DEFAULT_HAAR_SCALE_FACTOR 1.1
#define DEFAULT_HAAR_MIN_NEIGHBORS 3
#define DEFAULT_HAAR_FLAG CV_HAAR_DO_CANNY_PRUNING
#define DEFAULT_DETECTOR_MIN_OBJECT_SIZE_WIDTH 5.0
#define DEFAULT_DETECTOR_MIN_OBJECT_SIZE_HEIGHT 6.0
#define DEFAULT_HAAR_FACE_CASCADE_PATH "/usr/local/share/opencv/haarcascades/haarcascade_frontalface_alt2.xml"




using namespace std;
using namespace boost::program_options;


//! Face detector plugin class.
/*! The class derived from Detector.
 *  This class provides face detection based on OpenCV's implementation of Haar cascade classifier detector.
 */
class FaceDetectorPlugin : public Detector {
  public:

    //! Default Constructor.
    FaceDetectorPlugin();

    //! Constructor.
    FaceDetectorPlugin(string sConfigSectionName);

    //! Destructor.
    virtual ~FaceDetectorPlugin();

    //! Copy constructor.
    FaceDetectorPlugin(const FaceDetectorPlugin& source);

    //! Overloaded operator=.
    FaceDetectorPlugin& operator=(const FaceDetectorPlugin& source);

    void loadConfig(string sConfigFileName);

protected:

    bool checkZone(Zone *zone, const Image *zmImage);

    //! Path to the xml file with cascade.
    string m_sHaarCascadePath;

    //! Cascade of classifiers.
    CvHaarClassifierCascade* m_cascade;

    //! Scale factor.
    double m_fScaleFactor;

    //! Minimum number (minus 1) of neighbors rectangles that makes up an object.
    size_t m_nMinNeighbors;

    //! Mode of operation.
    int m_nFlag;

    //! Minimal object's sizes.
    double m_fMinObjWidth;
    double m_fMinObjHeight;

    //! Pointer to storage for calculations.
    CvMemStorage* m_pStorage;

private:
    
    //! Load xml Haar cascade file.
    void _loadHaarCascade(string sConfigPath);

    //! Detect faces on OpenCV' CvMat image.
    vector<CvRect> _opencvHaarDetect(const CvMat* pMatImage);
};



#endif //HAAR_DETECTOR_PLUGIN_H

