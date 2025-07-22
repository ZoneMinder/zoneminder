$xml = Xml::fromArray(array('response' => $event_data));
echo $xml->asXML();
