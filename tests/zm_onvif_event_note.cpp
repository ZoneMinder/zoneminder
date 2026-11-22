/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#ifdef WITH_GSOAP
#include <memory>

#define private public
#define protected public
#include "zm_monitor_onvif.h"
#undef protected
#undef private

TEST_CASE("ONVIFEventNote", "[onvif]") {
  SECTION("Detection type in the topic: the active state adds nothing") {
    // Reolink
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/MyRuleDetector/PeopleDetect", {{"State", "true"}}, "")
            == "MyRuleDetector/PeopleDetect");
  }

  SECTION("Detection type in the item name is kept") {
    // Tapo sends one generic topic and names the detection in the item
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent",
                           {{"IsPeople", "true"}}, "MyTPSmartEventDetectorRule")
            == "TPSmartEventDetector/TPSmartEvent IsPeople (MyTPSmartEventDetectorRule)");
  }

  SECTION("Detection type in the item value is kept") {
    // Ajax / Profile M object classification
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/ObjectDetection/Object",
                           {{"ClassTypes", "Human Vehicle"}}, "AjaxObjectRule")
            == "ObjectDetection/Object ClassTypes=Human Vehicle (AjaxObjectRule)");
  }

  SECTION("Namespace prefixes are stripped from every segment") {
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/tnsajax:MotionDetector/Detection", {{"Detected", "true"}}, "")
            == "MotionDetector/Detection Detected");
    REQUIRE(ONVIFEventNote("tns1:Device/Trigger/tnshik:AlarmIn", {{"State", "true"}}, "")
            == "Device/Trigger/AlarmIn");
  }

  SECTION("Only the RuleEngine root is dropped") {
    REQUIRE(ONVIFEventNote("tns1:VideoSource/MotionAlarm", {{"State", "true"}}, "")
            == "VideoSource/MotionAlarm");
    REQUIRE(ONVIFEventNote("tns1:RuleEngine", {{"State", "true"}}, "") == "RuleEngine");
  }

  SECTION("Generic state items are dropped only when they say active") {
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/MotionRegionDetector/Motion", {{"State", "1"}}, "")
            == "MotionRegionDetector/Motion");
    REQUIRE(ONVIFEventNote("tns1:Device/Trigger/Relay", {{"LogicalState", "active"}}, "")
            == "Device/Trigger/Relay");
    REQUIRE(ONVIFEventNote("tns1:Device/Trigger/DigitalInput", {{"State", "Open"}}, "")
            == "Device/Trigger/DigitalInput State=Open");
  }

  SECTION("Items are dropped by the same inactive words as the alarm state") {
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent",
                           {{"IsVehicle", "false"}, {"IsMotion", "Idle"}, {"Count", "00"}, {"IsPeople", "true"}}, "")
            == "TPSmartEventDetector/TPSmartEvent IsPeople");
  }

  SECTION("Per-object ids, empty values and padding stay out of the note") {
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/LineDetector/Crossed", {{"ObjectId", "17"}}, "MyLineDetectorRule")
            == "LineDetector/Crossed (MyLineDetectorRule)");
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/Foo/Bar", {{"Zone", ""}, {"Direction", " Left \n"}}, "")
            == "Foo/Bar Direction=Left");
  }

  SECTION("A value found without a name") {
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/Foo/Bar", {{"", "true"}}, "") == "Foo/Bar");
    REQUIRE(ONVIFEventNote("tns1:RuleEngine/Foo/Bar", {{"", "LeftToRight"}}, "") == "Foo/Bar LeftToRight");
  }

  SECTION("A topic without prefixes or items is left as it is") {
    REQUIRE(ONVIFEventNote("VideoSource/MotionAlarm", {}, "") == "VideoSource/MotionAlarm");
  }

  SECTION("A topic that reduces to nothing is used as it is") {
    REQUIRE(ONVIFEventNote(" tns1: ", {{"State", "true"}}, "") == "tns1:");
    REQUIRE(ONVIFEventNote("///", {}, "") == "///");
  }

  SECTION("Whitespace around the topic and its segments is ignored") {
    REQUIRE(ONVIFEventNote("\n  tns1:RuleEngine/MyRuleDetector/Package \n", {{"State", "true"}}, "")
            == "MyRuleDetector/Package");
  }
}

