$xml = Xml::fromArray(array('response' => $message));
echo $xml->asXML();
