$xml = Xml::fromArray(array('response' => $events));
echo $xml->asXML();
