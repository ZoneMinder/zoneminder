<?php
      $optionsColours = array(
        1 => '8 bit grayscale',
        3 => '24 bit color',
        4 => '32 bit color'
      );
      $this->set('optionsColours', $optionsColours);

      $protocoloptions = array(
        'rtsp' => 'RTSP',
        'http' => 'HTTP'
      );
      $this->set('protocoloptions', $protocoloptions);

      $methodoptions = array(
        'simple' => 'Simple',
        'regexp' => 'Regexp'
      );
      $this->set('methodoptions', $methodoptions);

      $channeloptions = array();
      for ($i=1; $i<32; $i++) {
        array_push($channeloptions, $i);
      }
      $this->set('channeloptions', $channeloptions);

      $formatoptions = array(
        255 => "PAL",
        45056 => "NTSC",
        1 => "PAL B",
        2 => "PAL B1",
        4 => "PAL G",
        8 => "PAL H",
        16 => "PAL I",
        32 => "PAL D",
        64 => "PAL D1",
        128 => "PAL K",
        256 => "PAL M",
        512 => "PAL N",
        1024 => "PAL Nc",
        2048 => "PAL 60",
        4096 => "NTSC M",
        8192 => "NTSC M JP",
        16384 => "NTSC 443",
        32768 => "NTSC M KR",
        65536 => "SECAM B",
        131072 => "SECAM D",
        262144 => "SECAM G",
        524288 => "SECAM H",
        1048576 => "SECAM K",
        2097152 => "SECAM K1",
        4194304 => "SECAM L",
        8388608 => "SECAM LC",
        16777216 => "ATSC 8 VSB",
        33554432 => "ATSC 16 VSB"
      );
      $this->set('formatoptions', $formatoptions);

      $optionsPalette = array(
        0 => 'Auto',
        1497715271 => 'Gray',
        877807426 => 'BGR32',
        876758866 => 'RGB32',
        861030210 => 'BGR24',
        859981650 => 'RGB24',
        1448695129 => '*YUYV',
        1195724874 => '*JPEG',
        1196444237 => '*MJPEG',
        875836498 => '*RGB444',
        1329743698 => '*RGB555',
        1346520914 => '*RGB565',
        1345466932 => '*YUV422P',
        1345401140 => '*YUV411P',
        875836505 => '*YUV444',
        961959257 => '*YUV410',
        842093913 => '*YUV420'
      );
      $this->set('optionsPalette', $optionsPalette);

      $optionsMethod = array(
        'v4l2' => 'Video For Linux 2'
      );
      $this->set('optionsMethod', $optionsMethod);
?>

<div class="tab-pane" id="source">
<?php
switch ($monitor['Type']) {
case 'Remote':
	echo $this->Form->input('Protocol', array(
		'type' => 'select',
		'options' => $protocoloptions
	));
	echo $this->Form->input('Method', array(
		'type' => 'select',
		'options' => $methodoptions
	));
	echo $this->Form->input('Host');
	echo $this->Form->input('Port');
	echo $this->Form->input('Path');
	break;

case 'Local':
	echo $this->Form->input('Path');
	echo $this->Form->input('Method', array(
		'type' => 'select',
		'options' => $optionsMethod
	));
	echo $this->Form->input('Channel', array(
		'type' => 'select',
		'options' => $channeloptions
	));
	echo $this->Form->input('Format', array(
		'type' => 'select',
		'options' => $formatoptions
	));
	echo $this->Form->input('Palette', array(
		'type' => 'select',
		'options' => $optionsPalette
	));
	break;

case 'File':
	break;
}

echo $this->Form->input('Colours', array(
	'type' => 'select',
	'options' => $optionsColours
));
echo $this->Form->input('Width');
echo $this->Form->input('Height');

?>
</div>
