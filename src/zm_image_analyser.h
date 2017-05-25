#ifndef ZM_IMAGE_ANALYSER_H
#define ZM_IMAGE_ANALYSER_H



#include <list>
#include <string>
#include <stdexcept>
#include <memory>

#include "zm.h"
#include "zm_image.h"
#include "zm_zone.h"
#include "zm_event.h"



using namespace std;



//! Class for handling image detection.
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

private: 

};



#endif //ZM_IMAGE_ANALYSER_H
