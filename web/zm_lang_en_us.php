<?php

// Translators Notes
// 1. When composing the language tokens in your language you should try and keep to roughly the
// same length text if possible. Abbreviate where necessary as spacing is quite close in a number of places.
// 2. There are three types of string replacement
// Simple replacements are words or short phrases that are static and used directly. This type of
// replacement can be used 'as is'.
// Complex replacements involve some dynamic element being included and so may require substitution
// or changing into a different order. The token listed in this file will be passed through sprintf as
// a formatting string. If the dynamic element is a number you will usually need to use a variable
// replacement also as described below.
// Variable replacements are used in conjunction with complex replacements and involve the generation
// of a singular or plural noun depending on the number passed into the zmVlang function. This is
// intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to conjunct correctly
// with the associated numerator. Variable replacements are expressed are arrays with a series of
// counts and their associated words. When doing a replacement the passed value is compared with 
// those counts in descending order and the nearest match below is used if no exact match is found.
// Therefore is you have a variable replacement with 0,1 and 2 counts, which would be the normal form
// in English, if you have 5 'things' then the nearest match below is '2' and so that plural would be used.
// 3. The tokens listed below are not used to build up phrases or sentences from single words. Therefore
// you can safely assume that a single word token will only be used in that context.
// 4. In new language files, or if you are changing only a few words or phrases it makes sense from a 
// maintenance point of view to include the original language file rather than copy all the language
// tokens across. Change this to whatever your base language is and uncomment it.
require_once( 'zm_lang_en_gb.php' );

// Simple String Replacements
$zmSlang24BitColour          = '24 bit color';
$zmSlang8BitGrey             = '8 bit grayscale';
$zmSlangColour               = 'Color';
$zmSlangGrey                 = 'Gray';
$zmSlangRemoteImageColours   = 'Remote Image Colors';
$zmSlangZoneAlarmColour      = 'Alarm Color (RGB)';

?>
