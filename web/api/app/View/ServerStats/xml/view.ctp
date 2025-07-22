$xml = Xml::fromArray(array('response' => $serverstat));
echo $xml->asXML();
