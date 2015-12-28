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

#ifndef OPENALPR_PLUGIN_H
#define OPENALPR_PLUGIN_H

#include <stdexcept>
#include <fstream>
#include <algorithm>

#include <alpr.h>

#include <opencv2/core/core.hpp>
#include <opencv2/imgproc/imgproc.hpp>
#include <opencv2/highgui/highgui.hpp>

#include <boost/algorithm/string.hpp>
#include <boost/program_options.hpp>
#include <boost/program_options/option.hpp>
#include <boost/program_options/options_description.hpp>
#include <boost/program_options/errors.hpp>

#include "zm_plugin_manager.h"
#include "zm_detector.h"
#include "zm_rgb.h"

#define DEFAULT_CONFIG_FILE "/etc/openalpr/openalpr.conf"
#define DEFAULT_COUNTRY_CODE "us"
#define DEFAULT_TEMPLATE_REGION ""
#define DEFAULT_TOPN 10
#define DEFAULT_DETECT_REGION 0
#define DEFAULT_DET_CAUSE "Plate Detected"
#define DEFAULT_PLUGIN_LOG_PREFIX "OPENALPR PLUGIN"

#define DEFAULT_ALARM_SCORE 99
#define DEFAULT_ASSUME_TARGETS 0
#define DEFAULT_MAX_CHARACTERS 99
#define DEFAULT_MIN_CHARACTERS 0
#define DEFAULT_MIN_CONFIDENCE 0
#define DEFAULT_ONLY_TARGETS 0
#define DEFAULT_STRICT_TARGETS 0

//! OpenALPR plugin class.
/*! The class derived from Detector.
 *  This class provides license plate detection based on tesseract OCR.
 */
class OpenALPRPlugin : public Detector {
  public:

    //! Default Constructor.
    OpenALPRPlugin();

    //! Constructor.
    OpenALPRPlugin(std::string sConfigSectionName);

    //! Destructor.
    virtual ~OpenALPRPlugin();

    //! Copy constructor.
    OpenALPRPlugin(const OpenALPRPlugin& source);

    //! Overloaded operator=.
    OpenALPRPlugin& operator=(const OpenALPRPlugin& source);

    int loadConfig(std::string sConfigFileName, std::map<unsigned int,std::map<std::string,std::string> > mapPluginConf);

protected:

    void onCreateEvent(Zone *zone, unsigned int n_zone, Event *event);
    void onCloseEvent(Zone *zone, unsigned int n_zone, Event *event, std::string &noteText);
    bool checkZone(Zone *zone, unsigned int n_zone, const Image *zmImage);

    std::string m_sConfigFilePath;
    std::string m_sCountry;
    std::string m_sRegionTemplate;
    unsigned int m_nMaxPlateNumber;
    bool m_bRegionIsDet;

private:

    alpr::Alpr *ptrAlpr;

    struct pConf
    {
        unsigned int alarmScore;
        bool assumeTargets;
        unsigned int maxCharacters;
        unsigned int minCharacters;
        unsigned int minConfidence;
        bool onlyTargets;
        bool strictTargets;
        std::vector<std::string> targetList;
        pConf():
            alarmScore(DEFAULT_ALARM_SCORE),
            assumeTargets(DEFAULT_ASSUME_TARGETS),
            maxCharacters(DEFAULT_MAX_CHARACTERS),
            minCharacters(DEFAULT_MIN_CHARACTERS),
            minConfidence(DEFAULT_MIN_CONFIDENCE),
            onlyTargets(DEFAULT_ONLY_TARGETS),
            strictTargets(DEFAULT_STRICT_TARGETS)
        {}
    };

    std::vector<pConf> pluginConfig;

    struct strPlate
    {
        std::string num;
        float conf;
    };

    struct sortByConf
    {
        bool operator()(const strPlate a, const strPlate b) const
        {
            return a.conf > b.conf;
        }
    };

    std::vector<std::vector<strPlate> > plateList;
    std::vector<std::vector<std::string> > tmpPlateList;

    bool addPlate(Zone *zone, unsigned int n_zone, strPlate detPlate);

};
#endif // OPENALPR_PLUGIN_H
