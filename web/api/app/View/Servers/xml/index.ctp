$xml = Xml::fromArray(array('response' => $servers));
echo $xml->asXML();
