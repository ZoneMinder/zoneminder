$xml = Xml::fromArray(array('response' => $message, 'zone'=>$zone));
echo $xml->asXML();
