$xml = Xml::fromArray(array('response' => $monitors));
echo $xml->asXML();
