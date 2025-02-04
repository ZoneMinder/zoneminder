#ifndef ZM_MQTT_H
#define ZM_MQTT_H

#if MOSQUITTOPP_FOUND

#include "mosquittopp.h"

#include <stdint.h>
#include <iostream>
#include <map>
#include <list>
#include <time.h>
#include <chrono>
#include <functional>

class Monitor;

class MQTT : public mosqpp::mosquittopp {
 public:
  MQTT(Monitor *);
  ~MQTT();
  void autoconfigure();
  void connect();
  void disconnect();
  void send(const std::string &message);
  void addSensor(std::string name, std::string type);
  void add_subscription(const std::string &name);

  void addValue(std::string name, double value);
  void listValues(const std::string &sensor_name);
  void on_connect(int rc);
  void on_message(const struct mosquitto_message *message);
  void on_subscribe(int mid, int qos_count, const int *granted_qos);
  void on_publish(int mid);
  enum sensorTypes {
    NUMERIC = 0,
    DIGITAL
  };

 private:
  std::map<std::string, std::map<std::chrono::milliseconds, double>> sensorList;
  std::map<std::string, std::map<std::chrono::milliseconds, double>>::iterator sensorListIterator;
  std::map<std::string, int> actuatorList;

  Monitor *monitor_;
  bool connected_;
};
#endif // MOSQUITTOPP_FOUND

#endif // ZM_MQTT_H

