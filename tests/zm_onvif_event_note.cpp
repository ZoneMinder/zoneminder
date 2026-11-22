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

namespace {
// Object classes as the database seeds them (db/camera_classes.sql), plus a
// COCO class with no aliases.
ONVIFTagNames SeedTagNames() {
  ONVIFTagNames names;
  ONVIFAddTagNames(names, "person", "People,PeopleDetect,Human,HumanShapeDetect");
  ONVIFAddTagNames(names, "car", "");
  ONVIFAddTagNames(names, "vehicle", "VehicleDetect");
  ONVIFAddTagNames(names, "animal", "Pet,DogCatDetect");
  ONVIFAddTagNames(names, "package", "PackageDeliver,PackagePickup");
  ONVIFAddTagNames(names, "face", "FaceDetect");
  ONVIFAddTagNames(names, "doorbell", "Visitor");
  return names;
}
}  // namespace

TEST_CASE("ONVIFAddTagNames", "[onvif]") {
  ONVIFTagNames names;

  SECTION("A class is named by itself and its aliases, in any case") {
    ONVIFAddTagNames(names, "person", " People ,,PeopleDetect, ");
    REQUIRE(names == ONVIFTagNames{{"person", "person"}, {"people", "person"}, {"peopledetect", "person"}});
  }

  SECTION("An alias wins over a class name, whichever comes first") {
    ONVIFAddTagNames(names, "car", "");
    ONVIFAddTagNames(names, "vehicle", "Car");
    ONVIFAddTagNames(names, "truck", "");
    ONVIFAddTagNames(names, "lorry", "truck");
    ONVIFAddTagNames(names, "TRUCK", "");
    REQUIRE(names.at("car") == "vehicle");
    REQUIRE(names.at("truck") == "lorry");
  }

  SECTION("A class without a name adds nothing") {
    ONVIFAddTagNames(names, "", "Human");
    REQUIRE(names.empty());
  }
}

TEST_CASE("ONVIFEventTags", "[onvif]") {
  using Tags = std::vector<std::string>;
  const ONVIFTagNames names = SeedTagNames();

  SECTION("Object in the topic") {
    // Reolink
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/PeopleDetect", {{"State", "true"}}, names) == Tags{"person"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/VehicleDetect", {{"State", "true"}}, names) == Tags{"vehicle"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/DogCatDetect", {{"State", "true"}}, names) == Tags{"animal"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/FaceDetect", {{"State", "true"}}, names) == Tags{"face"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/Package", {{"State", "true"}}, names) == Tags{"package"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/Visitor", {{"State", "true"}}, names) == Tags{"doorbell"});
    REQUIRE(ONVIFEventTags("tns1:UserAlarm/IVA/HumanShapeDetect", {{"State", "true"}}, names) == Tags{"person"});
  }

  SECTION("Object in an active item name, without its Is") {
    // Tapo
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {{"IsPeople", "true"}}, names)
            == Tags{"person"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent",
                           {{"IsVehicle", "false"}, {"IsPet", "true"}}, names) == Tags{"animal"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {{"IsPackageDeliver", "true"}}, names)
            == Tags{"package"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {{"isPeople", "true"}}, names)
            == Tags{"person"});
    // 1 is true for a flag, as it is for the alarm state
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {{"IsPeople", "1"}}, names)
            == Tags{"person"});
  }

  SECTION("Objects in an item value, any case") {
    // Ajax / Profile M
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/ObjectDetection/Object", {{"ClassTypes", "Human Vehicle"}}, names)
            == Tags{"person", "vehicle"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/ObjectDetection/Object", {{"ClassTypes", "ANIMAL"}}, names) == Tags{"animal"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/ObjectDetection/Object", {{"ClassTypes", "Human, Vehicle"}}, names)
            == Tags{"person", "vehicle"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/ObjectDetection/Object", {{"ClassTypes", ",Human,,Vehicle,"}}, names)
            == Tags{"person", "vehicle"});
  }

  SECTION("Each tag once") {
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/PeopleDetect", {{"ClassTypes", "Human"}}, names)
            == Tags{"person"});
  }

  SECTION("A COCO class the camera names itself") {
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/ObjectDetection/Object", {{"ClassTypes", "Car"}}, names) == Tags{"car"});
  }

  SECTION("No object named: the last topic segment") {
    REQUIRE(ONVIFEventTags("tns1:VideoSource/MotionAlarm", {{"State", "true"}}, names) == Tags{"MotionAlarm"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/CellMotionDetector/Motion", {{"IsMotion", "true"}}, names) == Tags{"Motion"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {{"IsLineCross", "true"}}, names)
            == Tags{"TPSmartEvent"});
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/LineDetector/Crossed", {{"ObjectId", "4"}}, names) == Tags{"Crossed"});
    // "Is" is only a prefix before a capital
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/Foo/Bar", {{"Island", "true"}}, names) == Tags{"Bar"});
  }

  SECTION("No object classes: the last topic segment") {
    REQUIRE(ONVIFEventTags("tns1:RuleEngine/MyRuleDetector/PeopleDetect", {{"State", "true"}}, {})
            == Tags{"PeopleDetect"});
  }

  SECTION("Nothing to tag") {
    REQUIRE(ONVIFEventTags("", {}, names).empty());
    REQUIRE(ONVIFEventTags("///", {{"State", "true"}}, names).empty());
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
    REQUIRE(ONVIFEventTags(topic, items, SeedTagNames()) == std::vector<std::string>{"person"});
  }

  SECTION("An active state item wins over an earlier inactive one") {
    auto *msg = MakeMessage(soap.get(), "tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {},
                            {{"IsVehicle", "false"}, {"IsMotion", "Idle"}, {"IsPeople", "true"}, {"IsPet", "false"}});
    REQUIRE(ONVIF::parse_event_message(msg, topic, value, operation, items, rule));
    REQUIRE(value == "true");
    REQUIRE(ONVIFEventTags(topic, items, SeedTagNames()) == std::vector<std::string>{"person"});

    auto *off = MakeMessage(soap.get(), "tns1:RuleEngine/TPSmartEventDetector/TPSmartEvent", {},
                            {{"IsVehicle", "false"}, {"IsPeople", "false"}});
    REQUIRE(ONVIF::parse_event_message(off, topic, value, operation, items, rule));
    REQUIRE(value == "false");
  }

  SECTION("A later item describing the object does not raise the alarm") {
    auto *msg = MakeMessage(soap.get(), "tns1:RuleEngine/FieldDetector/ObjectsInside", {},
                            {{"IsInside", "false"}, {"ObjectId", "3"}, {"Count", "1"}});
    REQUIRE(ONVIF::parse_event_message(msg, topic, value, operation, items, rule));
    REQUIRE(value == "false");
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
