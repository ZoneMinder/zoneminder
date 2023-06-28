$xml = Xml::fromArray(array('response' => $serverstats));
echo $xml->asXML();
