#include "zm.h"
#include "zm_logger.h"
#include "zm_monitor.h"
#include "zm_mqtt.h"
#ifdef MOSQUITTOPP_FOUND
#include "zm_time.h"

#include <sstream>
#include <string.h>

MQTT::MQTT(Monitor *monitor) :
  monitor_(monitor),
  connected_(false)
{
  std::string name="ZoneMinder"+std::to_string(monitor->Id());
  mosquittopp(name.c_str());

  mosqpp::lib_init();
  connect();
}

void MQTT::connect() {
  if (config.mqtt_username[0]) {
    Debug(1, "MQTT setting username to %s, password to %s", config.mqtt_username, config.mqtt_password);
    int rc = mosqpp::mosquittopp::username_pw_set(config.mqtt_username, config.mqtt_password);
    if (MOSQ_ERR_SUCCESS != rc) {
      Warning("MQTT username pw set returns %d %s", rc, strerror(rc));
    }
  }
  Debug(1, "MQTT connecting to %s:%d", config.mqtt_hostname, config.mqtt_port);
  int rc = mosqpp::mosquittopp::connect(config.mqtt_hostname, config.mqtt_port, 60);
  if (MOSQ_ERR_SUCCESS != rc) {
    if (MOSQ_ERR_INVAL == rc) {
      Warning("MQTT reports invalid parameters to connect");
    } else {
      Warning("MQTT connect returns %d %s", rc, strerror(rc));
    }
  } else {
    Debug(1, "Starting loop");
    loop_start();
  }
}

void MQTT::autoconfigure() {
}

void MQTT::disconnect() {
}

void MQTT::on_connect(int rc) {
  Debug(1, "Connected with rc %d", rc);
  if (rc == MOSQ_ERR_SUCCESS) connected_ = true;
}

void MQTT::on_message(const struct mosquitto_message *message) {
  Debug(1, "MQTT: Have message %s: %s", message->topic, static_cast<const char *>(message->payload));
}

void MQTT::on_subscribe(int mid, int qos_count, const int *granted_qos) {
  Debug(1, "MQTT: Subscribed to topic ");
}

void MQTT::on_publish(int mid) {
  Debug(1, "MQTT: on_publish %d", mid);
}

void MQTT::send(const std::string &message) {
  if (!connected_) connect();

  std::stringstream mqtt_topic;
  //mqtt_topic << "/" << config.mqtt_topic_prefix;
  mqtt_topic << config.mqtt_topic_prefix;
  mqtt_topic << "/monitor/" << monitor_->Id();

  const std::string mqtt_topic_string = mqtt_topic.str();
  //Debug(1, "DEBUG: MQTT TOPIC: %s : message %s", mqtt_topic_string.c_str(), message.c_str());
  //int rc = publish(&mid, mqtt_topic_string.c_str(), message.length(), message.c_str(), 0, true);
  int rc = publish(nullptr, mqtt_topic_string.c_str(), message.length(), message.c_str());
  if (MOSQ_ERR_SUCCESS != rc) {
    Warning("MQTT publish returns %d %s", rc, strerror(rc));
  }
}

void MQTT::addSensor(std::string name, std::string type) {
  std::map<std::chrono::milliseconds, double> valuesList;
  sensorList.insert ( std::pair<std::string,std::map<std::chrono::milliseconds, double>>(name, valuesList));
}

void MQTT::add_subscription(const std::string &name) {
//, std::function <void(int val)> f) {
  int mid;
  Debug(1, "MQTT add subscription to %s", name.c_str());
  subscribe(&mid, name.c_str());
}

void MQTT::addValue(std::string name, double value) {
  sensorListIterator = sensorList.find(name);
  Debug(1, "found sensor: %s", sensorListIterator->first.c_str());
  //    if(it == sensorList.end()) {
  //        clog<<__FUNCTION__<<" Could not find corresponding sensor name"<<endl;
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
#endif
