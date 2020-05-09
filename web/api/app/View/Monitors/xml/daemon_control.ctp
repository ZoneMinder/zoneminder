$xml = Xml::fromArray(array('response' => $status_text));
echo $xml->asXML();
