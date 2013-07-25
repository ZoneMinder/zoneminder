#ifndef ZM_IMAGE_ANALYSER_H
#define ZM_IMAGE_ANALYSER_H



#include <list>
#include <string>
#include <stdexcept>
#include <iostream>
#include <memory>

#include "zm.h"
#include "zm_detector.h"
#include "zm_image.h"
#include "zm_zone.h"
#include "zm_event.h"



using namespace std;


//! List of available detectors.
typedef std::list<Detector *> DetectorsList;


//! Class for handling image detection.
/*! Contains all detectors loaded from plugins.
 */
class ImageAnalyser {
  public:
    
    //!Default constructor.
    ImageAnalyser() {};

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
    int DoDetection(const Image &comp_image, Zone** zones, int n_numZones, Event::StringSetMap noteSetMap, std::string& det_cause);

    //! Configure all loaded plugins using given configuration file.
    void configurePlugins(string sConfigFileName);

private: 

    //! All available detectors.
    DetectorsList m_Detectors;
};



#endif //ZM_IMAGE_ANALYSER_H
