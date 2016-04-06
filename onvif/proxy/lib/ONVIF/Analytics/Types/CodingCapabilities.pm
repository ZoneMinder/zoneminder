package ONVIF::Analytics::Types::CodingCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %AudioEncodingCapabilities_of :ATTR(:get<AudioEncodingCapabilities>);
my %AudioDecodingCapabilities_of :ATTR(:get<AudioDecodingCapabilities>);
my %VideoDecodingCapabilities_of :ATTR(:get<VideoDecodingCapabilities>);

__PACKAGE__->_factory(
    [ qw(        AudioEncodingCapabilities
        AudioDecodingCapabilities
        VideoDecodingCapabilities

    ) ],
    {
        'AudioEncodingCapabilities' => \%AudioEncodingCapabilities_of,
        'AudioDecodingCapabilities' => \%AudioDecodingCapabilities_of,
        'VideoDecodingCapabilities' => \%VideoDecodingCapabilities_of,
    },
    {
        'AudioEncodingCapabilities' => 'ONVIF::Analytics::Types::AudioEncoderConfigurationOptions',
        'AudioDecodingCapabilities' => 'ONVIF::Analytics::Types::AudioDecoderConfigurationOptions',
        'VideoDecodingCapabilities' => 'ONVIF::Analytics::Types::VideoDecoderConfigurationOptions',
    },
    {

        'AudioEncodingCapabilities' => 'AudioEncodingCapabilities',
        'AudioDecodingCapabilities' => 'AudioDecodingCapabilities',
        'VideoDecodingCapabilities' => 'VideoDecodingCapabilities',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Analytics::Types::CodingCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
CodingCapabilities from the namespace http://www.onvif.org/ver10/schema.

This type contains the Audio and Video coding capabilities of a display service.




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * AudioEncodingCapabilities


=item * AudioDecodingCapabilities


=item * VideoDecodingCapabilities




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::CodingCapabilities
   AudioEncodingCapabilities =>  { # ONVIF::Analytics::Types::AudioEncoderConfigurationOptions
     Options =>  { # ONVIF::Analytics::Types::AudioEncoderConfigurationOption
       Encoding => $some_value, # AudioEncoding
       BitrateList =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateList =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
     },
   },
   AudioDecodingCapabilities =>  { # ONVIF::Analytics::Types::AudioDecoderConfigurationOptions
     AACDecOptions =>  { # ONVIF::Analytics::Types::AACDecOptions
       Bitrate =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
     },
     G711DecOptions =>  { # ONVIF::Analytics::Types::G711DecOptions
       Bitrate =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
     },
     G726DecOptions =>  { # ONVIF::Analytics::Types::G726DecOptions
       Bitrate =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Analytics::Types::IntList
         Items =>  $some_value, # int
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::AudioDecoderConfigurationOptionsExtension
     },
   },
   VideoDecodingCapabilities =>  { # ONVIF::Analytics::Types::VideoDecoderConfigurationOptions
     JpegDecOptions =>  { # ONVIF::Analytics::Types::JpegDecOptions
       ResolutionsAvailable =>  { # ONVIF::Analytics::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedInputBitrate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     H264DecOptions =>  { # ONVIF::Analytics::Types::H264DecOptions
       ResolutionsAvailable =>  { # ONVIF::Analytics::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedH264Profiles => $some_value, # H264Profile
       SupportedInputBitrate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     Mpeg4DecOptions =>  { # ONVIF::Analytics::Types::Mpeg4DecOptions
       ResolutionsAvailable =>  { # ONVIF::Analytics::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedMpeg4Profiles => $some_value, # Mpeg4Profile
       SupportedInputBitrate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Analytics::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::VideoDecoderConfigurationOptionsExtension
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

