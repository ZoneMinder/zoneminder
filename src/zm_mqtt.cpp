#include "zm.h"
#include "zm_logger.h"
#include "zm_mqtt.h"
#include "zm_monitor.h"
#include "zm_time.h"

#include <sstream>
#include <string.h>

MQTT::MQTT(Monitor *monitor) :
  mosquittopp(),
  monitor_(monitor),
  connected_(false)
{
  mosqpp::lib_init();
}

void MQTT::connect(const char * hostname, unsigned int port, unsigned int keepalive) {
  mosqpp::mosquittopp::connect(hostname, port, keepalive);
  loop_start();
}

void MQTT::autoconfigure() {
}

void MQTT::disconnect() {
}

void MQTT::on_connect(int rc) {
  Debug(1, "Connected with rc %d", rc);
  if (rc == 0) connected_ = true;
}

void MQTT::on_message(const struct mosquitto_message *message) {
}

void MQTT::on_subscribe(int mid, int qos_count, const int *granted_qos) {
  Debug(1, "Subscribed to topic ");
}

void MQTT::on_publish() {
}

void MQTT::send() {
  while (!connected_) { std::this_thread::sleep_for(Seconds(1)); }

  for (auto outer_iter=sensorList.begin(); outer_iter!=sensorList.end(); ++outer_iter) {
    for (auto inner_iter=outer_iter->second.begin(); inner_iter!=outer_iter->second.end(); ++inner_iter) {
      Debug(1, "%s %ld %f", outer_iter->first.c_str(), inner_iter->first.count(), inner_iter->second);
      int mid;
      std::stringstream mqtt_topic;
      std::stringstream mqtt_payload;
      //mqtt_topic << "/a/";
      mqtt_topic << "/" << MQTT_TOPIC_PREFIX;
      mqtt_topic << "/monitor/" << monitor_->Id();
      //mqtt_topic << "/sensor/" << outer_iter->first;
      mqtt_topic << "/data";

      //mqtt_payload << "{ \"value\":"<<inner_iter->second<<" }";

      const std::string mqtt_topic_string = mqtt_topic.str();
      const std::string mqtt_payload_string = mqtt_payload.str();
      Debug(1, "DEBUG: MQTT TOPIC: %s", mqtt_topic_string.c_str());
      publish(&mid, mqtt_topic_string.c_str(), mqtt_payload_string.length(), mqtt_payload_string.c_str(), 0, true);

      auto this_iter = inner_iter;
      inner_iter++;

      outer_iter->second.erase(this_iter);
      if (inner_iter == outer_iter->second.end()) {
        break;
      }
    }  // end foreach inner
  }  // end foreach outer
}

void MQTT::addSensor(std::string name, std::string type) {
  std::map<std::chrono::milliseconds, double> valuesList;
  sensorList.insert ( std::pair<std::string,std::map<std::chrono::milliseconds, double>>(name, valuesList));
}

void MQTT::addActuator(std::string name, std::function <void(int val)> f) {
  int mid;
  std::stringstream mqtt_topic;

  //mqtt_topic << "/a/";
  //mqtt_topic << api_key;
  //mqtt_topic << "/p/";
  //mqtt_topic << project_id;
  //mqtt_topic << "/device/" << device_id;
  //mqtt_topic << "/actuator/" << name;
  //mqtt_topic << "/state";

  subscribe(&mid, mqtt_topic.str().c_str());
}

void MQTT::addValue(std::string name, double value) {
  sensorListIterator = sensorList.find(name);
  Debug(1, "found sensor: %s", sensorListIterator->first.c_str());
  //    if(it == sensorList.end()) {
  //        clog<<__FUNCTION__<<" Could not find coresponding sensor name"<<endl;
  //    } else {
  //
  //    }
  // valuesList.insert ( std::pair<std::string,double>(name, value));
  std::chrono::milliseconds ms = std::chrono::duration_cast< std::chrono::milliseconds >(
      std::chrono::high_resolution_clock::now().time_since_epoch()
      );
  sensorListIterator->second.insert(std::pair<std::chrono::milliseconds, double>(ms, value));
}

void MQTT::listValues(const std::string &sensor_name) {
  Debug(1, "%s", sensor_name.c_str());
  auto sensorListIterator = sensorList.find(sensor_name);
  Debug(1, "found sensor: %s", sensorListIterator->first.c_str());
  for (auto inner_iter=sensorListIterator->second.begin(); inner_iter!=sensorListIterator->second.end(); ++inner_iter) {
    std::cout << "ts: " << inner_iter->first.count() << ", value:" << inner_iter->second << std::endl;
  }
}

MQTT::~MQTT() {
}
