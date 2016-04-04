#include "zm_image_analyser.h"



/*!\fn ImageAnalyser::ImageAnalyser(const ImageAnalyser& source)
 * \param source is the object to copy
 */
ImageAnalyser::ImageAnalyser(const ImageAnalyser& source)
{
  m_Detectors = source.m_Detectors;
}



/*!\fn ImageAnalyser::operator=(const ImageAnalyser& source)
 * \param source is the object to copy
 */
ImageAnalyser& ImageAnalyser::operator=(const ImageAnalyser& source)
{
  m_Detectors = source.m_Detectors;
  return *this;
}



ImageAnalyser::~ImageAnalyser()
{
  for(DetectorsList::reverse_iterator It = m_Detectors.rbegin();
    It != m_Detectors.rend();
    ++It)
    delete *It;
}



/*!\fn ImageAnalyser::DoDetection(const Image &comp_image, Zone** zones, int n_numZones, Event::StringSetMap noteSetMap, std::string& det_cause)
 * \param comp_image is the image to analyse
 * \param zones is the zones array to analyse
 * \param n_numZones is the number of zones
 * \param noteSetMap is the map of events descriptions
 * \param det_cause is a string describing detection cause
 */
int ImageAnalyser::DoDetection(const Image &comp_image, Zone** zones, int n_numZones, Event::StringSetMap noteSetMap, std::string& det_cause)
{
  Event::StringSet zoneSet;
  int score = 0;

  for(DetectorsList::iterator It = m_Detectors.begin();
    It != m_Detectors.end();
    ++It)
  {
    int detect_score = (*It)->Detect(comp_image, zones, n_numZones, zoneSet);
    if (detect_score)
    {
      score += detect_score;
      noteSetMap[(*It)->getDetectionCause()] = zoneSet;
      if (det_cause.length())
        det_cause += ", ";
      det_cause += (*It)->getDetectionCause();
    }
  }
  return score;
}