TEST_CASE("ONVIF interpret_alarm_value", "[onvif]") {
  // The alarm state decision these words now share with the event note; it
  // must not have changed.
  for (const char *off : {"", "false", "FALSE", "inactive", "off", "no", "idle", "Normal", "closed", "0", "000"})
    REQUIRE_FALSE(ONVIF::interpret_alarm_value(off));
  for (const char *on : {"true", "active", "1", "001", "LeftToRight", "Human Vehicle", "open"})
    REQUIRE(ONVIF::interpret_alarm_value(on));
}

namespace {
struct SoapDeleter {
  void operator()(struct soap *soap) const {
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
  }
};

// A notification as a camera would send it: topic, tt:Source items, tt:Data
// items. Allocated on soap, so freed with it.
wsnt__NotificationMessageHolderType *MakeMessage(struct soap *soap, const char *topic,
    const std::vector<ONVIFEventItem> &source, const std::vector<ONVIFEventItem> &data) {
  auto *msg = soap_new_wsnt__NotificationMessageHolderType(soap);
  msg->Topic = soap_new_wsnt__TopicExpressionType(soap);
  msg->Topic->__any.text = soap_strdup(soap, topic);

  struct soap_dom_element *message = &msg->Message.__any;
  soap_elt_set(message, nullptr, "tt:Message");
  soap_att_text(soap_att(message, nullptr, "PropertyOperation"), "Changed");
  auto add_items = [&](const char *parent_tag, const std::vector<ONVIFEventItem> &items) {
    struct soap_dom_element *parent = soap_elt(message, nullptr, parent_tag);
    for (size_t i = 0; i < items.size(); i++) {
      struct soap_dom_element *item = soap_nth_elt(parent, nullptr, "tt:SimpleItem", i + 1);
      soap_att_text(soap_att(item, nullptr, "Name"), items[i].name.c_str());
      soap_att_text(soap_att(item, nullptr, "Value"), items[i].value.c_str());
    }
  };
  add_items("tt:Source", source);
  add_items("tt:Data", data);
  return msg;
}
}  // namespace

TEST_CASE("ONVIF parse_event_message keeps every Data item and the Rule", "[onvif]") {
  std::unique_ptr<struct soap, SoapDeleter> soap(soap_new());
  std::string topic, value, operation, rule;
  std::vector<ONVIFEventItem> items;

  SECTION("Tapo") {
    auto *msg = MakeMessage(soap.get(), "tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent",
                            {{"VideoSourceConfigurationToken", "vsconf"}, {"Rule", "MyTPSmartEventDetectorRule"}},
                            {{"IsPeople", "true"}});
    REQUIRE(ONVIF::parse_event_message(msg, topic, value, operation, items, rule));
    REQUIRE(topic == "tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent");
    REQUIRE(operation == "Changed");
    REQUIRE(value == "true");
    REQUIRE(rule == "MyTPSmartEventDetectorRule");
    REQUIRE(ONVIFEventNote(topic, items, rule)
            == "TPSmartEventDetector/TPSmartEvent IsPeople (MyTPSmartEventDetectorRule)");
  }

  SECTION("The alarm value is still the first state item, all items reach the note") {
    auto *msg = MakeMessage(soap.get(), "tns1:RuleEngine/ObjectDetection/Object",
                            {{"Rule", "AjaxObjectRule"}},
                            {{"ClassTypes", "Human Vehicle"}, {"ObjectId", "3"}, {"IsInside", "true"}});
    REQUIRE(ONVIF::parse_event_message(msg, topic, value, operation, items, rule));
    REQUIRE(value == "3");  // ObjectId is the first item is_data_simple_item accepts
    REQUIRE(items.size() == 3);
    REQUIRE(ONVIFEventNote(topic, items, rule)
            == "ObjectDetection/Object ClassTypes=Human Vehicle IsInside (AjaxObjectRule)");
  }

  SECTION("Reolink: no Rule in Source") {
    auto *msg = MakeMessage(soap.get(), "tns1:RuleEngine/MyRuleDetector/PeopleDetect",
                            {{"Source", "000"}}, {{"State", "true"}});
    REQUIRE(ONVIF::parse_event_message(msg, topic, value, operation, items, rule));
    REQUIRE(value == "true");
    REQUIRE(rule.empty());
    REQUIRE(ONVIFEventNote(topic, items, rule) == "MyRuleDetector/PeopleDetect");
  }
}
#endif  // WITH_GSOAP
