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

#ifndef ANPR_PLUGIN_H
#define ANPR_PLUGIN_H

#include <cv.h>
//#include <liprec.h>
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

#define DETECTED_CAUSE "Plate Detected"
#define LOG_PREFIX "ZM PLATEDEC PLUGIN"


#define DEFAULT_DETECTOR_MIN_OBJECT_SIZE 600
#define DEFAULT_DETECTOR_MAX_OBJECT_SIZE 6000
#define DEFAULT_ALARM_SCORE 99

using namespace std;
using namespace boost::program_options;


//! Face detector plugin class.
/*! The class derived from Detector.
 *  This class provides face detection based on OpenCV's implementation of Haar cascade classifier detector.
 */
class ANPRPlugin : public Detector {
  public:

    //! Default Constructor.
    ANPRPlugin();

    //! Constructor.
    ANPRPlugin(string sConfigSectionName);

    //! Destructor.
    virtual ~ANPRPlugin();

    //! Copy constructor.
    ANPRPlugin(const ANPRPlugin& source);

    //! Overloaded operator=.
    ANPRPlugin& operator=(const ANPRPlugin& source);

    void loadConfig(string sConfigFileName);

protected:

    bool checkZone(Zone *zone, const Image *zmImage);

    int m_nMinObjSize;
    int m_nMaxObjSize;

    int m_nAlarmScore;

};



#endif // ANPR_PLUGIN_H

